<?php
	/*======================================================================================
		
		Module:		index_queue_process

		Purpose:	Process and index incoming queue

		Author:		joe, sky.stebnicki@aereus.com
					Copyright (c) 2011 Aereus Corporation. All rights reserved.
		
	======================================================================================*/

	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../users/user_functions.php");		
	require_once("../email/email_functions.awp");		
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../email/email_functions.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set("memory_limit", "500M");	
	
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

			$result = $dbh->Query("select id, object_type, object_id from object_index_queue order by ts_entered DESC");
			$num = $dbh->GetNumberRows($result);
			// First lets index all non-deleted objects
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);

				$obj = new CAntObject($dbh, $row['object_type'], $row['object_id']);
				$obj->index();
				echo "Processed ".$row['object_type'].":".$row['object_id']."\n";
				$dbh->Query("delete from object_index_queue where id='".$row['id']."'");
			}
			$dbh->FreeResults($result);
		}
	}
?>
