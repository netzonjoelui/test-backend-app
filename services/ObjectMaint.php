<?php
/**
 * ANT Service that handles background maintenance tasks for objects
 *
 * @category	AntService
 * @package		ObjectDynIdx
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntUser.php");		
require_once("lib/CAntObject.php");		
require_once("lib/AntRoutine.php");		

class ObjectMaint extends AntRoutine
{
	public function main(&$dbh)
	{
		$this->maintCapped($dbh);
	}

	/**
	 * Maintain capped tables
	 *
	 * @param CDatabase $dbh Handle to account database
	 */
	private function maintCapped($dbh)
	{
		// CAPPED tables can have a limited number of rows stored. This is handy for log rotation.
		$result = $dbh->Query("SELECT id, name, capped FROM app_object_types WHERE capped is not null");
		$num = $dbh->GetNumberRows($result);
		echo "Checking objects for ".$dbh->dbname."\n";
		// First lets index all non-deleted objects
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			$capNum = $row['capped'];
			$obj = CAntObject::factory($dbh, $row['name'], null, $this->user);

			// Get the table for this object
			$objTable = $obj->getObjectTable(true); // Get current object partition
			echo "Checking $objTable\n";

			// Get the count
			$res2 = $dbh->Query("select reltuples::integer from pg_class where relname='" . $dbh->Escape($objTable) . "';");
			if ($dbh->GetNumberRows($res2))
			{
				$numRows = $dbh->GetValue($res2, 0, "reltuples");

				if ($numRows > $capNum)
				{
					$offset = $capNum;
					$pullPer = 10000;

					$olist = new CAntObjectList($dbh, $row['name']);
					//$olist->addCondition("and", "f_deleted" , "is_equal", "false");
					//$olist->addOrderBy("ts_entered", "DESC");
					$olist->addOrderBy("id", "DESC");
					$olist->getObjects($offset, $pullPer);

					// Double check and make sure we are over the capped limit with a real count
					if ($olist->getTotalNumObjects() > $capNum)
					{
						echo "\tFound " . $olist->getTotalNumObjects(). " and only $capNum allowed. Trimming...\n";
						$numObjs = $olist->getNumObjects();
						for ($o = 0; $o < $numObjs; $o++)
						{
							try {
								$obj = $olist->getObject($o);
								echo "\t\tRemoving offset " . ($offset + $o) . " of " . $olist->getTotalNumObjects() . "\n";
								$obj->remove();
							}
							catch (Exception $ex) {
							}

							// Get next page if more than one
							if (($o+1) == $numObjs && ($numObjs+$offset) < $olist->getTotalNumObjects())
							{
								$offset += $pullPer - 1;
								$olist->getObjects($offset, $pullPer); // get next 100 objects

								// Reset counters
								$o = 0;
								$numObjs = $olist->getNumObjects();
							}
						}
					}
				}
			}

			$dbh->FreeResults($res2);
		}
		$dbh->FreeResults($result);
	}
}
