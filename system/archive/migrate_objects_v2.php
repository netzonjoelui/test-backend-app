<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../users/user_functions.php");		
	require_once("../email/email_functions.awp");		
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../email/email_functions.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set("memory_limit", "500M");	
	
	$USERID = null;
	$ALIB_CACHE_DISABLE = true; // disable caching

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select '$ACCOUNT_DB' as database");
	else if ($settings_version) // limit to current version
		$res_sys = $dbh_sys->Query("select distinct database from accounts where version='$settings_version'");
	else
		$res_sys = $dbh_sys->Query("select distinct database from accounts");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);

			$result = $dbh->Query("select name, object_table from app_object_types where name!='activity'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$otid = objGetAttribFromName($dbh, $row['name'], "id");
				
				echo "Pulling ".$row['name']."\n";
				$res2 = $dbh->Query("select * from ".$row['object_table']." where 
									 not exists (select 1 from object_index where type_id='$otid' and object_id=".$row['object_table'].".id)");
				$num2 = $dbh->GetNumberRows($res2);
				for ($j = 0; $j < $num2; $j++)
				{
					$oid = $dbh->GetValue($res2, $j, "id");
					$obj = new CAntObject($dbh, $row['name'], $oid);

					$obj->index();
					echo $row['name'].": imported ".($j+1)." of $num2\n";
					/* Not sure why we were skipping
					if ($obj->owner_id)
					{
						$obj->index();
						echo "Indexed ".$row['name'].":$oid\n";
					}
					else
					{
						echo "Skipping because no owner set\n";
					}
					*/
				}
				$dbh->FreeResults($res2);
			}
			$dbh->FreeResults($result);
		}
	}
?>
