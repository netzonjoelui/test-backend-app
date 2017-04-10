<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Account\Module\DataMapper;

use Netric\Error\AbstractHasErrors;
use Netric\Account\Module\Module;
use Netric\Db\DbInterface;
use Netric\Config\Config;
use Netric\Entity\ObjType\UserEntity;
use SimpleXMLElement;


class DataMapperDb extends AbstractHasErrors implements DataMapperInterface
{
    /**
     * Handle to account database
     *
     * @var DbInterface
     */
    private $dbh = null;

    /**
     * Netric configuration
     *
     * @var Config
     */
    private $config = null;

    /**
     * Current user
     *
     * @var UserEntity
     */
    private $user = null;

    /**
     * Construct and initialize dependencies
     *
     * @param DbInterface $dbh
     * @param Config $config The configuration object
     */
    public function __construct(DbInterface $dbh, Config $config, UserEntity $user)
    {
        $this->dbh = $dbh;
        $this->config = $config;
        $this->user = $user;
    }

    /**
     * Save changes or create a new module
     *
     * @param Module $module The module to save
     * @return bool true on success, false on failure with details in $this->getLastError
     */
    public function save(Module $module)
    {

        // Setup data for the database columns
        $data = array(
            "id" => $this->dbh->escapeNumber($module->getId()),
            "name" => "'" . $this->dbh->escape($module->getName()) . "'",
            "title" => "'" . $this->dbh->escape($module->getTitle()) . "'",
            "short_title" => "'" . $this->dbh->escape($module->getShortTitle()) . "'",
            "scope" => "'" . $this->dbh->escape($module->getScope()) . "'",
            "f_system" => ($module->isSystem()) ? "'t'" : "'f'",
            "user_id" => $this->dbh->escapeNumber($module->getUserId()),
            "team_id" => $this->dbh->escapeNumber($module->getTeamId()),
            "sort_order" => $this->dbh->escapeNumber($module->getSortOrder()),
            "icon" => "'" . $this->dbh->escape($module->getIcon())  . "'",
            "default_route" => "'" . $this->dbh->escape($module->getDefaultRoute())  . "'"
        );

        // Make sure that the module is dirty before we set the navigation
        if($module->isDirty())
        {
            $moduleNavigation = null;

            // Make sure the the navigation is an array
            if($module->getNavigation() && is_array($module->getNavigation()))
            {
                // Setup the xml object
                $xmlNavigation = new SimpleXMLElement('<navigation></navigation>');

                // Now converte the module navigation data into xml
                $this->arrayToXml($module->getNavigation(), $xmlNavigation);

                // Save the xml string
                $moduleNavigation = $this->dbh->escape($xmlNavigation->asXML());
            }

            // Set the module navigation
            $data["xml_navigation"] = "'" . $this->dbh->escape($moduleNavigation)  . "'";
        }

        // Compose either an update or insert statement
        $sql = "";
        if ($module->getId()) {
            // Update existing record
            $updateStatements = "";
            foreach ($data as $colName=>$colValue) {
                if ($updateStatements) $updateStatements .= ", ";
                $updateStatements .= $colName . "=" . $colValue;
            }
            $sql = "UPDATE applications SET $updateStatements " .
                   "WHERE id=" . $this->dbh->escapeNumber($module->getId()) . ";" .
                   "SELECT " . $this->dbh->escapeNumber($module->getId()) ." as id;";
        } else {
            // Insert new record
            $columns = [];
            $values = [];
            foreach ($data as $colName=>$colValue) {
                if ($colName != 'id') {
                    $columns[] = $colName;
                    $values[] = $colValue;
                }
            }

            $sql = "INSERT INTO applications(" . implode(',', $columns) . ") " .
                   "VALUES(" . implode(',', $values) . ") RETURNING id";
        }

        // Run the query and return the results
        $result = $this->dbh->query($sql);
        if (!$result)
        {
            $this->addErrorFromMessage($this->dbh->getLastError());
            return false;
        }

        // Update the module id
        if ($this->dbh->getNumRows($result) && !$module->getId())
        {
            $module->setId($this->dbh->getValue($result, 0, 'id'));
        }

        return true;
    }

