<?php
/**
 * Manage entity forms
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity;

use Netric\Db\DbInterface;
use Netric\Entity\ObjType\UserEntity;
use Netric\EntityDefinition;
use Netric\Config\Config;


/**
 * Class for managing entity forms
 *
 * @package Netric\Entity
 */
class Forms
{
    /**
     * Database handle
     *
     * @var \Netric\Db\DbInterface
     */
    private $dbh = null;

    /**
     * Netric configuration
     *
     * @var \Netric\Config\Config
     */
    private $config = null;

    /**
     * Class constructor to set up dependencies
     *
     * @param \Netric\Db\DbInterface
     */
    public function __construct(DbInterface $dbh, Config $config)
    {
        $this->dbh = $dbh;
        $this->config = $config;
    }

    /**
     * Service creation factory
     *
     * @param \Netric\EntityDefinition $def
     * @return array Associative array
     */
    public function getDeviceForms(EntityDefinition $def, UserEntity $user)
    {
        $dbh = $this->dbh;

        /*
         * First look for the new form names: small, medium, large, xlarge
         */
        $small = $this->getFormUiXml($def, $user, "small");
        $medium = $this->getFormUiXml($def, $user, "medium");
        $large = $this->getFormUiXml($def, $user, "large");
        $xlarge = $this->getFormUiXml($def, $user, "xlarge");

        // Use nearest match if all forms have not been applied
        if (!$xlarge && $large)
            $xlarge = $large;
        if (!$medium && $large)
            $medium = $large;
        if (!$large && $medium)
            $large = $medium;

        /*
         * We are translating the new form names 'small|medium|large|xlarge'
         * to the old 'mobile|default' names for the time being
         * because these scopes are accessed all throughout the
         * old code base. Once we replace the entire UI then it should be
         * pretty easy to remove all old references to mobile/default
         * and then just do an SQL update to rename exsiting custom forms.
         */
        $default = $this->getFormUiXml($def, $user, "default");
        if (!$small)
        {
            $small = $this->getFormUiXml($def, $user, "mobile");
            if (!$small)
                $small = $default;
        }
        if (!$medium)
        {
            $medium = $this->getFormUiXml($def, $user, "mobile");
            if (!$medium)
                $medium = $default;
        }
        if (!$large)
        {
            $large = $default;
        }
        if (!$xlarge)
        {
            $xlarge = $default;
        }

        $forms = array(
            'small' => $small,
            'medium' => $medium,
            'large' => $large,
            'xlarge' => $xlarge,
            'infobox' => $this->getFormUiXml($def, $user, "infobox"),
        );

        return $forms;
    }

