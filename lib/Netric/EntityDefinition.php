<?php
/**
 * Define the meta-data for an object
 *
 * @category	EntityDefinition
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric;

use Netric\Permissions\Dacl;

// Legacy includes
require_once(dirname(__FILE__) . "/../CAntObjectView.php");
require_once(dirname(__FILE__) . "/../CAntObjectCond.php");
require_once(dirname(__FILE__) . "/../CAntObjectSort.php");

/** 
 * Definition class
 */
class EntityDefinition
{
	/**
	 * Unique id for this entity
	 *
	 * @var string
	 */
	public $id = "";

	/**
	 * List of fields
	 *
	 * @var EntityDefinition\Field[]
	 */
	private $fields = array();
	
	/**
	 * The object type name for this definiton
	 *
	 * @var string
	 */
	private $objType = "";

	/**
	 * The human readable title of this object type
	 *
	 * @var string
	 */
	public $title = "";

	/**
	 * The unique system id of this object type
	 *
	 * @var string
	 */
	private $otid = "";

	/**
	 * Is a system object which cannot be deleted
	 *
	 * We assume it is
	 *
	 * @var {bool}
	 */
	public $system = true;

	/**
	 * Optional icon name
	 *
	 * Object icons are all stored in /images/icons/objects/ and must have 16, 24, 32, and 48 px variants
	 * named after the object name like. customer_16.png, customer_32.png or can even have subcategories with
	 * a forward slash representing a subdirectory like 'customers/account' would 
	 * map to /images/icons/objects/customers/account_[size].png
	 *
	 * @var {string}
	 */
	public $icon = "";

	/**
	 * Flag if we are using a custom table
	 *
	 * True if we are using a table that is not part of the objects partitions
	 *
	 * @var bool
	 */
	public $useCustomTable = false;

	/**
	 * The table where objects are stored
	 *
	 * @var string
	 */
	public $object_table = "";

	/**
	 * The default activity level to use when working with this object type
	 *
	 * @var int
	 */
	public $defaultActivityLevel = 3;

	/**
	 * Saved collection views
	 *
	 * @var EntityCollection_View[]
	 */
	public $collectionViews = array();

	/**
	 * Unique name settings string
	 *
	 * If empty then uname will not be generated automatically and id will be used
	 *
	 * @var string
	 */
	public $unameSettings = "";

	/**
	 * The current revision of this definition
	 *
	 * @var int
	 */
	public $revision = 0;

	/**
	 * Field to use for the name/title in lists
	 *
	 * @var string
	 */
	public $listTitle = "name";

	/**
	 * System forms for UIXML forms
	 *
	 * @var array('desktop', 'mobile', 'infobox', 'small', 'medium', 'large', 'xlarge')
	 */
	private $forms = array();

	/**
	 * Is this a private object type where only the owner gets acces
	 *
	 * @var bool
	 */
	public $isPrivate = false;

	/**
	 * Reucrrance rules
	 *
	 * @var array
	 */
	public $recurRules = null;

	/**
	 * Aggregate object reference fields
	 *
	 * @var array
	 */
	public $aggregates = array();
	
	/**
	 * Define a field reference to inherit permissions from if set like cases and projects
	 *
	 * @var string
	 */
	public $inheritDaclRef;

	/**
	 * The application id that owns this object
	 *
	 * @var string
	 */
	public $applicationId = "";

	/**
	 * Whether or not we should store revisions of each change
	 *
	 * @var bool
	 */
	public $storeRevisions = true;
    
    /**
     * Put a cap on the number of objects this entity can have per account
     * 
     * @var int
     */
    public $capped = false;
    
    /**
     * Parent field
     * 
     * @var string
     */
    public $parentField = "";

	/**
	 * Default access control list for all entities of this type
	 *
	 * @var null
	 */
	private $dacl = null;

	/**
	 * Class constructor
	 */
	public function __construct($objType)
	{
		$this->objType = trim($objType);

		// Set object table
		$this->object_table = "objects_" . $objType;

		// Set default fields
		$this->setDefaultFields();
	}

