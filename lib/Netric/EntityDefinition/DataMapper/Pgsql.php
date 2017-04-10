<?php
/**
 * This is the PostgreSQL implementation of a datamapper
 * 
 * TODO: we are currently porting this over to v4 framework from v3
 * So far it has just been copied and the namespace replaced the prefix name
 *
 * @category	DataMapper
 * @package		Pgsql
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntityDefinition\DataMapper;

use Netric\EntityDefinition;
use Netric\Permissions\Dacl;

class Pgsql extends EntityDefinition\DataMapperAbstract
{
	/**
	 * The database host
	 *
	 * @var string
	 */
	private $host = "";

	/**
	 * The database name
	 *
	 * @var string
	 */
	private $dbname = "";

	/**
	 * The database user name
	 *
	 * @var string
	 */
	private $username = "";

	/**
	 * The database password
	 *
	 * @var string
	 */
	private $password = "";

	/**
	 * Handle to database
	 *
	 * @var \Netric\Db\Pgsql
	 */
	private $dbh = null;
	
	/**
	 * Class constructor
	 * 
	 * @param \Netric\Account\Account $account Account for tennant that we are mapping data for
	 * @param \Netric\Db\Pgsql $dbh Handle to database
	 */
	public function __construct($account, $dbh)
	{
		$this->setAccount($account);

		// Right now we will use the CDatabase class because it is already setup
		// Later we might want to start using direct pgsql api calls
		$this->dbh = $dbh;
	}

	/**
	 * Open an object definition by name
	 *
     * @var string $objType The name of the object type
     * @var string $id The Id of the object
	 * @return DomainEntity
	 */
	public function fetchByName($objType)
	{
		if (!$objType || !is_string($objType))
			throw new \Exception('objType is a required param');

		$dbh = $this->dbh;
		$def = new \Netric\EntityDefinition($objType);


		// Get basic object definition
		// ------------------------------------------------------
		$result = $dbh->query("select 
			id, object_table, revision, title, object_table, f_system, dacl
			from app_object_types where name='$objType'"
		);
		if ($dbh->getNumRows($result))
		{
			$row = $dbh->getRow($result, 0);
			$def->title = $row["title"];
			$def->revision = $row["revision"];
			$def->system = ($row["f_system"] != 'f') ? true : false;
			$def->setId($row["id"]);
			if ($row['object_table'])
				$def->setCustomTable($row['object_table']);

			// Check if this definition has an access control list
			if ($row['dacl']) {
				$daclData = json_decode($row['dacl'], true);
				if ($daclData) {
					$dacl = new Dacl($daclData);
					$def->setDacl($dacl);
				}
			}

			// If this is the first load of this object type and not a custom table
			// then create the object table
			if ($def->revision <= 0 && !$def->useCustomTable)
				$this->save($def);
		}

        // Make sure this a valid definition
        if (!$def->getId())
            throw new \RuntimeException($this->getAccount()->getName() . ":" . $objType . " has no id in " . $dbh->getSchema() . " - " . $dbh->getValue($dbh->query("SHOW search_path;"), 0, "search_path"));


        // Get field definitions
		// ------------------------------------------------------
		$sql = "select * from app_object_type_fields where " .
               "type_id='" . $def->getId() . "' order by title";
        $result = $dbh->query($sql);
		if (!$result)
			throw new \Exception('Could not pull type fields from db for ' . $this->getAccount()->getName() . ":" . $objType . ":" . $dbh->getLastError());

		for ($i = 0; $i < $dbh->getNumRows($result); $i++)
		{
			$row = $dbh->getRow($result, $i);

			$objecTable = $row['subtype'];
			
			// Fix the issue on user files not using the actual object table
			if($row['subtype'] == "user_files")
			{
				$row['fkey_table_title'] = "name";
				$objecTable = "objects_file_act";
			}
				
			// Build field
			$field = new EntityDefinition\Field();
			$field->id = $row['id'];
			$field->name = $row['name'];
			$field->title = $row['title'];
			$field->type = $row['type'];
			$field->subtype = $row['subtype'];
			$field->mask = $row['mask'];
			if ($row['use_when'])
				$field->setUseWhen($row['use_when']);
			$field->required = ($row['f_required']=='t') ? true : false;
			$field->system = ($row['f_system']=='t') ? true : false;
			$field->readonly = ($row['f_readonly']=='t') ? true : false;
			$field->unique = ($row['f_unique']=='t') ? true : false;
																  
			if ($row['type'] == "fkey" || $row['type'] == "object" || $row['type'] == "fkey_multi")
			{
				if ($row['fkey_table_key'])
				{
					$field->fkeyTable = array(
						"key"=>$row['fkey_table_key'], 
						"title"=>$row['fkey_table_title'], 
						"parent"=>$row['parent_field'],
						"filter"=>(($row['filter'])?unserialize($row['filter']):null),
					);

					if ($row['type']=='fkey_multi' && $row['fkey_multi_tbl'])
					{
						$field->fkeyTable['ref_table'] = array(
							"table"=>$row['fkey_multi_tbl'], 
							"this"=>$row['fkey_multi_this'], 
							"ref"=>$row['fkey_multi_ref']
						);
					}
				}

				// Autocreate
				$field->autocreate = ($row['autocreate']=='t') ? true : false;
				$field->autocreatebase = $row['autocreatebase'];
				$field->autocreatename = $row['autocreatename'];
			}

			// Check for default
			$res2 = $dbh->query("select * from app_object_field_defaults where field_id='".$row['id']."'");
			for ($j = 0; $j < $dbh->getNumRows($res2); $j++)
			{
				$row2 = $dbh->getRow($res2, $j);

				$default = array('on'=>$row2['on_event'], 'value'=>$row2['value']);
				if ($row2['coalesce'])
					$default['coalesce'] = unserialize($row2['coalesce']);
				if ($row2['where_cond'])
					$default['where'] = unserialize($row2['where_cond']);

				// Make sure that coalesce does not cause a circular reference to self
				if (isset($default['coalesce']) && $default['coalesce'])
				{
					foreach ($default['coalesce'] as $colfld)
					{
						if (is_array($colfld))
						{
							foreach ($colfld as $subcolfld)
							{
								if ($subcolfld == $row['name'])
								{
									$default = null;
									break;
								}
							}
						}
						else if ($colfld == $row['name'])
						{
							$default = null;
							break;
						}
					}
				}

				$field->default = $default;
			}
			
			// Check for optional vals (drop-down)
			$res2 = $dbh->query("select * from app_object_field_options where field_id='".$row['id']."'");
			for ($j = 0; $j < $dbh->getNumRows($res2); $j++)
			{
				$row2 = $dbh->getRow($res2, $j);
				if (!isset($this->fields[$row['name']]['optional_values']))
					$this->fields[$row['name']]['optional_values'] = array();

				if (!$row2['key'])
					$row2['key'] = $row2['value'];

				if (!$field->optionalValues)
					$field->optionalValues = array();

				$field->optionalValues[$row2['key']] = $row2['value'];
			}

			/*
			 * Check to see if optional values are in a custom table rather than the generic
			 * app_object_field_options table. We are trying to move everything over to the new
			 * generic table but it will take some time.
			 */
			if ($row['type'] === "fkey" && !empty($row['subtype']))
			{
				$resultBackComp = $dbh->query("select * from {$row['subtype']}");
				for ($index = 0; $index < $dbh->getNumRows($resultBackComp); $index++)
				{
					$rowOptionalValue = $dbh->getRow($resultBackComp, $index);
					if (!isset($this->fields[$row['name']]['optional_values']))
						$this->fields[$row['name']]['optional_values'] = array();

					if (!$field->optionalValues)
						$field->optionalValues = array();

					$field->optionalValues[$rowOptionalValue['name']] = $rowOptionalValue['name'];
				}
			}

			$def->addField($field);
		}

		return $def;
	}

	/**
	 * Delete object definition
	 *
	 * @param EntityDefintion $def The definition to delete
	 * @return bool true on success, false on failure
	 */
	public function deleteDef(&$def)
	{
		// System objects cannot be deleted
		if ($def->system)
			return false;

		// Only delete existing types of course
		if (!$def->getId())
			return false;

		// Delete object type entries from the database
		$this->dbh->query("DELETE FROM app_object_type_fields WHERE type_id='" . $def->getId() . "'"); // Will cascade
		$this->dbh->query("DELETE FROM app_object_types WHERE id='" . $def->getId() . "'");

		// Leave object table, it's partitioned and won't hurt anything for now
		// Later we may want a cleanup routine - joe

		return true;
	}
    
	/**
	 * Save a definition
	 *
	 * @param EntityDefintion $def The definition to save
	 * @return string|bool entity id on success, false on failure
	 */
	public function saveDef($def)
	{
		// Define type update
		$data= array(
			"name" => "'" . $def->getObjType() . "'",
			"title" => "'" . $def->title . "'",
			//"revision" => $def->revision + 1, // Increment revision in $def after updates are complete for initializing schema
			// No longer incrementing because this was causing update problems with unit tests and every time a user added a field
			"revision" => $def->revision, // Increment revision in $def after updates are complete for initializing schema
			"object_table" => (($def->isCustomTable()) ? "'" . $def->getTable() . "'" : "NULL"),
			"f_system" => "'" . (($def->system) ? 't' : 'f') . "'",
			"application_id" => ($def->applicationId) ? "'" . $def->applicationId. "'" : 'NULL',
			"capped" => ($def->capped) ? "'" . $def->capped. "'" : 'NULL',
			"dacl" => ($def->getDacl()) ? "'" . json_encode(($def->getDacl()->toArray())) . "'" : "NULL",
		);

		if ($def->getId())
		{
			$query = "";

			foreach ($data as $colName=>$colValue)
			{
				if ($query)
					$query .= ", ";

				$query .= $colName . "=" . $colValue;
			}

			$query  = "UPDATE app_object_types SET " . $query 
					  . " WHERE id='" . $def->getId() . "'";
		}
		else
		{
			$cols = "";
			$vals = "";

			foreach ($data as $colName=>$colValue)
			{
				if ($cols)
				{
					$cols .= ", ";
					$vals .= ", ";
				}

				$cols .= $colName;
				$vals .= $colValue;
			}

			$query = "INSERT INTO app_object_types($cols) VALUES($vals); select currval('app_object_types_id_seq') as id;";
		}

		// Execute query and get id if this is a new type
		$ret = $this->dbh->query($query);
		if ($ret && !$def->getId()) {
			$def->setId($this->dbh->getValue($ret, 0, "id"));
		} else if (!$ret) {
            throw new \RuntimeException("Error saving definition: " . $this->dbh->getLastError());
        }

		// Check to see if this dynamic object has yet to be initilized
		if (!$def->useCustomTable)
			$this->createObjectTable($def->getObjType(), $def->getId());

		// Save and create fields
		$this->saveFields($def);

		// Associate with applicaiton if set
		if ($def->applicationId)
			$this->associateWithApp($def, $def->applicationId);
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
	 * Get data for a grouping field (fkey)
	 *
	 * @param EntityDefintion $def The eneity type definition we are working with
	 * @param EntityDefinition_Field The grouping field
	 * @param array $filter Array of conditions used to slice the groupings
	 * @return array of grouping in an associate array("id", "title", "viewname", "color", "system", "children"=>array)
	 */
	public function getGroupingsData($def, $field, $filter=array())
	{
		$data = array();

		if ($field->type != "fkey" && $field->type != "fkey_multi")
			return false;

		$dbh = $this->dbh;

		$query = "SELECT * FROM ". $field->subtype;

		if ($field->subtype == "object_groupings")
			$cnd = "object_type_id='".$this->object_type_id."' and field_id='".$field->id."' ";
		else
			$cnd = "";

		// Check filters to refine the results - can filter by parent object like project id for cases or tasks
		if ($field->fkeyTable['filter'])
		{
			foreach ($field->fkeyTable['filter'] as $referenced_field=>$object_field)
			{
				if (($referenced_field=="user_id" || $referenced_field=="owner_id") && $filter[$object_field])
					$filter[$object_field] = $this->user->id;

				if ($filter[$object_field])
				{
					if ($cnd) $cnd .= " and ";

					// Check for parent
					$obj_rfield = $this->def->getField($object_field);
					if ($obj_rfield->fkeyTable && $obj_rfield->fkeyTable['parent'])
					{
                        // TODO: We need to get rid of the reference to CAntObject below!!!
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
			if ($dbh->columnExists($field->subtype, "owner_id"))
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= "owner_id='".$this->user->id."' ";
			}
			else if ($dbh->columnExists($field->subtype, "user_id"))
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= "user_id='".$this->user->id."' ";
			}
		}

		if ($field->fkeyTable['parent'])
		{
			if ($parent)
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= $field->fkeyTable['parent']."='".$parent."' ";
			}
			else
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= $field->fkeyTable['parent']." is null ";
			}
		}

		if ($nameValue)
		{
			if ($cnd) $cnd .= " and ";
			$cnd .= "lower(" . $field->fkeyTable['title'] . ")='".strtolower($dbh->escape($nameValue))."'";
		}
        
        // Add conditions for advanced filtering
        if(isset($conditions) && is_array($conditions))
        {
            foreach($conditions as $cond)
                $cnd .= $cond['blogic'] . " " . $cond['field'] . " " .  $cond['operator'] . " " .  $cond['condValue'] . " ";
        }

		if ($cnd)
			$query .= " WHERE $cnd ";

		if ($dbh->columnExists($field->subtype, "sort_order"))
			$query .= " ORDER BY sort_order, ".(($field->fkeyTable['title']) ? $field->fkeyTable['title'] : $field->fkeyTable['key']);
		else
			$query .= " ORDER BY ".(($field->fkeyTable['title']) ? $field->fkeyTable['title'] : $field->fkeyTable['key']);

		if ($limit) $query .= " LIMIT $limit";

		$result = $dbh->query($query);
		$num = $dbh->getNumRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->getRow($result, $i);
            
			$item = array();
			$viewname = $prefix.str_replace(" ", "_", str_replace("/", "-", $row[$field->fkeyTable['title']]));
            
			$item['id'] = $row[$field->fkeyTable['key']];
			$item['uname'] = $row[$field->fkeyTable['key']]; // groupings can/should have a unique-name column
			$item['title'] = $row[$field->fkeyTable['title']];
			$item['heiarch'] = ($field->fkeyTable['parent']) ? true : false;
			$item['parent_id'] = $row[$field->fkeyTable['parent']];
			$item['viewname'] = $viewname;
			$item['color'] = $row['color'];
			$item['f_closed'] = (isset($row['f_closed']) && $row['f_closed']=='t') ? true : false;
            $item['system'] = (isset($row['f_system']) && $row['f_system']=='t') ? true : false;
            
            if(isset($row['type']))
                $item['type'] = $row['type'];
                
            if(isset($row['mailbox']))
                $item['mailbox'] = $row['mailbox'];
            
            if(isset($row['sort_order']))
                $item['sort_order'] = $row['sort_order'];

			if(isset($field->fkeyTable['parent']) && $field->fkeyTable['parent'])
				$item['children'] = $this->getGroupingData($field->name, $conditions, $filter, $limit, $row[$field->fkeyTable['key']], null, $prefix."&nbsp;&nbsp;&nbsp;");
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
			$ret = $this->verifyDefaultGroupings($field->name, $data, $nameValue);
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
			}
		}

		$fields = array();
		$values = array();

		if ($title && $field->fkeyTable['title'])
		{
			$fields[] = $field->fkeyTable['title'];
			$values[] = "'".$this->dbh->escape($title)."'";
		}

		if ($system && $this->dbh->columnExists($field->subtype, "f_system"))
		{
			$fields[] = "f_system";
			$values[] = "'t'";
		}

		if ($color && $this->dbh->columnExists($field->subtype, "color"))
		{
			$fields[] = "color";
			$values[] = "'".$this->dbh->escape($color)."'";
		}

		if ($sortOrder && $this->dbh->columnExists($field->subtype, "sort_order"))
		{
			$fields[] = "sort_order";
			$values[] = $this->dbh->escapeNumber($sortOrder);
		}

		if ($parentId && $field->fkeyTable['parent'])
		{
			$fields[] = $field->fkeyTable['parent'];
			$values[] = $this->dbh->escapeNumber($parentId);
		}

		if ($field->subtype == "object_groupings")
		{
			$fields[] = "object_type_id";
            $values[] = "'".$this->object_type_id."'";
            
			$fields[] = "field_id";
			$values[] = "'".$field->id."'";
		}

		if ($this->def->isPrivate && $this->user)
		{
			if ($this->dbh->columnExists($field->subtype, "owner_id"))
			{
				$fields[] = "owner_id";
				$values[] = $this->dbh->escapeNumber($this->user->id);
			}
			else if ($this->dbh->columnExists($field->subtype, "user_id"))
			{
				$fields[] = "user_id";
				$values[] = $this->dbh->escapeNumber($this->user->id);
			}
		}
        
        if (isset($args['type']))
        {
            $fields[] = "type";
            $values[] = "'".$this->dbh->escape($args['type'])."'";
        }
        
        if (isset($args['mailbox']))
        {
            $fields[] = "mailbox";
            $values[] = "'".$this->dbh->escape($args['mailbox'])."'";
        }
        
        if (isset($args['feed_id']))
        {
            $fields[] = "feed_id";
            $values[] = "'".$this->dbh->escape($args['feed_id'])."'";
        }

		// Execute query
		if (sizeof($fields) > 0)
		{
            $query = "INSERT INTO ".$field->subtype."(" . implode(", ", $fields) . ") VALUES(" . implode(", ", $values) . "); 
                                      SELECT currval('".$field->subtype."_id_seq') as id;";
			$ret = $this->dbh->query($query);
			if ($this->dbh->getNumRows($ret))
			{
				$eid = $this->dbh->getValue($ret, 0, "id");

				$item = array();
				$item['id'] = $eid;
				$item['title'] = $title;
				$item['heiarch'] = ($field->fkeyTable['parent']) ? true : false;
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
        $result = $this->dbh->query($query);
        $num = $this->dbh->getNumRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $this->dbh->getNextRow($result, $i);


			$ret = array();
			$viewname = $prefix.str_replace(" ", "_", str_replace("/", "-", $row[$field->fkeyTable['title']]));
            
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
        $result = $this->dbh->query($query);
        if ($this->dbh->getNumRows($result))
        {
            $row = $this->dbh->getNextRow($result, 0);

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

		if ($field->type != "fkey" && $field->type != "fkey_multi")
			return false;

		if (!is_numeric($entryId) || !$field)
			return false;

		// First delete child entries
		if ($field->fkeyTable['parent'])
		{
            $query = "SELECT id FROM ".$field->subtype." WHERE ".$field->fkeyTable['parent']."='$entryId'";
            
			$result = $this->dbh->query($query);
			$num = $this->dbh->getNumRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$this->deleteGroupingEntry($fieldName, $this->dbh->getValue($result, $i, "id"));
			}
		}

        $query = "DELETE FROM ".$field->subtype." where id='$entryId'";
		$this->dbh->query($query);

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
			$up .= $field->fkeyTable['title']."='".$this->dbh->escape($title)."'";
		}

		if ($color)
		{
			if ($up) $up .= ", ";
			$up .= "color='".$this->dbh->escape($color)."'";
		}

		if ($sortOrder && $this->dbh->columnExists($field->subtype, "sort_order"))
		{
			if ($up) $up .= ", ";
			$up .= "sort_order=".$this->dbh->escapeNumber($sortOrder);
		}

		if ($parentId && $field->fkeyTable['parent'])
		{
			if ($up) $up .= ", ";
			$up .= $field->fkeyTable['parent']."=".$this->dbh->escapeNumber($parentId);
		}

		// Execute query
		if ($up != "")
		{
			$this->dbh->query("UPDATE ".$field->subtype." SET ".$up." WHERE id='$entryId'");
		}

		// Update sync stats
		$this->updateObjectSyncStat('c', $fieldName, $entryId);

		return true;
	}

	/**
	 * Save fields
	 *
	 * @param EntityDefintionn $def The EntityDefinition we are saving
	 */
	private function saveFields(&$def)
	{
		$dbh = $this->dbh;

		// We need to include the removed fields, so it will be permanently removed from the definition
		$fields = $def->getFields(true);

		$sort_order = 1;
		foreach ($fields as $fname=>$field)
		{
			if ($field == null)
			{
				// Delete field
				$this->removeField($def, $fname);
			}
			else
			{
				// Update or add field
				$this->saveField($def, $field, $sort_order);
			}

			$sort_order++;
		}
	}

	/**
	 * Save a field
	 *
	 * @param EntityDefintionn $def The EntityDefinition we are saving
	 * @param EntityDefinition\Field $field The field definition to save
	 * @param int $sort_order The order id of this field
	 */
	private function saveField(&$def, $field, $sort_order)
	{
		$dbh = $this->dbh;

		$fname = $field->name;

		$result = $dbh->query("select id, use_when from app_object_type_fields where name='$fname' and type_id='".$def->getId()."'");
		if ($dbh->getNumRows($result))
		{
			$fid = $dbh->getValue($result, 0, "id");
			$field->id = $fid;
			
			$updateFields = array();
			
			$updateFields[] = "name='$fname'";
			$updateFields[] = "title='" . $dbh->escape($field->title) . "'";
			$updateFields[] = "type='" . $field->type . "'";
			$updateFields[] = "subtype='" . $field->subtype . "'";

			if(isset($field->fkeyTable['key']))
				$updateFields[] = "fkey_table_key='" . $dbh->escape($field->fkeyTable['key']) . "'";
				
			if(isset($field->fkeyTable['title']))
				$updateFields[] = "fkey_table_title='" . $dbh->escape($field->fkeyTable['title']) . "'";
				
			if(isset($field->fkeyTable['parent']))
				$updateFields[] = "parent_field='" . $dbh->escape($field->fkeyTable['parent']) . "'";
				
			if(isset($field->fkeyTable['ref_table']['table']))
				$updateFields[] = "fkey_multi_tbl='" . $dbh->escape($field->fkeyTable['ref_table']['table']) . "'";
				
			if(isset($field->fkeyTable['ref_table']['this']))
				$updateFields[] = "fkey_multi_this='" . $dbh->escape($field->fkeyTable['ref_table']['this']) . "'";
			
			if(isset($field->fkeyTable['ref_table']['ref']))
				$updateFields[] = "fkey_multi_ref='" . $dbh->escape($field->fkeyTable['ref_table']['ref']) . "'";
				
			$updateFields[] = "sort_order='$sort_order'";                    

			$updateFields[] = "autocreate='" . (($field->autocreate) ? 't' : 'f') . "'";
			
			if($field->autocreatebase)
				$updateFields[] = "autocreatebase='" . $dbh->escape($field->autocreatebase) . "'";
				
			if($field->autocreatename)
				$updateFields[] = "autocreatename='" . $dbh->escape($field->autocreatename) . "'";
			
			if($field->getUseWhen())    
				$updateFields[] = "use_when='" . $dbh->escape($field->getUseWhen()) . "'";

			if($field->mask)
				$updateFields[] = "mask='" . $dbh->escape($field->mask) . "'";
				
			if(isset($field->fkeyTable['filter']) && is_array($field->fkeyTable['filter']))
				$updateFields[] = "filter='".$dbh->escape(serialize($field->fkeyTable['filter']))."'";
				
			$updateFields[] = "f_required='" . (($field->required) ? 't' : 'f') . "'";
			$updateFields[] = "f_readonly='" . (($field->readonly) ? 't' : 'f') . "'";
			$updateFields[] = "f_system='" . (($field->system) ? 't' : 'f') . "'";                    
			$updateFields[] = "f_unique='" . (($field->unique) ? 't' : 'f') . "'";
						
			$query = "update app_object_type_fields set " . implode(", ", $updateFields) . "where id='$fid';";
			$dbh->query($query);

			// Save default values
			if ($field->id && $field->default)
			{
                if (!isset($field->default['coalesce']))
                    $field->default['coalesce'] = null;
                
                if (!isset($field->default['where']))
                    $field->default['where'] = null;
                
				$dbh->query("delete from app_object_field_defaults where field_id='" . $field->id . "'");
				$dbh->query("insert into app_object_field_defaults(field_id, on_event, value, coalesce, where_cond) 
								values('" . $field->id . "', '".$field->default['on']."', '".$dbh->escape($field->default['value'])."', 
								'".$dbh->escape(serialize($field->default['coalesce']))."',
								'".$dbh->escape(serialize($field->default['where']))."')");
			}

			// Save field optional values
			if ($field->id && $field->optionalValues)
			{
				$dbh->query("delete from app_object_field_options where field_id='" . $field->id . "'");
				foreach ($field->optionalValues as $okey=>$oval)
				{
					$dbh->query("insert into app_object_field_options(field_id, key, value) 
									values('" . $field->id . "', '" . $dbh->escape($okey) . "', '" . $dbh->escape($oval) . "')");
				}
			}
		}
		else
		{
			$key = null;
			$fKeytitle = null;
			$fKeyParent = null;
			$fKeyFilter = null;
			$fKeyRef = null;                    
			$fKeyRefTable = null;
			$fKeyRefThis = null;
			$autocreatebase = null;
			$autocreatename = null;
			$mask = null;
			$useWhen = null;
			
			if (isset($field->fkeyTable['key']))
				$key = $field->fkeyTable['key'];
			
			if (isset($field->fkeyTable['title']))    
				$fKeytitle = $field->fkeyTable['title'];
				
			if (isset($field->fkeyTable['parent']))    
				$fKeyParent = $field->fkeyTable['parent'];
				
			if (isset($field->fkeyTable['filter']) && is_array($field->fkeyTable['filter']))    
				$fKeyFilter = serialize($field->fkeyTable['filter']);
			
			if (isset($field->fkeyTable['ref_table']['ref']))
				$fKeyRef = $field->fkeyTable['ref_table']['ref'];
				
			if (isset($field->fkeyTable['ref_table']['table']))
				$fKeyRefTable = $field->fkeyTable['ref_table']['table'];
				
			if (isset($field->fkeyTable['ref_table']['this']))
				$fKeyRefThis = $field->fkeyTable['ref_table']['this'];
				
			if ($field->autocreatebase)
				$autocreatebase = $field->autocreatebase;
				
			if ($field->autocreatename)
				$autocreatename = $field->autocreatename;
				
			if ($field->mask)
				$mask = $field->mask;
				
			if ($field->getUseWhen())
				$useWhen = $field->getUseWhen();
				
			$autocreate = "f";
			$required = "f";
			$readonly = "f";
			$unique = "f";
				
			if($field->autocreate)
				$autocreate = "t";
				
			if($field->required)
				$required = "t";
				
			if($field->readonly)
				$readonly = "t";

			if($field->unique)
				$unique = "t";
				
			$query = "insert into app_object_type_fields(type_id, name, title, type, subtype, fkey_table_key, fkey_table_title, parent_field,
					  fkey_multi_tbl, fkey_multi_this, fkey_multi_ref, sort_order, f_system, autocreate, autocreatebase, autocreatename,
					  mask, f_required, f_readonly, filter, use_when, f_unique)
					  values('".$def->getId()."', '$fname', '".$dbh->escape($field->title)."', '".$field->type."', '".$field->subtype."',
					  '$key', '$fKeytitle', '$fKeyParent', '$fKeyRefTable', '$fKeyRefThis', '$fKeyRef', 
					  '$sort_order', '".(($field->system)?'t':'f')."',
					  '$autocreate', '".$dbh->escape($autocreatebase)."', '".$dbh->escape($autocreatename)."',
					  '".$dbh->escape($mask)."', '$required', '$readonly',
					  '".$dbh->escape($fKeyFilter)."',
					  '".$dbh->escape($useWhen)."',
					  '$unique');
					  select currval('app_object_type_fields_id_seq') as id;";

			$result = $dbh->query($query);
			if ($dbh->getNumRows($result))
			{
				$fdefCoalesce = null;
				$fdefWhere = null;
				
				if(isset($field->default['coalesce']))
					$fdefCoalesce = $field->default['coalesce'];
					
				if(isset($field->default['where']))
					$fdefWhere = $field->default['where'];
				
				$fid = $dbh->getValue($result, 0, "id");
				$field->id = $fid;

				if ($fid && $field->default)
				{
					$dbh->query("insert into app_object_field_defaults(field_id, on_event, value, coalesce, where_cond) 
									values('$fid', '".$field->default['on']."', '".$dbh->escape($field->default['value'])."', 
									'".$dbh->escape(serialize($fdefCoalesce))."',
									'".$dbh->escape(serialize($fdefWhere))."')");
				}

				if ($fid && $field->optionalValues)
				{
					foreach ($field->optionalValues as $okey=>$oval)
					{
						$dbh->query("insert into app_object_field_options(field_id, key, value) 
										values('$fid', '".$dbh->escape($okey)."', '".$dbh->escape($oval)."')");
					}
				}
			}
		}

		// Make sure column exists
		$this->checkObjColumn($def, $field);
	}

	/**
	 * Remove a field from the schema and definition
	 *
	 * @param EntityDefintionn $def The EntityDefinition we are editing
	 * @param string $fname The name of the field to delete
	 */
	private function removeField(&$def, $fname)
	{
		if (!$def->getId())
			return false;

		$this->dbh->query("delete from app_object_type_fields where name='$fname' and type_id='".$def->getId()."'");
		$this->dbh->query("ALTER TABLE " . $def->getTable() . " DROP COLUMN $fname;");
	}

	/**
	 * Make sure column exists for a field
	 *
	 * @param EntityDefintionn $def The EntityDefinition we are saving
	 * @param EntityDefinition_Field The Field to verity we have a column for
	 * @return bool true on success, false on failure
	 */
	private function checkObjColumn($def, $field)
	{
		$colname = $field->name;
		$ftype = $field->type;
		$subtype = $field->subtype;

		// Use different type for creating the system revision commit_id
		if ($field->name == "commit_id")
			$fType = "bigint";

		if (!$this->dbh->columnExists($def->getTable(), $colname))
		{
			$index = ""; // set to create dynamic indexes

			switch ($ftype)
			{
			case 'text':
				if ($subtype)
				{
					if (is_numeric($subtype))
					{
						$type = "character varying($subtype)";
						$index = "btree";
					}
					else
					{
						// Handle special types
						switch ($subtype)
						{
						case 'email':
							$type = "character varying(256)";
							$index = "btree";
							break;
						case 'zipcode':
							$type = "character varying(32)";
							$index = "btree";
							break;
						default:
							$type = "text";
							$index = "gin";
							break;
						}
					}
				}
				else
				{
					$type = "text";
					$index = "gin";
				}

				// else leave it as text
				break;
			case 'alias':
				$type = "character varying(128)";
				$index = "btree";
				break;
			case 'timestamp':
				$type = "timestamp with time zone";
				$index = "btree";
				break;
			case 'date':
				$type = "date";
				$index = "btree";
				break;
			case 'integer':
				$type = "integer";
				$index = "btree";
				break;
			case 'bigint':
				$type = "bigint";
				$index = "btree";
				break;
			case 'numeric': // If ftype is already numeric, it should set the type
				$type = "numeric";
				$index = "btree";
				break;
			case 'int':
			case 'integer':
			case 'number':
				if ($subtype)
					$type = $subtype;
				else
					$type = "numeric";
					
				$index = "btree";
				break;
			case 'fkey':
				$type = "integer";
				$index = "btree";
				break;

			case 'fkey_multi':
				$type = "text"; // store json

				//$type = "integer[]";
				//$index = "GIN";
				break;

			case 'object_multi':
				$type = "text"; // store json

				//$type = "text[]";
				//$index = "GIN";
				break;

			case 'bool':
			case 'boolean':
				$type = "bool DEFAULT false";
				break;

			case 'object':
				if ($subtype)
				{
					$type = "bigint";
					$index = "btree";
				}
				else
				{
					$type = "character varying(512)";
					$index = "btree";
				}
				break;

			default:
				$type = ""; // do not try to enter it if we don't know what it is
				break;
			}
			
			if ($type)
			{
				$query = "ALTER TABLE " . $def->getTable() . " ADD COLUMN $colname $type";
				$this->dbh->query($query);

				// Store cached foreign key names
				if ($ftype == "fkey" || $ftype == "object" || $ftype == "fkey_multi"  || $ftype == "object_multi")
					$this->dbh->query("ALTER TABLE " . $def->getTable() . " ADD COLUMN ".$colname."_fval text");
			}
		}
		else
		{
			// Make sure that existing foreign fields have local _fval caches
			if ($ftype == "fkey" || $ftype == "object" || $ftype == "fkey_multi"  || $ftype == "object_multi")
			{
				if (!$this->dbh->columnExists($def->getTable(), $colname . "_fval"))
					$this->dbh->query("ALTER TABLE " . $def->getTable() . " ADD COLUMN ".$colname."_fval text");
			}
		}

		return true;
	}

	/**
	 * Object tables are created dynamically to inherit from the parent object table
	 *
	 * @param string $objType The type name of this table
	 * @param int $typeId The unique id of the object type
	 */
	private function createObjectTable($objType, $typeId)
	{
		$dbh = $this->dbh;
		$base = "objects_" . $objType;
		$tables = array("objects_" . $objType . "_act", "objects_" . $objType . "_del");

		// Make sure the table does not already exist
		if (!$dbh->tableExists($base))
		{
			// Base table for this object type
			$query = "CREATE TABLE $base () INHERITS (objects);";
			$dbh->query($query);
		}

		// Active
		if (!$dbh->tableExists($tables[0]))
		{
			$query = "CREATE TABLE " . $tables[0] . "
						(
							CONSTRAINT " . $tables[0] . "_pkey PRIMARY KEY (id),
							CHECK(object_type_id='" . $typeId . "' and f_deleted='f')
						) 
						INHERITS ($base);";
			$dbh->query($query);
		}

		// Deleted / Archived
		if (!$dbh->tableExists($tables[1]))
		{
			$query = "CREATE TABLE ".$tables[1]."
						(
							CONSTRAINT " . $tables[1] . "_pkey PRIMARY KEY (id),
							CHECK(object_type_id='" . $typeId . "' and f_deleted='t')
						) 
						INHERITS ($base);";
			$dbh->query($query);
		}

		// Create indexes for system columns
		foreach ($tables as $tbl)
		{
			if (!$dbh->indexExists($tbl . "_uname_idx"))
			{
				$dbh->query("CREATE INDEX " . $tbl . "_uname_idx
							  ON $tbl
							  USING btree (lower(uname))
							  where uname is not null;");
			}

			if (!$dbh->indexExists($tbl . "_tsv_fulltext_idx"))
			{
				$dbh->query("CREATE INDEX " . $tbl . "_tsv_fulltext_idx
							  ON $tbl
							  USING gin (tsv_fulltext)
							  where tsv_fulltext is not null;");
			}
		}
	}

	/**
	 * Create a dynamic index for a field in this object type
	 *
	 * This is primarily used in /services/ObjectDynIdx.php to build
	 * dynamic indexes from usage stats.
	 *
	 * @param EntityDefintionn $def The EntityDefinition we are saving
	 * @param EntityDefinition_Field The Field to verity we have a column for
	 */
	public function createFieldIndex(&$def, $field)
	{
		if (!$field)
			return false;

		$colname = $field->name;
		$ftype = $field->type;
		$subtype = $field->subtype;

		if ($this->dbh->columnExists($def->getTable(), $colname) && $def->getId())
		{
			$index = ""; // set to create dynamic indexes

			switch ($ftype)
			{
			case 'text':
				$index = ($subtype) ? "btree" : "gin";
				break;
			case 'timestamp':
			case 'date':
			case 'integer':
			case 'numeric':
			case 'number':
			case 'fkey':
			case 'object':
				$index = "btree";
				break;

			case 'fkey_multi':
				$type = "text"; // store json

				//$type = "integer[]";
				//$index = "GIN";
				break;

			case 'object_multi':
				$type = "text"; // store json

				//$type = "text[]";
				//$index = "GIN";
				break;

			case 'bool':
			case 'boolean':
			default:
				break;
			}

			// Create dynamic index
			if ($index)
			{
				// If we are using generic obj partitions then make sure _del table is updated as well
				if (!$def->isCustomTable())
				{
					$indexCol = $colname;

					if ($ftype == "text" && $subtype) 
						$indexCol = "lower($colname)";
					else if ($ftype == "text" && !$subtype && $index == "gin")
						$indexCol = "to_tsvector('english', $colname)";

					if (!$this->dbh->indexExists($def->getTable()."_act_".$colname."_idx"))
					{
						$this->dbh->query("CREATE INDEX ".$def->getTable()."_act_".$colname."_idx
											  ON ".$def->getTable()."_act
											  USING $index
											  (".$indexCol.");");
					}

					if (!$this->dbh->indexExists($def->getTable()."_act_".$colname."_idx"))
					{
						$this->dbh->query("CREATE INDEX ".$def->getTable()."_del_".$colname."_idx
											  ON ".$def->getTable()."_del
											  USING $index
											  (".$indexCol.");");
					}

					// Update indexed flag for this field
					$this->dbh->query("UPDATE app_object_type_fields SET f_indexed='t' WHERE type_id='".$def->getId()."' and name='$fname'");
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Associate an object with an application
	 *
	 * @param EntityDefintion $def The definition to associate with an application
	 * @param string $applicationId The unique id of the application we are associating with
	 * @return bool true on success, false on failure
	 */
	public function associateWithApp($def, $applicatoinId)
	{
		$dbh = $this->dbh;
		$otid = $def->getId();

		if (!$dbh->getNumRows($dbh->query("select id from application_objects where application_id='$applicatoinId' and object_type_id='$otid'")))
			$dbh->query("insert into application_objects(application_id, object_type_id) values('$applicatoinId', '$otid');");
	}

	/**
	 * Get all the entity object types
	 *
	 * @return array Collection of objects
	 */
	public function getAllObjectTypes() {
		$dbh = $this->dbh;
		$result = $dbh->query("select name from app_object_types");

		$num = $dbh->getNumRows($result);
		$ret = array();
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->getRow($result, $i);
			$ret[] = $row['name'];
		}

		return $ret;
	}
}
