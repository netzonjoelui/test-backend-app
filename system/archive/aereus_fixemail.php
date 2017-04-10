<?php
/**
 * A mass delete pruged email that should not have been purged resulting in re-downloading all messages from imap!
 */
require_once("../../lib/AntConfig.php");
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("memory_limit", "4000M");

$dbh = new CDatabase(AntConfig::getInstance()->db['syshost'], "aereus_ant");
$dbh->setSchema("acc_12");
$dbh->accountId = 12;

// Delete all messages that were re-downloaded
function aereus_fixemail_todelete($dbh, $mid, $uid, $userId)
{
    // Check in deleted
    $list = new CAntObjectList($dbh, "email_message");
    $list->addCondition("and", "f_deleted", "is_equal", 't');
    $list->addCondition("and", "message_uid", "is_equal", $uid);
    $list->addCondition("and", "owner_id", "is_equal", $userId);
    $list->getObjects();
    if ($list->getNumObjects()>0)
        return true;
    
    // Now check if it was added previously but moved
    $list = new CAntObjectList($dbh, "email_message");
    $list->addCondition("and", "message_uid", "is_equal", $uid);
    $list->addCondition("and", "id", "is_less_than", $mid);
    $list->addCondition("and", "owner_id", "is_equal", $userId);
    $list->getObjects();
    if ($list->getNumObjects()>0)
        return true;

    return false;
}
$list = new CAntObjectList($dbh, "email_message");
$list->addCondition("and", "ts_updated", "is_greater_than", "06/25/2014");
$list->addCondition("and", "ts_updated", "is_less_than", "06/26/2014");
$list->addCondition("and", "message_uid", "is_not_equal", "");
$list->getObjects(0, 100000);
for ($i = 0; $i < $list->getNumObjects(); $i++)
{
    $obj = $list->getObject($i);
    
    if (aereus_fixemail_todelete($dbh, $obj->id, $obj->getValue("message_uid"), $obj->getValue("owner_id")))
    {
        $obj->remove();
        echo "Remove," . $obj->id . "\n";
    }
}

// Restore all messages that were deleted yesterday
$list = new CAntObjectList($dbh, "email_thread");
$list->addCondition("and", "f_deleted", "is_equal", 't');
$list->addCondition("and", "time_updated", "is_greater_than", "06/25/2014");
$list->addCondition("and", "time_updated", "is_less_than", "06/26/2014");
$list->getObjects(0, 100000);
for ($i = 0; $i < $list->getNumObjects(); $i++)
{
    $obj = $list->getObject($i);
    $obj->unremove();
    echo "Unremove: " . $obj->id . "\n";
}
