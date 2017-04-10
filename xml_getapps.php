<?php
	require_once("lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectFields.php");
	require_once("lib/global_functions.php");
	require_once("lib/WorkFlow.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
?>
<applications>
	<application name='crm' title='CRM' icon="/images/icons/apps/crm_16.png" />
	<application name='projects' title='Projects &amp; Tasks' icon="/images/icons/apps/projects_16.png" />
	<application name='files' title='Files' icon="/images/icons/apps/afs_16.png" />
	<application name='notes' title='Notes' icon="/images/icons/apps/notes_16.png" />
	<application name='cms' title='Content Manager' icon="/images/icons/apps/cms_16.png" />
	<application name='infocenter' title='Infocenter' icon="/images/icons/apps/infocenter_16.png" />
	<application name='messages' title='Messages' icon="/images/icons/apps/email_16.png" />
	<application name='contacts' title='Personal Contacts' icon="/images/icons/apps/contacts_16.png" />
	<application name='calendar' title='Calendar' icon="/images/icons/apps/calendar_16.png" />
</applications>
