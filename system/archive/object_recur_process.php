<?php
	/**************************************************************
	*	Routine:	object_recur_process
	*
	*	Purpose:	Process recurrence patterns for today
	***************************************************************/
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../users/user_functions.php");		
	require_once("../email/email_functions.awp");		
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../lib/CAntObject.php");
	require_once("../lib/CRecurrencePattern.php");
	require_once("../email/email_functions.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$USERID = null;
	$ALIB_CACHE_DISABLE = true; // disable caching

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select '$ACCOUNT_DB' as database");
	else if ($settings_version) // limit to current version
		$res_sys = $dbh_sys->Query("select distinct database from accounts where version='$settings_version'");
	else
		$res_sys = $dbh_sys->Query("select distinct database from accounts where version is null or version=''");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		echo "Creating recurs for $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($settings_db_server, $dbname);

			// Create test object....
			/*
			$user = new AntUser($dbh, -1);
			$obj = new CAntObject($dbh, "task", null, $user);
			$obj->setValue("name", "My Automated Recurring Task");
			$obj->setValue("start_date", "3/4/2011");
			$obj->setValue("deadline", "3/5/2011");
			$rp = $obj->getRecurrencePattern();
			$rp->type = RECUR_DAILY;
			$rp->interval = 1;
			$rp->dateStart = "1/1/2011";
			$rp->dateEnd = "3/5/2011";
			$obj->save();
			*/
			
			$query = "select id from object_recurrence where f_active is true and date_processed_to<'".date("m/d/Y")."'";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			//echo "Num: $num";
			for ($i = 0; $i < $num; $i++)
			{
				$rid = $dbh->GetValue($result, $i, "id");

				$rp = new CRecurrencePattern($dbh, $rid);
				$rp->createInstances(date("m/d/Y")); // Create instances up to today
				$rp->save();
			}
			$dbh->FreeResults($result);
		}
	}
?>
