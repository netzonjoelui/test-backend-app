<?php
/**
 * This update assures that the objects_id_seq is higher than the moved id
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("Update 51 failed because $ ant is not defined");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

// Get the current id of the objects sequence
$curid = $dbh->GetValue($dbh->Query("select nextval('objects_id_seq') as nextval;"), 0, "nextval");
$emailThId = $dbh->GetValue($dbh->Query("select nextval('email_threads_id_seq') as nextval;"), 0, "nextval");
$emailMsgId = $dbh->GetValue($dbh->Query("select nextval('email_messages_id_seq') as nextval;"), 0, "nextval");
$actId = $dbh->GetValue($dbh->Query("select nextval('activity_id_seq') as nextval;"), 0, "nextval");

$contend = ($emailThId > $actId) ? $emailThId : $actId;
$contend = ($emailMsgId > $contend) ? $emailMsgId : $contend;

if ($contend > $curid)
	$dbh->Query("SELECT setval('public.objects_id_seq', $contend, true);");
