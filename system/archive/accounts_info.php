<?php
	require_once("../lib/AntConfig.php");
    require_once("../settings/settings_functions.php");        
	require_once("ant.php");		
	require_once("../users/user_functions.php");		
	require_once("../email/email_functions.awp");		
	require_once("../lib/aereus.lib.php/CAntCustomer.php");
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../email/email_functions.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set("memory_limit", "200M");	
	
	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	$res_sys = $dbh_sys->Query("select distinct billing_customer_number from 
								accounts ".(($_SERVER['argv'][1])?"WHERE name='".$_SERVER['argv'][1]."'":'')."");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($c = 0; $c < $num_sys; $c++)
	{
		$billing_customer_number = $dbh_sys->GetValue($res_sys, $c, 'billing_customer_number');

		if ($billing_customer_number)
		{
			$total_users = 0;

			$res_sys2 = $dbh_sys->Query("select id, database, customer_number, server from 
											accounts where billing_customer_number='$billing_customer_number'");
			$num_sys2 = $dbh_sys->GetNumberRows($res_sys2);
			for ($s = 0; $s < $num_sys2; $s++)
			{
				$dbname = $dbh_sys->GetValue($res_sys2, $s, 'database');
				$acid = $dbh_sys->GetValue($res_sys2, $s, 'id');
				$cust_num = $dbh_sys->GetValue($res_sys2, $s, 'customer_number');
				$server = $dbh_sys->GetValue($res_sys2, $s, 'server');
				if (!$server)
					$server = AntConfig::getInstance()->db['host'];

				echo "Checking $dbname\n";

				if ($dbname)
				{
					$dbh = new CDatabase($server, $dbname);

					$query = "select count(*) as cnt from users where active is true and id > '0' ";
					if ($dbh->ColumnExists("users", "f_deleted"))
						$query .= " and f_deleted is not true ";
					$query .= "and name!='administrator' and account_id='$acid'";
					$result = $dbh->Query($query);
					if ($dbh->GetNumberRows($result))
					{
						$cnt = $dbh->GetValue($result, 0, "cnt");
						$total_users += $cnt;

						echo "\t$cnt users\n";

						// Check if account has already been renewed (will be set to 'f')
						$exp_pref = $ANT->settingsGet("general/trial_expired");

						$cust = new CAntCustomer("aereus.ant.aereus.com", "administrator", "Password1");
						$cust->open($cust_num);
						$cust->setAttribute("ant_num_users", $cnt); // Set stage to trial expired
						$total_users = 0;
						$exp = $cust->getAttribute("ant_trial_exp");
						$stage = $cust->getAttribute("stage_id"); // 14 == customer
						if ($exp && $exp_pref!='f' && $exp_pref!='t' && $stage!=14)
						{
							$time = strtotime($exp);
							if (time() >= $time)
							{
								settingsAccountSet($dbh, $acid, "general/trial_expired", "t");
								$cust->setAttribute("stage_id", 18); // Set stage to trial expired
								echo "\taccount expired on $exp\n";
							}
							else
							{
								settingsAccountSet($dbh, $acid, "general/trial_expired", "");
							}
						}
						else
						{
							settingsAccountSet($dbh, $acid, "general/trial_expired", "");
						}

						// Check to see if we should force gredit card/billing update wizard
						if ($cust->getAttribute("ant_billsusp") == "t")
						{
							settingsAccountSet($dbh, $acid, "general/suspended_billing", "t");
							echo "\taccount was suspended for billing reasons\n";
						}
						else
						{
							settingsAccountSet($dbh, $acid, "general/suspended_billing", "");
						}

						$cust->saveChanges();
					}
				}
			}

			if ($total_users && $num_sys2 > 1)
			{
				$cust_bill = new CAntCustomer("ant.aereus.com", "administrator", "Password1");
				$cust_bill->open($cust_num);
				$cust_bill->setAttribute("ant_num_users", $total_users);
				$cust_bill->saveChanges();
			}
		}
	}
?>
