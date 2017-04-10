<?php 
require_once("../../lib/AntConfig.php");
require_once("ant.php");
require_once("lib/AntUser.php");

// Get params
$EID = $_GET['eid']; // email_campain id - REQUIRED
$CID = $_GET['cid']; // customer id, must either be this or eml which is base64 encoded
$EML = (!$CID && $_GET['eml']) ? base64_decode($_GET['eml']) : null;

// Make sure we are looking at a specific message
if (!$EID)
	die("No messaged defined");

if (!$CID && !$EML)
	die("Sorry, recipients could not be found");

// Create objects
// ---------------------------------------------------------
$user = new AntUser($ANT->dbh, USER_ANONYMOUS);
$cust = CAntObject::factory($ANT->dbh, "customer", $CID, $user);
$ecamp = CAntObject::factory($ANT->dbh, "email_campaign", $EID, $user);

$mcamp = null;
if ($ecamp->getValue("campaign_id")) 
	$mcamp = CAntObject::factory($ANT->dbh, "marketing_campaign", $ecamp->getValue("campaign_id"), $user);

// Create an email object to render the body dynamically
$email = CAntObject::factory($ANT->dbh, "email_message", null, $user);
if ($ecamp->getValue("body_html"))
	$email->setBody($ecamp->getValue("body_html"), "html");
else if ($ecamp->getValue("body_plain"))
	$email->setBody($ecamp->getValue("body_plain"), "plain");

// Populate merge fields
$email->processCampaignTemplate($ecamp, $cust);

//echo "<!DOCTYPE HTML><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
//echo "<title>" . $ecamp->getValue("subject") . "</title>";
//echo "<body>";
echo $email->getBody(true);
//echo "</body></html>";

// Tacking
// ---------------------------------------------------------
if ($mcamp)
{
	$sysuser = new AntUser($ANT->dbh, USER_SYSTEM);

	if ($cust)
	{
		// Reopem cust with system account for updating stats
		$cust = CAntObject::factory($ANT->dbh, "customer", $CID, $sysuser);

		$act = $cust->addActivity("read", "Browser view  - " . $ecamp->getValue('name'), 
								  "Email campaign was viewed in the browser", null, null, 't', $user->id, 4);
		$act->addAssociation("marketing_campaign", $mcamp->id, "associations");
		$act->save();
	}

	// Reopem marketing camp with system account for updating stats
	$mcamp = CAntObject::factory($ANT->dbh, "marketing_campaign", $ecamp->getValue("campaign_id"), $sysuser);
	// Register open
	$opens = $mcamp->getValue("email_opens");
	$opens = (is_numeric($opens)) ? ++$opens : 1;
	$mcamp->setValue("email_opens", $opens);
	$mcamp->save(false);
}
