<?php
/**
 * This file is used to create default html templates used throughout ANT
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("This script must be called from the system schema manager and ant mut be set");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_SYSTEM);

// Simple
$uname = "email_clean";
$obj = CAntObject::factory($dbh, "html_template", "uname:" . $uname, $user);
$rev = 3;
if (!$obj->id || $obj->getValue("revision") < $rev)
{
    $obj->setValue("name", "Simple & Clean");
    $obj->setValue("scope", "system");
    $obj->setValue("obj_type", "email_message");
    $obj->setValue("owner_id", USER_SYSTEM);    
    $obj->setValue("revision", $rev-1);
    $obj->setValue("uname", $uname);
	$obj->setValue("body_html", file_get_contents(APPLICATION_PATH . "/public/email/templates/simple-basic.html"));
    $obj->save(false);
}

// Simple 2col-1-2
$uname = "email_2col_1_2";
$obj = CAntObject::factory($dbh, "html_template", "uname:" . $uname, $user);
$rev = 2;
if (!$obj->id || $obj->getValue("revision") < $rev)
{
    $obj->setValue("name", "Simple & Clean - 2 Column");
    $obj->setValue("scope", "system");
    $obj->setValue("obj_type", "email_message");
    $obj->setValue("owner_id", USER_SYSTEM);    
    $obj->setValue("revision", $rev-1);
    $obj->setValue("uname", $uname);
	$obj->setValue("body_html", file_get_contents(APPLICATION_PATH . "/public/email/templates/2col-1-2.html"));
    $obj->save(false);
}
