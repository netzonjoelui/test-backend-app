<?php
/**
 * This is the PostgreSQL implementation of a datamapper
 *
 * @category	DataMapper
 * @package		Pgsql
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Entity\DataMapper;

use Netric\Entity;
use Netric\Entity\DataMapperAbstract;
use Netric\Entity\DataMapperInterface;
use Netric\Db\DbInterface;

use Netric\EntityDefinition\Exception\DefinitionStaleException;

class Pgsql extends DataMapperAbstract implements DataMapperInterface
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
	 * @var DbInterface
	 */
	private $dbh = null;

	/**
	 * Setup this class called from the parent constructor
	 *
	 * @param ServiceLocator $sl The ServiceLocator container
	 */
	protected function setUp()
	{
		// Right now we will use the CDatabase class because it is already setup
		// Later we might want to start using direct pgsql api calls
		$this->dbh = $this->account->getServiceManager()->get("Db");
	}

	/**
	 * Open object by id
	 *
	 * @var Entity $entity The entity to load data into
	 * @var string $id The Id of the object
	 * @return bool true on success, false on failure
	 */
	protected function fetchById(&$entity, $id)
	{
		$objType = $entity->getObjType();
		$def = $entity->getDefinition();

		$dbh = $this->dbh;

		$query = "select * from ".$def->getTable()." where id='" . $dbh->escape($id) . "'";
		$result = $dbh->query($query);
		if (!$this->dbh->getNumRows($result))
		{
			/*
			// Object id not found, see if we can find the object in the moved index (maybe it was merged)
			if (($movedToId = $this->entityHasMoved($def, $id)))
			{
				// Looks like this object was moved to another object - usually merged
				return $this->fetchById($entity, $movedToId);
			}
			*/

			// The object was not found
			return false;
		}

		$row = $dbh->getRow($result, 0);

		// Load data for foreign keys
		$all_fields = $def->getFields();
		foreach ($all_fields as $fname=>$fdef)
		{
			// Populate values and foreign values for foreign entries if not set
			if ($fdef->type == "fkey" || $fdef->type == "object" || $fdef->type == "fkey_multi" || $fdef->type == "object_multi")
			{
				$mvals = null;

				// If fval is not set which should only occur on old objects prior to caching data in version 2
				if (!$row[$fname . "_fval"] || ($row[$fname . "_fval"]=='[]' && $row[$fname]!='[]' && $row[$fname]!=''))
				{
					$mvals = $this->getForeignKeyDataFromDb($fdef, $row[$fname], $entity->getId(), $def->getId());
					$row[$fname . "_fval"] = ($mvals) ? json_encode($mvals) : "";
				}

				// set values of fkey_multi and object_multi fields as array of id(s)
				if ($fdef->type == "fkey_multi" || $fdef->type == "object_multi")
				{
					if ($row[$fname])
					{
						$parts = $this->decodeFval($row[$fname]);
						if ($parts !== false)
						{
							$row[$fname] = $parts;
						}
					}

					// Was not set in the column, try reading from mvals list that was generated above
					if (!$row[$fname])
					{
						if (!$mvals && $row[$fname . "_fval"])
							$mvals = $this->decodeFval($row[$fname . "_fval"]);

						if ($mvals)
						{
							foreach ($mvals as $id=>$mval)
								$row[$fname][] = $id;
						}
					}
				}

				// Get object with no subtype - we may want to store this locally eventually
				// so check to see if the data is not already defined
				if (!$row[$fname] && $fdef->type == "object" && !$fdef->subtype)
				{
					if (!$mvals && $row[$fname . "_fval"])
						$mvals = $this->decodeFval($row[$fname . "_fval"]);

					if ($mvals)
					{
						foreach ($mvals as $id=>$mval)
							$row[$fname] = $id; // There is only one value but it is assoc
					}
				}
			}

			switch ($fdef->type)
			{
				case "bool":
					$row[$fname] = ($row[$fname] == 't') ? true : false;
					break;
				case "date":
				case "timestamp":
					$row[$fname] = ($row[$fname]) ? strtotime($row[$fname]) : null;
					break;
			}

			// Check if we have an fkey label/name associated with column ids - these are cached in the object
			$fkeyValueName = (isset($row[$fname."_fval"])) ? $this->decodeFval($row[$fname."_fval"]) : null;

			// Set entity value
			if (isset($row[$fname]))
				$entity->setValue($fname, $row[$fname], $fkeyValueName);
		}

		return true;
	}

	/**
	 * Delete object by id
	 *
	 * @var Entity $entity The entity to load data into
	 * @return bool true on success, false on failure
	 */
	protected function deleteHard(&$entity)
	{
		// Only delete existing objects
		if (!$entity->getId())
			return false;

		$def = $entity->getDefinition();

		// Remove revision history
		$this->dbh->query("DELETE FROM object_revisions WHERE object_id='" . $entity->getId() . "'
							AND object_type_id='" . $def->getId() . "'");

		// Delete the object from the object table
		$ret = $this->dbh->query("DELETE FROM " . $def->getTable() . " where id='" . $entity->getId() . "'");

		// Remove associations
		$this->dbh->query("DELETE FROM object_associations WHERE
							(object_id='" . $entity->getId() . "' and type_id='" . $def->getId() . "')
							or (assoc_object_id='" . $entity->getId() . "' and assoc_type_id='" . $def->getId() . "')");

		// We just need to make sure the main object was deleted
		if ($ret == false)
			return false;
		else
			return true;
	}

	/**
	 * Delete object by id
	 *
	 * @var Entity $entity The entity to load data into
	 * @return bool true on success, false on failure
	 */
	protected function deleteSoft(&$entity)
	{
		// Update the deleted flag and save
		$entity->setValue("f_deleted", true);
		$ret = $this->save($entity);
		return ($ret == false) ? false : true;
	}

	/**
	 * Get object definition based on an object type
	 *
	 * @param string $objType The object type name
	 * @param string $fieldName The field name to get grouping data for
	 * @return EntityGrouping[]
	 */
	public function getGroupings($objType, $fieldName, $filters=array())
	{
		$def = $this->getAccount()->getServiceManager()->get("EntityDefinitionLoader")->get($objType);
		if (!$def)
			throw new \Exception("Entity could not be loaded");

		$field = $def->getField($fieldName);

		if ($field->type != "fkey" && $field->type != "fkey_multi")
			throw new \Exception("$fieldName:" . $field->type . " is not a grouping (fkey or fkey_multi) field!");

		$dbh = $this->dbh;

		if ($field->subtype == "object_groupings")
			$cnd = "object_type_id='". $def->getId() ."' and field_id='" . $field->id . "' ";
		else
			$cnd = "";

		// Check filters to refine the results - can filter by parent object like project id for cases or tasks
		if (isset($field->fkeyTable['filter']))
		{
			foreach ($field->fkeyTable['filter'] as $grouping_field=>$object_field)
			{
				if (isset($filters[$object_field]))
				{
					if ($cnd) $cnd .= " and ";

					// Replacing the below block for now to add simple filters
					$cnd .= " $grouping_field='".$filters[$object_field]."' ";

					/**
					 * This appears to be a powerful feature, but I don't know if we are even
					 * using it anywhere so I am commenting it out for the time being.
					 * - joe
					 *
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
					$tbl, $filters[$object_field]);
					if ($root && $root!=$filters[$object_field])
					{
					$cnd .= " ($referenced_field='".$filters[$object_field]."' or $referenced_field='".$root."')";
					}
					else
					{
					$cnd .= " $referenced_field='".$filters[$object_field]."' ";
					}
					}
					else
					{
					$cnd .= " $referenced_field='".$filters[$object_field]."' ";
					}
					 */
				}
			}
		}

		// Filter results to this user of the object is private
		if ($def->isPrivate && !isset($filters["user_id"]) && !isset($filters["owner_id"]))
		{
			throw new \Exception("Private entity type called but grouping has no filter defined - " . $def->getObjType());
		}

		$sql = "SELECT * FROM ". $field->subtype;

		if ($cnd)
			$sql .= " WHERE $cnd ";

		if ($this->dbh->columnExists($field->subtype, "sort_order"))
			$sql .= " ORDER BY sort_order, ".(($field->fkeyTable['title']) ? $field->fkeyTable['title'] : $field->fkeyTable['key']);
		else
			$sql .= " ORDER BY ".(($field->fkeyTable['title']) ? $field->fkeyTable['title'] : $field->fkeyTable['key']);

		// Technically, the limit of groupings is 1000 per field, but just to be safe
		$sql .= " LIMIT 10000";

		$groupings = new \Netric\EntityGroupings($objType, $fieldName, $filters);

		$result = $dbh->Query($sql);
		$num = $this->dbh->getNumRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->getRow($result, $i);

			$group = new \Netric\EntityGroupings\Group();
			$group->id = $row[$field->fkeyTable['key']];
			$group->uname = $row[$field->fkeyTable['key']]; // groupings can/should have a unique-name column
			$group->name = $row[$field->fkeyTable['title']];
			$group->isHeiarch = (isset($field->fkeyTable['parent'])) ? true : false;
			if (isset($field->fkeyTable['parent']) && isset($row[$field->fkeyTable['parent']]))
				$group->parentId = $row[$field->fkeyTable['parent']];
			$group->color = (isset($row['color']) )? $row['color'] : "";
			if(isset($row['sort_order']))
				$group->sortOrder = $row['sort_order'];
			$group->isSystem = (isset($row['f_system']) && $row['f_system']=='t') ? true : false;
			$group->commitId = (isset($row['commit_id'])) ? $row['commit_id'] : 0;

			//$item['f_closed'] = (isset($row['f_closed']) && $row['f_closed']=='t') ? true : false;

			// Add all additional fields which are usually used for filters
			foreach ($row as $pname=>$pval)
			{
				if (!$group->getValue($pname))
					$group->setValue($pname, $pval);
			}

			// Make sure the group is not marked as dirty
			$group->setDirty(false);

			$groupings->add($group);
		}

		// TODO: we need to think about how we can manage default groupings
		// Make sure that default groupings exist (if any)
		//if (!$parent && sizeof($conditions) == 0) // Do not create default groupings if data is filtered
		//	$ret = $this->verifyDefaultGroupings($fieldName, $data, $nameValue);
		//else
		//	$ret = $data;

		return $groupings;
	}

	/**
	 * Save groupings
	 *
	 * @param \Netric\EntityGroupings
	 * @param int $commitId The commit id of this save
	 * @return array("changed"=>int[], "deleted"=>int[]) Log of changed groupings
	 */
	protected function _saveGroupings(\Netric\EntityGroupings $groupings, $commitId)
	{
		$def = $this->getAccount()->getServiceManager()->get("EntityDefinitionLoader")->get($groupings->getObjType());
		if (!$def)
			return false;

		$field = $def->getField($groupings->getFieldName());

		$ret = array("deleted"=>array(), "changed"=>array());

		$toDelete = $groupings->getDeleted();
		foreach ($toDelete as $grp)
		{
			$query = "DELETE FROM " . $field->subtype . " where id='" . $grp->id . "'";
			$this->dbh->query($query);

			// Log here
			$ret['deleted'][$grp->id] = $grp->commitId;
		}

		$toSave = $groupings->getChanged();
		foreach ($toSave as $grp)
		{
			// Cache for updates to object_sync
			$lastCommitId = $grp->getValue("commitId");

			// Set the new commit id
			$grp->setValue("commitId", $commitId);

			if ($this->saveGroup($def, $field, $grp))
			{
				$grp->setDirty(false);
				// Log here
				$ret['changed'][$grp->id] = $lastCommitId;
			}
		}

		return $ret;
	}

	/**
	 * Save a new or existing group
	 *
	 * @param \Netric\EntityDefinition $def Entity type definition
	 * @param \Netric\EntityDefinition\Feidl $field The field we are saving a grouping for
	 * @param \Netric\EntityGroupings\Group $grp The grouping to save
	 * @return bool true on sucess, false on failure
	 */
	private function saveGroup($def, $field, \Netric\EntityGroupings\Group $grp)
	{
		if (!$field)
			return false;

		if ($field->type != "fkey" && $field->type != "fkey_multi")
			return false;

		$columns = array();
		$values = array();

		if ($grp->name && $field->fkeyTable['title'])
		{
			$columns[] = $field->fkeyTable['title'];
			$values[] = "'".$this->dbh->escape($grp->name)."'";
		}

		if ($grp->color && $this->dbh->columnExists($field->subtype, "color"))
		{
			$columns[] = "color";
			$values[] = "'".$this->dbh->escape($grp->color)."'";
		}

		if ($grp->isSystem && $this->dbh->columnExists($field->subtype, "f_system"))
		{
			$columns[] = "f_system";
			$values[] = "'t'";
		}

		if ($grp->sortOrder && $this->dbh->columnExists($field->subtype, "sort_order"))
		{
			$columns[] = "sort_order";
			$values[] = $this->dbh->escapeNumber($grp->sortOrder);
		}

		if ($grp->parentId && isset($field->fkeyTable['parent']))
		{
			$columns[] = $field->fkeyTable['parent'];
			$values[] = $this->dbh->escapeNumber($grp->parentId);
		}

		if ($grp->commitId)
		{
			$columns[] = "commit_id";
			$values[] = $this->dbh->escapeNumber($grp->commitId);
		}

		if ($field->subtype == "object_groupings")
		{
			$columns[] = "object_type_id";
			$values[] = "'" . $def->getId() . "'";

			$columns[] = "field_id";
			$values[] = "'" . $field->id . "'";
		}

		$data = $grp->toArray();
		foreach ($data["filter_fields"] as $name=>$value)
		{
			// Make sure that the column name does not exists yet
			if (in_array($name, $columns)) {
				continue;
			}

			if ($value && $this->dbh->columnExists($field->subtype, $name))
			{
				$columns[] = $name;
				$values[] = "'".$this->dbh->escape($value)."'";
			}
		}
		/*
        if (($this->def->isPrivate || $field->subtype == "object_groupings") && $this->user)
        {
            if ($this->dbh->ColumnExists($field->subtype, "owner_id"))
            {
                $columns[] = "owner_id";
                $values[] = $this->dbh->EscapeNumber($this->user->id);
            }
            else if ($this->dbh->ColumnExists($field->subtype, "user_id"))
            {
                $columns[] = "user_id";
                $values[] = $this->dbh->EscapeNumber($this->user->id);
            }
        }
         */

		// Execute query
		if (count($columns) == 0)
			return false;

		if ($grp->id)
		{
			$upSql = "";
			for ($i = 0; $i < count($columns); $i++)
			{
				if ($i > 0)
					$upSql .= ", ";

				$upSql .= $columns[$i] . "=" . $values[$i];
			}

			$sql = "UPDATE ".$field->subtype." SET " . $upSql . " WHERE id='" . $grp->id . "'";
		}
		else
		{
			$sql = "INSERT INTO ".$field->subtype."(" . implode(", ", $columns) . ") VALUES(" . implode(", ", $values) . ");
                                      SELECT currval('".$field->subtype."_id_seq') as id;";
		}

		$res = $this->dbh->Query($sql);
		if (!$res)
			return false;

		if ($this->dbh->getNumRows($res))
		{
			if (!$grp->id)
			{
				$grp->id = $this->dbh->getValue($res, 0, "id");
			}
		}

		return true;
	}

	/**
	 * Save object data
	 *
	 * @param Entity $entity The entity to save
	 * @return string|bool entity id on success, false on failure
	 */
	protected function saveData($entity)
	{
		$dbh = $this->dbh;
		$ret = array();
		$def = $entity->getDefinition();

		// TODO: Reload cached foreign values based on the current values
		// $this->reloadFVals();

		// Convert to cols=>vals escaped array
		$data = $this->getColsVals($entity);

		$all_fields = $def->getFields();

		// Try to manipulate data to correctly build the sql statement based on custom table definitions
		if (!$def->isCustomTable())
			$data["object_type_id"] = $def->getId();

		$targetTable = $def->getTable();

		if (!$def->isCustomTable() && $entity->isDeleted())
			$targetTable .= "_del";
		else if (!$def->isCustomTable())
			$targetTable .= "_act";

		/*
		 * If we are using a custom table or the deleted status has not changed
		 * on a generic object table then update row.
		 * The last condition checks if update is greater than 1, since 1 will be the value
		 * of the very first save. It is possible that a user set a specific ID of an entity
		 * when creating it. This will not matter at all for partitioned tables since it will
		 * automatically delete before inserting, but for custom tables it could cause a bug
		 * where it tried to update an ID that does not exist.
		 */
		if (
			$entity->getId() && (
				$def->isCustomTable() ||
				(!$entity->fieldValueChanged("f_deleted") && !$def->isCustomTable())
			) && $entity->getValue("revision") > 1
		)
		{
			$query = "UPDATE " . $targetTable . " SET ";
			$update_fields = "";
			foreach ($data as $colname=>$colval)
			{
				if ($colname == "id") // skip over id
					continue;

				if ($update_fields) $update_fields .= ", ";
				$update_fields .= '"'.$colname.'"' . "=" . $colval; // val is already escaped
			}
			$query .= $update_fields." WHERE id='" . $entity->getId() . "'";
			$res = $dbh->query($query);

			if (!$res) {
				throw new \RuntimeException(
					"Could not update entity $targetTable." .
					$entity->getId(). ":" . $dbh->getLastError()
				);
			}

			$performed = "update";
		}
		else
		{
			// Clean out old record if it exists in a different partition
			if ($entity->getId() && !$def->isCustomTable())
			{
				$dbh->query("DELETE FROM " . $def->getTable() . " WHERE id='" . $entity->getId() . "'");
			}

			$cols = "";
			$vals = "";

			foreach ($data as $colname=>$colval)
			{
				// Skip over id if it is null
				if ($colname == "id" && (empty($colval) || strtolower($colval) == 'null')) {
					continue;
				}

				// Add comma
				if ($cols) {
					$cols .= ", ";
				}

				if ($vals) {
					$vals .= ", ";
				}

				// Add culumn name and column value
				$cols .= $colname;
				$vals .= $colval; // val is already escaped
			}

			$query = "insert into " . $targetTable . "($cols) VALUES($vals);";

			$seqName = ($def->isCustomTable()) ? $targetTable . "_id_seq" : "objects_id_seq";
			if ($entity->getId())
				$query .= "select '" . $entity->getId() . "' as id;";
			else
				$query .= "select currval('$seqName') as id;";

			$result = $dbh->query($query);

			if (!$result)
			{
				throw new DefinitionStaleException(
					"Could not save entity: " . $dbh->getLastError() .
					" - " . $query
				);
			}

			// Set event
			$performed = (!$entity->getId()) ? "create" : "update";

			// If this was a new object the set the id, otherwise leave as is
			if ($dbh->getNumRows($result) && !$entity->getId())
				$entity->setValue("id", $dbh->getValue($result, 0, "id"));
		}

		// handle fkey_multi && Auto
		if ($entity->getId())
		{
			// Handle autocreate folders - only has to fire the very first time
			foreach ($all_fields as $fname=>$fdef)
			{
				if ($fdef->type=="object" && $fdef->subtype=="folder"
					&& $fdef->autocreate && $fdef->autocreatebase && $fdef->autocreatename
					&& !$entity->getValue($fname) && $entity->getValue($fdef->autocreatename))
				{
					// We should use the service locator to load this service
					$fileSystem = $this->account->getServiceManager()->get("Netric/FileSystem/FileSystem");
					$fldr = $fileSystem->openFolder($fdef->autocreatebase . "/" . $entity->getValue($fdef->autocreatename), true);
					if ($fldr->getId())
					{
						$entity->setValue($fname, $fldr->getId());
						$dbh->query("update " . $targetTable . " set $fname='" . $fldr->getId() . "' where id='" . $entity->getId() . "'");
					}
				}
			}

			// Handle updating reference membership if needed
			$defLoader = $this->getAccount()->getServiceManager()->get("EntityDefinitionLoader");
			foreach ($all_fields as $fname=>$fdef)
			{
				if (!$fdef->id)
					throw new DefinitionStaleException("For some reason there is no ID for field $fname of object type " . $def->getObjType());

				if ($fdef->type == "fkey_multi")
				{
					// Cleanup
					// --------------------------------------
					$queryStr = "delete from ".$fdef->fkeyTable['ref_table']['table']."
								 where ".$fdef->fkeyTable['ref_table']["this"]."='" . $entity->getId() . "'";
					// object_type_id is needed for generic groupings
					if ($fdef->subtype == "object_groupings")
						$queryStr .= " and object_type_id='" . $def->getId() . "' and field_id='" . $fdef->id ."'";

					$dbh->query($queryStr);

					// Populate foreign table
					// --------------------------------------
					$mvalues = $entity->getValue($fname);
					if (is_array($mvalues))
					{
						foreach ($mvalues as $val)
						{
							if ($val)
							{
								$queryStr = "INSERT INTO ".$fdef->fkeyTable['ref_table']['table']."
									(".$fdef->fkeyTable['ref_table']['ref'].", ".$fdef->fkeyTable['ref_table']["this"];

								// object_type_id is needed for generic groupings
								if ($fdef->subtype == "object_groupings")
									$queryStr .= ", object_type_id, field_id";

								// Add values
								$queryStr .= ") VALUES('" . $val . "', '" . $entity->getId() . "'";

								// object_type_id is needed for generic groupings
								if ($fdef->subtype == "object_groupings")
									$queryStr .= ", '" . $def->getId() . "', '" . $fdef->id . "'";

								$queryStr .= ");";

								$dbh->query($queryStr);
							}
						}
					}
				}

				// Handle object associations
				if ($fdef->type == "object_multi" || $fdef->type == "object")
				{
					// Cleanup
					$dbh->query("DELETE FROM object_associations
								 WHERE object_id='" . $entity->getId() . "' AND
								 type_id='" . $def->getId() . "'
								 AND field_id='" . $fdef->id . "'");

					// Set values
					$mvalues = $entity->getValue($fname);
					if (is_array($mvalues))
					{
						foreach ($mvalues as $val)
						{
							$subtype = null; // Set the initial value of subtype to null

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

							if ($subtype)
							{
								$assocDef = $defLoader->get($subtype);
								if ($assocDef->getId() && $objid)
								{
									$dbh->query("insert into object_associations(object_id, type_id, assoc_type_id, assoc_object_id, field_id)
												 values('".$entity->getId()."', '".$def->getId()."', '".$assocDef->getId()."', '".$objid."', '".$fdef->id."');");
								}
							}
						}
					}
					else if ($mvalues)
					{
						if ($fdef->subtype)
						{
							$assocDef = $defLoader->get($fdef->subtype);
							if ($assocDef->getId())
							{
								$dbh->query("insert into object_associations(object_id, type_id, assoc_type_id, assoc_object_id, field_id)
												values(
													'".$entity->getId()."',
													'".$def->getId()."',
													'".$assocDef->getId()."',
													'".$mvalues."',
													'".$fdef->id."');");
							}
						}
						else
						{
							$parts = explode(":", $mvalues);
							if (count($parts)==2)
							{
								$assocDef = $defLoader->get($parts[0]);
								if ($assocDef->getId() && $parts[1])
								{
									$dbh->query("insert into object_associations(object_id, type_id, assoc_type_id, assoc_object_id, field_id)
												 values('".$entity->getId()."', '".$def->getId()."', '".$assocDef->getId()."', '".$parts[1]."', '".$fdef->id."');");
								}
							}
						}
					}
				}
			}

			return $entity->getId();
		}
		else
		{
			// Failed to save
			return false;
		}
	}

	/**
	 * Convert fields to column names for saving table and escape for insertion/updates
	 *
	 * @param Entity $entity The entity we are saving
	 * @return array("colname"=>"value")
	 */
	private function getColsVals($entity)
	{
		$dbh = $this->dbh;
		$ret = array();
		$def = $entity->getDefinition();

		$all_fields = $def->getFields();

		foreach ($all_fields as $fname=>$fdef)
		{
			$setVal = "";
			$val= $entity->getValue($fname);

			switch ($fdef->type)
			{
				case 'auto': // Calculated fields
					break;
				case 'fkey_multi':
					$fvals = $entity->getValueNames($fname);
					if ($val)
						$setVal = "'".$dbh->escape(json_encode($val))."'";
					else
						$setVal = "'".$dbh->escape(json_encode(array()))."'";
					break;
				case 'object':
					if ($fdef->subtype)
						$setVal = $dbh->escapeNumber($val);
					else
						$setVal = "'".$dbh->escape($val)."'";
					break;
				case 'object_multi':
					if ($val)
						$setVal = "'".$dbh->escape(json_encode($val))."'";
					else
						$setVal = "'".$dbh->escape(json_encode(array()))."'";
					break;
				case 'fkey':
					$setVal = $dbh->escapeNumber($val);
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
					$setVal = $dbh->escapeNumber($val);
					break;
				case 'date':
					if (is_numeric($val) && $val > 0)
					{
						$strDate = date("Y-m-d", $val);
						$setVal = $dbh->escapeDate($strDate);
					}
					break;
				case 'timestamp':
					if (is_numeric($val) && $val > 0)
					{
						$strTs = date("Y-m-d h:i:s A T", $val);
						$setVal = $dbh->escapeTimestamp($strTs);
					}
					break;
				case 'bool':
					$bVal = ($val) ? 't' : "f"; // Set the default values to 'f'
					$setVal = "'$bVal'";
					break;
				case 'text':
					$tmpval = $val;
					if (is_numeric($fdef->subtype))
					{
						if (strlen($tmpval)>$fdef->subtype)
							$tmpval = substr($tmpval, 0, $fdef->subtype);
					}
					$setVal = "'".$dbh->escape($tmpval)."'";
					break;
				default:
					$setVal = "'".$dbh->escape($val)."'";
					break;
			}

			if ($setVal) // Setval must be set to something for it to update a column
				$ret[$fname] = $setVal;

			// Set fval
			if ($fdef->type == "fkey" || $fdef->type == "fkey_multi" || $fdef->type == "object" || $fdef->type == "object_multi")
			{
				$fvals = $entity->getValueNames($fname);
				if (is_array($fvals) && count($fvals))
				{
					$ret[$fname . "_fval"] = "'" . $dbh->escape(json_encode($fvals)) . "'";
				}
				else
				{
					$ret[$fname . "_fval"] = "'" . $dbh->escape(json_encode(array())) . "'";
				}
			}
		}

		return $ret;
	}

	/**
	 * Decode fval which is saved as json encoded string
	 *
	 * @param string $val The encoded string
	 * @return array on success, null on failure
	 */
	private function decodeFval($val)
	{
		if ($val == null || $val=="")
			return null;

		return json_decode($val, true);
	}

	/**
	 * Load foreign values from the database
	 *
	 * @param EntityDefinition_Field $fdef The field we are getting foreign lavel/title for
	 * @param string $value Raw value from field if exists
	 * @param string $oid The object id we are getting values for
	 * @param string $otid The object type id id we are getting values for
	 * @return array('keyid'=>'value/name')
	 */
	private function getForeignKeyDataFromDb($fdef, $value, $oid, $otid)
	{
		$dbh = $this->dbh;
		$ret = array();

		if ($fdef->type == "fkey" && $value)
		{
			$query = "SELECT " . $fdef->fkeyTable['key'] ." as id, " . $fdef->fkeyTable['title'] . " as name ";
			$query .= "FROM " . $fdef->subtype . " ";
			$query .= "WHERE " . $fdef->fkeyTable['key'] . "='$value'";
			$result = $dbh->query($query);
			$num = $dbh->getNumRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->getRow($result, $i);
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
						".$fdef->fkeyTable['ref_table']["this"]."='" . $oid . "'";
			$result = $dbh->query($query);

			for ($i = 0; $i < $dbh->getNumRows($result); $i++)
			{
				$row = $dbh->getRow($result, $i);

				$ret[(string)$row['id']] = $row['name'];
			}
		}

		/**
		 * joe: The below are grossly inefficient but should only be necessary for very old
		 * objects and then will be cached by the loader in the caching datamapper.
		 * Eventually we will just remove it along with this entire function.
		 */
		if ($fdef->type == "object" && $fdef->subtype && $this->getAccount()->getServiceManager() && $value)
		{
			$entity = $this->getAccount()->getServiceManager()->get("EntityLoader")->get($fdef->subtype, $value);
			if ($entity) {
				$ret[(string)$value] = $entity->getName();
			} else {

				$log = $this->getAccount()->getApplication()->getLog();
				$log->error("Could not load {$fdef->subtype}.{$value} to update foreign value");
			}

		}
		else if (($fdef->type == "object" && !$fdef->subtype) || $fdef->type == "object_multi")
		{
			$query = "select assoc_type_id, assoc_object_id, app_object_types.name as obj_name
							 from object_associations inner join app_object_types on (object_associations.assoc_type_id = app_object_types.id)
							 where field_id='".$fdef->id."' and type_id='" . $otid . "'
							 and object_id='" . $oid . "' LIMIT 1000";
			$result = $dbh->query($query);
			for ($i = 0; $i < $dbh->getNumRows($result); $i++)
			{
				$row = $dbh->getRow($result, $i);

				$oname = "";

				// If subtype is set in the field, then only the id of the object is stored
				if ($fdef->subtype)
				{
					$oname = $fdef->subtype;
					$idval = (string)$row['assoc_object_id'];
				}
				else
				{
					$oname = $row['obj_name'];
					$idval = $oname.":".$row["assoc_object_id"];
				}

				/* Removed this code since it is causing a circular reference
				 *
				 * When an entity (e.g. User) has a referenced entity (e.g File),
				 * EntityLoader will try to get the referenced entity data from the datamapper (if referenced entity is not yet cached)
				 * And then File entity will try to get the User Entity which will cause a circular reference
					if ($oname)
					{
						$entity = $this->getAccount()->getServiceManager()->get("EntityLoader")->get($oname, $row['assoc_object_id']);

						// Update if field is not referencing an entity that no longer exists
						if ($entity)
							$ret[(string)$idval] = $entity->getName();
					}
				*/

				/*
				 * Set the value to null since we cant get the referenced entity name for now.
				 * Let the caller handle getting the name of the referenced entity
				 */
				$ret[(string)$idval] = null;
			}
		}

		return $ret;
	}

	/**
	 * Check to see if this object id was moved or merged into a different id
	 *
	 * @return string new Entity id if moved, otherwise false
	 */
	protected function entityHasMoved($def, $id)
	{
		if (!$id)
			return false;

		$result = $this->dbh->query("SELECT moved_to FROM objects_moved WHERE
										object_type_id='" . $def->getId() . "'
										and object_id='" . $this->dbh->escape($id) . "'");
		if ($this->dbh->getNumRows($result))
		{
			$moved_to = $this->dbh->getValue($result, 0, "moved_to");

			// Kill circular references - objects moved to each other
			if (in_array($id, $this->movedToRef))
				return false;

			$this->movedToRef[] = $moved_to;

			return $moved_to;
		}

		return false;
	}

	/**
	 * Set this object as having been moved to another object
	 *
	 * @param EntityDefinition $def The defintion of this object type
	 * @param string $fromId The id to move
	 * @param stirng $toId The unique id of the object this was moved to
	 * @return bool true on succes, false on failure
	 */
	public function setEntityMovedTo(&$def, $fromId, $toId)
	{
		if (!$fromId || $fromId == $toId) // never allow circular reference or blank values
			return false;

		$ret = $this->dbh->query("INSERT INTO objects_moved(object_type_id, object_id, moved_to)
									VALUES('" . $def->getId() . "', '" . $this->dbh->escape($fromId) . "',
										'" . $this->dbh->escape($toId) . "');");

		return ($ret == false) ? false : true;
	}

	/**
	 * Save revision snapshot
	 *
	 * @param Entity $entity The entity to save
	 * @return string|bool entity id on success, false on failure
	 */
	protected function saveRevision($entity)
	{
		$dbh = $this->dbh;
		$def = $entity->getDefinition();

		if ($entity->getValue("revision") && $entity->getId() && $def->getId())
		{
			$data = serialize($entity->toArray());
			$dbh->query("insert into object_revisions(object_id, object_type_id, revision, ts_updated, data)
						   values('" . $entity->getId() . "', '" . $def->getId() . "',
								  '" . $entity->getValue("revision") . "', 'now', '" . $dbh->escape($data) . "');");
		}
	}


	/**
	 * Get Revisions for this object
	 *
	 * @param string $objType The name of the object type to get
	 * @param string $id The unique id of the object to get revisions for
	 * @return array("revisionNum"=>Entity)
	 */
	public function getRevisions($objType, $id)
	{
		if (!$objType || !$id)
			return false;


		$def = $this->getAccount()->getServiceManager()->get("EntityDefinitionLoader")->get($objType);

		if (!$def)
			return false;

		$dbh = $this->dbh;
		$ret = array();

		$results = $this->dbh->query("SELECT id, revision, data FROM object_revisions WHERE 
										object_type_id='" . $def->getId() . "' AND object_id='" . $dbh->escape($id) ."' ORDER BY revision");
		$num = $this->dbh->getNumRows($results);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->getRow($results, $i);

			$ent = $this->getAccount()->getServiceManager()->get("EntityFactory")->create($objType);
			$ent->fromArray(unserialize($row['data']));
			$ret[$row['revision']] = $ent;
		}

		return $ret;
	}
}
