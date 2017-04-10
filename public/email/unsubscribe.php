<?php 
require_once("../../lib/AntConfig.php");
require_once("ant.php");
require_once("lib/AntUser.php");
require_once("lib/CPageShellPublic.php");

// Get params
$EID = $_GET['eid']; // email_campain id
$CID = $_GET['cid']; // customer id, must either be this or eml which is base64 encoded
$EML = (!$CID && $_GET['eml']) ? base64_decode($_GET['eml']) : null;

if (!$CID && !$EML)
	die("Sorry, recipients could not be found");

// Create objects
// ---------------------------------------------------------
$user = new AntUser($ANT->dbh, USER_ANONYMOUS);
$cust = CAntObject::factory($ANT->dbh, "customer", $CID, $user);

$ecamp = null;
$mcamp = null;
if ($EID)
{
	$ecamp = CAntObject::factory($ANT->dbh, "email_campaign", $EID, $user);

	if ($ecamp->getValue("campaign_id")) 
		$mcamp = CAntObject::factory($ANT->dbh, "marketing_campaign", $ecamp->getValue("campaign_id"), $user);
}

if ($CID)
{
	// Update customer
	$cust = new CAntObject($ANT->dbh, "customer", $CID);
	$cust->setValue("f_noemailspam", 't');
	$cust->save();

	if ($mcamp)
	{
		$sysuser = new AntUser($ANT->dbh, USER_SYSTEM);

		// Reopem marketing camp with system account for updating stats
		$mcamp = CAntObject::factory($ANT->dbh, "marketing_campaign", $mcamp->id, $sysuser);
		// Register open
		$unsub  = $mcamp->getValue("email_unsubscribers");
		$unsub = (is_numeric($unsub)) ? ++$unsub : 1;
		$mcamp->setValue("email_unsubscribers", $unsub);
		$mcamp->save(false);
	}
}
else if ($EML)
{
	// TODO: query by email
}

$page = new CPageShellPublic("Unsubscribe", "home");
$page->opts['print_subnav'] = false;
$page->PrintShell();	
echo "<p class='success'>Your request to be removed from our mailing list has been received!</p>";
