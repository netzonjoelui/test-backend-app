<?php
	require_once("../lib/AntConfig.php");
	require_once("lib/ant_error_handler.php");
	require_once("settings/settings_functions.php");		
	require_once("users/user_functions.php");		
	require_once("email/email_functions.awp");		
	// ANT LIB
	require_once("lib/CAntObject.php");
	require_once("lib/CDatabase.awp");
	require_once("lib/WorkFlow.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$dbh = new CDatabase();

	$USERID = null;
	$settings_account_number = null;

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
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

		if ($dbname)
		{
			$dbh = new CDatabase($server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);

			$wflist = new WorkFlow_List($dbh, "f_active='t' and f_on_daily='t'");
			for ($w = 0; $w < $wflist->getNumWorkFlows(); $w++)
			{
				$wf = $wflist->getWorkFlow($w);

				// Look for/sweep for matching objects
				$ol = new CAntObjectList($dbh, $wf->object_type);
				// Build condition
				for ($j = 0; $j < $wf->getNumConditions(); $j++)
				{
					$cond = $wf->getCondition($j);
					$ol->addCondition($cond->blogic, $cond->fieldName, $cond->operator, $cond->value);
				}
				// Now get objects
				$ol->getObjects();
				for ($j = 0; $j < $ol->getNumObjects(); $j++)
				{
					$obj = $ol->getObject($j);
					if ($obj->getValue("f_deleted") != 't')
						$wf->execute($obj);
				}
			}
		}
	}
?>