    /**
     * Get a module by name
     *
     * @param string $name The name of the module to retrieve
     * @return Module|null
     */
    public function get($name)
    {
        $sql = "SELECT * FROM applications WHERE name='" . $this->dbh->escape($name) . "'";
        $result = $this->dbh->query($sql);
        if (!$result)
        {
            $this->addErrorFromMessage($this->dbh->getLastError());
            return null;
        }

        if ($this->dbh->getNumRows($result)) {
            $row = $this->dbh->getRow($result, 0);
            return $this->createModuleFromRow($row);
        }

        // Not found
        return null;
    }

    /**
     * Get all modules installed in this account
     *
     * @param string $scope One of the defined scopes in Module::SCOPE_*
     * @return Module[]|null on error
     */
    public function getAll($scope = null)
    {
        $modules = [];

        $sql = "SELECT * FROM applications ";
        if ($scope)
            $sql .= "WHERE scope='" . $this->dbh->escape($scope) . "' ";
        $sql .= "ORDER BY sort_order";
        $result = $this->dbh->query($sql);
        if (!$result)
        {
            $this->addErrorFromMessage($this->dbh->getLastError());
            return null;
        }

        $num = $this->dbh->getNumRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $this->dbh->getRow($result, $i);
            $modules[] = $this->createModuleFromRow($row);
        }

        // Settings navigation that will be displayed in the frontend
        $settingsData = array(
            "id" => null,
            "name" => "settings",
            "title" => "Settings",
            "short_title" => "Settings",
            "f_system" => "t"
        );

        $modules['settings'] = $this->createModuleFromRow($settingsData);

        return $modules;
    }

    /**
     * Delete a non-system module
     *
     * @param Module $module Module to delete
     * @return bool true on success, false on failure with details in $this->getLastError
     */
    public function delete(Module $module)
    {
        if ($module->isSystem())
        {
            $this->addErrorFromMessage("Cannot delete a system module");
            return false;
        }

        if (!$module->getId())
        {
            throw new \InvalidArgumentException("Missing ID - cannot delete an unsaved module");
        }

        $sql = "DELETE FROM applications WHERE id=" . $this->dbh->escapeNumber($module->getId());
        $result = $this->dbh->query($sql);

        // Check to see if there was a problem
        if (!$result)
        {
            $this->addErrorFromMessage("DB error: " . $this->dbh->getLastError());
            return false;
        }

        return true;
    }

    /**
     * Translate row data to module properties and return instance
     *
     * @param array $row The associative array of column data from a row
     * @return Module
     */
    private function createModuleFromRow(array $row)
    {
        $module = new Module();
        $module->fromArray($row);

        /*
         * If module data from the database has xml_navigation, then we will use this to set the module's navigation
         * Otherwise, we will use the module navigation file
         */
        if(isset($row['xml_navigation']) && !empty($row['xml_navigation']))
        {
            // Convert the xml navigation string into an array
            $xml = simplexml_load_string($row['xml_navigation']);
            $json = json_encode($xml);

            // Make sure that the navigation array is not an associative array
            $nav['navigation'] = array_values(json_decode($json, true));

            // Import the module data coming from the database
            $module->fromArray($nav);

            // Set the system value separately
            $module->setSystem(($row['f_system'] == 't') ? true : false);
        }
        else
        {
            // Get the location of the module navigation file
            $basePath = $this->config->get("application_path") . "/data";

            // Make sure that the pathy and file is existing
            if ($module->getName() && file_exists($basePath . "/modules/" . $module->getName() . ".php")) {
                $moduleData = include($basePath . "/modules/" . $module->getName() . ".php");

                // Import module data coming from the navigation fallback file
                $module->fromArray($moduleData);
            }

            // Flag this module as clean, since we just loaded navigation file
            $module->setDirty(false);
        }

        return $module;
    }

    /**
     * Convert the array data to xml
     *
     * @param array $data The module data that will be converted into xml string
     * @param SimpleXMLElement $xmlData The xml object that will be used to convert
     */
    private function arrayToXml (array $data, SimpleXMLElement &$xmlData ) {
        foreach( $data as $key => $value ) {
            if( is_array($value) ) {
                if( is_numeric($key) ){
                    $key = 'item'.$key; //dealing with <0/>..<n/> issues
                }
                $subnode = $xmlData->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xmlData->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }
}