<?php
/**
 * Queue spam messages for sa-learn
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/Dacl.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

$olist = new CAntObjectList($dbh, "email_message");
$olist->addCondition("and", "flag_spam", "is_equal", "t");
$olist->addCondition("and", "message_date", "is_greater_or_equal", date("m/d/Y", strtotime("-90 days", time())));
$olist->addOrderBy("message_date", "desc");
$olist->getObjects(0, 100000);
for ($i = 0; $i < $olist->getNumObjects(); $i++)
{
    $obj = $olist->getObject($i);
    $obj->markSpam(true); // Put in queue to learn
    unset($obj);
}