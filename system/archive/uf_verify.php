<?php
	/*****************************************************************************
	* 	Script:		uf_verify
	*
	* 	Author:		joe, sky.stebnicki@aereus.com, 2010
	*
	* 	Purpose:	Set the f_verified flag on the ans server to allow for purging
	* 				or orphaned files.
	*
	******************************************************************************/

	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../userfiles/file_functions.awp");
	require_once("../users/user_functions.php");
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../lib/aereus.lib.php/CAnsClient.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	ini_set("max_execution_time", "28800");	
	ini_set('default_socket_timeout', "28800"); 

	$DEBUG = TRUE;
	
	$ans = new CAnsCLient();

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select '$ACCOUNT_DB' as database");
	else
		$res_sys = $dbh_sys->Query("select distinct database from accounts where f_use_ans is not false");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		echo "Verifying for $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);

			$result = $dbh->Query("select id, file_name, file_title, file_type, category_id, user_id from user_files 
								   where remote_file is not null");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$title = ($row['file_title']) ? $row['file_title'] : $row['file_name'];
				$settings_account_number = UserFilesGetCatAccount($dbh, $row['category_id']);
				$settings_account_number2 = UserGetAccount($dbh, $row['user_id']);

				if (!$ans->fileVerify($title, $row['id'], "/userfiles"))
				{
					// File not there, do something...
				}
				else
				{
					echo "\tverified [".$row['id']."] $title\n";
				}
			}
			$dbh->FreeResults($result);
		}
	}
?>
