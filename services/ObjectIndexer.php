<?php
/**
 * Handle indexing or reindexing objects
 *
 * Command line arguments
 * -a,--account = (optional) Limit to one account
 * -t,--type = (optional) Object type to index, otherwise index all
 * -i,--index = (optional) Index type to index to, otherwise use system settings
 * -s,--status = (optional) Deleted status can be 'deleted', 'undeleted' or default to 'all'
 * -l,--limit = (optional) Limit the number of objects to index in one pass
 * -p,--purge = (optional) If set then clean existing index and re-index all objects
 *
 * @category	AntService
 * @package		ObjectDynIdx
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntUser.php");		
require_once("lib/CAntObject.php");		
require_once("lib/AntRoutine.php");		

class ObjectIndexer extends AntRoutine
{
	public function main(&$dbh)
	{
		global $_SERVER;
		$rows = array();

		$options = getopt("t:i:s:l:r:");
		
		// Set index type to index to
		$indexType = AntConfig::getInstance()->object_index->type;
		if ($options['i'] || $options['index'])
			$indexType = ($options['i']) ? $options['i'] : $options['index'];

		// Set object type to pull, default to all
		$objectType = "all";
		if ($options['t'] || $options['type'])
			$objectType = ($options['t']) ? $options['t'] : $options['type'];

		// Determine deleted status
		$status = "all";
		if ($options['s'] || $options['status'])
			$status = ($options['s']) ? $options['s'] : $options['status'];

		// Limit
		$limit = null;
		if ($options['l'] || $options['limit'])
			$limit = ($options['l']) ? $options['l'] : $options['limit'];

		// Uninit / reset
		if ($options['r'] || $options['reset'])
		{
			// Turn the cache back on so we can clear the initialized state in cache
			global $ALIB_CACHE_DISABLE;
			$oldval = $ALIB_CACHE_DISABLE;

			// Reset the index
			echo "Resetting index...\t";
			$odef = CAntObject::factory($dbh, "customer");
			if ($indexType) // Can set index type after object type in command line
				$odef->setIndex($indexType);
			$index = $odef->getIndex();
			$index->uninit(); // clear initialized flags and delete index
			echo "[done]\n";

			// Restore cache setting
			$ALIB_CACHE_DISABLE = $oldval;
		}
	
		// Index object based on settings
		// ----------------------------------------------------------------------
		if ($objectType != "all")
			$query = "select id, name, object_table from app_object_types where name='" . $objectType . "' order by id";
		else
			$query = "select id, name, object_table from app_object_types order by id";

		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);

		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			
			$otid = $row['id'];
			$odef = CAntObject::factory($dbh, $row['name']);
			if ($indexType) // Can set index type after object type in command line
				$odef->setIndex($indexType);
			$indexTypeId = $odef->getIndexTypeId();

			// Skip object if f_deleted is not a field (really old data)
			if (!$odef->fields->getField("f_deleted"))
				continue;

			echo "Pulling ".$row['name']." from ".$odef->object_table;
			$query = "select id from " . $odef->getObjectTable() . " where ";

			if ("undeleted" == $status)
				$query .= "f_deleted is not true and ";
			else if ("deleted" == $status)
				$query .= "f_deleted is true and ";

			$query .= " not exists (select 1 from object_indexed where object_type_id='$otid' 
												and object_id=".$odef->object_table.".id 
												and revision=".$odef->object_table.".revision 
												and index_type='$indexTypeId')";
			/*
			if ($odef->fields->getField("ts_updated"))
				$query .= " order by ts_updated DESC";
			else if ($odef->fields->getField("time_updated"))
				$query .= " order by time_updated DESC";
			else if ($odef->fields->getField("time_entered"))
				$query .= " order by time_entered DESC";
			 */
			$query .= " order by id DESC";
			if ($limit)
				$query .= " limit " . $limit;

			$ids = array();
			$res2 = $dbh->Query($query);
			$num2 = $dbh->GetNumberRows($res2);
			echo "\tfound $num2 rows\n";
			for ($j = 0; $j < $num2; $j++)
			{
				$ids[] = $dbh->GetValue($res2, $j, "id");
			}
			$dbh->FreeResults($res2);
			
			for ($j = 0; $j < count($ids); $j++)
			{
				$oid = $ids[$j];

				$loadstart = microtime(true);
				$obj = new CAntObject($dbh, $row['name'], $oid);

				// Fix object if the revision is not set
				if (!$obj->getValue("revision"))
				{
					$dbh->Query("UPDATE ".$odef->object_table." SET revision='1' WHERE id='".$obj->id."'");
					$obj->setValue("revision", 1); // set for index
				}

				if ($indexType) // Can set index type after object type in command line
					$obj->setIndex($indexType);

				$loadend = microtime(true);

				$start = microtime(true);
				if ($obj->index(false, true))
				{
					echo $dbh->dbname."-".$row['name'].": indexed ".($j+1)." of $num2 to " .
							$obj->getIndexTypeId()." in " . 
							round((microtime(true)-$start), 3) . ":" .
							round(($loadend-$loadstart), 3) . " sec\n";
				}
				else
				{
					echo $row['name'].": FAILED ".($j+1)." of $num2 to ".$obj->getIndexTypeId()."\n";
					echo "\t".$obj->index->lastError."\n";
				}


				unset($obj);
			}
		}
	}
}
