<?php
/**
 * Field definition
 * 
 * @category	EntityDefinition
 * @section		Field
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntityDefinition;

class Field implements \ArrayAccess
{
	/**
	 * Unique id if the field was loaded from a database
	 *
	 * @var string
	 */
	public $id = "";

	/**
	 * Field name (REQUIRED)
	 *
	 * No spaces or special characters allowed. Only alphanum up to 32 characters in lenght.
	 *
	 * @var string
	 */
	public $name = "";

	/**
	 * Human readable title
	 *
	 * If not set then $this->name will be used:
	 *
	 * @var string
	 */
	public $title = "";

	/**
	 * The type of field (REQUIRED)
	 *
	 * @var string
	 */
	public $type = "";

	/**
	 * The subtype
	 *
	 * @var string
	 */
	public $subtype = "";

	/**
	 * Optional mask for formatting value
	 *
	 * @var string
	 */
	public $mask = "";

	/**
	 * Is this a required field?
	 *
	 * @var bool
	 */
	public $required = false;

	/**
	 * Is this a system defined field
	 *
	 * Only user fields can be deleted or edited
	 *
	 * @var bool
	 */
	public $system = false;

	/**
	 * If read only the user cannot set this value
	 *
	 * @var bool
	 */
	public $readonly = false;

	/**
	 * This field value must be unique across all objects
	 *
	 * @var bool
	 */
	public $unique = false;

	/**
	 * Optional use_when condition will only display field when condition is met
	 *
	 * This is used for things like custom fields for posts where each feed will have special
	 * custom fields on a global object - posts.
	 *
	 * @var string
	 */
	private $useWhen = "";

	/**
	 * Default value to use with this field
	 *
	 * @var array('on', 'value')
	 */
	public $default = null;

	/**
	 * Optional values
	 *
	 * If an associative array then the id is the key, otherwise the value is used
	 *
	 * @var array
	 */
	public $optionalValues = null;

	/**
	 * Foreign key used with fkey type
	 *
	 * Below are they keys
	 * array(
	 * 	"key" // The unique id of the referenced object
	 * 	"title" // The title column for the label
	 * 	"parent" // If set, the field that defines the parent for a heirarchial view of objects
	 * 	"filter" // serialized array to use as a filter array('thisTableColumn'=>'matchesForeignColumn')
	 * 	"ref_table" = array( // If an fkey_multi that is not grouping, then this is the *_mem table
	 * 			"table" // The table name to get memberships from
	 * 			"this" // The field in the membership table that refers to this object
	 * 			"ref" // The field in the membership table that refers to the foreign object
	 * 		)
	 * )
	 *
	 * @var array
	 */
	public $fkeyTable = null;

	/**
	 * Sometimes we need to automatically create foreign reference
	 *
	 * @var bool
	 */
	public $autocreate = false;

	/**
	 * If autocreate then the base is used to define where to put the new referenced object
	 *
	 * @var string
	 */
	public $autocreatebase = "";

	/**
	 * If autocreate then which field should we use for the name of the new object
	 *
	 * @var string
	 */
	public $autocreatename = "";

	/**
	 * Load field definition from array
	 *
	 * @param array $data
	 */
	public function fromArray($data)
	{
		if (isset($data["id"]))
			$this->id = $data["id"];

		if (isset($data["name"]))
			$this->name = $data["name"];

		if (isset($data["title"]))
			$this->title = $data["title"];

		if (isset($data["type"]))
			$this->type = $data["type"];

		if (isset($data["subtype"]))
			$this->subtype = $data["subtype"];

		if (isset($data["mask"]))
			$this->mask = $data["mask"];

		if (isset($data["required"]))
			$this->required = ($data["required"]===true || (string)$data["required"]=="true" || (string)$data["required"]=="t") ? true : false;

		if (isset($data["system"]))
			$this->system = ($data["system"]===true || (string)$data["system"]=="true" || (string)$data["system"]=="t") ? true : false;

		if (isset($data["readonly"]))
			$this->readonly = ($data["readonly"]===true || (string)$data["readonly"]=="true" || (string)$data["readonly"]=="t") ? true : false;

		if (isset($data["unique"]))
			$this->unique = ($data["unique"]===true || (string)$data["unique"]=="true" || (string)$data["unique"]=="t") ? true : false;

		if (isset($data["autocreate"]))
			$this->autocreate = $data["autocreate"];
		
		if (isset($data["autocreatename"]))
			$this->autocreatename = $data["autocreatename"];
		
		if (isset($data["autocreatebase"]))
			$this->autocreatebase = $data["autocreatebase"];

		if (isset($data["use_when"]))
			$this->setUseWhen($data["use_when"]);

		if (isset($data["default"]))
			$this->default = $data["default"];

		if (isset($data["optional_values"]))
			$this->optionalValues = $data["optional_values"];

		if (isset($data["fkey_table"]))
			$this->fkeyTable = $data["fkey_table"];

		// Check object groupings
		if (("fkey" == $this->type || "fkey_multi" == $this->type) && "object_groupings" == $this->subtype && $this->fkeyTable == null)
		{
			$this->fkeyTable = array(
				"key"=>"id", 
				"title"=>"name", 
				"parent"=>"parent_id",
					"ref_table"=>array(
						"table"=>"object_grouping_mem", 
						"this"=>"object_id", 
						"ref"=>"grouping_id"
					)
			);
		}
	}

	/**
	 * Conver field definition to array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
			"id" => $this->id,
			"name" => $this->name,
			"title" => $this->title,
			"type" => $this->type,
			"subtype" => $this->subtype,
			"default" => $this->default,
			"mask" => $this->mask,
			"required" => $this->required,
			"system" => $this->system,
			"readonly" => $this->readonly,
			"unique" => $this->unique,
			"use_when" => $this->useWhen,
			"default" => $this->default,
			"optional_values" => $this->optionalValues,
			"fkey_table" => $this->fkeyTable,
			"autocreate" => $this->autocreate,
			"autocreatename" => $this->autocreatename,
			"autocreatebase" => $this->autocreatebase,
		);
	}

	/**
	 * Set useWhen condition
	 *
	 * @param string $value
	 */
	public function setUseWhen($value)
	{
		// Modify name of the field if this is a new field
		if ($value && !$this->id)
		{
			$postpend = "";
			$parts = explode(":", $value);
			if (count($parts) > 1)
			{
				$postpend = "_".$parts[0]."_";

				$parts[1] = str_replace("-", "minus", $parts[1]);
				$parts[1] = str_replace("+", "plus", $parts[1]);

				$postpend .= $parts[1];
			}

			if ($postpend)
			{
				$this->name = $this->name . $postpend;
			}
		}

		$this->useWhen = $value;
	}

	/**
	 * Get useWhen condition
	 *
	 * @return string
	 */
	public function getUseWhen()
	{
		return $this->useWhen;
	}

	/**
	 * Get a default value based on an event like 'update'
	 *
	 * TODO: in-progress
	 *
	 * @param mixed $value The current value
	 * @param string $event The event to use the default on
	 * @param Entity $obj If set, update the object directly
	 * @param AntUser $user If set, use this for user variables
	 */
	public function getDefault($value, $event='update', $obj=null, $user=null)
	{
		$ret = $value;
		
		if ($this->default && is_array($this->default) && count($this->default))
		{
			if($this->default['on'])
				$on = $this->default['on'];

			// Check if condition is part of the default
			if (isset($this->default['where']) && $this->default['where'] && $obj)
			{
				if (is_array($this->default['where']))
				{
					foreach ($this->default['where'] as $condFName=>$condVal)
					{
						if ($obj->getValue($condFName) != $condVal)
							$on = ""; // Do not set default
					}
				}
			}

			// Determin appropriate event and action
			switch ($on)
			{
			case 'create':
				if ($value)
					break;
				else
					$ret = $this->default['value'];
				// Fall through to also use update
			case 'update':
				if ($on == "update")
				{
					if (isset($this->default['coalesce']) && is_array($this->default['coalesce']) && $obj)
					{
						$ret = $this->getDefaultCoalesce($this->default['coalesce'], $obj, ($this->type == "alias")?true:false);
						if (!$ret)
							$ret = $this->default['value'];
					}
					else
					{
						$ret = $this->default['value'];
					}
				}
				break;
			case 'delete':
				if ($on == "delete")
					$ret = $this->default['value'];
				break;
			case 'null':
				if ($ret==="" || $ret===null || $ret==$this->default['value'])
				{

					if (isset($this->default['coalesce']) && $this->default['coalesce'] && is_array($this->default['coalesce']) && $obj)
					{
						$ret = $this->getDefaultCoalesce($this->default['coalesce'], $obj, ($this->type == "alias")?true:false);
						if (!$ret)
							$ret = $this->default['value'];
					}
					else
					{
						$ret = $this->default['value'];
					}
				}
				break;
			}
		}

		// Convert values
		switch ($this->type)
		{
		case 'date':
			if ("now" == $ret)
				$ret = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
			break;
		case 'time':
		case 'timestamp':
			if ("now" == $ret)
				$ret = time();
			break;
		}

		// Look for variables
        if (is_string($ret))
        {
            if ("<%username%>" == (string)$ret)
            {
                if ($user)
                    $ret = $user->getValue('name');
                else
                    $ret = "";
            }

            if ("<%userid%>" == (string)$ret)
            {
                if ($user)
                    $ret = $user->getId();
                else
                    $ret = "";
            }
        }

		if ((($this->type == "fkey" && $this->subtype == "users") 
			  || ($this->type == "object" && $this->subtype == "user")) && $ret == "-3")
		{
			if ($user)
				$ret = $user->getId();
			else
				$ret = ""; // TODO: possibly use system or anonymous
		}


		return $ret;
	}

	/**
	 * If the default value involves combining more than one field
	 *
	 * @param
	 */
	public function getDefaultCoalesce($cfields, $obj, $alias=false)
	{
		$ret = "";

		foreach ($cfields as $field_to_pull)
		{
			if (is_array($field_to_pull))
			{
				foreach ($field_to_pull as $subcol)
				{
					$buf = $obj->getValue($subcol);
					if ($buf)
					{
						if ($ret) $ret .= " ";

						if ($alias)
						{
							$ret = $subcol;
							break;
						}
						else
						{
							$ret .= $buf;
						}
					}
				}

			}
			else
			{
				if ($alias)
				{
					$ret = $field_to_pull;
					break;
				}
				else
				{
					$ret = $obj->getValue($field_to_pull);
				}
			}

			// Check if name was found
			if ($ret)
				break;
		}

		return $ret;
	}


	/**
	 * ArrayAccess Implementation Functions
	 * -------------------------------------------------------------------
	 */
	public function offsetSet($offset, $value) 
	{
		if (is_null($offset)) {
		$this->container[] = $value;
		} else {
		$this->container[$offset] = $value;
		}
	}
	public function offsetExists($offset) {
		return isset($this->container[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->container[$offset]);
	}
	public function offsetGet($offset) {
		return isset($this->container[$offset]) ? $this->container[$offset] : null;
	}
}
