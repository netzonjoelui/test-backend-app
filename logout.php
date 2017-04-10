<?php

// ALIB
require_once("lib/AntConfig.php");
require_once("ant.php");

// ANT
// logout.php - destroys session and cookie and returns to login form

$onHourAgo = time() - 3600;
$ANT->setSessionVar("uid", "", $onHourAgo);
$ANT->setSessionVar("uname", "", $onHourAgo);
$ANT->setSessionVar("aid", "", $onHourAgo);
$ANT->setSessionVar("aname", "", $onHourAgo);

// redirect browser back to login page
$page = "index.php?e=".$_GET['e']."&p=".$_GET['p'];
if ($_REQUEST['user'])
	$page .= "&user=".$_REQUEST['user'];
if ($_REQUEST['account'])
	$page .= "&account=".$_REQUEST['account'];
header("Location: $page");
