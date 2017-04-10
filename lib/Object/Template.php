<?php
/**
 * This is the base class for dealing with object templates
 *
 * For now every object type will be handled with this class. In the future we
 * may want to subclass for object type specific behavior.
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @category  AntObject 
 * @package   AntObject_Temnplate
 * @copyright Copyright (c) 2013 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Template class
 */
class AntObject_Template
{
	/**
	 * The id of this template if saved
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * Account database
	 *
	 * @var CDatabase
	 */
	public $dbh = null;

	/**
	 * The name of the object type we are working with
	 *
	 * @var string
	 */
	public $objType = "";

	/**
	 * Referenced sub-templates
	 *
	 * @param array(array("obj_type", "ref_field", "template"))
	 */
	public $refTemplates = array();

	/**
	 * Field values
	 *
	 * Associative array of field values
	 *
	 * @var array
	 */
	private $fieldValues = array();

	/**
	 * Current user
	 *
	 * @var AntUser
	 */
	protected $user = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Account database object
	 * @param string $objType The object type we are working with
	 * @param int $id Optional id of previously saved template
	 * @param AntUser $user Current user object
	 */
	public function __construct(CDatabase $dbh, $objType, $id=null, $user=null)
	{
		$this->objType = $objType;
		$this->dbh = $dbh;
		$this->user = $user;

		// Load values if previously saved
		if ($id)
			$this->load($id);
	}

	/**
	 * Save this template
	 */
	public function save()
	{
		// TODO: save local variables and refernced templates
	}

	/**
	 * Load previously saved template
	 *
	 * @param int $id The id of the template to load
	 * @return bool true on success, false on failure or id not found
	 */
	public function load($id)
	{
		// TODO:  load local variable and any referenced object templates = $this->refTemplates
		return false;
	}

	/**
	 * Remove this template if previously saved
	 */
	public function remove()
	{
		if ($this->id)
		{
			// TODO: delete
			$this->id = null;
		}
	}

	/**
	 * Set property/field value
	 *
	 * @param string $fieldName The name of the object field
	 * @param string $fieldValue The value to set fieldname to
	 * @return bool true on success, false on failure
	 */
	public function setValue($fieldName, $fieldValue)
	{
		$this->fieldValues[$fieldName] = $fieldValue;
		return true;
	}

	/**
	 * Get a property/field value
	 *
	 * @param string $fieldName The name of the object field
	 * @return string The value of the field
	 */
	public function getValue($fieldName)
	{
		return $this->fieldValues[$fieldName];
	}

	/**
	 * Add object reference sub-template
	 *
	 * @param AntObject_Temnplate $template A template with the values of the new sub-object to create
	 * @param string $refField The field of the sub-template that will reference this object once created
	 */
	public function addObjectReferenced($template, $refField="")
	{
		$this->refTemplates[] = array(
			"obj_type" => $template->objType,
			"ref_field" => $refField,
			"template" => $template,
		);
	}

	/**
	 * Create or update an CAntObject based on the values in this template
	 *
	 * @param &CAntObject $obj If passed then the object will be edited with values only being set if null
	 * @retrun CAntObject
	 */
	public function createObject(&$obj=null)
	{
		if ($obj == null)
			$obj = CAntObject::factory($this->dbh, $this->objType, null, $this->user);

		// Attempt to resolve dependencies
		$fieldArr = $this->resolveDependencies($obj->fields->getFields());

		foreach ($fieldArr as $fname=>$fdef)
		{
			$val = $obj->getValue($fname);

			if (!$val) // Allow obj override of values if not set. TODO: what about defaults like owner_id?
				$val = $this->processValue($fname, $obj);

			if ($val)
				$obj->setValue($fname, $val);

			// TODO: Handle mvalue
		}
		
		// Save now - this is needed for referenced objects below
		$obj->save();
		if (!$obj->id)
			return false; // there was an error
		
		// Create referenced sub-objects from $this->refTemplates
		foreach ($this->refTemplates as $subtemp)
		{
			$subobj = CAntObject::factory($this->dbh, $subtemp['obj_type'], null, $this->user);

			if ($subtemp['ref_field'])
				$subobj->setValue($subtemp['ref_field'], $obj->id);
				
			// Set all other values from template
			$subtemp['template']->createObject($subobj);

			// Save values
			$subobj->save();
		}

		return $obj;
	}

