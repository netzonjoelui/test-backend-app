<?php
/**
 * Base class for managing objects in ANT
 *
 * Nearly everything in ANT is stored as a generic object. Objects have properties
 * defined with common types.
 *
 * If any object type specific functions are needed, /lib/objects/ is where
 * extended objects are created. For instance, /lib/objects/Comment.php is used
 * to send notifcations every time a comment is saved.
 *
 * @category  Ant
 * @package   CAntObject
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("settings/settings_functions.php");
require_once("lib/Ant.php");
require_once("lib/AntLog.php");
require_once("lib/Email.php");
require_once("lib/Stats.php");
require_once("lib/AntObjectDefLoader.php");
require_once("lib/CRecurrencePattern.php");
require_once("lib/CAntFs.awp");
require_once("lib/aereus.lib.php/CCache.php");
require_once("lib/CAntObjectIndex.php");
require_once("lib/CAntObjectView.php");
require_once("lib/CAntObjectCond.php");
require_once("lib/CAntObjectSort.php");
require_once("lib/WorkerMan.php");
require_once("objects/object_functions.php");
require_once("email/email_functions.awp");
require_once("lib/WorkFlow.php");
require_once("lib/DaclLoader.php");
require_once("lib/AntObjectLoader.php");
require_once('lib/ServiceLocatorLoader.php');

$OBJECT_FIELD_ACLS = array("View", "Edit", "Delete"); // Fields will inherit from object
define("ACT_TYPE_EMAIL", -2);

$G_OBJ_IND_EXISTS = array();

use Netric\Entity\Recurrence\RecurrencePattern;

/**
 * CAntObject class definition
 */
class CAntObject
{
	/**
	 * Handle to active account database
	 *
	 * @var CDatabase
	 */
	public $dbh;

	/**
	 * Unique id of this object - set if working with an existing object
	 *
	 * @var integer
	 */
	public $id = null;

	/**
	 * Cache used to track attempts at loading the object
	 *
	 * @var integer
	 */
	private $requestedId = null;

	/**
	 * Handle to fields definition object for this object type
	 *
	 * @var CAntObjectFields
	 */
	public $fields;

	/**
	 * Handle to object definition
	 *
	 * @var EntityDefinition
	 */
	public $def = null;

	/**
	 * Associative array of values for each field/property for this object
	 *
	 * Properties are referenced with $values['fieldname']
	 *
	 * @var array
	 */
	protected $values = array();

	/**
	 * Store foreign key values (names) for objects of type: fkey, fkey_multi, object, object_multi and alias
	 *
	 * Associateive array of arrays with the following structure:
	 * fValues [
	 * 	'idofkey' => 'text value',
	 * 	'idofkey' => 'text value'
	 * ]
	 *
	 * @var array
	 */
	protected $fValues = array();

	/**
	 * The object type title
	 *
	 * @var string
	 */
	public $title = "";

	/**
	 * Dacl class for security
	 *
	 * @var Dacl
	 */
	public $dacl = null;

	/**
	 * ANT user object
	 *
	 * If not set, then USER_SYSTEM is used
	 *
	 * @var AntUser
	 */
	public $user = null;

	/**
	 * The id of the user who owns this object
	 *
	 * @var int
	 */
	public  $owner_id;
    
    /**
     * Stores the save query string
     *
     * @var String
     */
    public $save_query;
    
    /**
     * Flag used to run object index when saving
     *
     * @var bool
     */
    public $runIndexOnSave = false;

	/**
	 * The plural version of the object type name
	 *
	 * @var string
	 */
	public $titlePl = "";

	var $fullTitle;
	var $object_type;
	var $object_table;
	var $object_type_id;
	var $processed_workflows; // Used to keep loops from happening
	var $views; // Stored views for browsing this object
	var $views_set;
	var $cache;
	var $name;
	var $nosync; // prevent circular sync
	var $skipContactOnsave; 			// prevent circular sync
	var $debug = false;					// Used to print debug info
	var $recurrencePattern = null;
    var $recurrenceException = false;   // Used to save this object only but not update recurrence pattern
	var $skipWorkflow = false; 	        // Will skip the execution of workflow

	/**
	 * The index type we are using for queries
	 *
	 * @var string
	 */
	public $indexType = "entityquery";

	/**
	 * If we are using an index other than 'db' then this tag sets it to be only for full-text
	 *
	 * @var bool
	 */
	public $indexFullTextOnly = false;

	/**
	 * The index class
	 *
	 * @var CAntObjectIndex
	 */
	public $index = null;

	/**
	 * Flag to indicate if all changes should be committed immediately
	 *
	 * @var bool
	 */
	public $indexCommit = true;

	/**
	 * Array used to cache moved_to references to we never have a circular reference
	 *
	 * @var int[]
	 */
	private $movedToRef = array();

	/**
	 * Ant object
	 *
	 * @var Ant
	 */
	private $ant = null;

	/**
	 * Flag to skip object sync logging
	 *
	 * @var bool
	 */
	public $skipObjectSyncStat = false;

	/**
	 * Flag to skip object sync logging for one collection
	 *
	 * @var int
	 */
	public $skipObjectSyncStatCol = null;
	
	/**
	 * Array that stores a record of all changes while object is in memory
	 *
	 * @var array
	 */
	public $changelog = array();

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh an active handle to account database
	 * @param string $obj_type the name of the object type this will represent
	 * @param int $id optional unique id of object instance that hsould be loaded
	 * @param AntUser $user option user object. If not specified user will be system
	 * @param array $fields_def optional array of field definitions. If set then no need to reload definition
	 * @param int $comprev optionally pass to determine if cached data is outdated
	 * @param array $cachedata can be passed to have objet load cached data rather than pulling from local store
	 */
	public function __construct($dbh, $obj_type, $id=null, $user=null, $fields_def=null, $comprev=null, $cachedata=null)
	{
		global $OBJECT_FIELD_ACLS, $USER, $G_CACHE_ANTOBJTYPES;
		if (!$user && $USER)
			$user = $USER;
			
		$this->object_type = $obj_type;
		$this->dbh = $dbh;
		$this->processed_workflows = array();
		$this->views = array();
		$this->views_set = false;
		$this->cache = CCache::getInstance();
		$this->dacl = null;
		$this->daclIsUnique = false; // see if this dacl is unique to this object. If false, then dacl is for all obj_types
		$this->user = $user;
		$this->owner_id = null;
		$this->label = "";	// name of instance of object (like first_name + " " + last_name)
		$this->form_layout_xml = null;
		$this->skipContactOnsave = false;
		$this->fSystem = false;
		if (defined('ANT_INDEX_TYPE'))
			$this->indexType = ANT_INDEX_TYPE;

		// @depricated We are now using $this->def
		// This is still using the old object defs, but
		// we will eventually want to move this to the new EntityDefinitionLoader
		$this->fields = AntObjectDefLoader::getInstance($dbh)->getDef($obj_type);

		// Load new EntityDefinition
		$sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$this->def = $sl->get("EntityDefinitionLoader")->get($obj_type);
		$this->object_type_id = $this->def->getId();
		$this->title = $this->def->title;
		$this->fSystem = $this->def->system;
		
		// Set plural title
		if ("y" == substr($this->title, strlen($this->title)-1, 1))
		{
			$this->titlePl = substr($this->title, 0, strlen($this->title)-1)."ies";
		}
		else if ("s" != substr($this->title, strlen($this->title)-1, 1))
			$this->titlePl = $this->title."s";
		else
			$this->titlePl = $this->title;

		// Get full name
		if (strpos($this->object_type, ".") !== false)
		{
			$parts = explode(".", $this->object_type);
			if (is_numeric($parts[0]))
			{
				$result = $dbh->Query("select name from dc_databases where id='".$parts[0]."'");
				if ($dbh->GetNumberRows($result))
					$this->fullTitle = $dbh->GetValue($result, 0, "name").".".$parts[1];
			}
			else
				$this->fullTitle = $parts[0].".".$this->title;
		}
		else
			$this->fullTitle = $this->title;

		//$this->object_table = $this->fields->object_table;
		//$this->fields->user = $this->user;
		$this->object_table = $this->def->getTable();

		if ((!$this->form_layout_xml || $this->form_layout_xml=='*') && $this->def->getForm("default"))
			$this->form_layout_xml = $this->def->getForm("default");

		// Convert uname to proper id        
		if ($id)
		{
            $pos = strpos($id, "uname:");
			if ($pos !== false)
            {                
                $id = $this->openByName($id, false); // Get id but do not load
            }
		}
		$this->id = $id;

		// Get default generic dacl for all objects of this type, using the loader will cache it
		// if the object has a specific DACL then it will be pulled in the load function or when
		// a variable is set to a parent object to inherit from
		$this->dacl = DaclLoader::getInstance($this->dbh)->byName("/objects/$obj_type");
		if (!$this->dacl->id)
		{
			// Create if it does not exist
			$this->dacl->grantGroupAccess(GROUP_ADMINISTRATORS);
			$this->dacl->grantUserAccess(GROUP_CREATOROWNER);
			$this->dacl->save();
		}

		// Check option that allows us to use a non-db external index only for full-text and the db for everything else
		if (AntConfig::getInstance()->object_index["fulltext_only"])
			$this->indexFullTextOnly = true;

		if (is_numeric($id))
		{
			$this->load($comprev, $cachedata);
		}
	}

	/**
	 * Class destructor
	 */
	public function __destruct()
	{
	}

	/**
	 * Load subclasses depending on type
	 *
	 * @param CDatabase $dbh an active handle to account database
	 * @param string $obj_type the name of the object type this will represent
	 * @param int $id optional unique id of object instance that hsould be loaded
	 * @param AntUser $user option user object. If not specified user will be system
	 * @param array $fields_def optional array of field definitions. If set then no need to reload definition
	 * @param int $comprev optionally pass to determine if cached data is outdated
	 * @param array $cachedata can be passed to have objet load cached data rather than pulling from local store
	 * @return true on success, false on failure
	 */
	static public function factory(CDatabase $dbh, $obj_type, $id=null, $user=null, $fields_def=null, $comprev=null, $cachedata=null)
	{
		// First convert object name to file name - camelCase
		$fname = ucfirst($obj_type);
		if (strpos($obj_type, "_") !== false)
		{
			$parts = explode("_", $fname);
			$fname = "";
			foreach ($parts as $word)
				$fname .= ucfirst($word);
		}

		// Reserved name for objects
		if ($obj_type == "object")
			throw new \Exception("object is a reserved entity name");


		// Dynamically load subclass if it exists
		if (file_exists(dirname(__FILE__)."/Object/" . $fname . ".php"))
		{
			$className = "CAntObject_" . $fname;
			if (!class_exists($className, false))
				require_once("lib/Object/" . $fname . ".php");

			$obj = new $className($dbh, null, $user, $fields_def);
		}
		else
		{
			$obj = new CAntObject($dbh, $obj_type, null, $user, $fields_def);
		}


		if ($id)
		{
			// Convert uname to proper id        
			if (strpos($id, "uname:") !== false)
			{                
				$obj->openByName($id);
			}
			else
			{
				$obj->id = $id;
				$obj->load($comprev, $cachedata);
			}
		}

		return $obj;
	}

	/**
	 * Load data into properties from local store (cache=>database)
	 *
	 * @param int $comprev optionally compare with cache to see if still valid
	 * @param array $cachedata can pass actual field properties bypassing cache and database pull
	 * @return true on success, false on failure
	 */
	public function load($comprev=null, $cachedata=null)
	{
		$dbh = $this->dbh;
		$start = microtime(true); // Time this operation

		if (!$this->id)
			return false;
        
        $objdata = null;

		if ($cachedata!=null)
		{
			$objdata = $cachedata;
				
			if ($objdata)
				Stats::increment("antobject.cache.indexhit");
			else
				Stats::increment("antobject.cache.indexmiss");
		}
		/*
		else
		{
			$objdata = $this->cache->get($this->dbh->dbname."/object/".$this->object_type."/".$this->id);

			if ($objdata)
				Stats::increment("antobject.cache.cchit");
			else
				Stats::increment("antobject.cache.ccmiss");
		}
		 */

		// Log stats
		if (!$objdata)
			Stats::increment("antobject.cache.miss");
		else
			Stats::increment("antobject.cache.hit");

		// Not cached, pull data from the database directly
		if (!$objdata)
		{
			// Cache originally requested id
			$this->requestedId = $this->id;
			$objdata = $this->getDataFromDb($this->id);
		}


		if (!is_array($objdata) || count($objdata) == 0)
			return false;

		// Set local values
		// ----------------------------------------------------
		$all_fields = $this->def->getFields();
		foreach ($all_fields as $fname=>$fdef)
		{
            $varval = "";
            if(isset($objdata[$fname]))
			    $varval = $objdata[$fname];

			// Cleanup old numeric values (these types are not used any more in objects)
			if ($varval && ($fdef->subtype=="real"  || $fdef->subtype=="double precision"  || $fdef->subtype=="float"))
				$varval = round($varval, 2);

			if ($fdef->type=="fkey_multi" || $fdef->type=="object_multi")
			{
				if (is_array($varval) && count($varval))
				{
					for ($i = 0; $i < count($varval); $i++)
					{
						if ($varval[$i])
							$this->setMValue($fname, $varval[$i]);
					}
				}
			}
			else
			{
				$this->setValue($fname, $varval, false);
			}

			// Set foreign value(s)
			if (isset($objdata[$fname."_fval"]))
			{
                if (is_array($objdata[$fname."_fval"]))
                        $this->fValues[$fname] = $objdata[$fname."_fval"]; // already decoded
                else 
                    $this->fValues[$fname] = $this->decodeFval($objdata[$fname."_fval"]); // Put into an associative array
			}
		}

		// Address fields with type=auto
		foreach ($all_fields as $fname=>$fdef)
		{
			if ($fdef->type == "auto")
			{
				$val = $fdef->getDefault($this->values[$fname], 'null', $this, $this->user);
				$this->setValue($fname, $val);
			}
		}

		// Set local properties from pulled values
		if ($this->def->getField("owner_id")!=null)
			$this->owner_id = $this->getValue("owner_id");
		else if ($this->def->getField("user_id")!=null)
			$this->owner_id = $this->getValue("user_id");

		if (!is_numeric($this->owner_id))
			$this->owner_id = null;

		if ($this->def->getField("revision")!=null)
			$this->revision = $this->getValue("revision");

		if ($this->def->getFields("dacl"))
		{
			$daclDat = $this->getValue("dacl");
			if ($daclDat)
			{
				$daclDat = json_decode($daclDat, true);
				$this->dacl = new Dacl($this->dbh, "/objects/" . $this->object_type . "/".$this->id);
				$this->dacl->loadByData($daclDat);

				if ($daclDat['id'])
					$this->daclIsUnique = true;
			}
		}
		else
		{
			// Check for dacl unique to this object
			$uni_dacl = $uni_dacl = Dacl::exists("/objects/" . $this->object_type . "/".$this->id, $this->dbh);
			if ($uni_dacl)
			{
				$this->daclIsUnique = true;
				$this->dacl = new Dacl($this->dbh, "/objects/" . $this->object_type . "/".$this->id);
			}
		}

		// Keep track of how long it takes to open objects
		Stats::timing("antobject.loadtime", microtime(true) - $start);

		// Fire loaded event
		$this->loaded();
	}

	/**
	 * Load an object by name or path
	 *
	 * @param string $path
	 * @return CAntObject|null Object if path is found, null if not found
	 */
	public function loadByPath($path)
	{
		$obj = null; // return value
		
		// If the object does not not have a parent then just return the name
		if (!$this->def->parentField)
			return $this->loadByName($path);

		// Loop through names to get to the last entry
		$names = explode("/", $path);
		$lastParent = null;
		foreach ($names as $oname)
		{
			if ($oname) // skip over first which is root and will be empty string
			{
				$obj = $this->loadByName($oname, $lastParent);
				if ($obj && $obj->id)
				{
					$lastParent = $obj->id;
				}
				else
				{
					return null; // not found
				}
			}
		}

		return $obj;
	}

	/**
	 * Get the id of an object by name
	 *
	 * @param string $name The name of the object - should be unique or just first found instance is returned
	 * @param int $parentId If hierarchial then id of the parent for this specific name
	 * @return CAntObject|null Object if name is found, null if not found
	 */
	public function loadByName($name, $parent=null)
	{
		$olist = new CAntObjectList($this->dbh, $this->object_type, $this->user);
		if ($parent && $this->def->parentField)
			$olist->addCondition("and", $this->def->parentField, "is_equal", $parent);
		else if ($this->def->parentField)
			$olist->addCondition("and", $this->def->parentField, "is_equal", "");
		$olist->addCondition("and", $this->def->listTitle, "is_equal", $name);
		$olist->getObjects(0, 1);
		if ($olist->getNumObjects())
		{
			return $olist->getObject(0);
		}

		return null;
	}

	/**
	 * Get object data from the datastore
	 */
	public function getDataFromDb()
	{
		$sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$loader = $sl->get("EntityLoader");
		$entity = $loader->get($this->object_type, $this->id);

		// Check to see if object not found
		if (!$entity)
		{
			$this->id = "";
			return false;
		}

		// Check to see if object moved
		if ($entity->getId() != $this->id)
		{
			// Check if entity actually not found then delete from index before setting the id to null
			$this->removeIndex();

			// Set id to whatever loaded entity is
			$this->id = $entity->getId();
		}

		$data = array();

		$all_fields = $entity->getDefinition()->getFields();
		foreach ($all_fields as $fname=>$fdef)
		{
			$data[$fname] = "";

			switch ($fdef->type)
			{
			case 'date':
				if ($entity->getValue($fname))
					$data[$fname] = date("Y-m-d", $entity->getValue($fname));
				break;
			case 'timestamp':
				if ($entity->getValue($fname))
					$data[$fname] = date("Y-m-d h:i:s A e", $entity->getValue($fname));
				break;
			case 'bool':
				$data[$fname] = ($entity->getValue($fname)) ? 't': 'f';
				break;
			default:
				$data[$fname] = $entity->getValue($fname);
				break;
			}

			// Get fkey vals
			if ($entity->getValueNames($fname))
				$data[$fname . "_fval"] = json_encode($entity->getValueNames($fname));
		}

		// Update cache
		//$this->cache->set($this->dbh->dbname."/object/".$this->object_type."/".$this->id, $data);

		return $data;

		/*
		$dbh = $this->dbh;
		$query = "select * from ".$this->getObjectTable()." where id='".$this->id."'";
		$result = $dbh->Query($query);
		if (!$this->dbh->GetNumberRows($result))
		{
			// Object id not found, see if we can find the object in the moved index (maybe it was merged)
			if ($this->checkMoved())
			{
				// The id we were looking for did move, checkMoved should update
				// $this->id so now we can try loading again
				return $this->load();
			}
			else
			{
				// Object id does not exist - clear results and set id to null
				$this->id = null;
				return null;
			}
		}

		$ret = $dbh->GetRow($result, 0);

		// Load data for foreign keys
		$all_fields = $this->def->getFields();
		foreach ($all_fields as $fname=>$fdef)
		{
			// Populate values and foreign values for foreign entries if not set
			if ($fdef->type == "fkey" || $fdef->type == "object" || $fdef->type == "fkey_multi" || $fdef->type == "object_multi")
			{
				$mvals = null;
                
				if (!$ret[$fname . "_fval"] || ($ret[$fname . "_fval"]=='[]' && $ret[$fname]!='[]' && $ret[$fname]!=''))
				{
					$mvals = $this->getForeignKeyDataFromDb($fname, $ret[$fname]);
					$ret[$fname . "_fval"] = ($mvals) ? json_encode($mvals) : "";
				}

				// set values of fkey_multi and object_multi fields as array of id(s)
				if ($fdef->type == "fkey_multi" || $fdef->type == "object_multi")
				{
					//echo "<pre>$fname before ".var_export($ret[$fname], true)."</pre>";
					if ($ret[$fname])
					{
						$parts = $this->decodeFval($ret[$fname]);
						if ($parts !== false)
						{
							$ret[$fname] = $parts;
						}
					}
					//echo "<pre>$fname after ".var_export($ret[$fname], true)."</pre>";

					// Was not set in the column, try reading from mvals list that was generated above
					if (!$ret[$fname])
					{
						if (!$mvals && $ret[$fname . "_fval"])
							$mvals = $this->decodeFval($ret[$fname . "_fval"]);

						if ($mvals)
						{
							foreach ($mvals as $id=>$mval)
								$ret[$fname][] = $id;
						}
					}

					//echo "<pre>$fname finally ".var_export($ret[$fname], true)."</pre>";
				}

				// Get object with no subtype - we may want to store this locally eventually
				// so check to see if the data is not already defined 
				if (!$ret[$fname] && $fdef->type == "object" && !$fdef->subtype)
				{
					if (!$mvals && $ret[$fname . "_fval"])
						$mvals = $this->decodeFval($ret[$fname . "_fval"]);

					if ($mvals)
					{
						foreach ($mvals as $id=>$mval)
							$ret[$fname] = $id; // There is only one value but it is assoc
					}
				}
			}
		}

		// Update cache
		$this->cache->set($this->dbh->dbname."/object/".$this->object_type."/".$this->id, $ret);


		return $ret;
		 */
	}