    /**
     * Get a UIXML form for an entity type but check for user/team customizations
     *
     * Forms are selected in the following order:
     * 1. If there is a form specificaly for the userId, then use it otherwise
     * 2. If there is a form specifically for the user's team, then use it otherwise
     * 3. If there is a customized form saved for the account, then use it otherwise
     * 4. Get the system default form form the file system
     *
     * In all the above cases it will be checking
     *
     * @param \Netric\EntityDefinition $def The object type definition
     * @param \Netric\Entity\ObjType\UserEntity $user User to get forms for
     * @param string $device The device scope / size - 'small', 'medium', 'large', 'xlarge'
     * @return string
     */
    public function getFormUiXml(EntityDefinition $def, UserEntity $user, $device)
    {
        $dbh = $this->dbh;

        // Protect against SQL Injection
        $scope = $dbh->escape($device);

        // Check for user specific form
        $result = $dbh->query("SELECT form_layout_xml FROM app_object_type_frm_layouts
                                WHERE user_id='" . $user->getId() . "'
                                    AND scope='" . $device . "'
                                    AND type_id='" . $def->getId() . "';");
        if ($dbh->getNumRows($result))
        {
            $val = $dbh->getValue($result, 0, "form_layout_xml");
            if ($val && $val!="*")
                return $val;
        }
        
        // Check for team specific form
        if ($user->getValue("team_id"))
        {
            $result = $dbh->query("SELECT form_layout_xml FROM app_object_type_frm_layouts
                                    WHERE team_id='" . $user->getValue("team_id") . "' 
                                        AND scope='" . $device . "'
                                        AND type_id='" . $def->getId() . "';");
            if ($dbh->getNumRows($result))
            {
                $val = $dbh->getValue($result, 0, "form_layout_xml");
                if ($val && $val!="*")
                    return $val;
            }
        }

        // Check for default custom that applies to all users and teams
        $result = $dbh->query("SELECT form_layout_xml FROM app_object_type_frm_layouts
                                WHERE scope='" . $device . "'
                                AND team_id IS NULL AND user_id IS NULL
                                AND type_id='" . $def->getId() . "';");
        if ($dbh->getNumRows($result))
        {
            $val = $dbh->getValue($result, 0, "form_layout_xml");
            if ($val && $val!="*")
                return $val;
        }

        // Get system default
        return $this->getSysForm($def, $device);
    }

    /**
     * Get system defined UIXML form for an object type
     *
     * @param \Netric\EntityDefinition $def The object type definition
     * @param string $device Device type/size 'small', 'medium', 'large', 'xlarge'
     * @return string UIXML form if defined
     * @throws \Exception When called when $def is not a valid object type
     */
    public function getSysForm($def, $device)
    {
        $objType = $def->getObjType();
        $xml = "";

        if (!$objType)
            throw new \Exception("Invalid object type");

        // Check form xml from a file found in /objects/{objType}/{device}.php
        $basePath = $this->config->get("application_path") . "/data";
        $formPath = $basePath . "/entity_forms/" . $objType . "/" . $device . ".php";
        if (file_exists($formPath))
        {
            $xml = file_get_contents($formPath);
        }

        return $xml;
    }

    /**
     * Override the default system form for a specific team
     *
     * @param \Netric\EntityDefinition $def The object type definition
     * @param int $teamId The unique id of the team that will use this form
     * @param string $deviceType The type of device the form is for: small|medium|large|xlarge
     * @param string $xmlForm The UIXML representing the form
     * @throws \RuntimeException If xml is bad
     * @throws \InvalidArgumentException If any param is null
     * @return bool true on success, false on failure
     */
    public function saveForTeam(EntityDefinition $def, $teamId, $deviceType, $xmlForm)
    {
        // Make sure teamId is set
        if (!is_numeric($teamId))
            throw new \InvalidArgumentException("teamId is required");


        return $this->saveForm($def, null, $teamId, $deviceType, $xmlForm);
    }

    /**
     * Override the default system form for a specific user
     *
     * @param \Netric\EntityDefinition $def The object type definition
     * @param int $userId The unique id of the user that will use this form
     * @param string $deviceType The type of device the form is for: small|medium|large|xlarge
     * @param string $xmlForm The UIXML representing the form
     * @return bool true on success, false on failure
     */
    public function saveForUser(EntityDefinition $def, $userId, $deviceType, $xmlForm)
    {
        // Make sure $userId is set
        if (!is_numeric($userId))
            throw new \InvalidArgumentException("userId is required");

        return $this->saveForm($def, $userId, null, $deviceType, $xmlForm);
    }

    /**
     * Override the default system form (file) for this account
     *
     * @param \Netric\EntityDefinition $def The object type definition
     * @param string $deviceType The type of device the form is for: small|medium|large|xlarge
     * @param string $xmlForm The UIXML representing the form
     * @return bool true on success, false on failure
     */
    public function saveForAccount(EntityDefinition $def, $deviceType, $xmlForm)
    {
        return $this->saveForm($def, null, null, $deviceType, $xmlForm);
    }

    /**
     * Save a form to the database if set, otherwise just delete exsiting form if null
     *
     * @param EntityDefinition $def The entity definition we are working with
     * @param int $userId If set save this for a specific user
     * @param int $teamId If set save for a specific team
     * @param string $deviceType The size of the device - small|medium|large|xlarge
     * @param string $xmlForm The UIXML form to save
     * @return bool true on success, false on failure
     * @throws \InvalidArgumentException If any of the provided params are invalid
     */
    private function saveForm(EntityDefinition $def, $userId, $teamId, $deviceType, $xmlForm)
    {
        // Either team or user can be set, but not both
        if ($userId && $teamId)
        {
            throw new \InvalidArgumentException("You cannot set both the userId and teamId");
        }

        if (!$this->validateXml($xmlForm))
            throw \RuntimeException("Invalid UIXML Detected", $xmlForm);

        // Make sure the deviceType is set
        if (!$deviceType)
            throw new \InvalidArgumentException("Device type is required");

        // Make sure required params are set
        if (!$def->getId())
            throw new \InvalidArgumentException("Entity definition is bad");

        // Clean any existing forms that match this deviceType (used to be called scope)
        $sql = "DELETE FROM app_object_type_frm_layouts WHERE
                scope='" . $this->dbh->escape($deviceType) . "' AND
                type_id=" . $this->dbh->escapeNumber($def->getId());
        if ($teamId)
            $sql .= " AND team_id=" . $this->dbh->escapeNumber($teamId);
        else if ($userId)
            $sql .= " AND user_id=" . $this->dbh->escapeNumber($userId);
        else
            $sql .= "AND user_id IS NULL and team_id IS NULL";
        $this->dbh->query($sql);

        // Insert the new form if set, otherwise just leave it deleted
        if (!$xmlForm !== null)
        {
            $sql = "INSERT INTO
                  app_object_type_frm_layouts(
                    scope,
                    team_id,
                    user_id,
                    type_id,
                    form_layout_xml
                  )
                VALUES (
                  '" . $this->dbh->escape($deviceType) . "',
                  " . $this->dbh->escapeNumber($teamId) . ",
                  " . $this->dbh->escapeNumber($userId) . ",
                  " . $def->getId() . ",
                  '" . $this->dbh->escape($xmlForm) . "'
                )";
            if ($this->dbh->query($sql))
            {
                return true;
            }
            else
            {
                echo "ERROR: " . $this->dbh->getLastError();
                return false;
            }
        }

        return true;
    }

    /**
     * Make sure that the user has supplied a valid xml document
     *
     * @param string $xml
     * @return bool true if the form is vaid xml, otherwise false
     */
    private function validateXml($xml)
    {
        $isValid = true;

        // The xml can be null if the user wants to delete it so default to true
        if ($xml !== null)
        {

        }

        return $isValid;
    }

}
