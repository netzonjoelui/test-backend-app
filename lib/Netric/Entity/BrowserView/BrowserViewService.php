<?php
/**
 * Manage entity browser views
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\BrowserView;

use Netric\Db\DbInterface;
use Netric\Entity\ObjType\UserEntity;
use Netric\EntityDefinition;
use Netric\Config\Config;
use Netric\Settings\Settings;
use Netric;

/**
 * Class for managing entity forms
 *
 * @package Netric\Entity\Entity
 */
class BrowserViewService
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
     * @var \Netric\Config
     */
    private $config = null;

    /**
     * A cache of all loaded BrowserViews from the DB
     *
     * Each object type will be cached in $this->views[$objType]
     *
     * @var array
     */
    private $views = array();

    /**
     * Entity defition loader to map type id to type name
     *
     * @var Netric\EntityDefinitionLoader
     */
    private $definitionLoader = null;

    /**
     * Account or user level settings service
     *
     * @var Settings|null
     */
    private $settings = null;

    /**
     * Class constructor to set up dependencies
     *
     * @param \Netric\Db\DbInterface
     * @param Config $config The configuration object
     * @param \Netric\EntityDefinitionLoader $defLoader To get definitions of entities by $objType
     * @param Settings $settings Account or user settings service
     */
    public function __construct(DbInterface $dbh, Config $config, Netric\EntityDefinitionLoader $defLoader, Settings $settings)
    {
        $this->dbh = $dbh;
        $this->config = $config;
        $this->definitionLoader = $defLoader;
        $this->settings = $settings;
    }

    /**
     * Get the user's default view for the given object type
     *
     * @param string $objType The object type
     * @param UserEntity $user The user we are getting the default for
     * @return string User's default view for the given object type
     */
    public function getDefaultViewForUser($objType, UserEntity $user)
    {
        $settingKey = "entity/browser-view/default/" . $objType;

        // First check to see if they set their own default
        $defaultViewId = $this->settings->getForUser($user, $settingKey);

        // TODO: Check the user's team

        // Check to see if there is an account default
        if (!$defaultViewId)
            $defaultViewId = $this->settings->get($settingKey);

        // Now load the system default
        if (!$defaultViewId)
        {
            $sysViews = $this->getSystemViews($objType);
            foreach ($sysViews as $view)
            {
                if ($view->isDefault())
                {
                    $defaultViewId = $view->getId();
                }
            }

            // If none were marked as default, then just grab the first one
            if (!$defaultViewId && count($sysViews))
            {
                $defaultViewId = $sysViews[0]->getId();
            }
        }

        return $defaultViewId;
    }

    /**
     * Get the user's default view for the given object type
     *
     * @param string $objType The object type
     * @param UserEntity $user The user we are getting the default for
     * @return string User's default view for the given object type
     */
    public function setDefaultViewForUser($objType, UserEntity $user, $defaultViewId)
    {
        $settingKey = "entity/browser-view/default/" . $objType;

        // Set the default view for this specific user
        $this->settings->setForUser($user, $settingKey, $defaultViewId);
    }

    /**
     * Get browser views for a user
     *
     * Here is how views will be loaded:
     *  1. First get system (file) views
     *  2. Then add account views
     *  3. Then add team views if user is a memeber
     *  4. Then add user specific views for the user
     * @param $objType
     * @param $user
     * @return array of BrowserView(s) for the user
     */
    public function getViewsForUser($objType, $user)
    {
        // If we have not loaded views from the database then do that now
        if (!isset($this->views[$objType]))
            $this->loadViewsFromDb($objType);

        $systemViews = $this->getSystemViews($objType);

        // Add account views
        $accountViews = $this->getAccountViews($objType);

        // Add team views if a user is a member of teams
        $teamViews = array();
        if (!empty($user->getValue("team_id")))
            $teamViews = $this->getTeamViews($objType, $user->getValue("team_id"));

        // Add user specific views
        $userViews = $this->getUserViews($objType, $user->getId());

        return array_merge($systemViews, $accountViews, $teamViews, $userViews);

    }

    /**
     * Get a browser view by id
     *
     * @param string $objType The object type for this view
     * @param string $id The unique id of the view
     * @return BrowserView
     */
    public function getViewById($objType, $id)
    {
        // If we have not loaded views from the database then do that now
        if (!isset($this->views[$objType]))
            $this->loadViewsFromDb($objType);

        foreach ($this->views[$objType] as $view)
        {
            if ($view->getId() == $id)
                return $view;
        }

        return null;
    }

    /**
     * Get team views that are saved to the database
     *
     * @param string $objType The object type to get browser views for
     * @param int $userId the unique id of the user to get views for
     * @return BrowserView[]
     */
    public function getUserViews($objType, $userId)
    {
        // If we have not loaded views from the database then do that now
        if (!isset($this->views[$objType]))
            $this->loadViewsFromDb($objType);

        // Return all views that are set for a specific team
        $ret = array();
        foreach ($this->views[$objType] as $view)
        {
            if ($view->getUserId() == $userId)
                $ret[] = $view;
        }
        return $ret;
    }

    /**
     * Get team views that are saved to the database for teams only
     *
     * @param $objType The object type to get browser views for
     * @return BrowserView[]
     */
    public function getTeamViews($objType, $teamId)
    {
        // If we have not loaded views from the database then do that now
        if (!isset($this->views[$objType]))
            $this->loadViewsFromDb($objType);

        // Return all views that are set for a specific team
        $ret = array();
        foreach ($this->views[$objType] as $view)
        {
            if ($view->getTeamId() && $view->getTeamId() == $teamId)
                $ret[] = $view;
        }
        return $ret;
    }

    /**
     * Get account views that are saved to the database for everyone
     *
     * @param $objType The object type to get browser views for
     * @return BrowserView[]
     */
    public function getAccountViews($objType)
    {
        // If we have not loaded views from the database then do that now
        if (!isset($this->views[$objType]))
            $this->loadViewsFromDb($objType);

        // Return all views that are not user or team views
        $ret = array();
        foreach ($this->views[$objType] as $view)
        {
            if (empty($view->getTeamId()) && empty($view->getUserId()))
                $ret[] = $view;
        }
        return $ret;
    }

    /**
     * Get system/default views from config files
     *
     * @param $objType The object type to get browser views for
     * @return BrowserView[]
     */
    public function getSystemViews($objType)
    {
        if (!$objType)
            return false;

        $views = array();

        // Check for system object
        $basePath = $this->config->get("application_path") . "/data";
        if (file_exists($basePath . "/browser_views/" . $objType . ".php"))
        {
            $viewsData = include($basePath . "/browser_views/" . $objType . ".php");

            // Initialize all the views from the returned array
            foreach ($viewsData as $key=>$vData)
            {
                // System level views must only have a name for the key because it is used for the id
                if (is_numeric($key))
                {
                    throw new \RuntimeException(
                        "BrowserViews must be defined with assiciative and unique keyname " .
                        "but " . $basePath . "/browser_views/" . $objType . ".php does not follow that rule"
                    );
                }

                $view = new BrowserView();
                $view->fromArray($vData);
                $view->setId($key); // For saving the default in user settings
                $view->setSystem(true);
                $views[] = $view;
            }

        }

        return $views;
    }

    /**
     * Save this view to the database
     *
     * @param BrowserView $view The view to save
     * @throws \RuntimeException if it cannot load the entity definition
     * @return int Unique id of saved view
     */
    public function saveView(BrowserView $view)
    {
        $dbh = $this->dbh;

        $def = $this->definitionLoader->get($view->getObjType());

        if (!$def)
            throw new \RuntimeException("Could not get entity definition for: " . $view->getObjType());

        $data = $view->toArray();

        if ($view->getId())
        {
            $sql = "UPDATE app_object_views SET
                      name='" . $dbh->escape($data['name']) . "',
                      description='" . $dbh->escape($data['description']) . "',
                      team_id=" . $dbh->escapeNumber($data['team_id']) . ",
                      user_id=" . $dbh->escapeNumber($data['user_id']) . ",
                      object_type_id=" . $dbh->escapeNumber($def->getId()) . ",
                      f_default='" . (($data['default']) ? 't' : 'f') . "',
                      owner_id=" . $dbh->escapeNumber($data['user_id']) . ",
                      conditions_data='" . $dbh->escape(json_encode($data['conditions'])) . "',
                      order_by_data='" . $dbh->escape(json_encode($data['order_by'])) . "',
                      table_columns_data='" . $dbh->escape(json_encode($data['table_columns'])) . "'
                    WHERE id='" . $view->getId() . "'; SELECT '" . $view->getId() . "' as id;";

        }
        else
        {
            $sql = "INSERT INTO app_object_views(
                  name,
                  description,
                  team_id,
                  user_id,
                  object_type_id,
                  f_default,
                  owner_id,
                  conditions_data,
                  order_by_data,
                  table_columns_data
                ) values (
                  '" . $dbh->escape($data['name']) . "',
                  '" . $dbh->escape($data['description']) . "',
                  " . $dbh->escapeNumber($data['team_id']) . ",
                  " . $dbh->escapeNumber($data['user_id']) . ",
                  " . $dbh->escapeNumber($def->getId()) . ",
                  '" . (($data['default']) ? 't' : 'f') . "',
                  " . $dbh->escapeNumber($data['user_id']) . ",
                  '" . $dbh->escape(json_encode($data['conditions'])) . "',
                  '" . $dbh->escape(json_encode($data['order_by'])) . "',
                  '" . $dbh->escape(json_encode($data['table_columns'])) . "'
                ); select currval('app_object_views_id_seq') as id;";
        }

        $result = $dbh->query($sql);
        if ($dbh->getNumRows($result))
        {
            $view->setId($dbh->getValue($result, 0, "id"));
            $this->addViewToCache($view);
            return $view->getId();
        }
        else
        {
            throw new \RuntimeException("Could not save view:" . $dbh->getLastError());
        }
    }

    /**
     * Delete a BrowserView
     *
     * @param BrowserView $view The view to delete
     * @return bool true on success, false on failure
     * @throws \RuntimeException if it cannot run the command on the backend database
     */
    public function deleteView(BrowserView $view)
    {
        if (!$view->getId())
            return false;

        // Remove from database
        $sql = "DELETE FROM app_object_views WHERE id='" . $view->getId() . "'";
        $result = $this->dbh->query($sql);
        if (!$result)
            throw new \RuntimeException("Could not delete BrowserView:" . $this->dbh->getLastError());

        // Remove the view from the local views cache
        $this->removeViewFromLocalCache($view->getObjType(), $view->getId());

        // Clear the ID since it is not saved anymore
        $view->setId(null);

        return true;

    }

    /**
     * Clear the views cache
     */
    public function clearViewsCache()
    {
        $this->views = array();
    }

    /**
     * Add the view to cache
     */
    private function addViewToCache(BrowserView $view)
    {
        $found = false;

        if (!isset($this->views[$view->getObjType()]))
            $this->views[$view->getObjType()] = array();

        // Make sure we do not add this view again
        foreach ($this->views[$view->getObjType()] as $cachedView)
        {
            if ($cachedView->getId() == $view->getId())
            {
                $found = true;
            }
        }

        if (!$found)
            $this->views[$view->getObjType()][] = $view;
    }

    /**
     * Remove a view from the local cached array
     *
     * @param string $objType The object type of the view to remove
     * @param string $viewId The unique id of the view to remove
     * @return bool true on success, false on failure
     */
    private function removeViewFromLocalCache($objType, $viewId)
    {
        if (empty($objType) || empty($viewId))
            return false;

        if (!isset($this->views[$objType]))
            return false;

        // Loop through each cached view for a match and remove it from the array if found
        for ($i = 0; $i < count($this->views[$objType]); $i++)
        {
            $cachedView = $this->views[$objType][$i];
            if ($cachedView->getId() === $viewId)
            {
                array_splice($this->views[$objType], $i, 1);

                // Break the for loop now that we have decreased the bounds of the array
                break;
            }
        }

        return true;
    }

    /**
     * This will do a one-time load of all the views from the database and cache
     *
     * @param string $objType The object type to load
     * @throws \RuntimeException if it cannot load the entity definition
     */
    private function loadViewsFromDb($objType)
    {
        $dbh = $this->dbh;

        // First clear out cache
        $this->views = array();

        $def = $this->definitionLoader->get($objType);

        if (!$def)
            throw new \RuntimeException("Could not get entity definition for $objType");

        // Initialize the cache
        if (!isset($this->views[$objType]))
            $this->views[$objType] = array();

        // Now get all views from the DB
        $sql = "SELECT
                    id, name, scope, description, filter_key,
                    user_id, object_type_id, f_default, team_id,
                    owner_id, conditions_data, order_by_data, table_columns_data
                FROM app_object_views WHERE object_type_id='" . $def->getId() . "'";
        $result = $dbh->Query($sql);
        $num = $dbh->getNumRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->getRow($result, $i);

            $viewData = array(
                'id' => $row['id'],
                'obj_type' => $objType,
                'name' => $row['name'],
                'description' => $row['description'],
                'user_id' => $row['user_id'],
                'team_id' => $row['team_id'],
                'default' => ($row['f_default'] === 't') ? true : false,
                'system' => false,
                'conditions' => json_decode($row['conditions_data'], true),
                'order_by' => json_decode($row['order_by_data'], true),
                'table_columns' => json_decode($row['table_columns_data'], true),
            );

            $view = new BrowserView();
            $view->fromArray($viewData);
            $this->views[$objType][] = $view;
        }
    }
}