	/**
	 * Set unique id
	 *
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Get unique id
	 *
	 * @return string The saved unique id of this definition
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return the object type for this definition
	 *
	 * @return string
	 */
	public function getObjType()
	{
		return $this->objType;
	}

	/**
	 * Set form for a medium
	 *
	 * @param string $xmlForm The UIXML form
	 * @param string $medium Either 'default', 'mobile' or 'infobox'
	 */
	public function setForm($xmlForm, $medium='default')
	{
		 $this->forms[$medium] = $xmlForm;
	}

	/**
	 * Get a form
	 *
	 * @param string $medium Either 'default', 'mobile' or 'infobox'
	 */
	public function getForm($medium='default')
	{
		return (isset($this->forms[$medium])) ? $this->forms[$medium] : null;
	}

	/**
	 * Return forms
	 *
	 * @return array
	 */
	public function getForms()
	{
		return $this->forms;
	}

	/**
	 * Add a field
	 *
	 * @param Netric\EntityDefinition\Field
	 * @retrun bool true on succes, false on failure
	 */
	public function addField($field)
	{
		if (!$field)
			return false;

		if (!$field->name || !$field->type)
			return false;

		// Add field witht the name as the index
		$this->fields[$field->name] = $field;

		return true;
	}

	/**
	 * Remove a field
	 *
	 * @param string $fieldName
	 * @return bool true on success, false on failure
	 */
	public function removeField($fieldName)
	{
		if ($this->fields[$fieldName])
		{
			if (!$this->fields[$fieldName]->system)
			{
				$this->fields[$fieldName] = null;
				return true;
			}
		}

		// Did not meet removal requirements
		return false;
	}

	/**
	 * Get a field
	 *
	 * @param string $name The name of the field to get
	 * @return Netric\EntityDefinition\Field
	 */
	public function getField($fname)
	{
		if (isset($this->fields[$fname]))
			return $this->fields[$fname];
		else
			return null;
	}

	/**
	 * Get all fields for this object type
	 *
	 * @param bool $includeRemoved If true, then removed fields will be returned with null values
	 * @return Netric\EntityDefinition\Field
	 */
	public function getFields($includeRemoved=false)
	{
		if ($includeRemoved)
		{
			return $this->fields;
		}
		else
		{
			$fields = array();
			foreach ($this->fields as $fname=>$field)
			{
				if ($field)
					$fields[$fname] = $field;
			}	

			return $fields;
		}
	}

	/**
	 * Get the total number of fields
	 *
	 * @return int The number of fields defined
	 */
	public function getNumFields()
	{
		return count($this->fields);
	}

	/**
	 * Get the type of a field by name
	 *
	 * @param string $name The name of the field to get the type for
	 * @return arrary('type'=>[type], 'subtype'=>[subtype])
	 */
	public function getFieldType($name)
	{
		$arr = array("type" => null, "subtype" => null);
		
		if(isset($this->fields[$name]->type))
			$arr['type'] = $this->fields[$name]->type;
			
		if(isset($this->fields[$name]->subtype))
			$arr['subtype'] = $this->fields[$name]->subtype;
			
		return $arr;
	}

	/**
	 * Add a defined collection view
	 *
	 * @param EntityCollection_View $view The view to add
	 */
	public function addView($view)
	{
		$this->collectionViews[] = $view;
	}

	/**
	 * Get all views
	 *
	 * @return EntityCollection_View[]
	 */
	public function getViews()
	{
		return $this->collectionViews;
	}

	/**
	 * Add an aggregate
	 *
	 * @param stdCls $agg
	 */
	public function addAggregate($agg)
	{
		$this->aggregates[] = $agg;
	}

