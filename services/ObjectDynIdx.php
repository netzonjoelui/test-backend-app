<?php
/**
 * ANT Service that gathers stats from queries and dynamically creates database indexes on field if needed
 *
 * @category	AntService
 * @package		ObjectDynIdx
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntUser.php");		
require_once("lib/CAntObject.php");		
require_once('lib/ServiceLocatorLoader.php');

class ObjectDynIdx extends AntService
{
	public function main(&$dbh)
	{
		// Only run this for accounts that are using the database as the index
		if (AntConfig::getInstance()->object_index['type'] != "db" && !AntConfig::getInstance()->object_index['fulltext_only'])
			return true;

	
		$result = $dbh->Query("SELECT id, name FROM app_object_types WHERE object_table is null or object_table=''");
		$num = $dbh->GetNumberRows($result);
		echo "Checking objects for ".$dbh->dbname."\n";
		// First lets index all non-deleted objects
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			$obj = CAntObject::factory($dbh, $row['name'], null, $this->user);

            $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
            $dm = $sl->get("EntityDefinition_DataMapper");
            $def = $sl->get("EntityDefinitionLoader")->get($row['name']);

			$res2 = $dbh->Query("select name from app_object_type_fields where type_id='".$row['id']."'
								 and name!='id' and name!='uname' and name!='f_deleted' and f_indexed is not true;");
			$num2 = $dbh->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$fname = $dbh->GetValue($res2, $j, "name");

				$cache = CCache::getInstance();
				$cur = $cache->get($dbh->dbname . "/objectdefs/" . $obj->object_type . "/fldidxstat/" . $fname);
				if ($cur != -1 && is_numeric($cur)) 
				{
					// If a field has been queried on 100 times, then we can index
					// but first check to make sure we have enough rows to even care about an index
					$numRows = $dbh->GetValue($dbh->Query("SELECT count(*) as cnt FROM " . $obj->getObjectTable()), 0, "cnt");
					if ($cur >= 100 && $numRows > 1000)
					{
						$field = $def->getField($fname);
						if ($field)
						{
							$ret = $dm->createFieldIndex($def, $field);

							// Flag field so we don't try to reindex it
							if ($ret)
							{
								echo "\tCreated dynamic index for ".$obj->object_type.":$fname\n";
								//$cache->set($dbh->dbname . "/objectdefs/" . $obj->object_type . "/fldidxstat/" . $fname, -1);
							}
							else
							{
								echo "\tFAILED trying to create dynamic index for ".$obj->object_type.":$fname\n";
							}
						}
						else
							echo "\tFAILED getting field for ".$obj->object_type.":$fname\n";
					}
				}
			}
			$dbh->FreeResults($res2);
		}
		$dbh->FreeResults($result);
	}
}
