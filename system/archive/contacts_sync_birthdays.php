<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../users/user_functions.php");		
	require_once("../email/email_functions.awp");		
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../email/email_functions.awp");
	require_once("../contacts/contact_functions.awp");
	require_once("../customer/customer_functions.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$dbh = new CDatabase();

	$USERID = null;
	$settings_account_number = null;

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	$ACCOUNT_DB = "ant_tfowler";

	// Get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select '$ACCOUNT_DB' as database");
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

			// Detach message attachments
			$result = $dbh->Query("select id, birthday, anniversary, birthday_spouse, user_id from contacts_personal");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);

				$CALID = GetDefaultCalendar($dbh, $row['user_id']);
		
				if ($row['birthday'])
				{
					echo "Updating birthday for [".$row['id']."]\n";	
					ContactAddCalDate($dbh, $row['user_id'], "Birthday", 'birthday', $row['id'], $CALID);
				}
				
				if ($row['anniversary']) // && $_POST['anniversary']!=$_POST['anniversary_old']	
				{
					echo "Updating anniversary for [".$row['id']."]\n";	
					ContactAddCalDate($dbh, $row['user_id'], "Anniversary", 'anniversary', $row['id'], $CALID);
				}

				if ($row['birthday_spouse']) // && $_POST['anniversary']!=$_POST['anniversary_old']	
				{
					echo "Updating birthday_spouse for [".$row['id']."]\n";	
					ContactAddCalDate($dbh, $row['user_id'], "Birthday Spouse", 'birthday_spouse', $row['id'], $CALID);
				}
			}
			$dbh->FreeResults($result);
		}
	}
?>