	/**
	 * Load foreign values from the database
	 *
	 * @param string $fname The name of the field to query values for
	 * @param string $value Raw value from field if exists
	 * @return array('keyid'=>'value/name')
	 */
	public function getForeignKeyDataFromDb($fname, $value)
	{
		$dbh = $this->dbh;
		$fdef = $this->def->getField($fname);
		$ret = array();

		if ($fdef->type == "fkey" && $value)
		{
			$query = "SELECT " . $fdef->fkeyTable['key'] ." as id, " . $fdef->fkeyTable['title'] . " as name ";
			$query .= "FROM " . $fdef->subtype . " ";
			$query .= "WHERE " . $fdef->fkeyTable['key'] . "='$value'";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				$ret[(string)$row['id']] = $row['name'];
			}

			// The foreign object is no longer in the foreign table, just use id
			if (!$num)
				$ret[$value] = $value;
		}

		if ($fdef->type == "fkey_multi")
		{
			$datTbl = $fdef->subtype;
			$memTbl = $fdef->fkeyTable['ref_table']['table'];
			$query = "SELECT $datTbl." . $fdef->fkeyTable['key'] . " as id, $datTbl." . $fdef->fkeyTable['title'] . " as name ";
			$query .= "FROM $datTbl, $memTbl ";
			$query .= "WHERE $datTbl." . $fdef->fkeyTable['key'] . "=$memTbl." . $fdef->fkeyTable['ref_table']['ref'] . " AND
						".$fdef->fkeyTable['ref_table']["this"]."='".$this->id."'";
			$result = $dbh->Query($query);

			for ($i = 0; $i < $dbh->GetNumberRows($result); $i++)
			{
				$row = $dbh->GetRow($result, $i);

				$ret[(string)$row['id']] = $row['name'];
			}
		}

		if ($fdef->type == "object" && $fdef->subtype)
		{
			if ($value)
			{
				$obj = CAntObject::factory($dbh, $fdef->subtype);
				$ret[(string)$value] = $obj->getName($value);

			}
		}
		else if (($fdef->type == "object" && !$fdef->subtype) || $fdef->type == "object_multi")
		{
			$query = "select assoc_type_id, assoc_object_id
							 from object_associations
							 where field_id='".$fdef->id."' and type_id='".$this->object_type_id."' 
							 and object_id='".$this->id."' LIMIT 1000";
			$result = $dbh->Query($query);
			for ($i = 0; $i < $dbh->GetNumberRows($result); $i++)
			{
				$row = $dbh->GetRow($result, $i);

				$oname = "";

				// If subtype is set in the field, then only the id of the object is stored
				if ($fdef->subtype)
				{
					$oname = $fdef->subtype;
					$idval = (string)$row['assoc_object_id'];
				}
				else
				{
					$oname = objGetNameFromId($dbh, $row['assoc_type_id']);
					$idval = $oname.":".$row["assoc_object_id"];
				}

				if ($oname)
				{
					//$ret[$idval] = objGetName($dbh, $oname, $row['assoc_object_id']);
					$objDef = CAntObject::factory($dbh, $oname);
					$ret[(string)$idval] = $objDef->getName($row['assoc_object_id']);
				}
			}
		}

