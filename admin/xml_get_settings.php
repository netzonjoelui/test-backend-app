<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectFields.php");
	require_once("lib/WorkFlow.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	
	echo "<settings>";
	echo "<incoming_server>".$ANT->settingsGet("email/incoming_server")."</incoming_server>";
	echo "<email_mode>".$ANT->settingsGet("email/mode")."</email_mode>";
	echo "<company_name>".$ANT->settingsGet("general/company_name")."</company_name>";
	echo "<company_website>".$ANT->settingsGet("general/company_website")."</company_website>";
	echo "<login_image>".$ANT->settingsGet("general/login_image")."</login_image>";
	echo "<header_image>".$ANT->settingsGet("general/header_image")."</header_image>";
	echo "<header_image_public>".$ANT->settingsGet("general/header_image_public")."</header_image_public>";
	echo "<welcome_image>".$ANT->settingsGet("general/welcome_image")."</welcome_image>";
	echo "<email_domain>".$ANT->getEmailDefaultDomain()."</email_domain>";
	echo "</settings>";
?>
