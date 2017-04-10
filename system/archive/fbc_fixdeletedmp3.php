<?php
/**
 * Undelete a whole bunch of MP3s for FBC
 * 
 * I have no idea how they got deleted in the first place, but it created a problem when we disabled
 * the public access to deleted files.
 */
require_once("../../lib/AntConfig.php");
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("memory_limit", "4000M");

$dbh = new CDatabase(AntConfig::getInstance()->db['syshost'], "ant_fbc");
$dbh->setSchema("acc_201");
$dbh->accountId = 201;

$list = new CAntObjectList($dbh, "file");
$list->addCondition("and", "f_deleted", "is_equal", "t");
$list->addCondition("and", "name", "contains", ".mp3");
$list->getObjects(0, 100000);
for ($i = 0; $i < $list->getNumObjects(); $i++)
{
    $obj = $list->getObject($i);
    $obj->unremove();
    echo "Unremoved: " . $obj->getValue("name") . "\n";
}