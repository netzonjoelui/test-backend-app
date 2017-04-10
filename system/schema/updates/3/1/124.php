<?php
/**
 * Move public schema to account schema if not alraedy set
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/Dacl.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

/**
 * Get inbox for every user
 */
$userList = new CAntObjectList($dbh, "user");
$userList->addCondition("and", "id", "is_greater", "0");
$userList->getObjects();
for ($i = 0; $i < $userList->getNumObjects(); $i++)
{
	$userdat = $userList->getObjectMin($i);
	$user = new AntUser($dbh, $userdat['id'], $ant);
	echo "\tChecking for " . $user->getValue("name") . "\n";
	$msg = CAntObject::factory($dbh, "email_message", null, $user);
	$inbx = $msg->getGroupingEntryByName("mailbox_id", "Inbox");

	if ($inbx['id'])
	{
		$msgList = new CAntObjectList($dbh, "email_message");
		$msgList->addCondition("and", "mailbox_id", "is_equal", $inbx['id']);
		$msgList->addCondition("and", "message_uid", "is_not_equal", "");
		$msgList->addCondition("and", "f_deleted", "is_equal", 't');
		$msgList->addOrderBy("message_date", "desc");
		$msgList->getObjects();
		for ($j = 0; $j < $msgList->getNumObjects(); $j++)
		{
			$msg = $msgList->getObject($j);

			echo "\tStat msg to delete {$msg->id}\n";
			$msg->updateObjectSyncStat('d');
		}
	}
}