	/**
	 * Build an array of this definition
	 *
	 * @return array
	 */
	public function toArray()
	{
		$ret = array(
			"id" => $this->id,
			"obj_type" => $this->objType,
			"title" => $this->title,
			"revision" => $this->revision,
			"default_activity_level" => $this->defaultActivityLevel,
			"is_private" => $this->isPrivate,
			"recur_rules" => $this->recurRules,
			"inherit_dacl_ref" => $this->inheritDaclRef,
			"uname_settings" => $this->unameSettings,
			"list_title" => $this->listTitle,
			"object_table" => ($this->useCustomTable) ? $this->object_table : "",
			"icon" => $this->icon,
			"system" => $this->system,
			"application_id" => $this->applicationId,
			"fields" => array(),
			"store_revisions" => $this->storeRevisions,
            "parent_field" => $this->parentField,
		);

		// Add fields for this object definition
		foreach ($this->fields as $fname=>$field)
		{
			// Make sure the the $field is not a deleted field
			if($field != null) {
				$ret['fields'][$fname] = $field->toArray();
			}
		}

        $views = $this->getViews();
        $ret['views'] = array();
        foreach ($views as $view)
        {
            $ret['views'][] = $view->toArray();
        }

		return $ret;
	}

	/**
	 * Load from an associative array
	 *
	 * @param array $data The data to load
	 * @return bool true on success, false on failure
	 */
	public function fromArray($data)
	{
		if (!is_array($data))
			return false;

		if (isset($data['revision']))
			$this->revision = $data['revision'];

		if (isset($data['fields']))
		{
			foreach ($data['fields'] as $name=>$fdef)
			{
				$field = new EntityDefinition\Field();
				$field->name = $name;
				$field->fromArray($fdef);
				$this->addField($field);
			}
		}

		if (isset($data['deleted_fields']))
		{
			foreach ($data['deleted_fields'] as $fieldName)
				$this->removeField($fieldName);
		}

		if (isset($data['system']))
			$this->system = $data['system'];

		if (isset($data['default_activity_level']))
			$this->defaultActivityLevel = $data['default_activity_level'];

		if (isset($data['is_private']))
			$this->isPrivate = $data['is_private'];

		if (isset($data['recur_rules']))
			$this->recurRules = $data['recur_rules'];

		if (isset($data['inherit_dacl_ref']))
			$this->inheritDaclRef = $data['inherit_dacl_ref'];

		if (isset($data['parent_field']))
			$this->parentField = $data['parent_field'];

		if (isset($data['uname_settings']))
			$this->unameSettings = $data['uname_settings'];

		if (isset($data['list_title']))
			$this->listTitle = $data['list_title'];

		if (isset($data['icon']))
			$this->icon = $data['icon'];

		if (isset($data['id']))
			$this->id = $data['id'];

		if (isset($data['title']))
			$this->title = $data['title'];

		if (isset($data['object_table']) && $data['object_table'])
			$this->setCustomTable($data['object_table']);

		if (isset($data['application_id']))
			$this->applicationId = $data['application_id'];

		if (isset($data['store_revisions']))
			$this->storeRevisions = $data['store_revisions'];

		return true;
	}

	/**
	 * Set a custom table to use other than partitions
	 *
	 * @param string $table
	 */
	public function setCustomTable($table)
	{
		$this->object_table = $table;
		$this->useCustomTable = true;
	}

	/**
	 * Check if this entity uses a custom table name as opposed to dynamic
	 *
	 * @return bool true if custon, false if dynamic
	 */
	public function isCustomTable()
	{
		return $this->useCustomTable;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->object_table;
	}

