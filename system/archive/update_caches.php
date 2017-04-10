<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../users/user_functions.php");		
	require_once("../email/email_functions.awp");		
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../email/email_functions.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set("memory_limit", "2G");	
	
	$dbh = new CDatabase();

	$USERID = null;
	$settings_account_number = null;
	$settings_debug = true;

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if ($settings_version) // limit to current version
		$res_sys = $dbh_sys->Query("select database, server from accounts where version='$settings_version' ".(($ACCOUNT_DB)?" and database='$ACCOUNT_DB'":''));
	else
		$res_sys = $dbh_sys->Query("select database, server from accounts ".(($ACCOUNT_DB)?" and database='$ACCOUNT_DB'":''));

	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		$server = $dbh_sys->GetValue($res_sys, $s, 'server');
		echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);

			$rows = array();
			
			if (isset($_SERVER["argv"][1]))
				$query = "select id, name, object_table from app_object_types where name='".$_SERVER['argv'][1]."' order by id";
			else
				$query = "select id, name, object_table from app_object_types order by id";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);

			// Index undeleted items
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				$otid = $row['id'];
				$odef = new CAntObject($dbh, $row['name']);
				$indexTypeId = $odef->getIndexTypeId();

				echo "Pulling undeleted ".$row['name']."\n";
				$res2 = $dbh->Query("select id, revision from ".$row['object_table']." where f_deleted is not true");
				$num2 = $dbh->GetNumberRows($res2);
				for ($j = 0; $j < $num2; $j++)
				{
					$oid = $dbh->GetValue($res2, $j, "id");
					$rev = $dbh->GetValue($res2, $j, "revision");
					$syncFixed = false;

					// Pull directly which will load from cache
					/*
					$obj = new CAntObject($dbh, $row['name'], $oid); // Will Load from cache
					if ($obj->getValue("revision") != $rev)
					{
						$obj->clearCache();
						echo $row['name'].": restting cache for ".($j+1)." of $num2\n";
						$syncFixed = true;
					}
					*/

					// Pull from object list which will load data from index
					$olist = new CAntObjectList($dbh, $row['name']);
					$olist->addCondition("and", "id", "is_equal", $oid);
					$olist->getObjects(0, 1);
					$numLst = $olist->getNumObjects();
					if ($numLst)
					{
						$obj = $olist->getObject(0);
						if ($obj->getValue("revision") != $rev)
						{
							unset($obj);
							// Load fresh from DB
							$obj = new CAntObject($dbh, $row['name'], $oid); // Will Load from cache
							$obj->index(); // reindex
							echo $row['name'].": reindexed for ".($j+1)." of $num2\n";
							$syncFixed = true;
						}
					}
					else // was not indexed
					{
						$obj = new CAntObject($dbh, $row['name'], $oid); // Will Load from cache
						$obj->index(); // index for first time
						echo $row['name'].": index for ".($j+1)." of $num2\n";
						$syncFixed = true;
					}

					if (!$syncFixed)
						echo $row['name'].": check ".($j+1)." of $num2\t\t[passed]\n";
				}
				$dbh->FreeResults($res2);

				$odef->indexCommit();
				//$odef->indexOptimize(); // finalize additions to index
			}

			$dbh->FreeResults($result);
		}
	}
?>
