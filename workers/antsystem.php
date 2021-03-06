<?php
/**
 * @fileoverview This worker handles antsystem functions - most notably new account creation
 */
require_once('lib/AntLog.php');
require_once('lib/AntSystem.php'); 
require_once('lib/Worker.php'); 
require_once('lib/CAntFs.awp'); 
require_once("lib/aereus.lib.php/AnsClient.php");

/**
 * Add functions to list of callbacks
 */
if (is_array($g_workerFunctions))
{
	$g_workerFunctions["antsystem/create_account"] = "antsys_createAccount";
}

/**
 * Create and initialize a new account
 *
 * Workload Variables:
 * account_name = the unique name of the account to create
 * username = the name of the first user
 * password = the password of the first user
 * company = the company or organization name
 * job_title = the title of the person signing up
 * website = the website of the clein
 * phone = the phone number of the administrative user
 * num_users = expected number of users
 * promotion_code = optional promotion codes to adjust price or trail term
 *
 * @param {WorkerJob} $job A handle to the current worker job
 * @return {bool} true on success, false on failure
 */
function antsys_createAccount($job)
{
	$data = unserialize($job->workload());
	$dbh = $job->dbh;
	$ant_cust_svr = ($data['ant_cust_svr']) ? 
						$data['ant_cust_svr'] : 
						AntConfig::getInstance()->aereus['server']; // should be set for testing purposes

	$api = new AntApi($ant_cust_svr, 
					  AntConfig::getInstance()->aereus['user'],
					  AntConfig::getInstance()->aereus['password']);

	// Verify required arguments
	if (!$data['account_name'])
		return false;

	if (!$data['username'])
		return false;

	if (!$data['password'])
		return false;

	// Create new account
	// -------------------------------------------------------------------------
	$antsys = new AntSystem();
	$ret = $antsys->createAccount($data['account_name']);
	if ($ret == false)
	{
		if ($data['testing'])
			echo "ERROR CREATING ACCPIMT";

		// Email error
		$message = "ERROR CREATING ACCOUNT:\n\n";
		$message .= "$custid_acct\n";
		$message .= "Error: ".$antsystem->lastError."\n\n";
		foreach ($data as $vname=>$vval)
			$message .= "$vname: $vval\n";
		AntLog::getInstance()->error($message);
		return false;
	}

	// Set general account info
	$ant = new Ant($ret['id']);
	$ant->settingsSet("general/company_name", $data['company']);
	$ant->settingsSet("general/company_website", $data['website']);

	// Set the selected edition
	switch($data['edition'])
	{
	case 'professional':
		$ant->settingsSet("system/edition", EDITION_PROFESSIONAL);
		break;
	case 'enterprise':
		$ant->settingsSet("system/edition", EDITION_ENTERPRISE);
		break;
	case 'free':
	default;
		$ant->settingsSet("system/edition", EDITION_FREE);
		break;
	}

	// Add default user to the new account and make it an administrator account
	// ------------------------------------------------------------------------
	$user = $ant->createUser($data['username'], $data['password']);
	$user->addToGroup(GROUP_ADMINISTRATORS);
	$user->setValue("email", $data['email']);
	$uid = $user->save();
	if ($uid == false)
	{
		if ($data['testing'])
			echo "ERROR CREATING USER FOR NEW ACCOUNT";

		// Email error
		$message = "ERROR CREATING USER FOR NEW ACCOUNT:\n\n";
		$message .= "$custid_acct\n";
		$message .= "Error: ".$antsystem->lastError."\n\n";
		foreach ($data as $vname=>$vval)
			$message .= "$vname: $vval\n";
		AntLog::getInstance()->error($message);
		return false;
	}

	// Add customer records and opportunities to the Aereus CRM
	// We do not need to create one a contact for the user because that happens at login
	// -------------------------------------------------------------------------

	// Create new contact for the company
	$cust = $api->getObject("customer");
	$cust->setValue("name", $data['company']);
	$cust->setValue("type_id", CUST_TYPE_ACCOUNT);
	$cust->setValue("phone_work", $data['phone']);
	$cust->setValue("website", $data['website']);
	$cust->setValue("email", $data['email']);
	$cust->setValue("email2", $data['username']."@".$data['account_name'].".".AntConfig::getInstance()->localhost_root);
	$cust->setValue("company", $data['company']);
	$cust->setValue("job_title", $data['job_title']);
	$cust->setValue("notes", "Generated by signup page\nPotential users:\n".$data['num_users']."\nCoupon:".$data['promotion_code']."\n");
	$cust->setValue("status_id", 1); // Active
	$cust->setValue("stage_id", 17); // Free Trial
	$cust->setMValue("groups", 149); // Put into ANT Accounts, if reseller then "ANT Resellers" group
	$custid_acct = $cust->save();

	if ($custid_acct)
	{
		// Set customer number of account in antsystem
		$ant->settingsSet("general/customer_id", $custid_acct);

		// Add opportunity
		$websource = 7;
		$price = ($data['edition']=='enterprise') ? 30 : 20;
		$amount = $price*$data['num_users'];

		$opp = $api->getObject("opportunity");
		$opp->setValue("created_by", "website");
		$opp->setValue("name", "ANT Signup - ".$data['company']);
		$opp->setValue("amount", $amount);
		$opp->setValue("probability_per", 25);
		$opp->setValue("customer_id", $custid_acct);
		$opp->setValue("lead_source_id", $websource);
		$opp->setValue("type_id", 7); // ANT
		$opp->setValue("stage_id", 3); // Trial

		$opp->save();
	}
	else
	{
		if ($data['testing'])
			echo "ERROR CREATING CUSTOMER";

		// Email error
		$message = "ERROR CREATING CUSTOMER:\n\n";
		$message .= "Account: $custid_acct\n";
		if ($cust)
			$message .= $cust->m_resp." \n\n";
		foreach ($data as $vname=>$vval)
			$message .= "$vname: $vval\n";
		$headers = array();
		$headers['From']  = AntConfig::getInstance()->email['noreply'];
		$headers['To']  = "sky.stebnicki@aereus.com";
		$headers['Subject']  = "Error creating customer-opportunity";
		$email = new Email();
		if (!$data['testing'])
			$status = $email->send($headers['To'], $headers, $message);

		AntLog::getInstance()->error($message);
		unset($email);
	}

	// Email login information
	// ------------------------------------------------------------------------
	$message = "Congratulations!\n\n";
	$message .= "Your account has been created and is ready to start using!\n\n";
	$message .= "To login navigate to: ";
	$message .= "http://".$data['account_name'].".".AntConfig::getInstance()->localhost_root."\n\n";
	$message .= "Your user name is: ".$data['username']."\n\n";
	$message .= "Feel free to email support@netricos.com or call (800) 974-5061 if you need assistance.";
	$headers = array();
	$headers['From']  = AntConfig::getInstance()->email['noreply'];
	$headers['To']  = $data['email'];
	$headers['Subject']  = "Netric Account";
	$email = new Email();
	if (!$data['testing'])
		$status = $email->send($headers['To'], $headers, $message);
	unset($email);

	AntLog::getInstance()->info("New Account Created - " . $data['account_name']);

	return $ret;
}
