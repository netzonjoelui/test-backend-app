<?php
class CAntObjectIndexDb extends CAntObjectIndex
{
	public function __construct($dbh, $obj) 
	{ 
        parent::__construct($dbh, $obj); 

		// Increment this if major changes are made to the way objects are indexed
		// causing them to be reindexed
		$this->engineRev = 1; 

		// Create default object list
		if (!$this->objList)
			$this->objList = new CAntObjectList($this->dbh, $obj->object_type, $obj->user);
    }

	/**
	 * Insert an object into the index
	 *
	 * This funciton is no longer used since we are querying the tables directly rather 
	 * than using the pivot tables.
	 *
	 * @param CAntObject $obj The object to index
	 */
	public function indexObject($obj, $commit = true)
	{
		global $G_OBJ_IND_EXISTS;
			
		if (!$obj->id || !$obj->object_type_id)
			return false;

		$dbh = $this->dbh;

		$buf = "";
		$snippet = "";
		$fields = $obj->fields->getFields();
		if (is_array($fields) && count($fields))
		{
			if (!$obj->fields->useCustomTable)
			{
				foreach ($fields as $field=>$fdef)
				{
					$val = $obj->getValue($field, true);
                    if (!is_array($val))
                        $buf .= strtolower($val." ");
				}

				AntLog::getInstance()->info("indexObject: Saving $buf");

				if ($buf)
				{
					$dbh->Query("UPDATE " . $obj->getObjectTable(true) . " 
									SET tsv_fulltext=to_tsvector('english', '".$dbh->Escape(strip_tags($buf))."')
									WHERE id='" . $obj->id . "';");
				}
			}

			// Update fulltext search
			// ----------------------------------------------------------------
			// Get all field values
			/*
			foreach ($fields as $field=>$fdef)
			{
				if ($fdef['type'] == 'text' || $fdef['type'] == 'fkey' || $fdef['type'] == 'object')
				{
					$val = $obj->getValue($field, true);
					$buf .= strtolower($val." ");

					if ($obj->object_type == "email_message" && $field=="orig_header" || $field=="keywords")
						continue;

					if ($val!="" && $val!=null && strlen($snippet) < 512) // limit to 512
						$snippet .= $fdef['title']." $val ";
				}
			}
			*/

			// Update snippet
			/*
			if (strlen($snippet) > 512) // limit to 512
				$snippet = substr($snippet, 0, 512);
			 */

			// Set index table
			/*
			if ($obj->getValue("f_deleted") == 't')
			{
				$index_tbl = "object_index_fulltext_del";
				$fdel = 't';
			}
			else
			{
				$index_tbl = "object_index_fulltext_act";
				$fdel = 'f';
			}

			$dbh->Query("delete from object_index_fulltext where object_type_id='".$obj->object_type_id."' and object_id='".$obj->id."'");
			if ($buf && $obj->object_type_id && $obj->id)
			{
				$pvt_owner = ($obj->isPrivate() && is_numeric($obj->owner_id)) ? "'".$obj->owner_id."'" : "NULL";

				$dbh->Query("insert into $index_tbl(object_type_id, object_id, tsv_keywords, private_owner_id, ts_entered, snippet, f_deleted) 
								values('".$obj->object_type_id."', '".$obj->id."', to_tsvector('english', '".$dbh->Escape(strip_tags($buf))."'), 
								$pvt_owner, 'now', '".$dbh->Escape($snippet)."', '$fdel')");
			}
			*/

			// Update structured data index
			// ----------------------------------------------------------------
			// Make sure index table exists for this object type
			/*
			if (!isset($G_OBJ_IND_EXISTS[$obj->object_type_id]) || $G_OBJ_IND_EXISTS[$obj->object_type_id]!=true)
			{
				$ex = $obj->cache->get($obj->dbh->dbname."/objects/".$obj->object_type_id."/indexexists");
				if ($ex!='1')
				{
					if (!$obj->dbh->TableExists("object_index_".$obj->object_type_id))
					{
						$obj->createObjectTypeIndex();
					}
					$obj->cache->set($obj->dbh->dbname."/objects/".$obj->object_type_id."/indexexists", '1');
				}

				$G_OBJ_IND_EXISTS[$obj->object_type_id] = true;
			}
			 */

			// Set index table
			/*
			if ($obj->getValue("f_deleted") == 't')
			{
				$index_tbl = "object_index_".$obj->object_type_id."_del";
				$fdel = 't';
			}
			else
			{
				$index_tbl = "object_index_".$obj->object_type_id;
				$fdel = 'f';
			}
			 */
			
			// Delete existing values
			//$ret = $dbh->Query("delete from object_index_".$obj->object_type_id." where object_id='".$obj->id."'");
			//$dbh->Query("delete from object_index_".$obj->object_type_id."_del where object_id='".$obj->id."'");

			// Catch error if index does not really exist - cache could be out of sync
			//if ($ret === false)
				//$obj->createObjectTypeIndex();

			/*
			foreach ($fields as $field=>$fdef)
			{
				switch ($fdef['type'])
				{
				case 'fkey_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						foreach ($vals as $val)
						{
							if (is_numeric($val))
							{
								$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_number) 
												values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', '".$val."');");
							}
						}
					}
					break;
				case 'object_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						foreach ($vals as $val)
						{
							$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_text) 
											values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', '".$val."');");
						}
					}
					break;
				case 'date':
				case 'timestamp':
					$val = $obj->getValue($field);
					if ($val)
					{
						$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_timestamp) 
										values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', '$val');");
					}
					break;
					break;
				case 'fkey':
				case 'integer':
				case 'number':
					$val = $obj->getValue($field);
					if ($val!==null && $val!='' && is_numeric($val))
					{
						$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_number) 
										values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', '$val');");
					}
					break;
				case 'object':
					$val = $obj->getValue($field);
					if (is_numeric($val) && $fdef['subtype'])
					{
						$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_number) 
										values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', '$val');");
					}
					else
					{
						$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_text) 
										values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', '$val');");
					}
					break;
				case 'boolean':
				case 'bool':
					$val = $obj->getValue($field);
					if ($val===true || $val=='t')
						$val = 't';
					else
						$val = 'f';

					$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_bool) 
									values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', '$val');");
					break;
				case 'text':
				default:
					$val = $obj->getValue($field);
					if ($fdef['subtype'] == "" || $fdef['subtype'] == null || $fdef['subtype'] == "html" || $fdef['subtype'] == "plain")
					{
						$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_tsv) 
							values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', to_tsvector('english', '".$dbh->Escape($val)."'));");
					}
					else
					{
						$dbh->Query("insert into $index_tbl(object_id, object_type_id, field_id, f_deleted, val_text) 
										values('".$obj->id."', '".$obj->object_type_id."', '".$fdef['id']."', '$fdel', '".$dbh->Escape($val)."');");
					}
					break;
				}
			}
			 */

			// Update the cache data for making it easier to pull lists with data
			//$dbh->Query("DELETE FROM object_index_cachedata WHERE object_type_id='".$obj->object_type_id."' AND object_id='".$obj->id."'");
			/*
			$dbh->Query("INSERT INTO object_index_cachedata(object_type_id, object_id, revision, data)
						 VALUES('".$obj->object_type_id."', '".$obj->id."', '".(($obj->revision) ? $obj->revision : 1)."', 
						 '".$dbh->Escape(json_encode($obj->getDataArray()))."')");
			 */
		}

		return true;
	}

	
	/**
	 * Remove an object from the index
	 *
	 * This funciton is no longer used since we are querying the tables directly rather 
	 * than using the pivot tables.
	 *
	 * @param CAntObject $obj The object to remove from the index
	 */
	public function removeObject($obj)
	{
		/*
		if ($obj->id)
		{
			$this->dbh->Query("delete from object_index where object_type_id='".$obj->object_type_id."' and object_id='".$obj->id."'");
			$this->dbh->Query("delete from object_index_cachedata where object_type_id='".$obj->object_type_id."' and object_id='".$obj->id."'");
		}
		*/
	}

	/**
	 * Query an index and populate $objList with results.
	 *
	 * @param CAntObjectList $objList Instance of object list that is calling this index
	 * @param string $conditionText Optional full-text query string
	 * @param array $conditions Conditions array - array(array('blogic', 'field', 'operator', 'value'))
	 * @param array $orderBy = array(array('fieldname'=>'asc'|'desc'))
	 * @param int $offset Start offset
	 * @param int $limit The number of items to return with each query
	 */
	public function queryObjects($objList, $conditionText="", $conditions=array(), $orderBy=array(), $offset=0, $limit=500)
	{
        parent::queryObjects($objList, $conditionText, $conditions, $orderBy, $offset, $limit); 
		$condition_query = "";

		// Set default f_deleted condition
		if ($this->obj->fields->getField("f_deleted"))
		{
			$fDeletedCondSet = false;

			if (count($conditions))
			{
				foreach ($conditions as $cond)
				{
					if ($cond['field'] == "f_deleted")
						$fDeletedCondSet = true;
				}
			}

			if (!$fDeletedCondSet && $this->objList->hideDeleted)
				$conditions[] = array('blogic'=>"and", "field"=>"f_deleted", "operator"=>"is_equal", "value"=>'f');
		}

		// Start constructing query
		//$objectTable = $this->obj->getObjectTable(true, ($objList->hideDeleted) ? false : true);
		$objectTable = $this->obj->getObjectTable(false);
		$query = "SET constraint_exclusion = on;";
		$query .= "SELECT *, count(*) OVER() AS full_count FROM ".$objectTable." where id is not null ";

		// Build condition string
		if (count($conditions) || $conditionText!="")
		{
			$condition_query = $this->buildConditionString($conditionText, $conditions);
			if ($condition_query)
				$query .= "and $condition_query";
		}

		// Add order by
		$order_cnd = "";
		if (count($orderBy))
		{
			foreach ($orderBy as $sortObj)
			{
				if ($order_cnd) $order_cnd .= ", ";

				// Replace name field to order by full name with path
				if ($this->obj->fields->parentField && $this->obj->fields->getField("path"))
					$order_fld = str_replace($this->obj->fields->listTitle, $this->obj->fields->listTitle."_full", $sortObj->fieldName);

				$order_cnd .= $sortObj->fieldName;
				if ($sortObj->direction)
					$order_cnd .= " ".$sortObj->direction;
			}
		}
		if ($order_cnd)
			$query .= " ORDER BY $order_cnd ";

		$query .= " OFFSET $offset";
		if ($limit)
			$query .= " LIMIT $limit";

		$this->objList->lastQuery = $query;
		//echo "<query>$query</query>";
		$this->objList->total_num = 0;

		// Get fields for this object type (used in decoding multi-valued fields)
		$ofields = $this->obj->fields->getFields();

        $result = $this->dbh->Query($query);
        for ($i = 0; $i < $this->dbh->GetNumberRows($result); $i++)
        {
            $row = $this->dbh->GetRow($result, $i);
            $id = $row["id"];

            // Set total num of returned objects from the window function count(*) OVER() above in the
            // query. Not sure if this more performant than running two separate queries or not.
            if ($i == 0)
                $this->objList->total_num = $row['full_count'];

            if (isset($row['owner_id']))
                $is_owner = ($this->objList->user->id==$row['owner_id']) ? true : false;
            else if (isset($row['user_id']))
                $is_owner = ($this->objList->user->id==$row['user_id']) ? true : false;
            else
                $is_owner = false;

            // Decode multival fields
            foreach ($ofields as $fname=>$fdef)
            {
                if ($fdef['type'] == "fkey_multi" || $fdef['type'] == "object_multi")
                {
                    if ($row[$fname])
                    {
                        $dec = json_decode($row[$fname]);
                        if ($dec !== false)
                            $row[$fname] = $dec;
                    }
                }
            }

            $ind = count($this->objList->objects);
            $this->objList->objects[$ind] = array();
            $this->objList->objects[$ind]['id'] = $id;
            $this->objList->objects[$ind]['obj'] = null;
            $this->objList->objects[$ind]['revision'] = ($row['revision']) ? $row['revision'] : 1;
            $this->objList->objects[$ind]['owner_id'] = (isset($row['owner_id'])) ? $row['owner_id'] : null;
            $this->objList->objects[$ind]['data_min'] = $row;
            $this->objList->objects[$ind]['data'] = $row;
            /*
            $this->objList->objects[$ind]['data_min'] = ($row['data']) ? json_decode($row['data'], true) : $row;
            $this->objList->objects[$ind]['data'] = ($row['data']) ? json_decode($row['data'], true) : null;
             */
        }	

        // Get total count
        // ----------------------------------------
        /** We are now using the full_count column above to test performance
        $query = "select count(*) as cnt from ".$objectTable." where id is not null ";
        if ($condition_query)
            $query .= "and ($condition_query)";
        $result = $this->dbh->Query($query);
        if ($this->dbh->GetNumberRows($result))
            $this->objList->total_num = $this->dbh->GetValue($result, 0, "cnt");
         */

        $this->dbh->FreeResults($result);

        // Get facets
        // ----------------------------------------
        if (is_array($this->facetFields) && count($this->facetFields))
        {
            foreach ($this->facetFields as $fldname=>$fldcnt)
            {
                $query = "select distinct($fldname), count($fldname) as cnt from ".$objectTable." where id is not null ";
                if ($condition_query)
                    $query .= " and ($condition_query) ";
                $query .= " GROUP BY $fldname";
                $result = $this->dbh->Query($query);
                $num = $this->dbh->GetNumberRows($result);
                for ($j = 0; $j < $num; $j++)
                {
                    $row = $this->dbh->GetRow($result, $j);

                    if(!isset($this->objList->facetCounts[$fldname]))
                        $this->objList->facetCounts[$fldname] = array();
                    else if(!is_array($this->objList->facetCounts[$fldname]))
                        $this->objList->facetCounts[$fldname] = array();

                    $this->objList->facetCounts[$fldname][$row[$fldname]] = $row['cnt'];
                }
            }
        }		

        // Get aggregates
        // ----------------------------------------
        if (is_array($this->aggregateFields) && count($this->aggregateFields))
        {
            foreach ($this->aggregateFields as $fldname=>$type)
            {
                switch ($type)
                {
                case 'avg':
                    $aggfunct = "avg";
                    break;
                case 'sum':
                default:
                    $aggfunct = "sum";
                }

                $query = "select $aggfunct($fldname) as cnt from ".$objectTable." where id is not null ";
                if ($condition_query)
                    $query .= " and ($condition_query) ";
                $result = $this->dbh->Query($query);
                $num = $this->dbh->GetNumberRows($result);
                for ($j = 0; $j < $num; $j++)
                {
                    $row = $this->dbh->GetRow($result, $j);

                    if(!isset($this->objList->aggregateCounts[$fldname]))
                        $this->objList->aggregateCounts[$fldname] = array();
                    else if(!is_array($this->objList->aggregateCounts[$fldname]))
                        $this->objList->aggregateCounts[$fldname] = array();

                    $this->objList->aggregateCounts[$fldname][$type] = $row['cnt'];
                }
            }
		}
	}

	/**
	 * Create the condition string based on params
	 *
	 * @param string $conditionText Optional full-text query string
	 * @param array $conditions Conditions array - array(array('blogic', 'field', 'operator', 'value'))
	 * @return string Condition string to be used in 'WHERE' statement of SQL query
	 */
	private function buildConditionString($conditionText, $conditions)
	{
		$dbh = $this->dbh;
		$cond_str = "";
		$ofields = $this->obj->fields->getFields();

		// General Search
		// -------------------------------------------------------------
		if ($conditionText && $this->obj->fields->useCustomTable)
		{
			// First add full-text fields
			// ------------------------------------------------
			$part_buf = "";
			foreach ($ofields as $fname=>$field)
			{
				$buf = "";

				if ($field['type'] == 'text' && $field['subtype'])
					$buf = "lower($fname) like lower('%".$dbh->Escape(str_replace(" ", "%", str_replace("*", "%", $conditionText)))."%') ";
				else if ($field['type'] == 'text')
					$buf = " to_tsvector($fname) @@ plainto_tsquery('".$dbh->Escape($conditionText)."') ";

				if ($buf)
				{
					if ($part_buf)
						$part_buf .= " OR ";

					$part_buf .= $buf;
				}
			}

			// Apply full text to the condition string if set
			if ($cond_str && $part_buf) $cond_str .= " AND ";
			if ($part_buf) $cond_str .= "($part_buf) ";
			
			// Now add all other fields
			// ------------------------------------------------
			if (strpos($varval, " ") != false)
				$parts = explode(" ", $varval);
			else
				$parts = array($varval);
			foreach ($parts as $part)
			{
				$part_buf = "";

				if (is_numeric($part))
				{
					foreach ($ofields as $fname=>$field)
					{
						$buf = "";
						switch ($field['type'])
						{
						/*
						case 'text':

							if ($field["subtype"])
								$buf .= "lower($fname) like lower('%".$dbh->Escape($part)."%') ";
							else
								$buf .= " to_tsvector($fieldName) @@ plainto_tsquery('".$dbh->Escape($condValue)."') ";

							break;
						*/
						case 'number':
						case 'real':
						case 'integer':
						case 'bigint':
						case 'int8':
							if (is_numeric($varval))
								$buf .= "$fname='".$dbh->Escape($part)."'";
							break;
						default:
							// No conditions
							break;
						}

						if ($buf)
						{
							if ($part_buf)
								$part_buf .= " OR ";

							$part_buf .= $buf;
						}
					}
				}

				if ($cond_str && $part_buf) $cond_str .= " AND ";
				if ($part_buf) $cond_str .= "($part_buf) ";
			}
			/*
			}
			else
			{
				foreach ($ofields as $fname=>$field)
				{
					$buf = "";
					switch ($field['type'])
					{
					case 'text':
						if ($field['subtype'])
							$buf .= "lower($fname) like lower('%".$dbh->Escape($varval)."%')";
						break;
					case 'number':
					case 'real':
					case 'integer':
					case 'bigint':
					case 'int8':
						if (is_numeric($varval))
							$buf .= "$fname='".$dbh->Escape($varval)."'";
						break;
					}

					if ($cond_str && $buf) $cond_str .= " or ";
					$cond_str .= $buf;
				}

				if (is_numeric($varval))
				{
					if ($cond_str) $cond_str .= " or ";
					$cond_str .= " id='$varval' ";
				}
			}
			 */
		}
		else if ($conditionText && !$this->obj->fields->useCustomTable)
		{
			$cond_str = "tsv_fulltext @@ plainto_tsquery('".$dbh->Escape($conditionText)."')";
		}

		if ($cond_str)
			$cond_str = " ($cond_str) ";

		// Advanced Search
		// -------------------------------------------------------------
		$adv_cond = $this->buildAdvancedConditionString($conditions);
		if ($cond_str && $adv_cond)
			$cond_str .= " and ($adv_cond) ";
		else if (!$cond_str && $adv_cond)
			$cond_str = " ($adv_cond) ";

		return $cond_str;
	}


	/**
	 * Take an array of conditions and parse them into an SQL query
	 *
	 * @param array $conditions Array associted array(array('blogoc', 'field', 'operator', 'value'))
	 */
	public function buildAdvancedConditionString($conditions)
	{
		if ($this->objList->user)
			$userid = $this->objList->user->id;
		$dbh = $this->dbh;
		$cond_str = "";
		$inOrGroup = false;

		// First determine if we are querying deleted items or not
		foreach ($conditions as $cond)
		{
			// Check for deleted
			if ($cond['field'] == "f_deleted" && $cond['operator'] == "is_equal" && $cond['value'] == "t")
				$this->objList->hideDeleted = false;
		}

        // No need to query partion because constraints should take care of that
		$objectTable = $this->obj->getObjectTable();
		//$objectTable = $this->obj->getObjectTable(true, ($this->objList->hideDeleted) ? false : true);

		if (count($conditions))
		{
			foreach ($conditions as $cond)
			{
				$blogic = $cond['blogic'];
				$fieldName = $cond['field'];
				$operator = $cond['operator'];
				$condValue = $cond['value'];

				$buf = "";

				// Check for deleted
				if ($fieldName == "f_deleted" && $operator == "is_equal" && $condValue == "t")
					$this->objList->hideDeleted = false;

				// Look for associated object conditions
				$parts = array($fieldName);
				if (strpos($fieldName, '.'))
					$parts = explode(".", $fieldName);

				$field = $this->obj->fields->getField($parts[0]);
				if (count($parts) > 1)
				{
					$fieldName = $parts[0];
					$ref_field = $parts[1];
					$field['type'] = "object_dereference";
				}
				else
				{
					$ref_field = "";
				}

				if (!$field)
					continue; // Skip non-existant field

				if ($condValue!="" && $condValue!=null)
				{
					switch ($operator)
					{
					case 'is_equal':
						switch ($field["type"])
						{
						case 'object':
							if ($field['subtype'])
							{
								/*
								if ($this->obj->fields->parentField == $fieldName && is_numeric($condValue))
								{
									$tmp_obj = new CAntObject($this->dbh, $field["subtype"]);
									$children = $this->objList->getHeiarchyDown($tmp_obj->getObjectTable(true), 
																				$field['fkey_table']["parent"], $condValue);
									$tmp_cond_str = "";
									foreach ($children as $child)
									{
										if ($tmp_cond_str) $tmp_cond_str .= " or ";
										$tmp_cond_str .= " $fieldName='".$dbh->Escape($child)."' ";
									}
									$buf .= "($tmp_cond_str) ";
								}
								*/
								if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
								{
									$tmp_obj = new CAntObject($this->dbh, $field["subtype"]);
									$children = $this->objList->getHeiarchyDown($tmp_obj->object_table, $field['fkey_table']["parent"], $condValue);
									$tmp_cond_str = "";
									foreach ($children as $child)
									{
										if ($tmp_cond_str) $tmp_cond_str .= " or ";
										$tmp_cond_str .= " $fieldName='".$dbh->Escape($child)."' ";
									}
									$buf .= "($tmp_cond_str) ";
								}
								else
								{
									$buf .= " $fieldName='".$dbh->Escape($condValue)."' ";
								}
								break;
							}
						case 'object_multi':
							$tmp_cond_str = "";
							if ($condValue == "" || $condValue == "NULL")
							{
								$buf .= " not EXISTS (select 1 from object_associations
														where object_associations.object_id=".$objectTable.".id
														and type_id='".$this->obj->object_type_id."'
														and field_id='".$field['id']."') ";
							}
							else
							{
								$parts = explode(":", $condValue); // obj_type:object_id

								if (count($parts)==2)
								{
									$otid = objGetAttribFromName($this->dbh, $parts[0], "id");
									if (is_numeric($otid) && is_numeric($parts[1]))
									{
										$buf .= " EXISTS (select 1 from object_associations 
													where object_associations.object_id=".$objectTable.".id
													and type_id='".$this->obj->object_type_id."' and field_id='".$field['id']."'
													and assoc_type_id='$otid' and assoc_object_id='".$parts[1]."') ";
									}
								}
								else if (count($parts)==1) // only query assocaited type
								{
									$otid = objGetAttribFromName($this->dbh, $parts[0], "id");
									if (is_numeric($otid))
									{
										$buf .= " EXISTS (select 1 from object_associations 
													where object_associations.object_id=".$objectTable.".id and
													type_id='".$this->obj->object_type_id."' and field_id='".$field['id']."'
													and assoc_type_id='$otid') ";
									}
								}
							}
							break;
						case 'object_dereference':
							if ($field['subtype'] && $ref_field)
							{
								$tmpobj = new CAntObject($this->dbh, $field['subtype']);
								$indx = new CAntObjectIndexDb($this->dbh, $tmpobj);
								$tmp_obj_cnd_str = $indx->buildAdvancedConditionString(
									array(array('blogoc'=>'and', 'field'=>$ref_field, 'operator'=>$operator, 'value'=>$condValue))
								);

								if ($condValue == "" || $condValue == "NULL")
								{
									$buf .= " ".$objectTable.".$fieldName not in (select id from ".$tmpobj->getObjectTable()."
																								where $tmp_obj_cnd_str) ";
								}
								else
								{
									$buf .= " ".$objectTable.".$fieldName in (select id from ".$tmpobj->getObjectTable()."
																								where $tmp_obj_cnd_str) ";
								}
							}
							break;
						case 'fkey_multi':
							$tmp_cond_str = "";
							if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
							{
								$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
								$tmp_cond_str = "";
								foreach ($children as $child)
								{
									if ($tmp_cond_str) $tmp_cond_str .= " or ";
									$tmp_cond_str .= $field['fkey_table']['ref_table']['ref']."='$child'";
								}
							}
							else if ($condValue && $condValue!="NULL")
							{
								$tmp_cond_str = $field['fkey_table']['ref_table']['ref']."='$condValue'";
							}

							$thisfld = $field['fkey_table']['ref_table']["this"];
							$reftbl = $field['fkey_table']['ref_table']['table'];
							if ($condValue == "" || $condValue == "NULL")
							{
								$buf .= " NOT EXISTS (select 1 from  ".$reftbl." where 
												  ".$reftbl.".".$thisfld."=".$objectTable.".idr) ";
							}
							else
							{
								$buf .= " EXISTS (select 1 from  ".$reftbl." where 
												  ".$reftbl.".".$thisfld."=".$objectTable.".id and ($tmp_cond_str)) ";
							}
							break;
						case 'fkey':
							if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
							{
								$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
								$tmp_cond_str = "";
								foreach ($children as $child)
								{
									if ($tmp_cond_str) $tmp_cond_str .= " or ";
									$tmp_cond_str .= " $fieldName='".$dbh->Escape($child)."' ";
								}
							}
							else
							{
								$tmp_cond_str = " $fieldName='".$dbh->Escape($condValue)."' ";
							}

