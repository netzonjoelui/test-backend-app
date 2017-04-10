<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Account\Module;

/**
 * Class represents a module (used to be called application) in netric
 *
 * Modules are like sub-applications within each account. They pretty much represent
 * a separate loadable applet within the netric main application. Default applications
 * are added to every account on initialization.
 */
class Module
{
    /**
     * Unique id if saved
     *
     * @var int
     */
    private $id = null;

    /**
     * Name of the module - must be unique
     *
     * @var string
     */
    private $name = "";

    /**
     * Human readable full title of the module
     *
     * @var string
     */
    private $title = "";

    /**
     * Short menu-friendly title
     *
     * @var string
     */
    private $shortTitle = "";

    /**
     * The scope indicating who sees the module
     *
     * @var string
     */
    private $scope = self::SCOPE_EVERYONE;
    const SCOPE_EVERYONE = "system";
    const SCOPE_USER = "user";
    const SCOPE_TEAM = "team";
    const SCOPE_NOBODY = "draft";

    /**
     * Flag to indicate if this is a system module or user-generated
     *
     * @var bool
     */
    private $system = true;

    /**
     * If scope is user, then a userId must be specified
     *
     * @var int
     */
    private $userId = null;

    /**
     * If scope is for a team, then teamId must be specified
     *
     * @var int
     */
    private $teamId = null;

    /**
     * The order in which the module should be displayed in the list
     *
     * In the future this will be overridden by usage stats - how often a user
     * launches a module - but for now it reigns as the primary sort field.
     *
     * @var int
     */
    private $sortOder = 0;

    /**
     * The icon that will be used in the navigation display
     *
     * @var string
     */
    private $icon = "";

    /**
     * The default route will specify what route to load in the frontend, when clicking the module
     *
     * @var string
     */
    private $defaultRoute = null;

    /**
     * Contains the navigation link details that will be displayed in the frontend
     *
     * @var array
     */
    private $navigation = null;

    /**
     * Flag that will determine if the module navigation data was changed and needs to be saved
     *
     * @var bool
     */
    private $dirty = false;


    /**
     * Get the id of this module
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the id of this module
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the unique name of this module
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the unique name of this module
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get full human readable title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set full human readable title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Return a short, menu-friendly title
     *
     * @return string
     */
    public function getShortTitle()
    {
        return $this->shortTitle;
    }

    /**
     * Set a short, menu-friendly title
     *
     * @param string $title
     */
    public function setShortTitle($title)
    {
        $this->shortTitle = $title;
    }

    /**
     * Get the scope
     *
     * @return string self::SCOPE_*
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set the publish scope
     *
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Check if the module is a system module or user created
     *
     * @return bool
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * Set whether or not this is a system module
     *
     * @param bool $isSystem
     */
    public function setSystem($isSystem = true)
    {
        $this->system = $isSystem;
    }

    /**
     * Get the user id, used if the scope is self::SCOPE_USER
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the user id, used if the scope is self::SCOPE_USER
     *
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;;
    }

    /**
     * Get the team id, used if the scope is self::SCOPE_TEAM
     *
     * @return int
     */
    public function getTeamId()
    {
        return $this->teamId;
    }

    /**
     * Set the team id, used if the scope is self::SCOPE_TEAM
     *
     * @param int $teamId
     */
    public function setTeamId($teamId)
    {
        $this->teamId = $teamId;
    }

    /**
     * Get the sort order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOder;
    }

    /**
     * Set the sort order
     *
     * @param int $sortOrder
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOder = $sortOrder;
    }

    /**
     * Set the module icon
     *
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Get the module icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set the module navigation default route
     *
     * @param string $defaultRoute
     */
    public function setDefaultRoute($defaultRoute)
    {
        $this->defaultRoute = $defaultRoute;
    }

    /**
     * Get the module navigation default route
     *
     * @return string
     */
    public function getDefaultRoute()
    {
        return $this->defaultRoute;
    }

    /**
     * Set the navigation details
     *
     * @param array|null $navigation
     */
    public function setNavigation ($navigation)
    {
        $this->navigation = $navigation;
        $this->dirty = true;
    }

    /**
     * Get the navigation details
     *
     * @return array
     */
    public function getNavigation ()
    {
        return $this->navigation;
    }

    /**
     * Import properties from an associative array
     *
     * @param array $data Associative array describing the module
     */
    public function fromArray(array $data)
    {
        if (isset($data['id']) && $data['id'])
            $this->id = $data['id'];

        if (isset($data['name']) && $data['name'])
            $this->name = $data['name'];

        if (isset($data['title']) && $data['title'])
            $this->title = $data['title'];

        if (isset($data['short_title']) && $data['short_title'])
            $this->shortTitle = $data['short_title'];

        if (isset($data['sort_order']) && $data['sort_order'])
            $this->sortOder = $data['sort_order'];

        if (isset($data['scope']) && $data['scope'])
            $this->scope = $data['scope'];

        if (isset($data['system']) && $data['system'])
            $this->system = $data['system'];
        else
            $this->system = false;

        if (isset($data['user_id']) && $data['user_id'])
            $this->userId = $data['user_id'];

        if (isset($data['team_id']) && $data['team_id'])
            $this->teamId = $data['team_id'];

        if (isset($data['icon']) && $data['icon'])
            $this->icon = $data['icon'];

        if (isset($data['default_route']) && $data['default_route'])
            $this->defaultRoute = $data['default_route'];

        if (isset($data['navigation']) && is_array($data['navigation']) && $data['navigation'])
            $this->navigation = $data['navigation'];
    }

    /**
     * Export properties as an array
     *
     * @return array Associative array of module properties
     */
    public function toArray()
    {
        return array(
            "id" => $this->id,
            "name" => $this->name,
            "title" => $this->title,
            "short_title" => $this->shortTitle,
            "scope" => $this->scope,
            "system" => $this->system,
            "user_id" => $this->userId,
            "team_id" => $this->teamId,
            "sort_order" => $this->sortOder,
            "icon" => $this->icon,
            "defaultRoute" => $this->defaultRoute,
            "navigation" => $this->navigation
        );
    }

    /**
     * Function that will flag this module as dirty.
     * This module will be flagged as dirty when the navigation is changed.
     *
     * @param bool $dirty Boolean that will determine if the module is dirty or not
     */
    public function setDirty($dirty=true)
    {
        $this->dirty = $dirty;
    }

    /**
     * Function that will determine if this module is dirty or not
     *
     * @return bool
     */
    public function isDirty()
    {
        return $this->dirty;
    }
}