	/**
	 * Ateempt to resolve dependencies through circular references in variables
	 *
	 * For instance, if an object has a local variable (owner_id.name) but owner_id is set with another
	 * varible then we want to try and make sure the owner_id field value is set first by putting it first
	 * in the array so that values fall through naturally.
	 *
	 * @param array $fields Array of fields from the CAntObjectFields class of the object we are creating
	 * @return array Resorted array of fields with dependencies resolved
	 */
	public function resolveDependencies($fields)
	{
		$ret = array();

		// TODO: resolve - for now just copy
		$ret = $fields;
		
		return $ret;
	}

	/**
	 * Process value variables meaning if the template value is a varible then convert to real value
	 *
	 * @param string $fieldName The name of the field we are setting
	 * @param CAntObject $obj The object we are editing, use for pulling values in variables
	 * @return string | array for *_multi
	 */
	public function processValue($fieldName, $obj)
	{
		$field = $obj->fields->getField($fieldName);
		
		if (!$field || !isset($this->fieldValues[$fieldName]))
			return null;

		$val = $this->fieldValues[$fieldName];

		switch ($field['type'])
		{
		case 'date':
			$val = $this->processValueTime($val, $obj, true);
			break;
		case 'timestamp':
			$val = $this->processValueTime($val, $obj);
			break;
		case 'text':
			$val = $this->processValueMerge($val, $obj);
			break;
		default:
			// Do no processing, just leave the value alone
			break;
		}

		return $val;
	}

	/**
	 * Process generic merge values starting with <% and ending with %>
	 *
	 * @param $value
	 * @param CAntObject $obj The object we are editing, use for pulling values in variables
	 * @return string Processed hard value to set
	 */
	public function processValueMerge($value, $obj)
	{
		// TODO: look for merge vars
		return $value;
	}
	
	/**
	 * Process value timestamp
	 * 
	 * @param $value
	 * @param CAntObject $obj The object we are editing, use for pulling values in variables
	 * @param bool $dateOnly If true then only mm/dd/yyyy part of value will be returned
	 * @return string Processed hard value to set
	 */
	public function processValueTime($value, $obj, $dateOnly=false)
	{
		$strToGet = ($dateOnly) ? "m/d/Y" : "m/d/Y h:i:s A Z";
		if ($value)
		{
			if ($value[0] == "=")
			{
				$value = substr($value, 1); // skip over '='

				// Parse string format = [time]:[span]:['before'|'after']:[variable]
				$parts = explode(":", $value);

				$time = $parts[0]; // 0 (immediate) - 100
				$span = $parts[1]; // span = 'minutes'|'hours'|'days'|'weeks'|'months'|'years'
				$direction = $parts[2]; // 'before' or 'after'
				$variable = $parts[3];

				// Set the variable value
				if ($variable)
				{
					// Explode object references if needed
					$vparts = explode('.', $variable);
					$field = $obj->fields->getField($vparts[0]);

					if (count($vparts) == 2)
					{
						// de-reference object if field type is object
						if ($field['type'] == "object" && $field['subtype'])
						{
							$refId = $obj->getValue($vparts[0]);

							if ($refId)
							{
								$refObj = CAntObject::factory($this->dbh, $field['subtype'], $refId, $this->user);
								$refField = $refObj->fields->getField($vparts[1]);
								if ($refField && ($refField['type'] == "date" || $refField['type'] == "timestamp"))
								{
									$tmp = $refObj->getValue($vparts[1]);
									$refVal = @strtotime($tmp);
								}
							}
						}
					}
					else
					{
						// Get value from local field
						if ($field['type'] == "date" || $field['type'] == "timestamp")
						{
							$tmp = $obj->getValue($vparts[0]);
							$refVal = @strtotime($tmp);
						}
					}
				}
				else
				{
					$refVal = time(); // Today
				}

				// Calculate date if time is anything but immediate
				if ($time && $refVal && $span)
				{
					$plmin = ($direction == 'before') ? "-" : "+";

					$value = date($strToGet, strtotime("$plmin $time $span", $refVal));
				}
				else if ($refVal) // 0 = the same as the referenced value
				{
					$value = date($strToGet, $refVal);
				}
			}
		}

		return $value;
	}
}
