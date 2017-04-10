<?php
/**
 * A saved (or defined by file) view for an entity browser
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\BrowserView;

use Netric\EntityQuery;


/**
 * Represent a single browser view
 *
 * @package Netric\Entity\BrowserView
 */
class BrowserView
{
    /**
     * User id if this view is owned by an individual user
     *
     * @var int
     */
    private $userId = null;

    /**
     * Set if this view is owned by a team
     *
     * @var int
     */
    private $teamId = null;

    /**
     * Unique id of this view if saved
     *
     * @var string
     */
    private $id = null;

    /**
     * Name describing this view
     *
     * @var string
     */
    private $name = null;

    /**
     * Full description of the view
     *
     * @var string
     */
    private $description = null;

    /**
     * Which fields to display in a table view
     *
     * @var array
     */
    private $tableColumns = array();

    /**
     * TODO: document or remove if we no longer need it
     *
     * @var string
     */
    private $filterKey = null;

    /**
     * True if this is the default view for the given user
     *
     * @var bool
     */
    private $default = false;

    /**
     * This is a system view which cannot be modified or deleted
     *
     * @var bool
     */
    private $system = false;

    /**
     * Array of order by fields
     *
     * @var EntityQuery\OrderBy[]
     */
    private $orderBy = array();

    /**
     * Array of where conditions
     *
     * @var EntityQuery\Where[]
     */
    private $wheres = array();

    /**
     * The type of object this view is describing
     *
     * @var string
     */
    private $objType = null;

    /**
     * Convert the data for this view to an array
     *
     * @return array
     */
    public function toArray($userid=null)
    {
        $ret = array(
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            //"filter_key" => $this->filterKey,
            "system" => $this->system,
            "default" => $this->default,
            "user_id" => $this->userId,
            "team_id" => $this->teamId,
            "obj_type" => $this->objType,
            "table_columns" => array(),
            "conditions" => array(),
            "order_by" => array(),
        );

        // Add view fields
        foreach ($this->tableColumns as $field)
        {
            $ret['table_columns'][] = $field;
        }

        // Add conditions
        foreach ($this->wheres as $where)
        {
            $ret['conditions'][] = $where->toArray();
        }

        // Add sort order
        foreach ($this->orderBy as $sort)
        {
            $ret['order_by'][] = $sort->toArray();
        }

        return $ret;
    }

    /**
     * Load this view from an associative array
     *
     * @param array $data
     */
    public function fromArray(array $data)
    {
        if (isset($data['id']))
            $this->id = $data['id'];

        if (isset($data['name']))
            $this->name = $data['name'];

        if (isset($data['obj_type']))
            $this->objType = $data['obj_type'];

        if (isset($data['description']))
            $this->description = $data['description'];

        if (isset($data['system']) && is_bool($data['system']))
            $this->system = $data['system'];

        if (isset($data['default']) && is_bool($data['default']))
            $this->default = $data['default'];

        if (isset($data['f_default']) && is_bool($data['f_default']))
            $this->default = $data['f_default'];

        if (isset($data['team_id']))
            $this->setTeamId($data['team_id']);

        // We put this last in case they set both team and user then user will override team
        if (isset($data['user_id']))
            $this->setUserId($data['user_id']);

        if (isset($data['table_columns']) && is_array($data['table_columns']))
        {
            foreach ($data['table_columns'] as $colField)
            {
                $this->tableColumns[] = $colField;
            }
        }

        if (isset($data['conditions']) && is_array($data['conditions']))
        {
            foreach ($data['conditions'] as $cond)
            {
                $where = new EntityQuery\Where($cond['field_name']);
                $where->fromArray($cond);
                $this->wheres[] = $where;
            }
        }

        if (isset($data['order_by']) && is_array($data['order_by']))
        {
            foreach ($data['order_by'] as $sortData)
            {
                $orBy = new EntityQuery\OrderBy($sortData['field_name'], $sortData['direction']);
                $this->orderBy[] = $orBy;
            }
        }
    }

    /**
     * Set the BrowserView id
     *
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the id of the BrowserView if saved in DB
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the name of this view
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the full description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get user id if set just for a user
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the user id
     *
     * If the userId is set, then this will clear the teamId
     * since only one can be set at a time.
     *
     * @param int $userId Unique user id for this view
     */
    public function setUserId($userId)
    {
        if ($this->getTeamId())
            $this->teamId = null;

        $this->userId = $userId;
    }

    /**
     * Get team id if only set for a team
     *
     * @return int
     */
    public function getTeamId()
    {
        return $this->teamId;
    }

    /**
     * Set the team id
     *
     * If the teamId is set, then this will clear the userId
     * since only one can be set at a time.
     *
     * @param int $teamId Unique team ID for this view
     */
    public function setTeamId($teamId)
    {
        if ($this->getUserId())
            $this->userId = null;

        $this->teamId = $teamId;
    }

    /**
     * Get the table colums array
     *
     * @return array
     */
    public function getTableColumns()
    {
        return $this->tableColumns;
    }

    /**
     * Get the object type this view is describing
     *
     * @return string
     */
    public function getObjType()
    {
        return $this->objType;
    }

    /**
     * Set the object type
     *
     * @param $objType
     */
    public function setObjType($objType)
    {
        $this->objType = $objType;
    }

    /**
     * Check if this is set as a default view
     *
     * @return bool true if this should be displayed by default
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * Check if this is a system view (from a file)
     *
     * @return bool true if this is not a db view (cannot be changed)
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * Set this to a system view which means it cannot be saved or changed
     *
     * @param bool $isSystem
     */
    public function setSystem($isSystem = false)
    {
        $this->system = $isSystem;
    }

    /**
     * Get conditions array
     *
     * @return EntityQuery\Where[]
     */
    public function getConditions()
    {
        return $this->wheres;
    }

    /**
     * Get order by array
     *
     * @return EntityQuery\OrderBy[]
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }
}