		return $ret;
	}

	/**
	 * Get the object table for this object
	 *
	 * @param bool $getPartition If this table is partitioned, get the current partition where this object resides
	 * @param bool $showDeleted If set query deleted objects
	 * @return string The table name to work with
	 */
	public function getObjectTable($getPartition = false, $showDeleted=false)
	{
		$objTable = $this->def->getTable();

		if ($getPartition)
		{
			if (!$this->def->isCustomTable() && ($this->getValue("f_deleted") == 't' || $showDeleted))
				$objTable .= "_del";
			else if (!$this->def->isCustomTable())
				$objTable .= "_act";
		}

		return $objTable;
	}

	/**
	 * Get the indexer and set if not already set
	 *
	 * @param string $type the name of the indexer (db, solr, elastic...)
	 * @return CAntObjectIndex
	 */
	public function getIndex($type=null)
	{
		if ($this->index != null && ($type==null || $this->indexType == $type))
			 return $this->index;
		else
			return $this->setIndex($type);
	}

	/**
	 * Set the index to be used with this object
	 *
	 * @param string $type The type can be manually set by passing the name as a param. Otherwise system setting will be used.
	 */
	public function setIndex($type=null)
	{
		if ($type)
			$this->indexType = $type;

		switch ($this->indexType)
		{
		case 'elastic':
			$this->index = new CAntObjectIndexElastic($this->dbh, $this);
			break;
		case 'solr':
			$this->index = new CAntObjectIndexSolr($this->dbh, $this);
			break;
        case 'entityquery':
		case 'db':
		default:
            // EntityQuery has now replaced the default db index because
            // we are moving everything over to lib\Netric\* v3 core.
            // - joe
			require_once("lib/CAntObjectIndex.php");
			require_once("lib/obj_indexers/entityquery.php");
            $this->index = new CAntObjectIndexEq($this->dbh, $this);
			//$this->index = new CAntObjectIndexDb($this->dbh, $this);
			break;
		}

		return $this->index;
	}

	/**************************************************************************
	* Function: 	getIndexAvailable
	*
	* Purpose:		Find out if an index type is available
	*
	* Params:		(string) $type = the name of the indexer (db, elatic....)
	**************************************************************************/
	function getIndexAvailable($type)
	{
		return index_is_available($type);
	}

	/**
	 * Open an object by a unique name
	 *
	 * @param string $uname The unique name to open
	 * @param bool $load If true then call $this->load(id) if found, otherwise return id
	 * @return int $id The unique id of the object mapped to the unique name
	 */
	public function openByName($uname, $load=true)
	{
		$dbh = $this->dbh;

        // need to properly explode the $uname
        $pos = strpos($uname, "uname:");
        if ($pos !== false) 
			$uname = substr($uname, strlen("uname:")); // skip over uname

		$olist = new CAntObjectList($this->dbh, $this->object_type, $this->user);

		if (!$uname)
			return false;

		// Check if the uname is namespace qualified
		if ($this->def->unameSettings)
		{
			$uriParts = explode(":", $this->def->unameSettings);
			$unameParts = explode(":", $uname);

			// Last entry will be the uname
			$olist->addCondition("and", "uname", "is_equal", $unameParts[count($unameParts)-1]);

			// Now add namespace if set
			if (count($uriParts) > 1 && count($uriParts) == count($unameParts))
			{
				// Loop through all but last entry (the uname entry) and add conditions
				for ($i = 0; $i < (count($uriParts) - 1); $i++)
				{
					$olist->addCondition("and", $uriParts[$i], "is_equal", $unameParts[$i]);
				}
			}
		}
		else
		{
			$olist->addCondition("and", "uname", "is_equal", $uname);
		}

		$olist->getObjects(0, 1);
		if ($olist->getNumObjects() > 0)
		{
			$omin = $olist->getObjectMin(0);

			if ($omin['id'] && $load)
				$this->openById($omin['id']);

			return $omin['id'];
		}

		return false;
	}

    
	/**
	 * Open an object by the unique id
	 *
	 * @param int $id The unique id of the object to load
	 * @return bool true if the object exists, false if it does not
	 */
    public function openById($id, $load=true)
    {
        $dbh = $this->dbh;
        
        if($id > 0)
        {
            $this->id  = $id;
            if ($load)
                $this->load();
                
            return true;
        }
        else
            return false;
    }

	/*
	 * Save data for this object
	 *
	 * @param bool $logact	If true an action will be logged for this event. Used to make multiple saves clean in activity log.
	 */
	public function save($logact=true)
	{
		$dbh = $this->dbh;
		$all_fields = $this->def->getFields();

		// Derrived classes can define before save event
		$this->beforesaved();

		/*
		if ($this->def->getField("revision"))
		{
			$revision = $this->getValue("revision");
			$revision = (is_numeric($revision)) ? $revision+1 : 1;
			$this->setValue("revision", $revision);
			$this->revision = $revision;
		}
		 */

		// Make sure that a parent field is not referening itself causing an endless loop
		if ($this->def->parentField)
		{
			$pfield = $this->def->getField($this->def->parentField);
			if ($pfield->subtype == $this->object_type && $this->getValue($this->def->parentField) == $this->id)
				$this->setValue($this->def->parentField, ""); // set to null
		}

		// Check and set security
		if ($this->user)
		{
			$fdef = $this->def->getField("owner_id");
			if ($fdef)
			{
				$this->owner_id = $this->getValue("owner_id");
				if (!$this->owner_id)
				{
					$this->owner_id = $fdef->getDefault("", 'null', $this, $this->user);
				}
			}

			if (!$this->owner_id)
			{
				$fdef = $this->def->getField("user_id");
				if ($fdef)
				{
					$this->owner_id = $this->getValue("user_id");
					if (!$this->owner_id)
					{
						$this->owner_id = $fdef->getDefault("", 'null', $this, $this->user);
					}
				}
			}

			if (!$this->dacl->checkAccess($this->user, "Edit", ($this->user->id==$this->owner_id)?true:false))
			{
				return false;
			}
			else if ($this->daclIsUnique)
			{
				// Save cached version of the dacl
				$this->setValue("dacl", $this->dacl->stringifyJson());
			}
		}

		// Deal with unique names - do not create unique names for activities
		if (!$this->getValue("uname") && $this->object_type!="activity")
		{
			$this->setValue("uname", $this->getUniqueName());
		}
		else if ($this->getValue("uname") && $this->object_type!="activity") // safe guard against duplicate unames - very bad!
		{
			$this->verifyUniqueName($this->getValue("uname"), true);
		}

		/*
		 * FIXME: This is now handled in the Entity - we need to eventually delete - Sky
		// Get recurrence pattern ID
		if (!$this->recurrenceException && $this->def->recurRules!=null)
		{
			// If this event had recur_id saved in field, then load, otherwise leave null
			if ($this->recurrencePattern == null)
			{
				$rid = $this->getValue($this->def->recurRules['field_recur_id']);
				if ($rid)
					$this->getRecurrencePattern($rid);
			}

			if ($this->recurrencePattern != null)
			{
				if (!isset($rid))
					$rid = $this->recurrencePattern->getNextId();

				if (!$this->getValue($this->def->recurRules['field_recur_id']))
					$this->setValue($this->def->recurRules['field_recur_id'], $rid);
			}
		}

		// Reload fvals cache
		// This will eventually move into the datamapper but for now just handle it here
		$this->reloadFVals();
		*/

		// Save values to the database
		// ------------------------------------------------------------------
		$sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$loader = $sl->get("EntityLoader");
		
		if ($this->id)
			$entity = $loader->get($this->object_type, $this->id);
		else
			$entity = $loader->create($this->object_type);

		// Put data in this object into new entity
		$fields = $this->def->getFields();
		foreach ($fields as $fname=>$fdef)
		{
			$val = $this->getValue($fname);
			$valName = $this->getFVals($fname);;

			switch ($fdef->type)
			{
			case 'bool':
				$val = ($val == 't') ? true : false;
				break;
			case 'date':
				if ($val == "now")
					$val = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
				else if ($val)
					$val = strtotime($val);
				break;
			case 'timestamp':
				if ($val == "now")
					$val = time();
				else if ($val)
					$val = strtotime($val);
				break;
			}

			$entity->setValue($fname, $val, $valName);
		}

        /*
         * FIXME: This is now managed in Entity - can eventually delete - Sky
         *
		// Set all null defaults
		$entity->setFieldsDefault("null", $this->user);

		// Set all update defaults
		$entity->setFieldsDefault("update", $this->user);

		// Set all create defaults if this is a new object
		if (!$entity->getId())
			$entity->setFieldsDefault("create", $this->user);
        */

		// Set the recurrence pattern
		if ($this->recurrencePattern)
		{
			// Build the recurrence data
			$recurrenceData = array (
				'id' => $this->recurrencePattern->id,
				'recur_type' => $this->recurrencePattern->type,
				'interval' => $this->recurrencePattern->interval,
				'instance' => $this->recurrencePattern->instance,
				'day_of_month' => $this->recurrencePattern->dayOfMonth,
				'month_of_year' => $this->recurrencePattern->monthOfYear,
				'day_of_week_mask' => $this->recurrencePattern->dayOfWeekMask,
				'date_start' => $this->recurrencePattern->dateStart,
				'date_end' => $this->recurrencePattern->dateEnd,
				'date_processed_to' => $this->recurrencePattern->dateProcessedTo,
				'obj_type' => $this->recurrencePattern->object_type,
				'ep_locked' => $this->recurrencePattern->epLocked,
				'field_date_start' => $this->recurrencePattern->fieldDateStart,
				'field_time_start' => $this->recurrencePattern->fieldTimeStart,
				'field_date_end' => $this->recurrencePattern->fieldDateEnd,
				'field_time_end' => $this->recurrencePattern->fieldTimeEnd
			);

			// Create the instance of recurrence pattern
			$recurrencePattern = new RecurrencePattern();

			// Set the data of the recurrence pattern
			$recurrencePattern->fromArray($recurrenceData);

			// Update the entity that we have a recurrence pattern
			$entity->setRecurrencePattern($recurrencePattern);
		}

		$dm = $sl->get("Entity_DataMapper");
		$dm->save($entity, $this->user);

		$performed = (!$this->id) ? "create" : "update";

		$this->id = $entity->getId();

		// Handle autocreate folders - only has to fire the very first time
		if ($this->id)
		{
			$changed = false; // Flag to reduce unnecessary saves
			foreach ($fields as $fname=>$fdef)
			{
				if ($fdef->type=="object" && $fdef->subtype=="folder" 
					&& $fdef->autocreate && $fdef->autocreatebase && $fdef->autocreatename 
					&& !$this->getValue($fname) && $this->getValue($fdef->autocreatename))
				{
					$antfs = new AntFs($this->dbh, $this->user);
					$fldr = $antfs->openFolder($fdef->autocreatebase."/".$this->getValue($fdef->autocreatename), true);
					$varval = $fldr->id;
					if ($fldr->id)
					{
						$this->setValue($fname, $fldr->id);
						// Save settings to entity datamapper
						$entity->setValue($fname, $fldr->id, $fldr->name);
						$changed = true;
					}
				}
			}

			if ($changed)
				$dm->save($entity, $this->user);
		}

		/*
		 * FIXME: This is now managed in Entity - can eventually delete - Sky
		 *
		$this->revision = $entity->getValue("revision");
		$this->setValue("revision", $entity->getValue("revision"));
		*/

		/*
		$objTable = $this->object_table;

		if (!$this->def->isCustomTable() && $this->getValue("f_deleted") == 't')
            $objTable .= "_del";
		else if (!$this->def->isCustomTable())
			$objTable .= "_act";

		//AntLog::getInstance()->info("In CAntObjects value before saveColVasl: " . $this->getValue("status_id"));

		$data = $this->saveColVals();
        
        // Try to manipulate data to correctly build the sql statement based on custom table definitions
		if (!$this->def->isCustomTable() && !$data["f_deleted"])
			$data["f_deleted"] = $dbh->EscapeNumber($this->object_type_id);

		//AntLog::getInstance()->info("In CAntObjects saving: " . var_export($data, true));

		// If we are using a custom table or the deleted status has not changed on a generic object table then update row
		if ($this->id && ($this->def->isCustomTable() || (!$this->fieldValueChanged("f_deleted") && !$this->def->isCustomTable())))
		{
			$query = "update ".$objTable." set ";
			$update_fields = "";
			foreach ($data as $colname=>$colval)
			{
				if ($colname == "id") // skip over id
					continue;

				if ($update_fields) $update_fields .= ", ";
				$update_fields .= '"'.$colname.'"' . "=" . $colval; // val is already escaped
			}
			$query .= $update_fields." where id='".$this->id."'";
            $this->save_query = $query;
			$res = $dbh->Query($query);

			$performed = "update";
		}
		else
		{
			$copyid = false; // Use this to preserve object id if moving between active or archived object tables (not custom)
			
			// Clean out old record if it exists in a different partition
			if ($this->id && !$this->def->isCustomTable())
			{
				$dbh->Query("DELETE FROM $this->object_table WHERE id='" . $this->id . "'");
				$copyid = true;
			}

			$cols = "";
			$vals = "";

			foreach ($data as $colname=>$colval)
			{
				if ($colname == "id" && !$copyid) // skip over id
					continue;

				if ($cols) $cols .= ", ";
				if ($vals) $vals .= ", ";
				$cols .= $colname;
				$vals .= $colval; // val is already escaped
			}
			$query = "insert into ".$objTable."($cols) VALUES($vals);";

			$seqName = ($this->def->isCustomTable()) ? $objTable . "_id_seq" : "objects_id_seq";
			if ($this->id)
				$query .= "select '".$this->id."' as id;";
			else 
				$query .= "select currval('$seqName') as id;";
			//echo $query;
            $this->save_query = $query;
			$result = $dbh->Query($query);

			// Set event
			$performed = (!$this->id) ? "create" : "update";

			// If this was a new object the set the id, otherwise leave as is
			if ($dbh->GetNumberRows($result) && !$this->id)
			{
				$this->id = $dbh->GetValue($result, 0, "id");
				$this->setValue("id", $this->id);
			}
		}
		 */

		// handle fkey_multi && Auto
		if ($this->id)
		{
			/*
			// Handle autocreate folders - only has to fire the very first time
			foreach ($all_fields as $fname=>$fdef)
			{
				if ($fdef->type=="object" && $fdef->subtype=="folder" 
					&& $fdef->autocreate && $fdef->autocreatebase && $fdef->autocreatename 
					&& !$this->getValue($fname) && $this->getValue($fdef->autocreatename))
				{
					$antfs = new AntFs($dbh, $this->user);
					$fldr = $antfs->openFolder($def['autocreatebase']."/".$this->getValue($fdef->autocreatename), true);
					$varval = $fldr->id;
					if ($fldr->id)
					{
						$this->setValue($fname, $fldr->id);
						$dbh->Query("update ".$this->object_table." set $fname='".$fldr->id."' where id='".$this->id."'");
					}
				}
			}

			// Handle updating reference membership if needed
			foreach ($all_fields as $fname=>$fdef)
			{
				if ($fdef->type == "fkey_multi")
				{
					// Cleanup
					$queryStr = "delete from ".$fdef->fkeyTable['ref_table']['table']."
								 where ".$fdef->fkeyTable['ref_table']["this"]."='".$this->id."'";
					if ($fdef->subtype == "object_groupings") // object_type_id is needed for generic groupings
						$queryStr .= " and object_type_id='".$this->object_type_id."' and field_id='".$fdef->id."'";
					$dbh->Query($queryStr);

					// Populate foreign table
					$mvalues = $this->getValue($fname);
					if (is_array($mvalues))
					{
						foreach ($mvalues as $val)
						{
							if ($val)
							{
								$queryStr = "INSERT INTO ".$fdef->fkeyTable['ref_table']['table']."
									(".$fdef->fkeyTable['ref_table']['ref'].", ".$fdef->fkeyTable['ref_table']["this"];
								if ($fdef->subtype == "object_groupings") // object_type_id is needed for generic groupings
									$queryStr .= ", object_type_id, field_id";
								$queryStr .= ") VALUES('".$val."', '".$this->id."'";
								if ($fdef->subtype == "object_groupings") // object_type_id is needed for generic groupings
									$queryStr .= ", '".$this->object_type_id."', '".$fdef->id."'";
								$queryStr .= ");";

								$dbh->Query($queryStr);
							}
						}
					}
				}

				// Handle object associations
				if ($fdef->type == "object_multi" || $fdef->type == "object")
				{
					// Cleanup
					$dbh->Query("delete from object_associations
								 where object_id='".$this->id."' and 
								 type_id='".$this->object_type_id."' 
								 and field_id='".$fdef->id."'");

					// Set values
					$mvalues = $this->getValue($fname);
					if (is_array($mvalues))
					{
						foreach ($mvalues as $val)
						{
							$otid = -1;
							if ($fdef->subtype)
							{
								$subtype = $fdef->subtype;
								$objid = $val;
							}
							else
							{
								$parts = explode(":", $val);
								if (count($parts)==2)
								{
									$subtype = $parts[0];
									$objid = $parts[1];
								}
							}

							$otid = objGetAttribFromName($dbh, $subtype, "id");
							if ($otid && $objid)
							{
								$dbh->Query("insert into object_associations(object_id, type_id, assoc_type_id, assoc_object_id, field_id)
											 values('".$this->id."', '".$this->object_type_id."', '".$otid."', '".$objid."', '".$fdef->id."');");
							}
						}
					}
					else if ($mvalues)
					{
						if ($fdef->subtype)
						{
							$otid = objGetAttribFromName($dbh, $fdef->subtype, "id");
							if ($otid)
							{
								$dbh->Query("insert into object_associations(object_id, type_id, assoc_type_id, assoc_object_id, field_id)
											 values('".$this->id."', '".$this->object_type_id."', '".$otid."', '".$mvalues."', '".$fdef->id."');");
							}
						}
						else
						{
							$parts = explode(":", $mvalues);
							if (count($parts)==2)
							{
								$otid = objGetAttribFromName($dbh, $parts[0], "id");
								if ($otid && $parts[1])
								{
									$dbh->Query("insert into object_associations(object_id, type_id, assoc_type_id, assoc_object_id, field_id)
												 values('".$this->id."', '".$this->object_type_id."', '".$otid."', '".$parts[1]."', '".$fdef->id."');");
								}
							}
						}
					}
				}
			}
		 	*/

			// Call saved for derrived class callbacks
			$this->saved();

			// Clear object values cache - will not clear definition
			$this->clearCache();

			// Save revision history - now handled in the datamapper
			//$this->saveRevision();

			/*
			 * FIXME: This is now managed in Entity - can eventually delete - Sky
			 *
			// Set and save recurrence pattern
			if (!$this->recurrenceException && $this->def->recurRules!=null && $this->recurrencePattern!=null)
				$rid = $this->recurrencePattern->saveFromObj($this);
			*/

			// Load inserted data for defaults
			if ($performed == "create")
				$this->load();

			// Index this object
			//$this->index();

			/*
			 * FIXME: This is now managed in Entity - can eventually delete - Sky
			 *
			// Comments on activities should be excluded from activities
			if ($this->object_type == "comment")
			{
				$obj_ref = $this->getValue("obj_reference");
				if ($obj_ref)
				{
					$parts = explode(":", $obj_ref);
					if ($parts[0] == "activity")
					{
						$logact = false;
					}
				}
			}
			*/

			// Update path
			//if ($this->def->parentField)
			//	$this->updateHeiarchPath();

			// Update uname index table
			//if ($this->getValue("uname"))
				//$this->setUniqueName($this->getValue("uname"));

			// Process workflow - now handled in new Entity classes
			//$this->processWorkflow($performed);

			// FIXME: This is now managed in Entity - can eventually delete - Sky
			// Process temp file uploads
			// $this->processTempFiles();

			// FIXME: This is now managed in Entity - can eventually delete - Sky
			// Update sync stats
			//if ($logact)
			//	$this->updateObjectSyncStat('c');
		}

		if ($logact)
		{
			// FIXME: This is now managed in Entity - can eventually delete - Sky
			/*
			if ($performed == "create" && $this->object_type != "activity")
			{
				$desc = $this->getDesc();
				//$this->addActivity("created", $this->getName(), ($desc)?$desc:"Created new " . $this->title, null, null, 't');

			}

			if ($performed == "update" && $this->object_type != "activity")
			{
				$desc = $this->getChangeLogDesc();
				//$this->addActivity("updated", $this->getName(), ($desc)?$desc:"Updated " . $this->title, null, null, 't');
			}
			*/
		}

		// FIXME: This is now handled inside the new Netric\Entity\DataMapperAbstract class
		//if (count($this->def->aggregates))
		//{
		//	$this->saveAggregates($this->def->aggregates);
		//}

		return $this->id;
	}


	/**
	 * Convert properties to column names for saving to datastore
	 *
	 * @return array("colname"=>"value")
	 */
	private function saveColVals()
	{
		$dbh = $this->dbh;
		$ret = array();

		// Reload cached foreign values based on the current values
		$this->reloadFVals();

		$all_fields = $this->def->getFields();

        $val = ""; // Initialize $val to fix the exception error - Undefined variable: val
		foreach ($all_fields as $fname=>$fdef)
		{
			$setVal = "";
            $fVal = "";
            
            if(isset($this->values[$fname]))
                $fVal = $this->values[$fname];
			
            $new = $fdef->getDefault($fVal, 'create', $this, $this->user);
			
            // Update fvals if we changed a reference
            if ($new != $val && ($fdef->type=='fkey' || $fdef->type=='fkey_multi' || $fdef->type=='object' || $fdef->type=='object_multi'))            
                $this->reloadFVals($fname);
                
            $val = $new;
            
			switch ($fdef->type)
			{
			case 'auto': // Calculated fields
				break;
			case 'fkey_multi':
                $fvals = $this->getFVals($fname);
				if ($val || isset($this->delFValues[$fname]))
					$setVal = "'".$dbh->Escape(json_encode($val))."'";
				break;
			case 'object':
				if ($fdef->subtype)
					$setVal = $dbh->EscapeNumber($val);
				else
					$setVal = "'".$dbh->Escape($val)."'";
				break;
			case 'object_multi':
				$fvals = $this->getFVals($fname);
                if ($val || isset($this->delFValues[$fname]))
					$setVal = "'".$dbh->Escape(json_encode($val))."'";
				break;
			case 'fkey':
				$setVal = $dbh->EscapeNumber($val);
				break;
			case 'int':
			case 'integer':
			case 'double':
			case 'double precision':
			case 'float':
			case 'real':
			case 'number':
			case 'numeric':
				if ($fdef->subtype == "integer" && $val)
					$val = round($val, 0);
				$setVal = $dbh->EscapeNumber($val);
				break;
			case 'date':
				$setVal = $dbh->EscapeDate($val);
				break;
			case 'timestamp':
                $setVal = $dbh->EscapeTimestamp($val);
				break;
			case 'bool':
				//$setVal = "'".(($val=='t')?'t':'f')."'";
                
                $bVal = "f"; // Set the default values to 'f'
                if($val=="t" || $val=="true") // Check if boolean value is 't' or 'true'
                    $bVal = "t";
                    
                $setVal = "'$bVal'";
				break;
			case 'text':
				$tmpval = $val;
				if (is_numeric($fdef->subtype))
				{
					if (strlen($tmpval)>$fdef->subtype)
						$tmpval = substr($tmpval, 0, $fdef->subtype);
				}
				$setVal = "'".$dbh->Escape($tmpval)."'";
				break;
			default:
				$setVal = "'".$dbh->Escape($val)."'";
				break;
			}

			if ($setVal) // Setval must be set to something for it to update a column
				$ret[$fname] = $setVal;

			// Set fval
			if ($fdef->type == "fkey" || $fdef->type == "fkey_multi" || $fdef->type == "object" || $fdef->type == "object_multi")
			{
				$fvals = $this->getFVals($fname);
				$ret[$fname . "_fval"] = ($fvals) ? "'".$dbh->Escape(json_encode($fvals))."'" : "'".$dbh->Escape(json_encode(array()))."'";
			}
		}
		return $ret;        
	}

	/**
	 * Function used for derrived classes to hook before save event
	 */
	protected function beforesaved()
	{
	}

	/**
	 * Function used for derrived classes to hook save event
	 */
	protected function saved()
	{
	}

	/**
	 * Function used for derrived classes to hook onload event
	 */
	protected function loaded()
	{
	}

	/**
	 * Function used for derrived classes to hook delete event
	 */
	protected function removed($hard=false)
	{
	}

	/**
	 * Function used for derrived classes to hook undelete event
	 */
	protected function unremoved()
	{
	}

	/**
	 * @depricated This is now handled in the datamapper
 	 * Save revision history. Will be called last after all changes are made so this->revision will have to be reduced by one.
	 */
	public function saveRevision()
	{
		if (!$this->id)
			return false;

		$dbh = $this->dbh;

		$values = array();

		if ($this->revision == 1) // take initial snapshot - starting point
		{
			$all_fields = $this->def->getFields();
			foreach ($all_fields as $fname=>$fdef)
			{
				$val = $this->getValue($fname);
				if (is_array($val))
				{
					foreach ($val as $subval)
						$values[] = array("fname"=>$fname, "val"=>$subval);
				}
				else
				{
					$values[] = array("fname"=>$fname, "val"=>$val);
				}
			}
		}
		else // record changes
		{
			foreach ($this->changelog as $fname=>$log)
			{
				$values[] = array(
					"fname"=>$fname, 
					"val"=>(is_array($log['newvalraw'])) ? implode(",", $log['newvalraw']) : $log['newvalraw'],
				);
			}
		}

		$start = microtime(true);

		if (count($values) && $this->revision && $this->id && $this->object_type_id)
		{
			$result = $dbh->Query("insert into object_revisions(object_id, object_type_id, revision, ts_updated)
								   values('".$this->id."', '".$this->object_type_id."', '".$this->revision."', 'now');
								   select currval('object_revisions_id_seq') as id;");
			if ($dbh->GetNumberRows($result))
			{
				$revhist = $dbh->GetValue($result, 0, "id");

				foreach ($values as $valset)
				{
					$dbh->Query("insert into object_revision_data(revision_id, field_value, field_name)
								 values('$revhist', '".$dbh->Escape($valset['val'])."', '".$dbh->Escape($valset['fname'])."')");
				}
			}
		}

		if ($this->debug)
			echo "saveRevision: " . number_format(microtime(true) - $start, 10) . "\n";
	}

	/**
 	 * Update field data at a past revision
	 *
	 * @param string $fieldName The name of the field to update
	 * @param string $value The value to set the field to
	 * @param int $revision The revision number to update
	 */
	public function updateRevField($fieldName, $value, $revision)
	{
		if (!$fieldName || !is_numeric($revision) || !$this->id)
			return false;

		if ($revision >= $this->revision)
			return false;

		$result = $this->dbh->Query("SELECT id FROM object_revisions WHERE object_type_id='".$this->object_type_id."'
									and object_id='".$this->id."' and revision='$revision'");
		if ($this->dbh->GetNumberRows($result))
		{
			$revId = $this->dbh->GetValue($result, 0, "id");

			if (is_numeric($revId))
			{
				// Cleanup
				$this->dbh->Query("DELETE FROM object_revision_data WHERE revision_id='$revId' AND field_name='".$this->dbh->Escape($fieldName)."';");

				// Update field
				$this->dbh->Query("INSERT INTO object_revision_data(revision_id, field_value, field_name)
									values('$revId', '".$this->dbh->Escape($value)."', '".$this->dbh->Escape($fieldName)."')");

				return true;
			}
		}

		// Nothing was updated
		return false;
	}

	/**
	 * Save aggregate values if set for this object
	 *
	 * @param array $aggregates Array of aggregates for this object type
	 */
	private function saveAggregates($aggregates)
	{
		foreach ($aggregates as $agg)
		{
			$field = $this->def->getField($agg->field);
			if ($this->getValue($agg->field) && $field->type=="object" && $field->subtype)
			{
				switch ($agg->type)
				{
				case 'sum':
					// Intentionally open without user to bypass permissions for this feature only
					$refObj = new CAntObject($this->dbh, $field->subtype, $this->getValue($agg->field));

					$objList = new CAntObjectList($this->dbh, $this->object_type, $this->user);
					$objList->addCondition("and", $agg->field, "is_equal", $this->getValue($agg->field));
					$objList->addAggregateField($agg->calcField, $agg->type);
					$objList->getObjects(0, 1);
					$cnt = 0;
					if ($objList->aggregateCounts[$agg->calcField][$agg->type])
						$cnt = $objList->aggregateCounts[$agg->calcField][$agg->type];

					$refObj->setValue($agg->refField, $cnt);
					$refObj->save(false);
					break;

				case 'avg':
					// Intentionally open without user to bypass permissions for this feature only
					$refObj = new CAntObject($this->dbh, $field->subtype, $this->getValue($agg->field));

					$objList = new CAntObjectList($this->dbh, $this->object_type, $this->user);
					$objList->addCondition("and", $agg->field, "is_equal", $this->getValue($agg->field));
					$objList->addAggregateField($agg->calcField, $agg->type);
					$objList->getObjects(0, 1);
					$cnt = 0;
					if ($objList->aggregateCounts[$agg->calcField][$agg->type])
						$cnt = $objList->aggregateCounts[$agg->calcField][$agg->type];

					$refObj->setValue($agg->refField, $cnt);
					$refObj->save(false);
					break;
				}
			}
		}
	}

	/**
	 * First soft delete, then hard delete this object
	 */
	public function removeHard()
	{
		$this->remove();
		return $this->remove();
	}

	/**
	 * Delete this object
	 *
	 * When called the first time the object is soft-deleted, meaning the delete flag is set to true.
	 * The second time it actually purges the record from the database.
	 */
	public function remove($commit=true)
	{
		$dbh = $this->dbh;

		// Check security
		if ($this->user)
		{
			if ($this->debug)
			{
				$this->dacl->debug = true;
			}

			if (!$this->dacl->checkAccess($this->user, "Delete", ($this->user->id==$this->owner_id)?true:false))
			{
				return false;
			}
		}

		if ($this->id)
		{
			$purge = true;
            
			if($this->getValue("f_deleted") !='t')
			{
				// Keep from commiting changes on save.
				// This is usually used for batch updates or deletions
				if (!$commit)
					$this->indexCommit = false;

				$this->setValue("f_deleted", 't');

				// First param false does not record activity or workflow
				$this->save(false);

				$purge = false;

				// Fire workflow after delete saved
				$this->processWorkflow("delete");

				// Update sync stats
				$this->updateObjectSyncStat('d');
			}
			
			if ($purge)
			{
				// Remove revision history
				$this->dbh->Query("delete from object_revisions where object_id='".$this->id."' and object_type_id='".$this->object_type_id."'");

				// Delete the record
				$dbh->Query("delete from ".$this->object_table." where id='".$this->id."'");
	
				// Remove unique DACL. Of course, we don't want to delete the dacl for all object types, just for this id
				if ($this->daclIsUnique && $this->dacl)
					$this->dacl->remove();

				// Remove associations
				$dbh->Query("delete from object_associations where 
								(object_id='".$this->id."' and type_id='".$this->object_type_id."')
								or (assoc_object_id='".$this->id."' and assoc_type_id='".$this->object_type_id."')");

				// Remove index
				$this->removeIndex();

				// Remove recurrance
				if ($this->def->recurRules!=null && !$this->recurrenceException)
				{
					$rid = $this->getValue($this->def->recurRules['field_recur_id']);
					if ($this->recurrencePattern==null && $rid)
						$this->getRecurrencePattern($rid);

					if ($this->recurrencePattern!=null)
					{
						$this->recurrencePattern->removeSeries($this);
						$this->recurrencePattern->remove();
					}
				}

				// TODO: delete groupings/foreign keys
			}

			// Purge unames
			// TMP: eventually this will be replaced with master objects table 'uname' field
			$dbh->Query("delete from object_unames where object_id='".$this->id."' and object_type_id='".$this->object_type_id."'");

			$this->clearCache();

			// Mark recurrance as inactive
			if (!$purge && $this->def->recurRules!=null && !$this->recurrenceException)
			{
				$rid = $this->getValue($this->def->recurRules['field_recur_id']);
				if ($this->recurrencePattern==null && $rid)
					$this->getRecurrencePattern($rid);

				if ($this->recurrencePattern!=null)
				{
					$this->recurrencePattern->removeSeries($this);
					$this->recurrencePattern->fActive = false;
					$this->recurrencePattern->save();
				}
			}

			$this->removed($purge);
			
			return true;
		}
		else if ($this->requestedId)
		{ 
			// Object has been deleted, clear from index
			$this->id = $this->requestedId;
			$this->removeIndex();
			return true;
		}

		// Can't delete an object that does not exist
		return false;
	}

	/**
	 * Undelete or unremove an object
	 */
	public function unremove()
	{
		$dbh = $this->dbh;
		$this->setValue("f_deleted", 'f');
		$this->index();
		$this->save(false);

		// Callback;
		$this->unremoved();
	}

	/**
	 * Remove this object from an index
	 */
	public function removeIndex()
	{
		$index = $this->getIndex();
		$ret = $index->removeObject($this);

		// Remove from indexed log
		if ($ret)
			$this->dbh->Query("delete from object_indexed WHERE object_type_id='".$this->object_type_id."' and object_id='".$this->id."'");


		return $ret;
	}

	/**
	 * Index this object
	 *
	 * If we are working with the db backend, then index the fulltext column tsv_fulltext for objects.
	 * If any other index is used the full object is indexed by the index backend class.
	 *
	 * @param bool $commit If true then wait for the index to finish before returning, otherwise just send and let it commit in the background
	 * @param bool $runNow If false then the index is put in the background, otherwise index immediately
	 */
	public function index($commit=true, $runNow=false)
	{
		global $G_OBJ_IND_EXISTS;
		$dbh = $this->dbh;
		$ret = true;

		if (!$this->id || !$this->object_type_id)
			return false;

		// Check for global commit override
		if (!$this->indexCommit)
			$commit = false;

		// Check if runNow should be true
		/*
		if (($this->indexType != "db" && !$this->indexFullTextOnly) || $this->runIndexOnSave)
			$runNow = true; // we are depending on external index for all our lists, we need to index immediately
		*/

		// If we are only using the alternate index as a full-text search, we don't need to force a commit
		// which is very costly and negatively impacts performance. Let it automatically process.
		if ($this->indexFullTextOnly && $commit)
			$commit = false;

		// Sky Stebnick
		// NOTE: requiring that all index insertions run immediately but if indexFullTextOnly is set then we can hold off on commit
		$runNow = true;

		if ($runNow)
		{
			$start = microtime(true); // Time this operation

			$index = $this->getIndex();

			$ret = $index->indexObject($this, $commit);

			$dbh->Query("delete from object_indexed where object_type_id='".$this->object_type_id."' and object_id='".$this->id."'
						 and (index_type='".$this->getIndexTypeId()."' OR revision>'".(($this->revision)?$this->revision:1)."')");
			if ($ret && $this->indexType!="db")
			{
				$dbh->Query("insert into object_indexed(object_type_id, object_id, revision, index_type) 
							 values('".$this->object_type_id."', '".$this->id."', 
									'".(($this->revision)?$this->revision:1)."', '".$this->getIndexTypeId()."')");
			}

			// Keep track of how long it took to index this object
			Stats::timing("antobject.indextime", microtime(true) - $start);
		}
		//else
		//{
			/*
			 * Add job to worker queue to index in database in the background.
			 * The db is serving all lists so we can do a lazy update of the index
			 * in the background because even a couple second lag is acceptable wait time
			 * before users can do a full-text search.
			 */
			//$data = array("oid"=>$this->id, "obj_type"=>$this->object_type);
			//$wman = new WorkerMan($dbh);
			//$jobid = $wman->runBackground("lib/object/index", serialize($data));
		//}

		// Always index to database if using different index type
		/*
		if ($this->indexType != "db" && $this->dbh->accountId)
		{
			$data = array("oid"=>$this->id, "obj_type"=>$this->object_type, "index_type"=>"db");
			// Add job to worker queue to index in database in the background
			$wman = new WorkerMan($dbh);
			$jobid = $wman->runBackground("lib/object/index", serialize($data));
		}
		*/

		return $ret;
	}

	/**
	 * If the current index supports commit, then this function will apply all changes
	 */
	public function indexCommit()
	{
		$index = $this->getIndex();
		$index->commit();
	}

	/**
	 * If the current index supports optimize, then this will optimize or defrag or vacuum the index
	 */
	public function indexOptimize()
	{
		$index = $this->getIndex();
		$index->optimize();
	}

	/**
	 * TODO: This should be moved to the extended CAntObject_Customer object
	 */
	function customerOnSave()
	{
		if ($this->user)
		{
			// Sync with linked contacts
			if (!$this->nosync)
				CustSyncContact($this->dbh, $this->user->id, $this->id, null, "cust_to_contact");

			// Sync child objects if set to inherit fields
			$result = $this->dbh->Query("select customer_association_types.inherit_fields, customer_associations.customer_id 
										 from customer_associations, customer_association_types where f_child='t' and inherit_fields is not null 
										 and inherit_fields!='' and customer_associations.type_id=customer_association_types.id 
										 and customer_associations.parent_id='".$this->id."'");
			for ($i = 0; $i < $this->dbh->GetNumberRows($result); $i++)
			{
				$inherit_fields = explode(":", $this->dbh->GetValue($result, $i, "inherit_fields"));
				$cust_id = $this->dbh->GetValue($result, $i, "customer_id");

				$child_cust = new CAntObject($this->dbh, "customer", $cust_id, $this->userid);
				foreach ($inherit_fields as $fname)
				{
					if ($fname)
					{
						$child_cust->setValue($fname, $this->getValue($fname));
					}
				}
				$child_cust->save();
			}
		}
	}

	/**
	 * TODO: This should be moved to an extended CAntObject_ContactPersonal object
	 */
	function contactOnSave()
	{
		$dbh = $this->dbh;

		if ($this->user && $this->id)
		{
			// TODO: This has been commented out because there an infinite loop was being created with a customer was saved
			// and the customer was linked to two separate contacts.

			// Sync with linked contacts
			/*
			$custid = $this->getValue("customer_id");
			if ($custid && !$this->nosync)
				CustSyncContact($this->dbh, $this->user->id, $custid, $this->id, "contact_to_cust");

			// Set calendar events
			$uid = $this->getValue("user_id");
			if ($uid)
			{
				$CALID = GetDefaultCalendar($dbh, $uid);
				$CID = $this->id;

				if ($this->getValue('birthday'))	
				{
					$reid = ContactAddCalDate($dbh, $uid, "Birthday", 'birthday', $CID, $CALID);
					$this->setValue("birthday_evnt", $reid);
				}
				else if (!$this->getValue('birthday')) // Make sure there are no stray events
				{
					ContactDelCalDate($dbh, NULL, 'birthday', $CID);
					$this->setValue("birthday_evnt", '');
				}

				if ($this->getValue('anniversary'))
				{
					$reid = ContactAddCalDate($dbh, $uid, "Anniversary", 'anniversary', $CID, $CALID);
					$this->setValue("anniversary_evnt", $reid);
				}
				else if (!$this->getValue('anniversary')) // Make sure there are no stray events
				{
					ContactDelCalDate($dbh, NULL, 'anniversary', $CID);
					$this->setValue("anniversary_evnt", '');
				}

				if ($this->getValue('birthday_spouse'))	
				{
					$reid = ContactAddCalDate($dbh, $uid, "Spouse Birthday (".$this->getValue('spouse_name').")", 'birthday_spouse', $CID, $CALID);
					$this->setValue("birthday_spouse_evnt", $reid);
				}
				else if (!$this->getValue('birthday_spouse')) // Make sure there are no stray events
				{
					ContactDelCalDate($dbh, NULL, 'birthday_spouse', $CID);
					$this->setValue("birthday_spouse_evnt", '');
				}

				$this->nosync = true;
				$this->skipContactOnsave = true;
				$this->save(false);
			}
			*/

			// Update customer images
			// TODO: make this a configurable option. For now linked contacts will copy images
			//$worker = new CWorkerPool();
			//$worker->runBackground("contact_sync_image", array("contact_id"=>$this->id,"user_id"=>$this->user->id,"account_id"=>$this->user->accountId));
		}
	}

	/**
	 * Set the 'path' variable to cache where this object is found which is relly useful for full text searches
	 */
	private function updateHeiarchPath()
	{
		$dbh = $this->dbh;

		if ($this->def->parentField)
		{
			$path = $this->getHeiarchyPath();

			$result = $dbh->Query("select id from ".$this->object_table." where ".$this->def->parentField."='".$this->id."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$oid = $dbh->GetValue($result, $i, "id");
				$obj = new CAntObject($dbh, $this->object_type, $oid);
				$obj->setValue("path", $path);
				$obj->save(false);
			}
		}
	}

	/**
	 * Work backwords from current object to get a heiarchy path
	 */
	private function getHeiarchyPath()
	{
		$dbh = $this->dbh;
		$path = $this->getName();
		$parent = $this->getValue($this->def->parentField);
		$loopcnt = 0; // Prevent circular reference loops

		while ($parent)
		{
			$obj = new CAntObject($dbh, $this->object_type, $parent);
			$parent = $obj->getValue($this->def->parentField);
			$tmp_buf = $obj->getName();
			$tmp_buf .= "/".$path;
			$path = $tmp_buf;
			$loopcnt++;
			if ($loopcnt > 200)
				break;
		}
		return $path;
	}

	/**
	 * Process workflows for a given event
	 *
	 * @param string $event The name of the event triggered that workflows might be listening for
	 */
	public function processWorkflow($event)
	{
        if($this->skipWorkflow) // if set to true, skip the execution of workflow
            return;
            
		$dbh = $this->dbh;

		// Now check for workflow
		require_once("lib/WorkFlow.php");
		$wflist = new WorkFlow_List($dbh, "object_type='".$this->object_type."' and f_active='t' and f_on_$event='t'");        

		for ($w = 0; $w < $wflist->getNumWorkFlows(); $w++)
		{
			$wf = $wflist->getWorkFlow($w);
			if ($this->user)
				$wf->user = $this->user;

			// Check if workflow conditions match and make sure the workflow has not already launched (avoid loops)
			if ($wf->conditionsMatch($this) && in_array($wf->id, $this->processed_workflows)==false)
			{
				$this->processed_workflows[] = $wf->id;
				$wf->execute($this);
			}
		}


		if ($this->object_type == "approval")
			$this->processApprovalWorkflow($event);
	}

	function processApprovalWorkflow($event)
	{
		$wf_inst = $this->getValue("workflow_instance_id");
		$wf_act = $this->getValue("workflow_action_id");
		$objRef = $this->getValue("obj_reference");

		// Make sure we have the action id
		if (!$this->getValue("workflow_action_id"))
			return false;

		// If the status has not been changed then do nothing
		if (!$this->fieldValueChanged("status"))
			return false;

		// Get object instance
		$parts = explode(":", $objRef);
		if (count($parts)!=2)
			return false;

		$obj = new CAntObject($this->dbh, $parts[0], $parts[1], $this->user);

		// Only execute action if we are updating or creating this approval
		if ($event == "update" || $event=="create")
		{
			$act = new WorkFlow_Action($this->dbh, $this->getValue("workflow_action_id"));
			switch ($this->getValue("status"))
			{
			case 'approved':
				$act->executeChildActions("approved", $obj);
				break;
			case 'declined':
				$act->executeChildActions("declined", $obj);
				break;
			case 'awaiting':
			default:
				break;
			}
		}
	}

	/**
	 * Set a value for a property
	 *
	 * @param string $name The name of the property to set
	 * @param mixed $value The value, almost always a string, of the property
	 * @param bool $logchange If true keep track of changes in the changelog
	 */
	public function setValue($name, $value, $logchange=true)
	{
		$field = $this->def->getField($name);
		$oldval = $this->getValue($name);

        if (!$field)
            throw new InvalidArgumentException("There is no field named $name in " . $this->object_type);

		// Clear foreign value cache
        $this->fValues[$name] = null;
        $this->values[$name] = null;

		if ($field && (($field->type=='object' && $field->subtype=='user') || ($field->type=='fkey' && $field->subtype=='users')) && $value==-3)
		{
			if ($this->user)
				$value = $this->user->id;
			else
				$value = "";
		}

		// Log changes
		if ($oldval != $value && $logchange)
		{
			$oldvalraw = $oldval;
			$newvalraw = $value;
            
			if ($field->type == 'fkey' || $field->type == 'object')
			{
				$oldval = $this->getForeignValue($name, null, false);
				$newval = $this->getForeignValue($name, $value, false);
			}
			else
			{
				$oldval = $oldval;
				$newval = $value;
			}
			$this->changelog[$name] = array("field"=>$name, "oldval"=>$oldval, "newval"=>$newval, "oldvalraw"=>$oldvalraw, "newvalraw"=>$newvalraw);

		}

		$this->values[$name] = $value;

		// Check if field change will modify the security for this object - inherited
		if ($name == $this->def->inheritDaclRef && $this->user)
		{
			if ($field->type == "object" && $field->subtype && is_numeric($value) && $this->user)
			{
				$ref_dacl = new Dacl($this->dbh, "/objects/".$field->subtype."/".$value."/".$this->object_type);
				if ($ref_dacl && $ref_dacl->id)
				{
					/*
					if ($this->id)
					{
						$this->dacl = new Dacl($this->dbh, "/objects/".$this->object_type."/".$this->id, true, $OBJECT_FIELD_ACLS);
						$this->dacl->setInheritFrom($ref_dacl->id);
						$this->daclIsUnique = true;
					}
					else
					{
					*/
						$this->dacl = $ref_dacl;
						$this->daclIsUnique = false;
					//}
				}
			}
		}
	}

	/**
	 * Add a value to a multi-valued field.
	 *
	 * This has duplicate detection so if you try to add a value twice it will simply update the array
	 *
	 * @param string $name The name of the field we are working with
	 * @param int $value The value to add
	 */
	public function setMValue($name, $value)
	{
		// Do not allow null vales to be stored
		if ($value == "" || $value==NULL)
			return false;
        
        $field = $this->def->getField($name);

		// Get old values
		$oldvalraw = $this->getValue($name);
		$oldval = $oldvalraw;
		// Commenting this out because it creates a circcular bug which we have solved in the new Netric\Entity
		/*
		if (isset($field) && ($field->type == 'fkey_multi' || $field->type == 'object_multi'))
			$oldval = $this->getForeignValue($name);
		else
			$oldval = $oldvalraw;
		*/

		// Clear foreign value cache
		$this->fValues[$name] = null;


		if (isset($this->values[$name]) && is_array($this->values[$name]))
		{
			$fFound = false;

			// Make sure multi-fkey is unique
			for ($i = 0; $i < count($this->values[$name]); $i++)
			{
				if ($this->values[$name][$i] == $value)
					$fFound = true;
			}

			if (!$fFound)
			{
				$this->values[$name][] = $value;
			}
		}
		else
		{
			$this->values[$name] = array();
			$this->values[$name][0] = $value;
		}

		// Log changes
		if ($oldvalraw != $this->values[$name])
		{
			/* This was causing a circular load with files and users - Sky
			if (isset($field) && ($field->type == 'fkey_multi' || $field->type == 'object_multi'))
				$newval = $this->getForeignValue($name, $value, false);
			else
			*/
				$newval = $this->values[$name];

			$this->changelog[$name] = array(
				"field"=>$name, 
				"oldval"=>$oldval, 
				"newval"=>$newval, 
				"oldvalraw"=>$oldvalraw, 
				"newvalraw"=>$this->values[$name]
			);
		}
	}

	/**
	 * Clear id from a multi-valued field
	 *
	 * @param string $name The name of the field we are working with
	 * @param int $value The value to remove (if it exists)
	 */
	public function removeMValue($name, $value)
	{
		$field = $this->def->getField($name);

		// Get old values
		$oldvalraw = $this->getValue($name);
		if ($field->type == 'fkey_multi' || $field->type == 'object_multi')
			$oldval = $this->getForeignValue($name);
		else
			$oldval = $oldvalraw;

		if(isset($this->values[$name]) && is_array($this->values[$name]))
		{
			$buf_arr = $this->values[$name];
			$this->values[$name] = array();
			for ($i = 0; $i < count($buf_arr); $i++)
			{
				if ($buf_arr[$i] != $value)
					$this->values[$name][] = $buf_arr[$i];
			}
            
            // Remove the fVals too
            unset($this->fValues[$name][$value]);
            unset($this->delFValues[$name]);
            
            // if MValue is already empty, set it in delFValues so it will be included in the update query
            if(sizeof($this->values[$name]) == 0)
                $this->delFValues[$name] = 1;
		}

		// Log changes
		if ($oldvalraw != $this->values[$name])
		{
			if ($field->type == 'fkey_multi' || $field->type == 'object_multi')
				$newval = $this->getForeignValue($name, $value, false);
			else
				$newval = $this->values[$name];

			$this->changelog[$name] = array(
				"field"=>$name, 
				"oldval"=>$oldval, 
				"newval"=>$newval, 
				"oldvalraw"=>$oldvalraw, 
				"newvalraw"=>$this->values[$name]
			);
		}
	}

	/**
	 * Clear all values from a multi-valued field
	 *
	 * @param string $name The name of the field to purge
	 */
	public function removeMValues($name)
	{
		$this->values[$name] = null;
		$this->values[$name] = array();
        
        $this->delFValues[$name] = 1;
        unset($this->fValues[$name]);
	}

	/**
	 * Get array for fval
	 *
	 * @param string $fname The name of the field
	 * @return array
	 */
	public function getFVals($fname)
	{
        if(isset($this->fValues[$fname]))
		    return $this->fValues[$fname];
        else
            return null;
	}

	/**
	 * Reload foreign values - usually called when saving to refresh all values
	 *
	 * @param string $onlyFName Optionally only update one field
	 */
	public function reloadFVals($onlyFName=null)
	{
		$all_fields = $this->def->getFields();
		foreach ($all_fields as $fname=>$fdef)
		{
			if ($onlyFName && !$fname!=$onlyFName)
				continue; // skip processing if we are only working with one field

			$val = $this->getValue($fname);
            
			$res = $this->loadForeignValLabel($fname, $val);
			if ($res)
			{
				foreach ($res as $entId=>$entVal)
					$this->fValues[$fname][(string)$entId] = $entVal;
			}
		}
	}

	/**
	 * Load fval label
	 *
	 * @param string $fname The field name
	 * @param mixed $val Can either be a single id or an array of ids to pull labels for
	 * @return array Associative array of 'id'=>'label'
	 */
	private function loadForeignValLabel($fname, $val)
	{
		$field = $this->def->getField($fname);

		// Make sure we are working with an id
		if ($field->subtype && !is_numeric($val) && !is_array($val))
			return "";

		// Make sure we are working with an empty array
		if (is_array($val))
			if (!count($val))
				return "";

		// Make sure field exists
		if (!$field)
			return "";

		// Make sure we are working with foreign references
		if ($field->type != "fkey" && $field->type != "fkey_multi" && $field->type != "object" && $field->type != "object_multi")
			return false;

		$ret = array();

		if (($field->type == "fkey" || $field->type == "fkey_multi") && $field->subtype && (is_numeric($val) || is_array($val)))
		{
            $tbl = $field->subtype;
            $titleField = $field->fkeyTable['title'];
            $where = "";
			$query = "select id, $titleField as name FROM $tbl ";
			if (is_array($val))
			{
				foreach ($val as $id)
				{
					if (is_numeric($id))
					{
						if ($where) $where .= " OR ";
						$where .= "id='$id'";
					}
				}
			}
			else
			{
                if (is_numeric($val))
				    $where .= "id='$val'";
			}
            
            if(!empty($where))
			    $query .= "WHERE $where \n";

			$this->fValues[$fname] = array();
			$result = $this->dbh->Query($query);
			for ($i = 0; $i < $this->dbh->GetNumberRows($result); $i++)
			{
				$row = $this->dbh->GetRow($result, $i);                
				$ret[(string)$row['id']] = $row['name'];
			}
		}
        
		if (($field->type == "object" || $field->type == "object_multi") && $val!=null)
		{
			$this->fValues[$fname] = array();

			$vals = (is_array($val)) ? $val : array($val);
            
			foreach ($vals as $valEnt)
			{
				$oname = null;

				if ($field->subtype)
				{
					// DEBUG code
					//if ($field->subtype == "object")
					//	throw new \Exception("Subtype was object for " . var_export($field, true));
					$oname = $field->subtype;
					$oid = $valEnt;
				}
				else
				{
					$parts = CAntObject::decodeObjRef($valEnt);
					if ($parts)
					{
						$oname = $parts['obj_type'];
						$oid = $parts['id'];
					}
				}

				if ($oname)
				{
					$objDef = CAntObject::factory($this->dbh, $oname);
					$label = $objDef->getName($oid);
					$this->fValues[$fname][(string)$valEnt] = $label;
					$ret[(string)$valEnt] = $label;
				}
			}
		}
        
		return $ret;
	}

	/**
	 * Get the foreign value for a filed of types: fkey, fkey_multi, object, object_multi, or alias
	 *
	 * @param string $name The name of the field to query
	 * @param int $val The id of the name to pull. If null then just get value string describing all values in field
     * @param bool $usecache Pull the values from the local cache. If false this will be forced to query datastore for name of $val
	 * @return string If val is passed then the name of the id pass, otherwise a full text representation of all values like "val1, val2, val3"
	 */
	public function getForeignValue($name, $val=null, $usecache=true)
	{
		$dbh = $this->dbh;

		// Check fValues cache for string name
		if (isset($this->fValues[$name]) && is_array($this->fValues[$name]) && count($this->fValues[$name]) && $usecache)
		{
			// Get specific id
			if ($val)
				return $this->fValues[$name]["$val"];

			$buf = "";
			foreach ($this->fValues[$name] as $fval)
			{
				if ($buf) $buf .= ", ";
				$buf .= $fval;
			}

			return $buf;
		}
        
		// Unable to find cached value
		if ($val==null)
			$val = $this->getValue($name);

		$retStr = "";
		$ret = false;
		
        // Get specific label for this id(val)
		$ret = $this->loadForeignValLabel($name, $val);

		if ($ret)
		{
			foreach ($ret as $id=>$rval)
			{
				if ($retStr) $retStr .= ",";
				$retStr .= $rval;
			}
		}

		return $retStr;

		/*
		$field = $this->fields->getField($name);
		if ($val==null && $field->type != 'fkey_multi' && ($field->type!='object' 
			|| ($field->type=='object' && $field->subtype)) && $field->type != 'object_multi')
			$val = $this->getValue($name);

		if ($val!=null && (($field->type=='object' && !$field->subtype) || $field->type=='object_multi'))
		{
			// Get obj_type from obj_type id $val['type_id']
			$parts = explode(":", $val);

			if (count($parts) == 1 && $field->subtype)
				$parts = array($field->subtype, $parts[0]);
	
			if (count($parts)==2)
			{
				$ret = objGetAttribFromName($this->dbh, $parts[0]);

				$oname = $this->cache->get($this->dbh->dbname."/objects/getname/".$parts[0]."/".$parts[1]);
				if ($oname)
				{
					$ret .= ": ".$oname;
				}
				else
				{
					$obj = new CAntObject($this->dbh, $parts[0], $parts[1], $this->user);
					$oname = $obj->getName();
					$ret .= ": ".$oname;
					$this->cache->set($this->dbh->dbname."/objects/getname/".$parts[0]."/".$parts[1], $oname);
				}

				return $ret;
			}
		}

		if ($val!=null && ($field->type=='object' && $field->subtype))
		{
			// Get obj_type from obj_type id $val['type_id']
			$oname = $this->cache->get($this->dbh->dbname."/objects/getname/".$field->subtype."/".$val);
			if ($oname)
			{
				$ret = $oname;
			}
			else
			{
				$obj = new CAntObject($this->dbh, $field->subtype, $val, $this->user);
				$ret = $obj->getName();
				$this->cache->set($this->dbh->dbname."/objects/getname/".$field->subtype."/".$val, $ret);
			}

			return $ret;
		}
		else if (is_array($field->fkeyTable) && is_numeric($val))
		{
			$query = "select ".$field->fkeyTable['key']." as key";
			if ($field->fkeyTable['title'])
				$query .= ", ".$field->fkeyTable['title']." as title";
			$query .= " from ".$field->subtype;
			$query .= " where ".$field->fkeyTable['key']."='$val' ";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetRow($result, 0);
				return ($row['title']) ? $row['title'] : $row['key'];
			}
		}
		else if ($field->type == "alias" && $this->values[$name])
		{
			return $this->values[$this->values[$name]];
		}
		 */

		// fail
		return null;
	}

	/**
	 * Get value of a field
	 *
	 * @param string $name The name of the property to retrieve
	 * @param bool $getforeign Get a referenced field label (object, fkey)
	 * @return mixed, either a string for the value or an array if type = *_multi
	 */
	public function getValue($name, $getforeign=false)
	{
		$fdef = $this->def->getField($name);	
		if (!$fdef)
			return null;

		if ($getforeign && ($fdef->type == "fkey" || $fdef->type == "alias" || ($fdef->type == "object" && $fdef->subtype)))
		{
			$val = $this->getForeignValue($name);
		}
		else
		{
            $val = null;
            
            if(isset($this->values[$name]))
			    $val = $this->values[$name];
		}

		// Pull default if name field
		if ($val==null && $fdef->type != "timestamp" && $fdef->type!='fkey_multi' && $fdef->type!='auto')
		{
            $val = $fdef->getDefault($val, 'null', $this, $this->user);
			if ($val!=null)
			{
				// Convert current user (-3) to actual user id if user is set
                //if ((($field->type=='object' && $field->subtype=='user') || ($field->type=='fkey' && $field->subtype=='users')) && $value==-3 && $this->user)
                if ((($fdef->type=='object' && $fdef->subtype=='user') || ($fdef->type=='fkey' && $fdef->subtype=='users')) && $val==-3 && $this->user)
					$val = $this->user->id;

				if ($fdef->type == "timestamp")
					$val = date("m/d/Y h:i a T", $val);

				if ($fdef->type == "time")
					$val = date("h:i a T", $val);

				if ($fdef->type == "date")
					$val = date("m/d/Y", $val);
				
				$this->values[$name] = $val;
			}
		}

		// Now apply data masks - accounts and users can crate masks and/or override system defaults
		$val = $this->applyMask($val, $fdef);

		return $val;
	}

	/**
	 * Get referenced object value
	 *
	 * @param string $fieldPath Path with '.' used to traverse fields
	 * @param CAntObject $parentObj If we are walking down a path tree then this may be the parent
	 * @return mixed|null The value of the field if set, otherwise null on failure
	 */
	public function getValueDeref($fieldPath, $parentObj=null)
	{
		if (empty($fieldPath))
			return null;

		if (!$parentObj)
			$parentObj = $this;

		// Will never start with '.' so we can assume past first position in string if dereferencing
		if (!strpos($fieldPath, '.'))
		{
			return $parentObj->getValue($fieldPath);
		}
		else
		{
			$parts = explode('.', $fieldPath);
			$refObj = null;

			$field = $parentObj->def->getField($parts[0]);
			if ($field && $field->type == 'object' && $field->subtype)
			{
				$oid = $parentObj->getValue($parts[0]);
				if ($oid)
					$refObj = AntObjectLoader::getInstance($this->dbh)->byId($field->subtype, $oid, $this->user);
			}

			// If object is behind the value
			if ($refObj)
			{
				$remPath = "";
				for ($i = 1; $i < count($parts); $i++)
				{
					if ($parts[$i])
					{
						if ($remPath) $remPath .= ".";
						$remPath .= $parts[$i];
					}
				}
				return $this->getValueDeref($remPath, $refObj);
			}
			else
			{
				return null;
			}
		}
	}

	/**
	 * Get all values for this object
	 *
	 * @return array An associative array of all values for this object
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * Get the number of fields in this object
	 *
	 * @return int Number of fields
	 */
	public function getNumFields()
	{
		return count($this->def->getFields());
	}

	/**
	 * Get the title/name of this objects
	 *
	 * If id is passed then this will try to load date from the database
	 *
	 * @param int $oid Alternate id of object to load if not this->id
	 * @return string The name of this object
	 */
	public function getName($oid=null)
	{
		// Get name for this object
		if (!$oid)
		{
			return $this->getValue($this->def->listTitle);
		}

		$ret = "";
		if ($oid)
		{
			$cache = CCache::getInstance();
			$ret = $cache->get($this->dbh->dbname."/object/getname/".$this->object_type."/".$oid);
			
			if (!$ret && $this->def->getField($this->def->listTitle))
			{
				$result = $this->dbh->Query("SELECT " . $this->def->listTitle . " as name FROM " . $this->getObjectTable() . " WHERE id='$oid'");
				if ($this->dbh->GetNumberRows($result))
					$ret = $this->dbh->GetValue($result, 0, "name");

				$cache->set($this->dbh->dbname."/object/getname/".$this->object_type."/".$oid, $ret);
			}
		}

		return $ret;
	}

	/**
	 * Try and get a textual description of this object typically found in fileds named "notes" or "decription"
	 *
	 * @return string The name of this object
	 */
	public function getDesc()
	{
		$fields = $this->def->getFields();
		foreach ($fields as $field=>$fdef)
		{
			if ($fdef->type == 'text')
			{
				if ($field == "description" || $field == "notes" || $field == "details" || $field == "comment")
					return $this->getValue($field);
			}

		}
		return "";
	}

	/**
	 * Get a textual representation of what changed
	 */
	private function getChangeLogDesc()
	{
		$hide = array(
			"revision",
			"uname",
			"num_comments",
			"num_attachments",
		);
		$buf = "";
		foreach ($this->changelog as $fname=>$log)
		{
			$oldVal = $log['oldval'];
			$newVal = $log['newval'];

			$field = $this->def->getField($fname);

			// Skip multi key arrays
			if ($field->type == "object_multi" || $field->type == "fkey_multi")
				continue;

			if ($field->type == "bool")
			{
				if ($oldVal == 't') $oldVal = "Yes";
				if ($oldVal == 'f') $oldVal = "No";
				if ($newVal == 't') $newVal = "Yes";
				if ($newVal == 'f') $newVal = "No";
			}

			if (!in_array($field->name, $hide))
			{
				$buf .= $field->title . " was changed ";
				if ($oldVal)
					$buf .="from \"" . $oldVal . "\" ";
				$buf .= "to \"" . $newVal . "\" \n";
			}
		}

		if (!$buf)
			$buf = "No changes were made";

		return $buf;
	}

	/**************************************************************************
	 * Function: 	fieldValueChanged
	 *
	 * Purpose:		Find out if the value for a field changed
	 **************************************************************************/
	public function fieldValueChanged($checkfield)
	{
		if (!is_array($this->changelog))
			return false;

		foreach ($this->changelog as $fname=>$log)
		{
			if ($fname == $checkfield)
				return true;
		}

		return false;
	}

	/**************************************************************************
	 * Function: 	getMValueExists
	 *
	 * Purpose:		Check if a value exists in a fkey_multi field.
	 * 				Returns true if value exists in field
	 *
	 * Params:		$name = the name of the field to pull
	 * 				$value = the value to look for
	 **************************************************************************/
	function getMValueExists($name, $value)
	{
		foreach ($this->values as $fname=>$fval)
		{
			if ($fname == $name)
			{
				if (is_array($fval))
				{
					foreach ($fval as $mval)
					{
						if ($mval == $value)
							return true;
					}
				}
				else
				{
					if ($fval == $value)
						return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get field type by name
	 *
	 * @param string $name The name of the field
	 * @return array("type"=>,"subtype"=>)
	 */
	public function getFieldType($name)
	{
		$field = $this->def->getField($name);
		if ($field)
		{
			return array(
				"type" => $field->type,
				"subtype" => $field->subtype,
			);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load saved views from the database and from the object definition
	 *
	 * The default status of a view is determined in the following order:
	 * 1. User individual default selection is first
	 * 2. Then team default
	 * 3. Then the system default
	 */
	public function loadViews($fromViewManager=false, $viewId=null)
	{
        $default_set = false;
        $this->views_set = true;
        $meView = array();        
        $userView = array();        
        $teamView = array();
        $everyoneView = array();
        $this->views = array();
        
		$dbh = $this->dbh;
		$user_default = ($this->user) ? $this->user->getSetting("/objects/views/default/".$this->object_type) : null;
		//$users_default_keys = array();

		// Load custom views
		// --------------------------------------------------------------
		$query = "select id, name, description, f_default, user_id, filter_key, report_id, team_id, scope
                from app_object_views where object_type_id='".$this->object_type_id."'";
                
        $whereClause = array();
        $whereClause[] = "(scope = 'everyone')";
		if ($this->user)
		{
			if (is_numeric($this->user->id))
				$whereClause[] = "(user_id='" . $this->user->id . "')";
			if (is_numeric($this->user->teamId))
				$whereClause[] = "(team_id='" . $this->user->teamId . "')";
            
            if($fromViewManager)
                $whereClause[] = "(owner_id='" . $this->user->id . "')";
		}
		
        if(!empty($viewId))
            $whereClause[] = "(id = '$viewId')";
        
        if(sizeof($whereClause) > 0)
            $query .= "and (" . implode("or", $whereClause) . ");";
        
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
        
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);

			$view = new CAntObjectView();
			$view->id = $row['id'];
			$view->name = $row['name'];
			$view->description = $row['description'];
			$view->filterKey = $row['filter_key'];
            $view->scope = $row['scope'];
            $view->f_system = false;
            $view->objDef = $this;
            $view->loadAttribs($dbh);
            
            if ($user_default)
                $view->fDefault = ($user_default==$row['id']) ? true : false;
            else
                $view->fDefault = ($row['f_default'] == 't') ? true : false;
            
            $view->userid = ($row['user_id']) ? $row['user_id'] : null;
            $userObj = new AntUser($this->dbh, $row['user_id']);
            
            switch(strtolower($row['scope']))
            {
                case "user": // Get User Details
                    $view->userName = $userObj->name;
                    $userView[] = $view;
                    break;
                case "me":
                    $view->userName = $userObj->name;
                    $meView[] = $view;
                    break;
                case "team": // Get Team Details
                    $userObj = new AntUser($this->dbh, $row['user_id']);
                    
                    // Manually set users team id
                    $userObj->teamId = $row['team_id'];
                    $view->teamName = $userObj->getTeamName();
                    $view->teamid = $row['team_id'];
                    $teamView[] = $view;
                    break;
                case "everyone":
                default:
                    $view->scope = "Everyone";
                    $view->userName = "All Users";
                    $view->teamName = "All Team";
                    $everyoneView[] = $view;
                    break;
            }

			if ($view->fDefault)
            	$default_set = true;
		}

		// Load system views from the object definition
		// --------------------------------------------------------------
		$views = $this->def->getViews();
		if (count($views))
		{
			foreach ($views as $systemView)
			{
                if(!empty($viewId) && $viewId!==$systemView->id)
                    continue;
                
				$view = new CAntObjectView();
				$view->id = $systemView->id;
				$view->name = $systemView->name;
				$view->description = $systemView->description;

				if ($default_set)
					$view->fDefault = false;
				else if ($user_default)
                	$view->fDefault = ($user_default==$view->id) ? true : false;
				else
					$view->fDefault = $systemView->fDefault;
				$view->userid = null;
				$view->objDef = $this;
				$view->view_fields = $systemView->view_fields;
				$view->conditions = $systemView->conditions;
				$view->sort_order = $systemView->sort_order;
                $view->f_system = true;
                $view->scope = "Everyone";
                $view->userName = "All Users";
                $view->teamName = "All Team";

				if ($view->fDefault)
					$default_set = true;
                    
                $everyoneView[] = $view;
			}
		}

		// Create default view if none exists - fallback if no system or custom views
		// --------------------------------------------------------------
		if (!$default_set)
		{
			$view = new CAntObjectView();
			$view->id = "sys_default";
			$view->name = "Default View";
			$view->description = "";
			$view->fDefault = true;
			$view->userid = null;
            $view->f_system = true;
			$view->objDef = $this;
            
			$fields = $this->def->getFields();
			$i = 0;
			foreach ($fields as $field=>$fdef)
			{
				if ($field == "id" || $fdef->type=='object_multi')
					continue;

				$view->view_fields[] = $field;
				$i++;
				if ($i > 10)
					break;
			}
            
            $everyoneView[] = $view;
		}
        
        $this->views = array_merge($meView, $userView, $teamView, $everyoneView);
	}

	function getNumViews()
	{
		if (!$this->views_set && $this->object_type_id)
			$this->loadViews();

		return count($this->views);
	}

	function getView($ind)
	{
		if (!$this->views_set && $this->object_type_id)
			$this->loadViews();

		return $this->views[$ind];
	}

	/****************************************************************************************
	*	Function:	applyMask
	*
	*	Purpose:	format data for displaying
	****************************************************************************************/
	function applyMask($val, $fdef)
	{
		if (!$val)
			return $val;

		$type_full = $fdef->type.".".$fdef->subtype;
		switch($type_full)
		{
		case 'text.phone':
			$val = $this->removeMask($val, $fdef);

			if(strlen($val) == 7)		
				$val = preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $val);	
			elseif(strlen($val) == 10)
				$val = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $val);

			break;
		case 'timestamp.':
			$ts = @strtotime($val);
			if ($ts)
				$val = date("m/d/Y h:i:s a T", $ts);
			break;
		case 'time.':
			$ts = @strtotime($val);
			if ($ts)
				$val = date("h:i:s a T", $ts);
			break;
		case 'date.':
			$ts = @strtotime($val);
			if ($ts)
				$val = date("m/d/Y", $ts);
			break;
		default:
			break;
		}
	
		return $val;
	}

	/****************************************************************************************
	*	Function:	removeMask
	*
	*	Purpose:	normalize data
	****************************************************************************************/
	function removeMask($val, $fdef)
	{
		if (!$val)
			return $val;

		$type_full = $fdef->type.".".$fdef->subtype;
		switch($type_full)
		{
		case 'text.phone':
			$val = preg_replace("/[^0-9]/", "", $val); 	
			break;
		default:
			break;
		}
	
		return $val;
	}

	/**
	 * Expunge the cache for this object
	 */
	public function clearCache()
	{
		// Legacy
		$this->cache->remove($this->dbh->dbname."/objects/gen/".$this->object_type);

		if ($this->id)
		{
			// Legacy
			$this->cache->remove($this->dbh->dbname."/object/".$this->object_type."/".$this->id."/hascomments");
			$this->cache->remove($this->dbh->dbname."/object/".$this->object_type."/".$this->id);
			$this->cache->remove($this->dbh->dbname."/object/getname/".$this->object_type."/".$this->id);

			// Clear EntityLoader cache
			$sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
			$sl->get("EntityLoader")->clearCache($this->object_type, $this->id);
		}

	}

	/**
	 * Proxy function to clear the definition cache of this object type
	 */
	public function clearDefinitionCache()
	{
		$sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$sl->get("EntityDefinitionLoader")->clearCache($this->object_type);

		/* This is done in the CAntObjectFields class as it should be
		global $G_CACHE_ANTOBJFDEFS;
		$G_CACHE_ANTOBJFDEFS = array();
		$res2 = $this->dbh->Query("select id from app_object_type_fields where type_id='".$this->object_type_id."'");
		$num = $this->dbh->GetNumberRows($res2);
		for ($i = 0; $i < $num; $i++)
		{
			$fid = $this->dbh->GetValue($res2, $i, "id");
			$this->cache->remove($this->dbh->dbname."/objectdefs/fielddefaults/".$this->object_type_id."/$fid");
			$this->cache->remove($this->dbh->dbname."/objectdefs/fieldoptions/".$this->object_type_id."/$fid");
		}
		*/
	}

	/**
	 * Create an association with this object
	 *
	 * @param string $obj_type The name of the object type to associate with this object
	 * @param string $oid The unique id of the object to associate with this object
	 * @param string $field If not the generic 'associations' then define manually
	 */
	public function addAssociation($obj_type, $oid, $field="associations")
	{
		$assoc_obj = new CAntObject($this->dbh, $obj_type, $oid, $this->user);
		$fdef = $this->def->getField($field);

		if ($this->object_type_id && $assoc_obj->object_type_id && $assoc_obj->id && $fdef)
		{
			// fdef 
			if ($fdef->type=='object_multi')
				$this->setMValue($field, $obj_type.":".$oid);
			else if ($fdef->type=='object' && !$fdef->subtype)
				$this->setValue($field, $obj_type.":".$oid);
			else if ($fdef->type=='object' && $fdef->subtype)
				$this->setValue($field, $oid);

			// Update activity associations
			if ($this->object_type != "activity" && $this->id) // no activity of activity - that would be bad
			{
				$otid = objGetAttribFromName($this->dbh, "activity", "id");
				$fid = objGetFldIdFromName($this->dbh, "activity", "obj_reference");
				$result = $this->dbh->Query("select id from objects_activity_act where id in 
												(select object_id from object_associations  where type_id='$otid'
													and assoc_type_id='".$this->object_type_id."' 
													and assoc_object_id='".$this->id."' and field_id='$fid')");
				$num = $this->dbh->GetNumberRows($result);
				for ($i = 0; $i < $num; $i++)
				{
					$aid = $this->dbh->getValue($result, $i, "id");
					$obja = new CAntObject($this->dbh, "activity", $aid, $this->user);
					$obja->addAssociation($obj_type, $oid, "associations");
					$obja->save();
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**************************************************************************
	 * Function: 	removeAssociation
	 *
	 * Purpose:		Remove an association with this object.
	 * 				NOTE: This function must be run AFTER the object has been
	 * 				opened or save. A valid is is absolutely necessary
	 *
	 * Params:		$obj_type = the name of the object type
	 * 				$oid = unique id of the object to reference
	 **************************************************************************/
	function removeAssociation($obj_type, $oid, $field)
	{
		$assoc_obj = new CAntObject($this->dbh, $obj_type, $oid, $this->user);
		$fld = $assoc_obj->def->getField($field);

		if ($this->object_type_id && $this->id && $assoc_obj->object_type_id && $assoc_obj->id && $fld)
		{
			$query = "delete from object_associations where type_id='".$this->object_type_id."' and object_id='".$this->id."'
						and assoc_type_id='".$assoc_obj->object_type_id."' and assoc_object_id='".$assoc_obj->id."' and field_id='".$fld->id."'";
			$this->dbh->Query($query);

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Add a new activity object and associate with this object
	 *
	 * Future Theory: subject (what did the action), verb (what the action was), object (what the verb was performed on), notes
	 * 
	 * @param string $verb The action performed like 'created', 'read', 'updated', 'deleted', 'sent', 'processed', 'completed'
	 * @param string $name The label for this activity
	 * @param string $notes Details for the activity
	 * @param int $type Manually set type if not just an object which is set automatically by the system
	 * @param char $direction Can be 'i' for incoming, 'o' for outgoing and 'n' for none
	 * @param bool $readOnly If true then this activity is not editable
	 * @param int $userId The user that owns this activity
	 * @param int $level Optional log level
	 */
	public function addActivity($verb, $name, $notes, $type=null, $direction='n', $readonly='f', $userid=null, $level=null)
	{
		// We don't add activities of activities - that could create an endless loop
		if ($this->object_type == "activity")
			return;

		if (($this->object_type == "comment") && $this->getValue("obj_reference"))
		{
			$parts = CAntObject::decodeObjRef($this->getValue("obj_reference"));
			if (isset($parts['name']))
				$name = $parts['name'];
			else if ($parts > 1)
				$name = objGetName($this->dbh, $parts['obj_type'], $parts['id'], $this->user);
		}

		$obja = new CAntObject($this->dbh, "activity", null, $this->user);
		$obja->setValue("name", $name);
		$obja->setValue("notes", $notes);
		$obja->setValue("verb", $verb);
		$obja->setValue("direction", $direction);
		$obja->setValue("f_readonly", $readonly);
		$obja->setValue("f_private", ($this->def->isPrivate) ? 't' : 'f');

		if (($this->object_type == "comment") && $this->getValue("obj_reference"))
			$obja->setValue("obj_reference", $this->getValue("obj_reference"));
		else
			$obja->setValue("obj_reference", $this->object_type.":".$this->id);

		//$obja->setValue("object_type", $this->object_type);
		//$obja->setValue("object_id", $this->id);
		if (is_numeric($type))
			$obja->setValue("type_id", $type);
		else
			$obja->setValue("type_id", $this->getActivityTypeFromObj());

		if ($userid)
		{
			$obja->setValue("user_id", $userid);
			$obja->setValue("subject", "user:" . $userid);
		}
		else if ($this->user)
		{
			$obja->setValue("user_id", $this->user->id);
			$obja->setValue("subject", "user:" . $this->user->id);
		}

		// Add associations
		$obja->addAssociation($this->object_type, $this->id, "associations");

		// Copy associations for this object
		$associations = $this->getValue("associations");
		if (is_array($associations) && count($associations))
		{
			foreach ($associations as $assoc)
			{
				$parts = explode(":", $assoc);
				if (count($parts)==2)
				{
					$obja->addAssociation($parts[0], $parts[1], "associations");
				}
			}
		}

		// If we're working with a comment copy attachments
		if ("comment" == $this->object_type)
		{
			$attachments = $this->getValue("attachments");
			if ($attachments)
			{
				foreach ($attachments as $attid)
					$obja->setMValue("attachments", $attid);
			}
		}

		// Now associate with all referenced objects
		$fields = $this->def->getFields();
		foreach ($fields as $field=>$fdef)
		{
			if ($fdef->type == 'object')
			{
				$val = $this->getValue($field);
				if ($val)
				{
					if ($fdef->subtype)
					{
						$obja->setMValue("associations", $fdef->subtype.":".$val);
					}
					else if (count(explode(":", $val))>1)
					{
						$obja->setMValue("associations", $val);
					}
				}
			}
		}
		if ($this->user)
			$obja->setMValue("associations", "user:".$this->user->id);

		// Now set level
		if ($level == null)
		{
			if ($this->user && $this->user->isSystemUser())
				$level = 1;
			else
				$level = $this->def->defaultActivityLevel;
		}

		$obja->setValue("level", $level);

		$aid = $obja->save();

		return $obja;
	}

	/**************************************************************************
	 * Function: 	getActivityTypeFromObj
	 *
	 * Purpose:		Make sure there is an activity type for every object, this
	 * 				is done dynamically.
	 *
	 * Params:		$obj_type = the name of the object type
	 **************************************************************************/
	function getActivityTypeFromObj()
	{
		$tid = null;

		$result = $this->dbh->Query("select id from activity_types where obj_type='".$this->object_type."'");
		if ($this->dbh->GetNumberRows($result))
		{
			$tid = $this->dbh->GetValue($result, 0, "id");
		}
		else
		{
			$result = $this->dbh->Query("insert into activity_types(obj_type, name) values('".$this->object_type."', '".$this->title."');
										 select currval('activity_types_id_seq') as id;");
			if ($this->dbh->GetNumberRows($result))
				$tid = $this->dbh->GetValue($result, 0, "id");
		}

		return $tid;
	}

	/**
	 * Check to see if object type is private (limited to a user)
	 *
	 * @return bool true if object type is private, otherwise false for global
	 */
	public function isPrivate()
	{
		return $this->def->isPrivate;
	}

	/**
	 * Check to see if an object has comments
	 *
	 * @return bool true if object has objects, false if not
	 */
	public function hasComments()
	{
		if (!$this->id)
			return false;

		$retval = false;

		// First try to use the new num_comments field which should be much faster
		if (is_numeric($this->getValue("num_comments")))
			return ($this->getValue("num_comments") == 0) ? false : true;

		/**
		 * Old way - slow and should go away soon
		 */

		// If this is an activity, then pull cached results for referenced object if available
		$objType = $this->object_type;
		$oid = $this->id;

		if ($objType == "activity" && $this->getValue("obj_reference"))
		{
			$parts = explode(":", $this->getValue("obj_reference"));
			if (count($parts)>=2)
			{
				$objType = $parts[0];
				$oid = $parts[1];
			}
		}

		$res = $this->cache->get($this->dbh->dbname."/object/".$objType."/".$oid."/hascomments");
		if ($res) // 't' || 'f'
		{
			$retval = ($res == 't') ? true : false;
		}
		else
		{
			$objDef = new CAntObject($this->dbh, "comment");

			$query = "select 1 from object_associations
							 where type_id='".$objDef->object_type_id."' 
							 and assoc_type_id='".$this->object_type_id."' and assoc_object_id='".$this->id."'";
			$result = $this->dbh->Query($query);
			if ($this->dbh->GetNumberRows($result))
			{
				$retval = true;
			}
			
			$this->cache->set($this->dbh->dbname."/object/".$this->object_type."/".$this->id."/hascomments", ($retval)?"t":"f");
		}

		return $retval;
	}

	/**
	 * Increment the comments counter
	 *
	 * @param bool $added If true increment, if false then decrement for deleted comment
	 * @param int $numComments Optional manual override to set total number of comments
	 */
	public function setHasComments($added=true, $numComments=null)
	{
		if (!$this->id)
			return false;

		// We used to store a flag in cache, but now we put comment counts in the actual object
		if ($numComments == null)
		{
			$cur = ($this->getValue('num_comments')) ? $this->getValue('num_comments') : 0;
			if ($added)
				$cur += 1;
			else if ($cur > 0)
				$cur -= 1;
		}
		else
		{
			$cur = $numComments;
		}

		$this->setValue("num_comments", $cur);
		$this->save(false); // save but no need to create activity because one will be created for the comment

		// Depricated but leave for legacy comments
		$this->cache->set($this->dbh->dbname."/object/".$this->object_type."/".$this->id."/hascomments", "t");
	}

	/**************************************************************************
	 * Function: 	isLoaded
	 *
	 * Purpose:		Check to see if this object loaded correctly. $this->id will
	 *				be set to null if data is not in database
	 **************************************************************************/
	function isLoaded()
	{
		return ($this->id) ? true : false;
	}

	/**
	 * Create a unique name for this object given the values of the object
	 *
	 * Unique names may only have alphanum chars, no spaces, no special
	 *
	 * @param bool $create If set to false we will not actually save the uname, just retrieve the value if we were to set it
	 */
	public function getUniqueName($create=true)
	{
		$dbh = $this->dbh;

		// If already set then return current value
		if ($this->getValue("uname") || !$create)
			return $this->getValue("uname");

		$uname = "";

		// Get unique name conditions
		$settings = $this->def->unameSettings;

		if ($settings)
		{
			$alreadyExists = false;

			$uriParts = explode(":", $settings);

			// Create desired uname from the right field
			if ($uriParts[count($uriParts)-1] == "name")
				$uname = $this->getName();
			else
				$uname = $this->getValue($uriParts[count($uriParts)-1]); // last one is the uname field

			// The uname must be populated before we try to save anything
			if (!$uname)
				return "";

			// Now escape the uname field to a uri fiendly name
			$uname = strtolower($uname);
			$uname = str_replace(" ", "-", $uname);
			$uname = str_replace("?", "", $uname);
			$uname = str_replace("&", "_and_", $uname);
			$uname = str_replace("---", "-", $uname);
			$uname = str_replace("--", "-", $uname);
			$uname = preg_replace('/[^A-Za-z0-9_-]/', '', $uname);

			$isUnique = $this->verifyUniqueName($uname, false); // Do not reset because that would create a loop

			// If the unique name already exists, then append with id or a random number
			if (!$isUnique)
			{
				$uname .= "-";
				$uname .= ($this->id) ? $this->id : uniqid(); 
			}
		}
		else if ($this->id)
		{
			// uname is required but we are working with objects that do not need unique uri names then just use the id
			$uname = $this->id;
		}
		

		return $uname;
	}

	/**
	 * Make sure that a uname is still unique
	 *
	 * This hsould safe-gard against values being saved in the object that change the namespace
	 * of the unique name causing unique collision.
	 *
	 * @param string $uname The name to test for uniqueness
	 * @param bool $reset If true then reset 'uname' field with new unique name
	 * @return bool true if the name is still unique, false if a duplicate was found
	 */
	public function verifyUniqueName($uname, $reset=false)
	{
		$dbh = $this->dbh;

		if (!$uname)
			return false;

		// If we are not using unique names with this object just succeed
		if (!$this->def->unameSettings)
			return true;

		// Search objects to see if the uname exists
		$olist = new CAntObjectList($this->dbh, $this->object_type, $this->user);
		$olist->addCondition("and", "uname", "is_equal", $uname);

		// Exclude this object from the query because of course it will be a duplicate
		if ($this->id)
			$olist->addCondition("and", "id", "is_not_equal", $this->id);

		// Loop through all namespaces if set with ':' in the settings
		$nsParts = explode(":", $this->def->unameSettings);
		if (count($nsParts) > 1)
		{
			// Use all but last, which is the uname field
			for ($i = 0; $i < (count($nsParts) - 1); $i++)
			{
				$olist->addCondition("and", $nsParts[$i], "is_equal", $this->getValue($nsParts[$i]));
			}
		}

		// Check if any objects match
		$olist->getObjects(0, 1);
		if ($olist->getNumObjects() > 0)
		{
			if ($reset)
			{
				$newname = $this->getUniqueName();
				$this->setValue("uname", $newname);
				return true;
			}
			return false;
		}
		else
		{
			return true;
		}
	}

	/**************************************************************************
	 * Function: 	createObjectTypeIndex
	 *
	 * Purpose:		Create index partition for this object type
	 **************************************************************************/
	function createObjectTypeIndex($idxonly=false)
	{
		$dbh = $this->dbh;

		$tblName = "object_index_".$this->object_type_id;
		if (!$idxonly)
			$dbh->Query("CREATE TABLE $tblName(CHECK(object_type_id='".$this->object_type_id."' and f_deleted='f')) INHERITS (object_index);");
		$dbh->Query("CREATE INDEX ".$tblName."_oid_idx
					  ON $tblName
					  USING btree
					  (object_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_ofid_idx
					  ON $tblName
					  USING btree
					  (field_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_vnum_idx
					  ON $tblName
					  USING btree (val_number)
					  where val_number is not null;");
		$dbh->Query("CREATE INDEX ".$tblName."_vtext_idx
					  ON $tblName
					  USING btree (lower(val_text))
					  where val_text is not null;");
		$dbh->Query("CREATE INDEX ".$tblName."_vtime_idx
					  ON $tblName
					  USING btree (val_timestamp)
					  where val_timestamp is not null;");
		$dbh->Query("CREATE INDEX ".$tblName."_tsv_idx
					  ON $tblName
					  USING gin (val_tsv)
					  with (FASTUPDATE=ON)
					  where val_tsv is not null;");

		$tblName = "object_index_".$this->object_type_id."_del";
		if (!$idxonly)
			$dbh->Query("CREATE TABLE $tblName(CHECK(object_type_id='".$this->object_type_id."' and f_deleted='t')) INHERITS (object_index);");
		$dbh->Query("CREATE INDEX ".$tblName."_oid_idx
					  ON $tblName
					  USING btree
					  (object_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_ofid_idx
					  ON $tblName
					  USING btree (field_id)
					  where field_id is not null;");
		$dbh->Query("CREATE INDEX ".$tblName."_vnum_idx
					  ON $tblName
					  USING btree (val_number)
					  where val_number is not null;");
		$dbh->Query("CREATE INDEX ".$tblName."_vtext_idx
					  ON $tblName
					  USING btree (lower(val_text))
					  where val_text is not null;");
		$dbh->Query("CREATE INDEX ".$tblName."_vtime_idx
					  ON $tblName
					  USING btree
					  (val_timestamp)
					  where val_timestamp is not null;");
		$dbh->Query("CREATE INDEX ".$tblName."_tsv_idx
					  ON $tblName
					  USING gin (val_tsv)
					  with (FASTUPDATE=ON)
					  where val_tsv is not null;");
	}

	/**************************************************************************
	 * Function: 	createObjectTypeIndex
	 *
	 * Purpose:		Create index partition for this object type
	 **************************************************************************/
	function createObjectAssocTbl()
	{
		$tblName = "object_assoc_".$this->object_type_id;
		$dbh->Query("CREATE TABLE $tblName(CHECK(object_type_id='".$this->object_type_id."')) INHERITS (object_assoc);");

		$dbh->Query("CREATE INDEX ".$tblName."_oid_idx
					  ON object_assoc
					  USING btree
					  (object_id);");

		$dbh->Query("CREATE INDEX ".$tblName."_fld_idx
					  ON object_assoc
					  USING btree
					  (object_id , field_id);");

		$dbh->Query("CREATE INDEX ".$tblName."_targetobjtype_idx
					  ON object_assoc
					  USING btree
					  (target_type_id, field_id);");

		$dbh->Query("CREATE INDEX ".$tblName."_targetobj_idx
					  ON object_assoc
					  USING btree
					  (target_type_id , target_object_id , field_id);");

	}

	/**************************************************************************
	 * Function: 	createObjectTypeTable
	 *
	 * Purpose:		Create inherited object table for this object type
	 **************************************************************************/
	function createObjectTypeTable()
	{
		$dbh = $this->dbh;


		// Create objects table
		// -----------------------------------------------

		// Active
		$tblName = "objects_".$this->object_type_id;
		$dbh->Query("CREATE TABLE $tblName(CHECK(type_id='".$this->object_type_id."' and f_deleted='f')) INHERITS (objects);");
		$dbh->Query("CREATE INDEX ".$tblName."_otid_idx
					  ON $tblName
					  USING btree
					  (type_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_oid_idx
					  ON $tblName
					  USING btree
					  (object_id);");

		// Deleted/Archived
		$tblName = "objects_".$this->object_type_id."_del";
		$dbh->Query("CREATE TABLE $tblName(CHECK(type_id='".$this->object_type_id."' and f_deleted='t')) INHERITS (objects);");
		$dbh->Query("CREATE INDEX ".$tblName."_otid_idx
					  ON $tblName
					  USING btree
					  (type_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_oid_idx
					  ON $tblName
					  USING btree
					  (object_id);");

		// Create object data table
		// -----------------------------------------------
		
		// Active
		$tblName = "object_data_".$this->object_type_id;
		$dbh->Query("CREATE TABLE $tblName(CHECK(type_id='".$this->object_type_id."' and f_deleted='f')) INHERITS (object_data);");
		$dbh->Query("CREATE INDEX ".$tblName."_otid_idx
					  ON $tblName
					  USING btree
					  (type_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_oid_idx
					  ON $tblName
					  USING btree
					  (object_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_fid_idx
					  ON $tblName
					  USING btree
					  (field_id);");

		// Deleted/Archived
		$tblName = "object_data_".$this->object_type_id."_del";
		$dbh->Query("CREATE TABLE $tblName(CHECK(type_id='".$this->object_type_id."' and f_deleted='t')) INHERITS (object_data);");
		$dbh->Query("CREATE INDEX ".$tblName."_otid_idx
					  ON $tblName
					  USING btree
					  (type_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_oid_idx
					  ON $tblName
					  USING btree
					  (object_id);");
		$dbh->Query("CREATE INDEX ".$tblName."_fid_idx
					  ON $tblName
					  USING btree
					  (field_id);");
	}

	/**
	 * For get the id of the current index
	 *
	 * @return int
	 */
	public function getIndexTypeId()
	{
		$types = CAntObjectIndex::getIndexTypes();

		foreach ($types as $id=>$name)
		{
			if ($name == $this->indexType)
				return $id;
		}
		
		return 1; // Default to db which is always type 1
	}

	/**
	 * Retrieve or create new recurrance pattern
	 *
	 * @return CRecurrencePattern
	 */
	public function getRecurrencePattern($rid=null)
	{
		if ($rid)
		{
			$this->recurrencePattern = new CRecurrencePattern($this->dbh, $rid);
		}
		else if ($this->recurrencePattern == null && $this->def->recurRules != null)
		{
			if (is_numeric($this->getValue($this->def->recurRules['field_recur_id'])))
				$rid = $this->getValue($this->def->recurRules['field_recur_id']);

			$this->recurrencePattern = new CRecurrencePattern($this->dbh, $rid);
			$this->recurrencePattern->object_type_id = $this->object_type_id;
			$this->recurrencePattern->object_type = $this->object_type;
			$this->recurrencePattern->parentId = $this->id;
			$this->recurrencePattern->fieldDateStart = $this->def->recurRules['field_date_start'];
			$this->recurrencePattern->fieldTimeStart = $this->def->recurRules['field_time_start'];
			$this->recurrencePattern->fieldDateEnd = $this->def->recurRules['field_date_end'];
			$this->recurrencePattern->fieldTimeEnd = $this->def->recurRules['field_time_end'];
		}
		
		return $this->recurrencePattern;
	}

	/**
	 * Find out if this is part of a recurring series
	 *
	 * @return bool true if it is part of a series, otherwise false
	 */
	public function isRecurring()
	{
		if ($this->recurrencePattern != null)
			return true;

		if ($this->def->recurRules != null)
		{
			if (is_numeric($this->getValue($this->def->recurRules['field_recur_id'])))
				return true;
		}

		return false;
	}

	/**
	 * Proxy to add a field to the definition
	 *
	 * @param string $fname The name of the field to add
	 * @param array $fdef The array definition of the field to add
	 */
	public function addField($fname, $fdef)
	{
		$field = new Netric\EntityDefinition\Field();
		$field->name = $fname;
		$field->fromArray($fdef);
		$this->def->addField($field);

        $sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$dm = $sl->get("EntityDefinition_DataMapper");
		$dm->save($this->def);

		/*
		if(isset($fdef['use_when']) && $fdef['use_when'])
		{
			$parts = explode(":", $fdef['use_when']);
			if (count($parts) > 1)
				$fname .= "_".$parts[0]."_".$this->fields->escapeUseWithFieldVal($parts[1]);
		}

		$this->fields->createObjFields(array($fname=>$fdef));

		// Force reload of fields
		$this->fields = AntObjectDefLoader::getInstance($this->dbh)->getDef($this->object_type);
		 */

		return $field->name;
	}

	/**
	 * Proxy to remove a field from the definition
	 *
	 * @param string $fname The name of the field to add
	 */
	public function removeField($fname)
	{
		$this->def->removeField($fname);
        $sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$dm = $sl->get("EntityDefinition_DataMapper");
		$dm->save($this->def);

		/*
		$this->fields->removeField($fname);

		// Force reload of fields
		$this->fields = AntObjectDefLoader::getInstance($this->dbh)->getDef($this->object_type);
		 */

		return true;
	}

	/**
	 * Get appropriate UIML to build a form based on params
	 *
	 * @param AntUser $user used to get user or team specific UIML
	 * @param int $mobile set to 1 if we are using a mobile interface
	 */
	public function getUIML($user=null, $scope='')
	{
		$dbh = $this->dbh;

		if ('mobile' == $scope)
		{
			$ret = "";
			
			// Check for custom mobile form
			if ("" == $ret)
			{
				$result = $dbh->Query("select form_layout_xml from app_object_type_frm_layouts
												where scope='mobile' and 
												type_id='".$this->object_type_id."';");
				if ($dbh->GetNumberRows($result))
				{
					$val = $dbh->GetValue($result, 0, "form_layout_xml");
					if ($val && $val!="*")
						$ret = $val;
				}
			}
			
			// Get default
			if ("" == $ret)
				$ret = $this->def->getForm("mobile");
		}
		else if ('infobox' == $scope)
		{
			$ret = "";
			
			// Check for custom mobile form
			if ("" == $ret)
			{
				$result = $dbh->Query("select form_layout_xml from app_object_type_frm_layouts
												where scope='infobox' and 
												type_id='".$this->object_type_id."';");
				if ($dbh->GetNumberRows($result))
				{
					$val = $dbh->GetValue($result, 0, "form_layout_xml");
					if ($val && $val!="*")
						$ret = $val;
				}
			}
			
			// Get default
			if ("" == $ret)
				$ret = $this->def->getForm("infobox");

			// Create simple xml for infobox
			if ("*" == $ret && $this->def->listTitle)
			{
				$ret = "<row>";
				$ret .= "<column width='50px'><icon width='48' /></column>";

				$ret .= "<column passing='0'><header field='" . $this->def->listTitle . "' /></column>";
				$ret .= "</row>";
			}
		}
		else
		{	
			$ret = "";

			// Check for user specific form
			if ("" == $ret)
			{
				$result = $dbh->Query("select form_layout_xml from app_object_type_frm_layouts
												where user_id='".$user->id."' and 
												type_id='".$this->object_type_id."';");
				if ($dbh->GetNumberRows($result))
				{
					$val = $dbh->GetValue($result, 0, "form_layout_xml");
					if ($val && $val!="*")
						$ret = $val;
				}
			}
			
			// Check for team specific form
			if ("" == $ret && $user->teamId)
			{
				$result = $dbh->Query("select form_layout_xml from app_object_type_frm_layouts
												where team_id='".$user->teamId."' and 
												type_id='".$this->object_type_id."';");
				if ($dbh->GetNumberRows($result))
				{
					$val = $dbh->GetValue($result, 0, "form_layout_xml");
					if ($val && $val!="*") // wildcard is legacy but should be interpreted as 'default'
						$ret = $val;
				}
			}

			// Check for custom default form
			if ("" == $ret)
			{
				$result = $dbh->Query("select form_layout_xml from app_object_type_frm_layouts
												where scope='default' and 
												type_id='".$this->object_type_id."';");
				if ($dbh->GetNumberRows($result))
				{
					$val = $dbh->GetValue($result, 0, "form_layout_xml");
					if ($val && $val!="*")
						$ret = $val;
				}
			}
			
			// Get default
			if ("" == $ret)
				$ret =  $this->def->getForm("default");
		}
		return $ret;
	}

	/**
	 * Get grouping data from a path
	 *
	 * @param string $fieldName The field containing the grouping information
	 * @param string $nameValue The unique value of the group to retrieve
	 * @return array See getGroupingData return value for definition of grouping data entries
	 */
	public function getGroupingEntryByPath($fieldName, $path)
	{
		$parts = explode("/", $path);
		$ret = null;

		// Loop through the path and get the last entry
		foreach ($parts as $grpname)
		{
			if ($grpname)
			{
				$parent = ($ret) ? $ret['id'] : "";
				$ret = $this->getGroupingEntryByName($fieldName, $grpname, $parent);
			}
		}

		return $ret;
	}

	/**
	 * Get grouping path by id
	 *
	 * Grouping paths are constructed using the parent id. For instance Inbox/Subgroup would be constructed
	 * for a group called "Subgroup" whose parent group is "Inbox"
	 *
	 * @param string $fieldName The field containing the grouping information
	 * @param string $gid The unique id of the group to get a path for
	 * @return string The full path of the heiarchy
	 */
	public function getGroupingPath($fieldName, $gid)
	{
		$grp = $this->getGroupingById($fieldName, $gid);

		$path = "";

		if ($grp['parent_id'])
			$path .= $this->getGroupingPath($fieldName, $grp['parent_id']) . "/";

		$path .= $grp['title'];

		return $path;
	}

	/**
	 * Retrive grouping data by a unique name
	 *
	 * @param string $fieldName The field containing the grouping information
	 * @param string $nameValue The unique value of the group to retrieve
	 * @param int $paren Optional parent id for querying unique names of sub-groupings
	 * @return array See getGroupingData return value for definition of grouping data entries
	 */
	public function getGroupingEntryByName($fieldName, $nameValue, $parent="")
	{
		$dat = $this->getGroupingData($fieldName, array(), array(), 1, $parent, $nameValue);
		
        if(sizeof($dat) > 0)
            return $dat[0];
        else
            return null;
	}

	/**
	 * Get data for a grouping field (fkey)
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @param array $conditions Array of conditions used to slice the groupings
	 * @param string $parent the parent id to query for subvalues
	 * @param string $nameValue namevalue to query for a single grouping by name
	 * @return array of grouping in an associate array("id", "title", "viewname", "color", "system", "children"=>array)
	 */
	public function getGroupingData($fieldName, $conditions=array(), $filter=array(), $limit=500, $parent="", $nameValue=null, $prefix="")
	{
		$data = array();
		$field = $this->def->getField($fieldName);
		
		// If field is not found in the new EntityDefinition then no need to continue
		if(!isset($field)) {
			return false;
		}

		if (isset($field->type) && $field->type != "fkey" && $field->type != "fkey_multi")
			return false;

		$dbh = $this->dbh;

		$query = "SELECT * FROM ". $field->subtype;

		if ($field->subtype == "object_groupings")
			$cnd = "object_type_id='".$this->object_type_id."' and field_id='".$field->id."' ";
		else
			$cnd = "";

		// Check filters to refine the results - can filter by parent object like project id for cases or tasks
		if (isset($field->fkeyTable['filter']))
		{
			foreach ($field->fkeyTable['filter'] as $referenced_field=>$object_field)
			{
				if (($referenced_field=="user_id" || $referenced_field=="owner_id") && isset($filter[$object_field]))
					$filter[$object_field] = $this->user->id;

				if (isset($filter[$object_field]))
				{
					if ($cnd) $cnd .= " and ";

					// Check for parent
					$obj_rfield = $this->def->getField($object_field);
					if ($obj_rfield->fkeyTable && $obj_rfield->fkeyTable['parent'])
					{
						if ($obj_rfield->type == "object")
						{
							$refo = new CAntObject($dbh, $obj_rfield->subtype);
							$tbl = $refo->object_table;
						}
						else
							$tbl = $obj_rfield->subtype;

						$root = objFldHeiarchRoot($dbh, $obj_rfield->fkeyTable['key'], 
													$obj_rfield->fkeyTable['parent'], 
													$tbl, $filter[$object_field]);
						if ($root && $root!=$filter[$object_field])
						{
							$cnd .= " ($referenced_field='".$filter[$object_field]."' or $referenced_field='".$root."')";
						}
						else
						{
							$cnd .= " $referenced_field='".$filter[$object_field]."' ";
						}
					}
					else
					{
						$cnd .= " $referenced_field='".$filter[$object_field]."' ";
					}
				}
			}
		}
        
		// Filter results to this user of the object is private
		if ($this->def->isPrivate && $this->user)
		{
			if ($dbh->ColumnExists($field->subtype, "owner_id"))
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= "owner_id='".$this->user->id."' ";
			}
			else if ($dbh->ColumnExists($field->subtype, "user_id"))
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= "user_id='".$this->user->id."' ";
			}
		}

		if (isset($field->fkeyTable['parent']))
		{
			if ($parent)
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= $field->fkeyTable['parent']."='".$parent."' ";
			}
			else if(!empty($field->fkeyTable['parent']))
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= $field->fkeyTable['parent']." is null ";
			}
		}

		if ($nameValue)
		{
			if ($cnd) $cnd .= " and ";
			$cnd .= "lower(" . $field->fkeyTable['title'] . ")='".strtolower($dbh->Escape($nameValue))."'";
		}
        
        // Add conditions for advanced filtering
        if(isset($conditions) && is_array($conditions))
        {
            foreach($conditions as $cond)
                $cnd .= $cond['blogic'] . " " . $cond['field'] . " " .  $cond['operator'] . " " .  $cond['condValue'] . " ";
        }

		if ($cnd)
			$query .= " WHERE $cnd ";

		if ($dbh->ColumnExists($field->subtype, "sort_order"))
			$query .= " ORDER BY sort_order, ".(($field->fkeyTable['title']) ? $field->fkeyTable['title'] : $field->fkeyTable['key']);
		else
			$query .= " ORDER BY ".(($field->fkeyTable['title']) ? $field->fkeyTable['title'] : $field->fkeyTable['key']);

		if ($limit) $query .= " LIMIT $limit";

		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
            
			$item = array();
			$viewname = $prefix.str_replace(" ", "_", str_replace("/", "-", $row[$field->fkeyTable['title']]));
            
			$item['id'] = $row[$field->fkeyTable['key']];
			$item['uname'] = $row[$field->fkeyTable['key']]; // groupings can/should have a unique-name column
			$item['title'] = (isset($field->fkeyTable['title'])) ? $row[$field->fkeyTable['title']] : null;
			$item['heiarch'] = (isset($field->fkeyTable['parent'])) ? true : false;
			$item['parent_id'] = (isset($field->fkeyTable['parent']) && isset($row[$field->fkeyTable['parent']]))
                ? $row[$field->fkeyTable['parent']] : null;
			$item['viewname'] = $viewname;
			$item['color'] = isset($row['color']) ? $row['color'] : null;
			$item['f_closed'] = (isset($row['f_closed']) && $row['f_closed']=='t') ? true : false;
            $item['system'] = (isset($row['f_system']) && $row['f_system']=='t') ? true : false;
            
            if(isset($row['type']))
                $item['type'] = $row['type'];
                
            if(isset($row['mailbox']))
                $item['mailbox'] = $row['mailbox'];
            
            if(isset($row['sort_order']))
                $item['sort_order'] = $row['sort_order'];

			if(isset($field->fkeyTable['parent']) && $field->fkeyTable['parent'])
				$item['children'] = $this->getGroupingData($fieldName, $conditions, $filter, $limit, $row[$field->fkeyTable['key']], null, $prefix."&nbsp;&nbsp;&nbsp;");
			else
				$item['children'] = array();

			// Add all additional fields which are usually used for filters
			foreach ($row as $pname=>$pval)
			{
				if (!isset($item[$pname]))
					$item[$pname] = $pval;
			}

			$data[] = $item;
		}
        
		// Make sure that default groupings exist (if any)
		if (!$parent && sizeof($conditions) == 0) // Do not create default groupings if data is filtered
			$ret = $this->verifyDefaultGroupings($fieldName, $data, $nameValue);
		else
			$ret = $data;
            
		return $ret;
	}

	/**
	 * Insert a new entry into the table of a grouping field (fkey)
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @param string $title the required title of this grouping
	 * @param string $parentId the parent id to query for subvalues
	 * @param bool $system If true this is a system group that cannot be deleted
	 * @param array $args Optional arguments
	 * @return array ("id", "title", "viewname", "color", "system", "children"=>array) of newly created grouping entry
	 */
	public function addGroupingEntry($fieldName, $title, $color="", $sortOrder=1, $parentId="", $system=false, $args=array())
	{
		$field = $this->def->getField($fieldName);

		if ($field->type != "fkey" && $field->type != "fkey_multi")
			return false;

		if (!$field)
			return false;


		// Handle hierarchical title - relative to parent if set
		if (strpos($title, "/"))
		{
			$parentPath = substr($title, 0, strrpos($title, '/'));
			$pntGrp = $this->getGroupingEntryByPath($fieldName, $parentPath);
			if (!$pntGrp) // go back a level and create parent - recurrsively
			{
				$this->addGroupingEntry($fieldName, $parentPath);
				$pntGrp = $this->getGroupingEntryByPath($fieldName, $parentPath);
			}

			$parentId = $pntGrp['id'];
			$title = substr($title, strrpos($title, '/')+1);
		}

		// Check to see if grouping with this name already exists
		if (!isset($args['no_check_existing'])) // used to limit infinite loops
		{
			$exGrp = $this->getGroupingEntryByName($fieldName, $title, $parentId);
			if (is_array($exGrp))
			{
				return $exGrp;
				// TODO: The below code is problematic - we should always return the existing group if if available
				// @author joe <sky.stebnicki@aereus.com>
				/*
				if($args['type']=="imap")
					return $exGrp;
				else
					return false;
				*/
			}
		}

		$fields = array();
		$values = array();

		if ($title && $field->fkeyTable['title'])
		{
			$fields[] = $field->fkeyTable['title'];
			$values[] = "'".$this->dbh->Escape($title)."'";
		}

		if ($system && $this->dbh->ColumnExists($field->subtype, "f_system"))
		{
			$fields[] = "f_system";
			$values[] = "'t'";
		}

		if ($color && $this->dbh->ColumnExists($field->subtype, "color"))
		{
			$fields[] = "color";
			$values[] = "'".$this->dbh->Escape($color)."'";
		}

		if ($sortOrder && $this->dbh->ColumnExists($field->subtype, "sort_order"))
		{
			$fields[] = "sort_order";
			$values[] = $this->dbh->EscapeNumber($sortOrder);
		}

		if (isset($field->fkeyTable['parent']) && $parentId)
		{
			$fields[] = $field->fkeyTable['parent'];
			$values[] = $this->dbh->EscapeNumber($parentId);
		}

		if ($field->subtype == "object_groupings")
		{
			$fields[] = "object_type_id";
            $values[] = "'".$this->object_type_id."'";
            
			$fields[] = "field_id";
			$values[] = "'".$field->id."'";
		}

		if (($this->def->isPrivate || $field->subtype == "object_groupings") && $this->user)
		{
			if ($this->dbh->ColumnExists($field->subtype, "owner_id"))
			{
				$fields[] = "owner_id";
				$values[] = $this->dbh->EscapeNumber($this->user->id);
			}
			else if ($this->dbh->ColumnExists($field->subtype, "user_id"))
			{
				$fields[] = "user_id";
				$values[] = $this->dbh->EscapeNumber($this->user->id);
			}
		}
        
        if (isset($args['type']))
        {
            $fields[] = "type";
            $values[] = "'".$this->dbh->Escape($args['type'])."'";
        }
        
        if (isset($args['mailbox']))
        {
            $fields[] = "mailbox";
            $values[] = "'".$this->dbh->Escape($args['mailbox'])."'";
        }
        
        if (isset($args['feed_id']))
        {
            $fields[] = "feed_id";
            $values[] = "'".$this->dbh->Escape($args['feed_id'])."'";
        }

		// Execute query
		if (sizeof($fields) > 0)
		{
            $query = "INSERT INTO ".$field->subtype."(" . implode(", ", $fields) . ") VALUES(" . implode(", ", $values) . "); 
                                      SELECT currval('".$field->subtype."_id_seq') as id;";
			$ret = $this->dbh->Query($query);
			if ($this->dbh->GetNumberRows($ret))
			{
				$eid = $this->dbh->GetValue($ret, 0, "id");

				$item = array();
				$item['id'] = $eid;
				$item['title'] = $title;
				$item['heiarch'] = isset($field->fkeyTable['parent']) ? true : false;
				$item['parent_id'] = $parentId;
				$item['viewname'] = $title;
				$item['color'] = $color;
				$item['system'] = $system;
                
                if (isset($args['type']))
                    $item['type'] = $args['type'];
                    
                if (isset($args['mailbox']))
                    $item['mailbox'] = $args['mailbox'];

				// Update sync stats
				$this->updateObjectSyncStat('c', $fieldName, $eid);

				return $item;
			}
		}

		return false;
	}

    /**
     * Get the grouping entry by id
     *
     * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
     * @param int $entryId the id to delete
     * @return bool true on sucess, false on failure
     */
    public function getGroupingById($fieldName, $entryId)
    {
        $field = $this->def->getField($fieldName);

        if ($field->type != "fkey" && $field->type != "fkey_multi")
            return false;

        if (!is_numeric($entryId) || !$field)
            return false;
        
        $ret = array();
        $query = "select * from {$field->subtype} where id='$entryId'";
        $result = $this->dbh->Query($query);
        $num = $this->dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $this->dbh->GetNextRow($result, $i);


			$ret = array();
			$viewname = str_replace(" ", "_", str_replace("/", "-", $row[$field->fkeyTable['title']]));
            
			$ret['id'] = $row[$field->fkeyTable['key']];
			$ret['uname'] = $row[$field->fkeyTable['key']]; // groupings can/should have a unique-name column
			$ret['title'] = $row[$field->fkeyTable['title']];
			$ret['heiarch'] = ($field->fkeyTable['parent']) ? true : false;
			$ret['parent_id'] = $row[$field->fkeyTable['parent']];
			$ret['viewname'] = $viewname;
			$ret['color'] = $row['color'];
			$ret['f_closed'] = (isset($row['f_closed']) && $row['f_closed']=='t') ? true : false;
            $ret['system'] = (isset($row['f_system']) && $row['f_system']=='t') ? true : false;
        }

        return $ret;
    }

	/**
     * Get the grouping full path by id
     *
     * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
     * @param int $entryId the id to get
     * @return string The full path delimited with '/'
     */
    public function getGroupingPathById($fieldName, $entryId)
    {
        $field = $this->def->getField($fieldName);

        if ($field->type != "fkey" && $field->type != "fkey_multi")
            return false;

        if (!is_numeric($entryId) || !$field)
            return false;
        
        $ret = "";
        $query = "SELECT * FROM {$field->subtype} WHERE id='$entryId'";
        $result = $this->dbh->Query($query);
        if ($this->dbh->GetNumberRows($result))
        {
            $row = $this->dbh->GetNextRow($result, 0);

			if ($row[$field->fkeyTable['parent']])
				$ret = $this->getGroupingPathById($fieldName, $row[$field->fkeyTable['parent']]);

			if ($ret)
				$ret .= "/";

			$ret .= $row[$field->fkeyTable['title']];
        }

        return $ret;
    }
    
	/**
	 * Delete and entry from the table of a grouping field (fkey)
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @param int $entryId the id to delete
	 * @return bool true on sucess, false on failure
	 */
	public function deleteGroupingEntry($fieldName, $entryId)
	{
		$field = $this->def->getField($fieldName);

        if (!$field)
        {
            throw new InvalidArgumentException("There is no grouping field called $fieldName in " . $this->object_type);
        }

		if ($field->type != "fkey" && $field->type != "fkey_multi")
			return false;

		if (!is_numeric($entryId) || !$field)
			return false;

		// First delete child entries
		if (isset($field->fkeyTable['parent']) && $field->fkeyTable['parent'])
		{
            $query = "SELECT id FROM ".$field->subtype." WHERE ".$field->fkeyTable['parent']."='$entryId'";
            
			$result = $this->dbh->Query($query);
			$num = $this->dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$this->deleteGroupingEntry($fieldName, $this->dbh->GetValue($result, $i, "id"));
			}
		}

        $query = "DELETE FROM ".$field->subtype." where id='$entryId'";
		$this->dbh->Query($query);

		// Update sync stats
		$this->updateObjectSyncStat('d', $fieldName, $entryId);

		return true;
	}

	/**
	 * Update an entry in the table of a grouping field (fkey)
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @param int $entryId the id to delete
	 * @param string $title the new name of the entry id
	 * @return bool true on sucess, false on failure
	 */
	public function updateGroupingEntry($fieldName, $entryId, $title=null, $color=null, $sortOrder=null, $parentId=null, $system=null)
	{
		if (!is_numeric($entryId))
			return false;

		$field = $this->def->getField($fieldName);

		if ($field->type != "fkey" && $field->type != "fkey_multi")
			return false;

		$up = "";

		if ($title && $field->fkeyTable['title'])
		{
			if ($up) $up .= ", ";
			$up .= $field->fkeyTable['title']."='".$this->dbh->Escape($title)."'";
		}

		if ($color)
		{
			if ($up) $up .= ", ";
			$up .= "color='".$this->dbh->Escape($color)."'";
		}

		if ($sortOrder && $this->dbh->ColumnExists($field->subtype, "sort_order"))
		{
			if ($up) $up .= ", ";
			$up .= "sort_order=".$this->dbh->EscapeNumber($sortOrder);
		}

		if ($parentId && $field->fkeyTable['parent'])
		{
			if ($up) $up .= ", ";
			$up .= $field->fkeyTable['parent']."=".$this->dbh->EscapeNumber($parentId);
		}

		// Execute query
		if ($up != "")
		{
			$this->dbh->Query("UPDATE ".$field->subtype." SET ".$up." WHERE id='$entryId'");
		}

		// Update sync stats
		$this->updateObjectSyncStat('c', $fieldName, $entryId);

		return true;
	}
    
    /**
     * Save an object
     *
     * @param string $default
     * @param int $mobile
     * @param int $teamId
     * @return int $userId
     * @return string $formLayoutXml
     */
    public function saveForm($objType, $default, $mobile, $teamId, $userId, $formLayoutXml)
    {        
        $otid = objGetAttribFromName($this->dbh, $objType, "id");
        if ($objType)
        {
            $scope = "";
            if($default != null)
            {
                $scope = "default";
                if(!$this->dbh->GetNumberRows($this->dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='default'")))
                {
                    $this->dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, form_layout_xml) values
                                    ('$otid', '$scope', '".$this->dbh->Escape($formLayoutXml)."');");    
                }
                else
                {
                    $this->dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$this->dbh->Escape($formLayoutXml)."' 
                                    where type_id='$otid' and scope='default'");
                }
            }
            if($mobile != null)
            {
                $scope = "mobile";
                if(!$this->dbh->GetNumberRows($this->dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='mobile'")))
                {
                    $this->dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, form_layout_xml) values
                                    ('$otid', '$scope', '".$this->dbh->Escape($formLayoutXml)."');");    
                }
                else
                {
                    $this->dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$this->dbh->Escape($formLayoutXml)."' 
                                    where type_id='$otid' and scope='mobile'");
                }
            }
            if($teamId != null)
            {
                $scope = "team";
                if(!$this->dbh->GetNumberRows($this->dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='team' and team_id='$teamId'")))
                {
                    $this->dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, team_id, form_layout_xml) values
                                    ('$otid', '$scope', '$teamId', '".$this->dbh->Escape($formLayoutXml)."');");    
                }
                else
                {
                    $this->dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$this->dbh->Escape($formLayoutXml)."' 
                                    where type_id='$otid' and scope='team' and team_id='$teamId'");
                }
            }
            if($userId != null)
            {
                $scope = "user";
                if(!$this->dbh->GetNumberRows($this->dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='user' and user_id='$userId'")))
                {
                    $this->dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, user_id, form_layout_xml) values
                                    ('$otid', '$scope', '$userId', '".$this->dbh->Escape($formLayoutXml)."');");    
                }
                else
                {
                    $this->dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$this->dbh->Escape($formLayoutXml)."' 
                                    where type_id='$otid' and scope='user' and user_id='$userId'");
                }
            }
            $ret = 1;
        }
        else
            $ret = "-1";
            
        return $ret;
    }
    
    /**
     * Delete an object
     *
     * @param string $objType
     * @param string $default
     * @param int $mobile
     * @param int $teamId
     * @return int $userId     
     */
    public function deleteForm($objType, $default, $mobile, $teamId, $userId)
    {        
        $otid = objGetAttribFromName($this->dbh, $objType, "id");
        if ($objType)
        {   
            if($default != null)
                $this->dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='default'");
            
            if($mobile != null)
                $this->dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='mobile'");
            
            if($teamId != null)
                $this->dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='team' and team_id='$teamId'");
            
            if($userId != null)
                $this->dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='user' and user_id='$userId'");
            
            $ret = 1;
        }
        else
            $ret = "-1";
            
        return $ret;
    }
    
    /**
     * Get the forms
     *
     * @param string $objType     
     */
    public function getForms($objType)
    {        
        $otid = objGetAttribFromName($this->dbh, $objType, "id");        
        if($objType)
        {
            $result = $this->dbh->Query("select type_id, scope, user_id, team_id from app_object_type_frm_layouts order by id");
            $num = $this->dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $this->dbh->GetRow($result, $i);
                    
                // only return forms with matching type_id
                if($otid == $row['type_id'])
                {
                    if ($ret) 
                        $ret .= ", ";
                
                    $ret .= "[\"".$row['type_id']."\", \"".$row['scope']."\", \"".$row['team_id']."\", \"".UserGetTeamName($this->dbh, $row['team_id'])."\", \"".$row['user_id']."\"]";
                }
            }
            $this->dbh->FreeResults($result);
            $ret = "[".$ret."]";
        }
        else
            $ret = "-1";
            
        return $ret;
    }
    
    /**
     * Save the view
     *
     * @param $array $params     
     */
    public function saveView($params)
    {
        $otid = $this->object_type_id;
        if ($otid)
        {            
            $userId = $this->user->id;
            $description = null;
            $filter_key = null;
            $report_id = null;
            $team_id = null;
            $scope = null;
            $f_default = null;
            
            if(isset($params['user_id']) && !empty($params['user_id']))
                $userId = $params['user_id'];
                
            if(isset($params['description']))
                $description = $params['description'];
                
            if(isset($params['filter_key']))
                $filter_key = $params['filter_key'];
                
            if(isset($params['report_id']))
                $report_id = $params['report_id'];
                
            if(isset($params['team_id']))
                $team_id = $params['team_id'];
                
            if(isset($params['scope']))
                $scope = $params['scope'];
                
            if(isset($params['f_default']))
                $f_default = $params['f_default'];
            
            // owner_id field is the creator's id.
            // user_id field will determine which user should use the object view
            $query = "insert into app_object_views(name, description, filter_key, user_id, object_type_id, report_id, team_id, scope, f_default, owner_id)
                        values('".$this->dbh->Escape($params['name'])."', '".$this->dbh->Escape($description)."', 
                               '".$this->dbh->Escape($filter_key)."', ".$this->dbh->EscapeNumber($userId).", '$otid', ".$this->dbh->EscapeNumber($report_id).",
                               ".$this->dbh->EscapeNumber($team_id).", '".$this->dbh->Escape($scope)."', '".$this->dbh->EscapeBool($f_default)."',
                               " .$this->dbh->EscapeNumber($this->user->id). ");
                        select currval('app_object_views_id_seq') as id;";
                                        
            $result = $this->dbh->Query($query);
            if ($this->dbh->GetNumberRows($result))
                $view_id = $this->dbh->GetValue($result, 0, "id");

            if ($view_id)
            {
                if (isset($params['conditions']) && $params['conditions'] && is_array($params['conditions']))
                {
                    foreach ($params['conditions'] as $id)
                    {
                        $field = $this->def->getField($params['condition_fieldname_'.$id]);

                        if ($field)
                        {
                            $this->dbh->Query("insert into app_object_view_conditions(view_id, field_id, blogic, operator, value)
                                                values('$view_id', '".$field->id."', '".$params['condition_blogic_'.$id]."', 
                                                        '".$params['condition_operator_'.$id]."', '".$params['condition_condvalue_'.$id]."')");
                        }
                    }
                }

                if (isset($params['sort_order']) && $params['sort_order'] && is_array($params['sort_order']))
                {
                    $sort_order = 1;
                    foreach ($params['sort_order'] as $id)
                    {
                        $field = $this->def->getField($params['sort_order_fieldname_'.$id]);

                        if ($field)
                        {
                            $this->dbh->Query("insert into app_object_view_orderby(view_id, field_id, order_dir, sort_order)
                                                values('$view_id', '".$field->id."', '".$params['sort_order_order_'.$id]."', '$sort_order')");
                        }
                        $sort_order++;
                    }
                }

                if (isset($params['view_fields']) && $params['view_fields'] && is_array($params['view_fields']))
                {
                    $sort_order = 1;
                    foreach ($params['view_fields'] as $id)
                    {
                        $field = $this->def->getField($params['view_field_fieldname_'.$id]);

                        if ($field)
                        {
                            $this->dbh->Query("insert into app_object_view_fields(view_id, field_id, sort_order)
                                         values('$view_id', '".$field->id."', '$sort_order')");
                        }
                        $sort_order++;
                    }
                }
            }

            if (isset($params['vid']) && $params['vid'])
                $this->dbh->Query("delete from app_object_views where id='".$params['vid']."'");
                
            if(isset($params['f_default']) && $params['f_default']) // Update other views in the same category with default value to no
                    $this->resetDefaultView($view_id, $params);
        }

        return $view_id;
    }
    
    /**
     * Sets the default value to no
     *
     * @param integer $view_id  Saved view Id
     * @param array $params     Contains array of view data
     */
    private function resetDefaultView($view_id, $params)
    {
        $whereClause = array();
        
        $whereClause[] = "scope = '" . $this->dbh->Escape($params['scope']) . "'";
        $whereClause[] = "object_type_id=" . $this->dbh->EscapeNumber($this->object_type_id);
        switch(strtolower($params['scope']))
        {
            case "user":
                $whereClause[] = "user_id = " . $this->dbh->EscapeNumber($params['user_id']);
                break;
            case "me":
                $whereClause[] = "user_id = '" . $this->user->id . "'";
                $whereClause[] = "owner_id = '" . $this->user->id . "'";
                break;
            case "team":
                $whereClause[] = "team_id = " . $this->dbh->EscapeNumber($params['team_id']);
                break;
            case "everyone":
            default:
                break;
        }
        
        $query = "update app_object_views set f_default = 'f' where id!=$view_id and " . implode(" and ", $whereClause);
        $this->dbh->Query($query);
    }
    
    /**
     * Save the activity type
     *
     * @param string $objType     
     * @param $array $params     
     */
    public function saveActivityType($id, $name)
    {
        if ($id)
        {
            $this->dbh->Query("update activity_types set name='".$this->dbh->Escape($name)."' where id='".$id."'");
            $ret = $id;
        }
        else
        {
            $result = $this->dbh->Query("insert into activity_types(name) values('".$this->dbh->Escape($name)."'); 
                                select currval('activity_types_id_seq') as id;");
            if ($this->dbh->GetNumberRows($result))
                $ret = $this->dbh->GetValue($result, 0, "id");
            else
                $ret = -1;
        }
        
        return $ret;
    }

    /**
     * Get the data for this object in an associative array
     *
     * @return array And associative array of all the data for this object
     */
    public function getDataArray()
    {
		if (!$this->id)
			$this->reloadFVals();

		$data = array();
		$all_fields = $this->def->getFields();

		foreach ($all_fields as $fname=>$fdef)
		{
			$val = $this->getValue($fname);

			if ($fdef->type=='fkey' || $fdef->type=='object' || $fdef->type=='fkey_multi' || $fdef->type=='object_multi')
			{
				$data[$fname."_fval"] = $this->getFVals($fname);
			}
			/*

			if ($fdef->type!='fkey_multi' || $fdef->type!='object_multi')
			{
				if (is_array($val))
				{
					$mvfval = array();
					foreach ($val as $valent)
						$mvfval[$valent] = $this->getForeignValue($fname, $valent);

					$data[$fname."_mvfval"] = $mvfval;
				}
			}
			 */
            
			$data[$fname] = $val;
		}

		$data['hascomments'] = $this->hasComments();

		return $data;
	}

	/**
     * Get the data for revisions of this object in an associative array
     *
	 * @param bool $includeCurrent If set to true the current revision will be included in the results
     * @return array of an associative array of all the data for this object for the revision
     */
    public function getRevisionData($includeCurrent=true)
    {
		$data = array();

		if (!$this->id)
			return $data;

		$dm = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator()->get("Entity_DataMapper");
		$revisions = $dm->getRevisions($this->object_type, $this->id);

		if ($revisions != false)
		{
			foreach ($revisions as $rev=>$ent)
			{
				$data[$rev] = $ent->toArray();
			}
		}


		/*
		$results = $this->dbh->Query("SELECT id, revision FROM object_revisions WHERE 
										object_type_id='".$this->object_type_id."' AND object_id='".$this->id."' ORDER BY revision");
		$num = $this->dbh->GetNumberRows($results);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->GetRow($results, $i);
			$data[$row['revision']] = array();

			$res2 = $this->dbh->Query("select field_name, field_value FROM object_revision_data WHERE revision_id='".$row['id']."'");
			$num2 = $this->dbh->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $this->dbh->GetRow($res2, $j);
				$data[$row['revision']][$row2["field_name"]] = $row2["field_value"];
			}
		}

		// Now get the most recent revision
		$all_fields = $this->def->getFields();
		foreach ($all_fields as $fname=>$fdef)
		{
			$val = $this->getValue($fname);
			$data[$this->revision][$fname] = $val;
		}
		 */

		return $data;
	}

	/**
	 * Insert a new entry into the table of a grouping field (fkey)
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
     * @param array $groupings A list of current groupings
	 * @param array $nameValue Spefic Mailbox Name
	 * @return array of groupings added
	 */
	public function verifyDefaultGroupings($fieldName, $groupings=array(), $nameValue=null)
	{
		$checkfor = $this->getVerifyDefaultGroupingsData($fieldName);

		if (count($checkfor) > 0)
		{
			foreach ($checkfor as $checkbox=>$sortOrder)
			{
				// If $nameValue is set, we should only check for this mailbox, and not include other default mailboxes
				// If we include other default mailboxes, theres a possibility it will create duplicate entry
				if(!empty($nameValue))
				{
					// Skip default mailbox, if its not the same value as $nameValue
					if($nameValue !== $checkbox)
						continue;
				}
				
				$found = false;
				foreach ($groupings as $item)
				{
					$parentId = null;                        
					if(isset($item['parent_id']))
						$parentId = $item['parent_id'];
						
					if($item['title'] == $checkbox && !$parentId && $item['system'])
						$found = true;
				}
				
				if (!$found) // Need to set sort order for default mailboxes - no_check_existing is needed to prevent an infinite loop
					$groupings[] = $this->addGroupingEntry($fieldName, $checkbox, null, $sortOrder, null, true, array("no_check_existing"=>true));
			}
		}

		return $groupings;
	}

	/**
	 * Get what groupings to check for
	 *
	 * This function should be over-ridden by all subclasses to create default user-level groupings.
	 * System wide groupings should be created in system updates to reduce overhead.
	 *
	 * For a working example refer to the CAntObject_EmailMessage example
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @return array of groupings to add or empty if no none
	 */
	public function getVerifyDefaultGroupingsData($fieldName)
	{
		return array();
	}

	/**
	 * @depricated This has been moved to the datamapper
	 *
	 * Check to see if this object id was moved or merged into a different id
	 *
	 * @return bool true if found in moved log, false if not
	 */
	public function checkMoved()
	{
		if (!$this->id)
			return false;

		$result = $this->dbh->Query("SELECT moved_to FROM objects_moved WHERE 
									 object_type_id='".$this->object_type_id."' and object_id='".$this->id."'");
		if ($this->dbh->GetNumberRows($result))
		{
			$id = $this->dbh->GetValue($result, 0, "moved_to");

			// Kill circular references - objects moved to each other
			if (in_array($id, $this->movedToRef))
				return false;

			$this->id = $id;
			$this->movedToRef[] = $id;

			return true;
		}

		return false;
	}

	/**
	 * Set this object as having been moved to another object
	 *
	 * @param int $movedTo The unique id of the object this was moved to
	 * @return bool true on succes, false on failure
	 */
	public function setMovedTo($movedTo)
	{
		if (!$this->id || !is_numeric($movedTo) || $this->id == $movedTo) // never allow circular reference or blank values
			return false;
			
		$this->dbh->Query("INSERT INTO objects_moved(object_type_id, object_id, moved_to) 
							VALUES('".$this->object_type_id."', '".$this->id."', '$movedTo');");

		return true;
	}

	/**
	 * Get icon name if it exists
	 *
	 * First checks to see if the object definition has an icon.
	 *
	 * If no icon defined in the object definition check /images/icons/objects/{objectname}_16 
	 * to see if icons exists for this object type.
	 *
	 * This function may also be overridden by any object subclasses for custom icon handling
	 *
	 * @return string The base name of the icon for this object if it exists
	 */
	public function getIconName()
	{
		if ($this->def->icon)
			return $this->def->icon;

		if (file_exists(APPLICATION_PATH . "/images/icons/objects/" . $this->object_type . "_16.png"))
			return $this->object_type;
	}

	/**
	 * Get full icon path
	 *
	 * @param int $width
	 * @param int $height
	 */
	public function getIcon($width=null, $height=null)
	{
		if ($this->getValue("image_id"))
		{
			$path = "/antfs/images/" . $this->getValue("image_id");
			if ($width || $height)
				$path .= "/" . (($width) ? $width : 0); // enter as null if height id defined
			if ($height)
				$path .= "/" . $height;
		
			return $path;
		}
		else if ($this->getIconName())
		{
			$iconName = $this->getIconName();
			return "/images/icons/objects/" . $iconName . "_16.png";
		}

		return false;
	}

	/**
	 * Encode fval array
	 *
	 * @param string $val The encoded string
	 * @return string
	 */
	private function encodeFval($val)
	{
	}

	/**
	 * Decode fval
	 *
	 * @param string $val The encoded string
	 * @return array on success, null on failure
	 */
	private function decodeFval($val)
	{
		if ($val == null || $val=="")
			return null;
		
		return json_decode($val, true);
		/*
		$obj = json_decode($val);

		if ($obj === false)
			return null;

		$ret = array();
		foreach($obj as $var=>$value) 
		{
			$ret[$var] = $value;
		}

		return $ret;
		*/
	}

	/**
	 * @depricated We now use EntityDefinitionLoader::getBrowserBlankContent
	 *
	 * Get object list blank state html
	 *
	 * This is set when the object definition loads
	 *
	 * @return string The html of the message to be preseted to the user when a list is blank
	public function getBrowserBlankMessage()
	{
		if (file_exists(dirname(__FILE__)."/../objects/olbstate/" . $this->object_type . ".php"))
		{
			$html = file_get_contents(dirname(__FILE__)."/../objects/olbstate/" . $this->object_type . ".php");
		}
		else
		{
			$html = "<div id='divBlankState'>No items found</div>";
		}

		return $html;
	}
	 */

	/**
	 * Check if this objet is deleted
	 *
	 * @return bool true if this object is deleted
	 */
	public function isDeleted()
	{
		return ($this->getValue("f_deleted") == 't') ? true : false;
	}

	/**
	 * Check if the 'f_deleted' status of this object changed since it was last opened
	 *
	 * @return bool true if the status changed
	 */
	public function deletedStatusChanged()
	{
		return $this->fieldValueChanged("f_deleted");
	}

	/**
	 * Get the base url for this account
	 *
	 * @param bool $inclProto Include the protocol in the base url
	 * @return string
	 */
	public function getAccBaseUrl($inclProto=true)
	{
		if (!$this->ant)
			$this->ant = new Ant($this->dbh->accountId);
		
		return $this->ant->getAccBaseUrl($inclProto);
	}

	/**
	 * Update object sync stat
	 *
	 * Devices may register with Netric and once registered all changes of watched object entities are
	 * tracked in the stat table making incremental updates effecient.
	 *
	 * If background processing is enabled, this will be launched as a background process to
	 * keep updates to objects as lean as possible.
	 *
	 * @param string $action Either 'c' for changed or 'd' for deleted
	 * @param string $fieldName Optional grouping field name
	 * @param string $fieldVal If field name is defined, then a value must be added for the entry id
	 */
	public function updateObjectSyncStat($action='c', $fieldName=null, $fieldVal=null)
	{
        /*
         * The below function is not longer needed since we are now using the new EntitySync
         * library which depends on a commit id query and real-time updates for deletion rather than
         * backend stats. See /tests/NetricTest/EntitySync/* for more information.
         * - joe (March 2, 2015)

		// Do not stat activity because it causes a big hit on performance
		if ($this->object_type == "activity")
			return; 

		// Add worker job to log the update in the device stat table
		$data = array(
			"oid"=>$this->id, 
			"obj_type"=>$this->object_type, 
			"field_name"=>$fieldName, 
			"field_val"=>$fieldVal,
			"action"=>$action,
			"debug"=>$this->debug,
			"skipcoll"=>$this->skipObjectSyncStatCol,
		);

		if ($this->def->parentField)
			$data['parent_id'] = $this->getValue($this->def->parentField);

		if (!$this->skipObjectSyncStat)
		{
			require_once("lib/WorkerMan.php");
			$wman = new WorkerMan($this->dbh);
			
			if (AntConfig::getInstance()->obj_sync_lazy_stat)
				$jobid = $wman->runBackground("lib/object/syncstat", serialize($data));
			else
				$jobid = $wman->run("lib/object/syncstat", serialize($data));

			// If we are in hierarchical object and we've moved then delete from the old folder/parent
			if ($this->def->parentField)
			{
				if ($this->fieldValueChanged($this->def->parentField) && $this->changelog[$this->def->parentField]['oldvalraw'])
				{
					$data['revision'] = $this->revision; // log at this revision rather than a specific
					$data['action'] = 'd';
					$data['parent_id']= $this->changelog[$this->def->parentField]['oldvalraw'];

					if (AntConfig::getInstance()->obj_sync_lazy_stat)
						$jobid = $wman->runBackground("lib/object/syncstat", serialize($data));
					else
						$jobid = $wman->run("lib/object/syncstat", serialize($data));
				}
			}

		}
        */
	}

	/**
	 * Set viewed for the current user
	 */
	public function setViewed()
	{
		// Skip the recording of certain types in the recent objects cache
		switch ($this->object_type)
		{
		case 'email_message':
		case 'email_thread':
		case 'activity':
		case 'comment':
		case 'dashboard':
			return;
			break;
		}

		$thisRef = $this->object_type . ":" . $this->id;

		$recent = $this->cache->get($this->dbh->dbname . "/RecentObjects/" . $this->user->id);
		if (is_array($recent))
		{
			// Remove current object if alrady in the list
			for ($i = 0; $i < count($recent); $i++)
			{
				if ($recent[$i] == $thisRef)
					array_splice($recent, $i, 1);
			}

			$recent = array_reverse($recent);
			array_push($recent, $thisRef);
			$recent = array_reverse($recent);

			// Trim to 10 items
			if (count($recent) > 10)
				$recent = array_slice($recent, 0, 10);
		}
		else
		{
			$recent = array($thisRef);
		}

		$this->cache->set($this->dbh->dbname . "/RecentObjects/" . $this->user->id, $recent);
	}
    
    /**
     * Replaces the special characters with blank
     *
     * @param String    $filename   Filename of the file
     */
    public function escapeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $filename);
        
        return $filename;
    }

	/**
	 * Process temporary file uploads and move them into the object folder
	 */
	public function processTempFiles()
	{
		$fields = $this->def->getFields();
		foreach ($fields as $fname=>$fdef)
		{
			if (($fdef->type == 'object' || $fdef->type == 'object_multi') && $fdef->subtype == "file")
			{
				if ($this->fieldValueChanged($fname))
				{
					$antfs = new AntFs($this->dbh); // Open as system to bypass permission limitations

					$files = ($fdef->type == "object") ? array($this->getValue($fname)) : $this->getValue($fname);

					if (is_array($files))
					{
						foreach ($files as $fid)
						{
							$file = $antfs->openFileById($fid);

							// Check to see if the file is a temp file
							if ($file)
							{
								if ($file->isTemp())
								{
									$fldr = $antfs->openFolder("/System/objects/" . $this->object_type . "/" . $this->id, true);
									if ($fldr->id)
										$file->move($fldr);
								}
							}
						}
					}
				}
			}
		}
	}


	/**
	 * Create and return a new object that is a copy of this one but with a new id
	 *
	 * @return CAntObject
	 */
	public function cloneObject()
	{
		$newObj = CAntObject::factory($this->dbh, $this->object_type, null, $this->user);

		$fields = $this->def->getFields();
		foreach ($fields as $fname=>$fdef)
		{
			if ($fdef->type != 'object_multi' && $fname!='id')
				$newObj->setValue($fname, $this->getValue($fname));
		}

		$newObj->save();
		return $newObj;
	}

	/**
	 * Close object references from $fromPid
	 *
	 * This if very often over-ridden in derrived classes
	 *
	 * @param int $fromPid The project to copy references from
	 */
	public function cloneObjectReferences($fromPid)
	{
	}

	/**
	 * Get object ref string for this object
	 *
	 * @return string Encoded object ref string - [obj_type]:[obj_id]:[name]
	 */
	public function getObjRefString()
	{
		if (!$this->id)
			return false;

		return $this->object_type . ":" . $this->id . "|" . $this->getName();
	}

	/**
	 * Static funciton used to decode object reference string
	 *
	 * @param string $value The object ref string - [obj_type]:[obj_id]:[name] (last param is optional)
	 * @return array Assoc array with the following keys: obj_type, id, name
	 */
	static public function decodeObjRef($value)
	{
		$parts = explode(":", $value);
		if (count($parts)>1)
		{
			$ret = array();
			$ret['obj_type'] = $parts[0];

			// Check for full name added after bar '|'
			$parts2 = explode("|", $parts[1]);
			if (count($parts2)>1)
			{
				$ret['id'] = $parts2[0];
				$ret['name'] = $parts2[1];
			}
			else
			{
				$ret['id'] = $parts[1];
			}

			return $ret;
		}
		else
			return null;
	}

	/**
	 * Generic member invitation function
	 *
	 * This will often be over-ridden in derrieved classes, like calendar_event,
	 * but by default it sends an approval request to the member if not the current
	 * logged in member.
	 *
	 * @param string $membersField The field of this object containing members
	 * @param bool $onlyNew Only send inviations to new people, otherwise send updates to all
	 * @return int The number of invitations sent
	 */
	public function sendInvitations($membersField, $onlyNew=false)
	{
		// TODO: send approval request if sent to user
	}

	/**
	 * Get followers of this object
	 *
	 * In the future, we will have a list of followers stored, for now we will get from object subtype=user and
	 * previous comments from users and customers
	 *
	 * @array array of followers in object_type:oid|name form
	 */
	public function getFollowers()
	{
		$ret = array();

		// first try to get all users associated with this object through direct fields
		$all_fields = $this->def->getFields();
		foreach ($all_fields as $fname=>$fdef)
		{
			if ("object" == $fdef->type && "user" == $fdef->subtype)
			{
				$val = $this->getValue($fname);
				if ($val)
					$ret[] = "user:" . $val;
			}
		}

		// Get people notified from comments
		$olist = new CAntObjectList($this->dbh, "comment", $this->user);
		$olist->addCondition("and", "obj_reference", "is_equal", $this->object_type . ":" . $this->id);
		$olist->getObjects();
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$comm = $olist->getObject($i);
			if ($comm->getValue("notified"))
			{
				$notifiedList = explode(",", $comm->getValue("notified"));
				foreach ($notifiedList as $ent)
				{
					if (!in_array(trim($ent), $ret))
						$ret[] = trim($ent);
				}
			}
		}

		return $ret;
	}
}
