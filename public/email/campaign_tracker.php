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

header("Content-Type: image/png");
header("Expires: Wed, 5 Feb 1986 06:06:06 GMT"); 
header("Cache-Control: no-cache"); 
header("Cache-Control: must-revalidate");
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');


// Tacking
// ---------------------------------------------------------
if ($mcamp)
{
	$sysuser = new AntUser($ANT->dbh, USER_SYSTEM);

	if ($cust)
	{
		// Reopem cust with system account for updating stats
		$cust = CAntObject::factory($ANT->dbh, "customer", $CID, $sysuser);

		$act = $cust->addActivity("read", "Email client view  - " . $ecamp->getValue('name'), 
								  "Email campaign was viewed from within the email client", null, null, 't', $user->id, 4);
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
