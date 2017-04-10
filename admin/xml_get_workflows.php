<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/WorkFlow.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$otypes = $_GET['otypes'];

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	
	echo "<workflows>";

	$cond = "";
	$types = explode(":", $otypes);
	foreach ($types as $type)
		$cond .= ($cond) ? "or object_type='$type' " : "object_type='$type' ";
	$wflist = new WorkFlow_List($dbh, $cond);
	for ($w = 0; $w < $wflist->getNumWorkFlows(); $w++)
	{
		$wf = $wflist->getWorkFlow($w);
		
		echo "<workflow>";
		echo "<id>".$wf->id."</id>";
		echo "<name>".rawurlencode($wf->name)."</name>";
		echo "<object_type>".rawurlencode($wf->object_type)."</object_type>";
		echo "<f_active>".(($wf->fActive)?"t":"f")."</f_active>";
		echo "</workflow>";
	}
	echo "</workflows>";
?>
