<?php
	// ANT Includes 
	require_once('../../lib/AntConfig.php');
	require_once('lib/Ant.php');
	require_once('lib/AntUser.php');
	require_once('lib/CAntObject.php');
	require_once('lib/aereus.lib.php/CAntObjectApi.php');

	$dbh = new CDatabase();
	$USER = new AntUser($dbh, USER_SYSTEM);
	$mailboxid = EmailGetSpecialBoxId($dbh, $USER->id, "Inbox");
	
	$numInsert = 1000000;
	$objectType = "email_thread";
	$body = "";
	for ($i = 0; $i < 300; $i++)
		$body .= "Message body ";

	for ($i = 0; $i < $numInsert; $i++)
	{
		echo "Inserting email thread ".($i+1)." of $numInsert\n";
		$obj = new CAntObject($dbh, $objectType, null, $USER);
		$obj->setValue("subject", "Regression Test $i");
		$obj->setMValue("mailbox_id", $mailboxid);
		$obj->setValue("body", $body);
		$obj->setValue("owner_id", USER_SYSTEM);
		$obj->setValue("senders", "test@test.com, test2@test.com");
		$obj->setValue("num_messages", 3);
		$tid = $obj->save();

		for ($j = 0; $j < 3; $j++)
		{
			$obj = new CAntObject($dbh, "email_message", null, $USER);
			$obj->setValue("subject", "Regression Test $i");
			$obj->setValue("mailbox_id", $mailboxid);
			$obj->setValue("thread", $tid);
			$obj->setValue("send_to", "test@test.com");
			$obj->setValue("sent_from", "test@test.com");
			$obj->setValue("owner_id", USER_SYSTEM);
			$obj->setValue("parse_rev", 1000);
			$obj->setValue("body", $body);
			$tid = $obj->save();
		}
	}

	echo "[done]\n";
?>