	/**
	 * Set common default fields for all objects
	 *
	 * @return array
	 */
	private function setDefaultFields()
	{
		// Add default fields that are common to all objects
		$defaultFields = array(
			"id" => array(
				'title'=>"ID", 
				'type'=>"number",
				'id'=>"0",
				'subtype'=>"", 
				'readonly'=>true, 
				'system'=>true, 
			),
			'associations' => array(
				'title'=>'Associations', 
				'type'=>'object_multi', 
				'subtype'=>'', 
				'readonly'=>true, 
				'system'=>true, 
			),
			'attachments' => array(
				'title'=>'Attachments',
				'type'=>'object_multi',
				'subtype'=>'file',
				'readonly'=>true,
				'system'=>true,
			),
			'followers' => array(
				'title'=>'Followers',
				'type'=>'object_multi',
				'subtype'=>'user',
				'readonly'=>true,
				'system'=>true,
			),
			'activity' => array(
				'title'=>'Activity', 
				'type'=>'object_multi', 
				'subtype'=>'activity', 
				'system'=>true, 
			),
			'comments' => array(
				'title'=>'Comments',
				'type'=>'object_multi', 
				'subtype'=>'comment',
				'readonly'=>false,
				'system'=>true,
			),
			'num_comments' => array(
				'title'=>'Num Comments',
				'type'=>'number',
				'subtype'=>'integer',
				'readonly'=>true,
				'system'=>true,
			),
			'commit_id' => array(
				'title'=>'Commit Revision',
				'type'=>'number', 
				'subtype'=>'', 
				'readonly'=>true, 
				'system'=>true, 
			),
			'f_deleted' => array(
				'title'=>'Deleted', 
				'type'=>'bool', 
				'subtype'=>'', 
				'readonly'=>true, 
				'system'=>true, 
			),
            'f_seen' => array(
                'title'=>'Seen',
                'type'=>'bool',
                'subtype'=>'',
                'readonly'=>true,
                'system'=>true,
            ),
			'revision' => array(
				'title'=>'Revision',
				'type'=>'number',
				'subtype'=>'',
				'readonly'=>true,
				'system'=>true, 
			),

			// The full path based on parent objects
			// DEPRICATED: appears to no longer be used, but maybe we should start
			// because searches would be a lot easier in the future.
			'path' => array(
				'title'=>'Path', 
				'type'=>'text', 
				'subtype'=>'', 
				'readonly'=>true, 
				'system'=>true, 
			),

			// Unique name in URL escaped form if object type uses it, otherwise the id
			'uname' => array(
				'title'=>'Uname', 
				'type'=>'text', 
				'subtype'=>'256', 
				'readonly'=>true, 
				'system'=>true, 
			),

			'dacl' => array(
				'title'=>'Security', 
				'type'=>'text', 
				'subtype'=>'', 
				'readonly'=>true, 
				'system'=>true, 
			),
			'ts_entered' =>	array(
				'title'=>'Time Entered', 
				'type'=>'timestamp', 
				'subtype'=>'', 
				'readonly'=>true, 
				'system'=>true, 
				'default'=>array(
					"value"=>"now",
					"on"=>"create"
				),
			),
			'ts_updated' => array(
				'title'=>'Time Changed', 
				'type'=>'timestamp', 
				'subtype'=>'', 
				'readonly'=>true, 
				'system'=>true, 
				'default'=>array(
					"value"=>"now", 
					"on"=>"update"
				),
			),
		);

		foreach ($defaultFields as $fname=>$fdef)
		{
			$field = new EntityDefinition\Field();
			$field->name = $fname;
			$field->system = true;
			$field->fromArray($fdef);
			$this->addField($field);
		}
	}

    /**
     * Get the title of this object type
     *
     * FIXME:
     * Even though the title property is public right now,
     * we intend on moving it to private in the near future so
     * this function can be used in preparation for that change.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the title of this object type
     *
     * The title is the human readable short description
     * of the object type and always has an upper case first letter.
     *
     * FIXME:
     * Right now $this->title is public, but as described in getTitle above,
     * we plan on moving it to private in the near future so we are providing
     * a getter and setter function for the property so code can begin using it
     * to make the transition to a better design easier. It's always nice when
     * we have less code to change.
     *
     * @param string $title The title of this object type
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

	/**
	 * Check if this is a private entity type
	 *
	 * @return bool
	 */
	public function isPrivate()
	{
		return $this->isPrivate;
	}

	/**
	 * Set discretionary access control list
	 *
	 * @param Dacl $dacl
	 */
	public function setDacl(Dacl $dacl = null)
	{
		$this->dacl = $dacl;
	}

    /**
     * Get the discretionary access control list for this object type
     *
     * @return Dacl
     */
    public function getDacl()
    {
        return $this->dacl;
    }

    /**
     * Set whether or not this is a system entity (can't be deleted)
     *
     * @param bool $isSystem
     */
    public function setSystem($isSystem)
    {
        $this->system = $isSystem;
    }

    /**
     * Get flag that indicates if this is a system entity or not (can't be deleted)
     */
    public function getSystem()
    {
        return $this->system;
    }
}
