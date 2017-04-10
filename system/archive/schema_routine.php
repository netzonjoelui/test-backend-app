<?php
	/************************************************************************
	 * This contains a list of SQL schema updates for the datbase
	 * **********************************************************************/

	require_once("../lib/AntConfig.php");
	require_once("settings/settings_functions.php");		
	require_once("lib/CDatabase.awp");
	require_once("lib/AntUser.php");
	require_once("lib/CAntObject.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	//$ACCOUNT_DB = "aereus_ant";

	// Get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select '$ACCOUNT_DB' as database");
	else
		$res_sys = $dbh_sys->Query("select distinct database from accounts");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		
		if (!$HIDE_MESSAGES)
			echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh_acc = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);

			// Get the current version of the database
			$settings_default_account = "aereus";
			//$result = $dbh_acc->Query("delete from activity");

			//include($INC_PATH."schema_updates/routines/custfldoptvals.php");
			//include($INC_PATH."schema_updates/routines/custacttosysact.php");
			include($INC_PATH."schema_updates/routines/movedatacentertoapps.php");
		}
	}
?>
