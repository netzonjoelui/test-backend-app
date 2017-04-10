<?php
	require_once("../lib/AntConfig.php");
	require_once("lib/ant_error_handler.php");
	require_once("settings/settings_functions.php");		
	require_once("users/user_functions.php");		
	require_once("email/email_functions.awp");		
	// ANT LIB
	require_once("lib/CAntObject.php");
	require_once("lib/CDatabase.awp");
	require_once("lib/AntUser.php");
	require_once("lib/WorkFlow.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$dbh = new CDatabase();

	$USERID = null;
	$settings_account_number = null;

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if ($settings_version) // limit to current version
		$res_sys = $dbh_sys->Query("select database, server from accounts where version='$settings_version' ".(($ACCOUNT_DB)?" and database='$ACCOUNT_DB'":''));
	else
		$res_sys = $dbh_sys->Query("select database, server from accounts ".(($ACCOUNT_DB)?" where database='$ACCOUNT_DB'":''));
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		$server = $dbh_sys->GetValue($res_sys, $s, 'server');

		if ($dbname)
		{
			$dbh = new CDatabase($server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);
			$result = $dbh->Query("select id, action_id, instance_id from workflow_action_schedule where ts_execute <= now() and inprogress='0'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);

				if (!WorkFlow::instanceActInProgress($dbh, $row['id']))
				{
					$obj = WorkFlow::getInstanceObj($dbh, $row['instance_id']);
					if ($obj->getValue("f_deleted") != 't')
					{
						$act = new WorkFlow_Action($dbh, $row['action_id']);
						$wf = new WorkFlow($dbh, $act->workflow_id);
						$act->execute($obj);
					}
					$dbh->Query("delete from workflow_action_schedule where id='".$row['id']."'");

					$wf->updateStatus($row['instance_id']);
				}
			}
			$dbh->FreeResults($result);
		}
	}
?>