							$buf .= "($tmp_cond_str) ";
							break;
						case 'text':
							if ($field["subtype"])
								$buf .= " lower($fieldName)=lower('".$dbh->Escape($condValue)."') ";
							else
								$buf .= " to_tsvector($fieldName) @@ plainto_tsquery('".$dbh->Escape($condValue)."') ";

							break;
						default:
							$buf .= " $fieldName='".$dbh->Escape($condValue)."' ";
							break;
						}
						break;
					case 'is_not_equal':
						switch ($field["type"])
						{
						case 'object':
							// Check if we are querying table directly, otherwise fall through to object_multi code
							if ($field['subtype'])
							{
								if ($condValue == "" || $condValue == "NULL")
								{
									$buf .= " $fieldName is not null";
								}
								else if (isset($field["subtype"]) && $this->obj->fields->parentField == $fieldName && is_numeric($condValue))
								{
									$tmp_obj = new CAntObject($this->dbh, $field["subtype"]);

									if ($tmp_obj->fields->parentField)
									{
										$buf .= " $fieldName not in (WITH RECURSIVE children AS
													(
														-- non-recursive term
														SELECT id FROM " . $tmp_obj->getObjectTable(true) . " WHERE id = '$condValue'
														UNION ALL
														-- recursive term
														SELECT " . $tmp_obj->getObjectTable(true) . ".id
														FROM
															" . $tmp_obj->getObjectTable(true) . "
														JOIN
															children AS chld
															ON (" . $tmp_obj->getObjectTable(true) . "." . $tmp_obj->fields->parentField . " = chld.id)
													)
													SELECT id
													FROM children)";
									}
								}
								else
								{
									$buf .= " $fieldName!='".$dbh->Escape($condValue)."' ";
								}

								break;
							}
						case 'object_multi':
							if ($condValue == "" || $condValue == "NULL")
							{
								$buf .= " ".$objectTable.".id in (select object_id from object_associations
																				where type_id='".$this->obj->object_type_id."'
																				and field_id='".$field['id']."') ";
							}
							else
							{
								$parts = explode(":", $condValue); // obj_type:object_id

								if (count($parts)==2)
								{
									$otid = objGetAttribFromName($this->dbh, $parts[0], "id");
									if (is_numeric($otid) && is_numeric($parts[1]))
									{
										$buf .= " ".$objectTable.".id not in (select object_id from object_associations 
																			where type_id='".$this->obj->object_type_id."' and field_id='".$field['id']."'
																			and assoc_type_id='$otid' and assoc_object_id='".$parts[1]."') ";
									}
								}
							}
							break;
						case 'object_dereference':
							$tmp_cond_str = "";
							if ($field['subtype'] && $ref_field)
							{
								$tmpobj = new CAntObject($this->dbh, $field['subtype']);
								$indx = new CAntObjectIndexDb($this->dbh, $tmpobj);
								$tmp_obj_cnd_str = $indx->buildAdvancedConditionString(
									array(array('blogoc'=>'and', 'field'=>$ref_field, 'operator'=>$operator, 'value'=>$condValue))
								);

								/*
								$tmpobj = new CAntObject($this->dbh, $field['subtype']);
								$ol = new CAntObjectList($this->dbh, $field['subtype'], $this->objList->user);
								$ol->addCondition("and", $ref_field, $operator, $condValue);
								$tmp_obj_cnd_str = $ol->buildConditionString($conds);
								 */

								if ($condValue == "" || $condValue == "NULL")
								{
									$buf .= " ".$objectTable.".$fieldName is not null ";
								}
								else
								{
									$buf .= " ".$objectTable.".$fieldName not in (select id from ".$tmpobj->getObjectTable(true)."
																								where $tmp_obj_cnd_str) ";
								}
							}
							break;
						case 'fkey_multi':
							if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
							{
								$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
								$tmp_cond_str = "";
								foreach ($children as $child)
								{
									if ($tmp_cond_str) $tmp_cond_str .= " or ";
									$tmp_cond_str .= $field['fkey_table']['ref_table']['ref']."='$child'";
								}
							}
							else
							{
								$tmp_cond_str = $field['fkey_table']['ref_table']['ref']."='$condValue'";
							}

							$buf .= " ".$objectTable.".id not in (select ".$field['fkey_table']['ref_table']["this"]."
																			  from ".$field['fkey_table']['ref_table']['table']." 
																			  where $tmp_cond_str) ";
							break;
						case 'fkey':
							if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
							{
								$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
								$tmp_cond_str = "";
								foreach ($children as $child)
								{
									if ($tmp_cond_str) $tmp_cond_str .= " and ";
									$tmp_cond_str .= " $fieldName!='".$dbh->Escape($child)."' ";
								}
							}
							else
							{
								$tmp_cond_str = " $fieldName!='".$dbh->Escape($condValue)."' ";
							}

							$buf .= "(($tmp_cond_str)  or $fieldName is null) ";
							break;
						case 'text':
							if ($field["subtype"])
								$buf .= " lower($fieldName)!=lower('".$dbh->Escape($condValue)."') ";
							else
								$buf .= " (to_tsvector($fieldName) @@ plainto_tsquery('".$dbh->Escape($condValue)."'))='f' ";
							break;
						default:
							$buf .= " ($fieldName!='".$dbh->Escape($condValue)."' or $fieldName is null) ";
							break;
						}
						break;
					case 'is_greater':
						switch ($field["type"])
						{
						case 'object_multi':
						case 'object':
						case 'fkey_multi':
						case 'text':
							break;
						default:
							$buf .= " $fieldName>'".$dbh->Escape($condValue)."' ";
							break;
						}
						break;
					case 'is_less':
						switch ($field["type"])
						{
						case 'object_multi':
						case 'object':
						case 'fkey_multi':
							break;
						case 'text':
							break;
						default:
							$buf .= " $fieldName<'".$dbh->Escape($condValue)."' ";
							break;
						}
						break;
					case 'is_greater_or_equal':
						switch ($field["type"])
						{
						case 'object':
							if ($field['subtype'])
							{
								$tmp_obj = CAntObject::factory($this->dbh, $field["subtype"]);
								if (isset($tmp_obj->def->parentField) && is_numeric($condValue))
								{
									$children = $this->objList->getHeiarchyDown($tmp_obj->object_table, $tmp_obj->def->parentField, $condValue);
									$tmp_cond_str = "";
									foreach ($children as $child)
									{
										if ($tmp_cond_str) $tmp_cond_str .= " or ";
										$tmp_cond_str .= " $fieldName='".$dbh->Escape($child)."' ";
									}
									$buf .= "($tmp_cond_str) ";
								}
								else
								{
									$buf .= " $fieldName='".$dbh->Escape($condValue)."' ";
								}
								break;
							}
							break;
						case 'object_multi':
						case 'fkey_multi':
							break;
						case 'text':
							break;
						default:
							$buf .= " $fieldName>='".$dbh->Escape($condValue)."' ";
							break;
						}
						break;
					case 'is_less_or_equal':
						switch ($field["type"])
						{
						case 'object':
							if (isset($field["subtype"]) && $this->obj->fields->parentField == $fieldName && is_numeric($condValue))
							{
								$tmp_obj = new CAntObject($this->dbh, $field["subtype"]);

								if ($tmp_obj->fields->parentField)
								{
									$buf .= " $fieldName in (WITH RECURSIVE children AS
												(
													-- non-recursive term
													SELECT id FROM " . $tmp_obj->getObjectTable(true) . " WHERE id = '$condValue'
													UNION ALL
													-- recursive term
													SELECT " . $tmp_obj->getObjectTable(true) . ".id
													FROM
														" . $tmp_obj->getObjectTable(true) . "
													JOIN
														children AS chld
														ON (" . $tmp_obj->getObjectTable(true) . "." . $tmp_obj->fields->parentField . " = chld.id)
												)
												SELECT id
												FROM children)";
								}
							}
							break;
						case 'object_multi':
						case 'fkey_multi':
							break;
						case 'text':
							break;
						default:
							$buf .= " $fieldName<='".$dbh->Escape($condValue)."' ";
							break;
						}
						break;
					case 'begins':
					case 'begins_with':
						switch ($field["type"])
						{
						case 'text':
							if ($field["subtype"])
								$buf .= " lower($fieldName) like lower('".$dbh->Escape($condValue)."%') ";
							else
								$buf .= " to_tsvector($fieldName) @@ plainto_tsquery('".$dbh->Escape($condValue)."*') ";
							break;
						default:
							break;
						}
						break;
					case 'contains':
						switch ($field["type"])
						{
						case 'text':
							/*
							if ($field["subtype"])
								$buf .= " lower($fieldName) like lower('%".$dbh->Escape($condValue)."%') ";
							else
							 */
							if ($field["subtype"])
								$buf .= " lower($fieldName) like lower('%".$dbh->Escape($condValue)."%') ";
							else
								$buf .= " to_tsvector($fieldName) @@ plainto_tsquery('".$dbh->Escape($condValue)."') ";

							break;
						default:
							break;
						}
						break;
					case 'day_is_equal':
						if ($field["type"] == "date" || $field["type"] == "timestamp")
						{
							switch ($condValue)
							{
							case '<%current_day%>':
								$tmpcond = "extract('day' from now())";
								break;
							default:
								$tmpcond = "'".$dbh->Escape($condValue)."'";
								break;
							}

							$buf .= " extract(day from $fieldName)=$tmpcond ";
						}
						break;
					case 'month_is_equal':
						if ($field["type"] == "date" || $field["type"] == "timestamp")
						{
							switch ($condValue)
							{
							case '<%current_month%>':
								$tmpcond = "extract('month' from now())";
								break;
							default:
								$tmpcond = "'".$dbh->Escape($condValue)."'";
								break;
							}

							$buf .= " extract(month from $fieldName)=$tmpcond ";
						}
						break;
					case 'year_is_equal':
						if ($field["type"] == "date" || $field["type"] == "timestamp")
						{
							switch ($condValue)
							{
							case '<%current_year%>':
								$tmpcond = "extract('year' from now())";
								break;
							default:
								$tmpcond = "'".$dbh->Escape($condValue)."'";
								break;
							}

							$buf .= " extract(year from $fieldName)=$tmpcond ";
						}
						break;
					case 'last_x_days':
						if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->Escape($condValue)." days')";
						}
						break;
					case 'last_x_weeks':
						if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->Escape($condValue)." weeks')";
						}
						break;
					case 'last_x_months':
						if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->Escape($condValue)." months')";
						}
						break;
					case 'last_x_years':
						if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->Escape($condValue)." years')";
						}
						break;
					case 'next_x_days':
						if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->Escape($condValue)." days')";
						}
						break;
					case 'next_x_weeks':
						if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->Escape($condValue)." weeks')";
						}
						break;
					case 'next_x_months':
						if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->Escape($condValue)." months')";
						}
						break;
					case 'next_x_years':
						if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->Escape($condValue)." years')";
						}
						break;
					}
				}
				else // handle null
				{
					switch ($operator)
					{
					case 'is_equal':
						// Deal with "isnull"
						switch ($field["type"])
						{
                        case 'object_multi':
							$buf .= " ".$objectTable.".id not in (select object_id from object_associations
																				where type_id='".$this->obj->object_type_id."'
																				and field_id='".$field['id']."') ";
                            break;
						case 'fkey_multi':
							$buf .= " ".$objectTable.".id not in (select ".$fdef['fkey_table']['ref_table']["this"]."
																		from ".$fdef['fkey_table']['ref_table']['table']." 
																		where ".$fdef['fkey_table']['ref_table']['this']."='".$this->obj->id."') ";
							break;
						case 'text':
							$buf .= " ($fieldName='' or $fieldName is null) ";
							break;
                        case 'object':
						default:
							$buf .= " $fieldName is null ";
							break;
						}
						break;
					case 'is_not_equal':
						// Deal with "isnull"
						switch ($field["type"])
						{
						case 'object_multi':
							$buf .= " ".$objectTable.".id in (select object_id from object_associations
																				where type_id='".$this->obj->object_type_id."'
																				and field_id='".$field['id']."') ";
							break;
						case 'fkey_multi':
							$buf .= " ".$objectTable.".id in (select ".$fdef['fkey_table']['ref_table']["this"]."
																		from ".$fdef['fkey_table']['ref_table']['table']." 
																		where ".$fdef['fkey_table']['ref_table']['this']."='".$this->obj->id."') ";
							break;
						case 'text':
							$buf .= " ($fieldName!='' and $fieldName is not null) ";
							break;
						case 'object':
						default:
							$buf .= " $fieldName is not null ";
							break;
						}
						break;
					}
				}

				// New system to added to group "or" statements
				if ($blogic == "and")
				{
					if ($buf)
					{
						if ($cond_str) 
							$cond_str .= ") $blogic (";
						else
							$cond_str .= " ( ";
						$inOrGroup = true;
					}
				}
				else if ($cond_str && $buf) // or
					$cond_str .= " $blogic ";

				$cond_str .= $buf;
			}

			// Close condtion grouping
			if ($inOrGroup)
				$cond_str .= ")";
		}

		//echo "COND STR: ".$cond_str;
		return $cond_str;
	}

	/**
	 * @depricated This has been rejected in favor of direct table queries
	public function buildConditionStringNew($conditionText, $conditions)
	{
		$dbh = $this->dbh;
		$cond_str = "";
		$ofields = $this->obj->fields->getFields();

		// Advanced Search
		// -------------------------------------------------------------
		$adv_cond = $this->buildAdvancedConditionStringNew($conditions);

		if ($adv_cond)
			$cond_str = " ($adv_cond) ";


		// General text Search
		// -------------------------------------------------------------
		if ($conditionText)
		{
			// Determine if we will pull from deleted, non-deleted, or both indexes
			$varval = str_replace("*", "%", $conditionText);
			$varval = str_replace(" ", " & ", $varval);

			$txt_cond = " EXISTS (select 1 from  object_index_fulltext where object_type_id='".$this->obj->object_type_id."'
										".(($this->objList->hideDeleted)?" and f_deleted is false ":"")."
										AND object_id=".$this->obj->object_table.".id
										AND tsv_keywords @@ to_tsquery('".$dbh->Escape($varval)."')) ";
		}

		if ($cond_str && $txt_cond)
			$cond_str .= " and ($txt_cond) ";
		else if (!$cond_str && $txt_cond)
			$cond_str = " ($txt_cond) ";

		return $cond_str;
	}
	 */

	/**
	 * @depricated This uses the object_index tables that are no longer needed
	private function buildAdvancedConditionStringNew($conditions)
	{
		if ($this->objList->user)
			$userid = $this->objList->user->id;
		$dbh = $this->dbh;
		$cond_str = "";
		$inOrGroup = false;
		$index_tbl = "object_index_".$this->obj->object_type_id; // Query directly

		// Define if we will pull from deleted, non-deleted, or both indexes
		if (count($conditions))
		{
			foreach ($conditions as $cond)
			{
				$blogic = $cond['blogic'];
				$fieldName = $cond['field'];
				$operator = $cond['operator'];
				$condValue = $cond['value'];

				if ($fieldName == "f_deleted" && $operator == "is_equal" && $condValue == "t")
				{
					$this->objList->hideDeleted = false;
				}
			}
		}

		if (count($conditions))
		{
			foreach ($conditions as $cond)
			{
				$blogic = $cond['blogic'];
				$fieldName = $cond['field'];
				$operator = $cond['operator'];
				$condValue = $cond['value'];
				$buf = "";

				// Look for associated object conditions
				if (strpos($fieldName, '.'))
				{
					$parts = explode(".", $fieldName);
				}
				else
				{
					$parts[0] = $fieldName;
				}

				$field = $this->obj->fields->getField($parts[0]);
				if (count($parts) > 1)
				{
					$fieldName = $parts[0];
					$ref_field = $parts[1];
					$field['type'] = "object_reference";
				}
				else
				{
					$ref_field = "";
				}

				$inx_query_cond = " object_type_id='".$this->obj->object_type_id."'";
				if ($this->objList->hideDeleted)
					$inx_query_cond .= " and f_deleted='f'";
				$inx_query_cond .= " and object_id=".$this->obj->object_table.".id and field_id='".$field['id']."' ";

				if (!$field)
					continue; // Skip non-existant field

				switch ($operator)
				{
				case 'is_equal':
					switch ($field["type"])
					{
					case 'object':
						if ($field['subtype'])
						{
							if ($condValue == "" || $condValue == "NULL")
							{
								$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond) ";
							}
							else if (is_numeric($condValue))
							{
								$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
														and val_number='$condValue') ";
							}
							break;
						}
					case 'object_reference':
						// TODO: review
						$tmp_cond_str = "";
						if ($field['subtype'] && $ref_field)
						{
							$tmpobj = new CAntObject($this->dbh, $field['subtype']);
							$ol = new CAntObjectList($this->dbh, $field['subtype'], $this->objList->user, $conds);
							$ol->addCondition("and", $ref_field, $operator, $condValue);
							$tmp_obj_cnd_str = $ol->buildConditionString($conds);

							if ($condValue == "" || $condValue == "NULL")
							{
								$buf .= " ".$this->obj->object_table.".$fieldName not in (select id from ".$tmpobj->object_table."
																							where $tmp_obj_cnd_str) ";
							}
							else
							{
								$buf .= " ".$this->obj->object_table.".$fieldName in (select id from ".$tmpobj->object_table."
																							where $tmp_obj_cnd_str) ";
							}
						}
						break;
					case 'object_multi':
						$tmp_cond_str = "";
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " not EXISTS (select 1 from object_associations
													where object_associations.object_id=".$this->obj->object_table.".id
													and type_id='".$this->obj->object_type_id."'
													and field_id='".$field['id']."') ";
						}
						else
						{
							$parts = explode(":", $condValue); // obj_type:object_id

							if (count($parts)==2)
							{
								$otid = objGetAttribFromName($this->dbh, $parts[0], "id");
								if (is_numeric($otid) && is_numeric($parts[1]))
								{
									$buf .= " EXISTS (select 1 from object_associations 
												where object_associations.object_id=".$this->obj->object_table.".id
												and type_id='".$this->obj->object_type_id."' and field_id='".$field['id']."'
												and assoc_type_id='$otid' and assoc_object_id='".$parts[1]."') ";
								}
							}
							else if (count($parts)==1) // only query assocaited type
							{
								$otid = objGetAttribFromName($this->dbh, $parts[0], "id");
								if (is_numeric($otid))
								{
									$buf .= " EXISTS (select 1 from object_associations 
												where object_associations.object_id=".$this->obj->object_table.".id and
												type_id='".$this->obj->object_type_id."' and field_id='".$field['id']."'
												and assoc_type_id='$otid') ";
								}
							}
						}
						break;
					case 'fkey_multi':
						$tmp_cond_str = "";
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
							$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " or ";
								$tmp_cond_str .= "val_number='$child'";
							}
						}
						else if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "val_number='$condValue'";
						}

						$thisfld = $field['fkey_table']['ref_table']["this"];
						$reftbl = $field['fkey_table']['ref_table']['table'];

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
													and ($tmp_cond_str)) ";
						}
						break;
					case 'fkey':
						$tmp_cond_str = "";
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
							$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " or ";
								$tmp_cond_str .= "val_number='$child'";
							}
						}
						else if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "val_number='$condValue'";
						}

						$thisfld = $field['fkey_table']['ref_table']["this"];
						$reftbl = $field['fkey_table']['ref_table']['table'];

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
													and ($tmp_cond_str)) ";
						}
						break;
					case 'text':
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else
						{
							if ($field['subtype'] == "" || $field['subtype'] == null || $field['subtype'] == "html" || $field['subtype'] == "plain")
							{
								$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
														and val_tsv @@ to_tsquery('".strtolower($dbh->Escape($condValue))."')) ";
							}
							else
							{
								$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
														and lower(val_text)='".strtolower($dbh->Escape($condValue))."') ";
							}
						}
						break;
					case 'integer':
					case 'number':
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else if (is_numeric($condValue))
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
													and val_number='$condValue') ";
						}
						break;
					case 'bool':
						if ($condValue == "" || $condValue == "f" || $condValue == "NULL")
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond and (val_bool='f' or val_bool is null)) ";
						}
						else
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond and val_bool='t') ";
						}
						break;
					default:
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else if (is_numeric($condValue))
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
													and val_text='$condValue') ";
						}

						//$buf .= " $fieldName='".$dbh->Escape($condValue)."' ";
						break;
					}
					break;
				case 'is_not_equal':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
						$tmp_cond_str = "";
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
							$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " or ";
								$tmp_cond_str .= "val_text='$child'";
							}
						}
						else if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "val_text='$condValue'";
						}

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond
													and ($tmp_cond_str)) ";
						}

						break;
					case 'object_reference':
						$tmp_cond_str = "";
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
							$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " or ";
								$tmp_cond_str .= "val_text='$child'";
							}
						}
						else if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "val_text='$condValue'";
						}

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond
													and ($tmp_cond_str)) ";
						}
						
						break;
					case 'fkey_multi':
						$tmp_cond_str = "";
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
							$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " or ";
								$tmp_cond_str .= "val_number='$child'";
							}
						}
						else if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "val_number='$condValue'";
						}

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond
													and ($tmp_cond_str)) ";
						}
						
						break;
					case 'fkey':
						$tmp_cond_str = "";
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
							$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " or ";
								$tmp_cond_str .= "val_number='$child'";
							}
						}
						else if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "val_number='$condValue'";
						}

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond
													and ($tmp_cond_str)) ";
						}
						break;
					case 'text':

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond) ";
						}
						else
						{
							if ($field['subtype'] == "" || $field['subtype'] == null || $field['subtype'] == "html" || $field['subtype'] == "plain")
							{
								$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond
														and val_tsv @@ to_tsquery('".strtolower($dbh->Escape($condValue))."')) ";
							}
							else
							{
								$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond
														and lower(val_text)='".strtolower($dbh->Escape($condValue))."') ";
							}
						}

						break;
					case 'integer':
					case 'number':
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= " EXISTS (select 1 from object_index where $inx_query_cond) ";
						}
						else if (is_numeric($condValue))
						{
							$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond
													and val_number='$condValue') ";
						}
						break;
					case 'bool':
						if ($condValue == "" || $condValue == "f" || $condValue == "NULL")
						{
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond and val_bool='t') ";
						}
						if ($condValue == "t")
						{
							//$buf .= " NOT EXISTS (select 1 from  object_index where $inx_query_cond and val_bool='f') ";
							$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond and val_bool='f') ";
						}
						break;
					default:
						// No default, unsafe
						//$buf .= " ($fieldName!='".$dbh->Escape($condValue)."' or $fieldName is null) ";
						break;
					}
					break;
				case 'is_greater':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
					case 'fkey_multi':
						break;
					case 'text':
						break;
					case 'number':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_number>".$dbh->EscapeNumber($condValue).") ";
						break;
					case 'date':
					case 'timestamp':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>".$dbh->EscapeDate($condValue).") ";
						break;
					default:
						//$buf .= " $fieldName>'".$dbh->Escape($condValue)."' ";
						break;
					}
					break;
				case 'is_less':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
					case 'fkey_multi':
						break;
					case 'text':
						break;
					case 'number':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_number<".$dbh->EscapeNumber($condValue).") ";
						break;
					case 'date':
					case 'timestamp':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp<".$dbh->EscapeDate($condValue).") ";
						break;
					default:
						//$buf .= " $fieldName<'".$dbh->Escape($condValue)."' ";
						break;
					}
					break;
				case 'is_greater_or_equal':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
					case 'fkey_multi':
						break;
					case 'text':
						break;
					case 'number':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_number>=".$dbh->EscapeNumber($condValue).") ";
						break;
					case 'date':
					case 'timestamp':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=".$dbh->EscapeDate($condValue).") ";
						break;
					default:
						//$buf .= " $fieldName>='".$dbh->Escape($condValue)."' ";
						break;
					}
					break;
				case 'is_less_or_equal':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
					case 'fkey_multi':
						break;
					case 'text':
						break;
					case 'number':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_number<=".$dbh->EscapeNumber($condValue).") ";
						break;
					case 'date':
					case 'timestamp':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp<=".$dbh->EscapeDate($condValue).") ";
						break;
					default:
						//$buf .= " $fieldName<='".$dbh->Escape($condValue)."' ";
						break;
					}
					break;
				case 'begins':
				case 'begins_with':
					switch ($field["type"])
					{
					case 'text':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and lower(val_text) like '".strtolower($dbh->Escape($condValue))."%') ";
						break;
					default:
						break;
					}
					break;
				case 'contains':
					switch ($field["type"])
					{
					case 'text':
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and lower(val_text) like '%".strtolower($dbh->Escape($condValue))."%') ";
						break;
					default:
						break;
					}
					break;
				case 'day_is_equal':
					if ($field["type"] == "date" || $field["type"] == "timestamp")
					{
						switch ($condValue)
						{
						case '<%current_day%>':
							$tmpcond = "extract('day' from now())";
							break;
						default:
							$tmpcond = "'".$dbh->Escape($condValue)."'";
							break;
						}

						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and extract(day from val_timestamp)=".$condValue.") ";
						//$buf .= " extract(day from $fieldName)=$tmpcond ";
					}
					break;
				case 'month_is_equal':
					if ($field["type"] == "date" || $field["type"] == "timestamp")
					{
						switch ($condValue)
						{
						case '<%current_month%>':
							$tmpcond = "extract('month' from now())";
							break;
						default:
							$tmpcond = "'".$dbh->Escape($condValue)."'";
							break;
						}

						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and extract(month from val_timestamp)=".$condValue.") ";
						//$buf .= " extract(month from $fieldName)=$tmpcond ";
					}
					break;
				case 'year_is_equal':
					if ($field["type"] == "date" || $field["type"] == "timestamp")
					{
						switch ($condValue)
						{
						case '<%current_year%>':
							$tmpcond = "extract('year' from now())";
							break;
						default:
							$tmpcond = "'".$dbh->Escape($condValue)."'";
							break;
						}

						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and extract(year from val_timestamp)=".$condValue.") ";
						//$buf .= " extract(year from $fieldName)=$tmpcond ";
					}
					break;
				case 'last_x_days':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=(now()-INTERVAL '".$dbh->Escape($condValue)." days')) ";
						//$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->Escape($condValue)." days')";
					}
					break;
				case 'last_x_weeks':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=(now()-INTERVAL '".$dbh->Escape($condValue)." weeks')) ";
						//$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->Escape($condValue)." weeks')";
					}
					break;
				case 'last_x_months':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=(now()-INTERVAL '".$dbh->Escape($condValue)." months')) ";
						//$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->Escape($condValue)." months')";
					}
					break;
				case 'last_x_years':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=(now()-INTERVAL '".$dbh->Escape($condValue)." years')) ";
						//$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->Escape($condValue)." years')";
					}
					break;
				case 'next_x_days':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=now() and val_timestamp<=(now()-INTERVAL '".$dbh->Escape($condValue)." days')) ";
						//$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->Escape($condValue)." days')";
					}
					break;
				case 'next_x_weeks':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=now() and val_timestamp<=(now()-INTERVAL '".$dbh->Escape($condValue)." weeks')) ";
						//$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->Escape($condValue)." weeks')";
					}
					break;
				case 'next_x_months':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=now() and val_timestamp<=(now()-INTERVAL '".$dbh->Escape($condValue)." months')) ";
						//$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->Escape($condValue)." months')";
					}
					break;
				case 'next_x_years':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= " EXISTS (select 1 from  object_index where $inx_query_cond
												and val_timestamp>=now() and val_timestamp<=(now()-INTERVAL '".$dbh->Escape($condValue)." years')) ";
						//$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->Escape($condValue)." years')";
					}
					break;
				}
				
				// New system to added to group "or" statements
				if ($blogic == "and")
				{
					if ($buf)
					{
						if ($cond_str) 
							$cond_str .= ") $blogic (";
						else
							$cond_str .= " ( ";
						$inOrGroup = true;
					}
				}
				else if ($cond_str && $buf) // or
					$cond_str .= " $blogic ";

				$cond_str .= $buf;
			}

			// Close condtion grouping
			if ($inOrGroup)
				$cond_str .= ")";
		}

		//echo "COND STR: ".$cond_str;
		return $cond_str;
	}
	 */
}
?>
