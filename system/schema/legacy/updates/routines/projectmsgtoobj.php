<?php
	// Copy all project messages to discussions
	$result = $dbh_acc->Query("select id, user_id, category_id, title, body, project_id, time_posted from project_messages");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		$obja = new CAntObject($dbh_acc, "discussion", null);
		$obja->setValue("name", $row['title']);
		$obja->setValue("message", $row['body']);
		$obja->setValue("ts_entered", $row['time_posted']);
		$obja->setValue("obj_reference", "project:".$row['project_id']);
		$obja->setValue("owner_id", $row['user_id']);
		$obja->addAssociation("project", $row['project_id'], "associations");

		// Get Type id
		$res2 = $dbh_acc->Query("select id from activity_types where name='discussion';");
		if ($dbh_acc->GetNumberRows($res2))
		{
			$obja->setValue("type_id", $dbh_acc->GetValue($res2, 0, "id"));
		}

		$did = $obja->save(false);
		echo "Added discussion ".($i+1)." of $num\n";

		// Now get comments
		$res2 = $dbh_acc->Query("select user_id, body, time_posted, notified_log from project_message_comments where message_id='".$row['id']."'");
		$num2 = $dbh_acc->GetNumberRows($res2);
		for ($j = 0; $j < $num2; $j++)
		{
			$row2 = $dbh_acc->GetRow($res2, $j);

			$obja = new CAntObject($dbh_acc, "comment", null);
			$obja->setValue("obj_reference", "discussion:".$did);
			$obja->setValue("owner_id", $row2['user_id']);
			$obja->setValue("comment", $row2['body']);
			$obja->setValue("ts_entered", $row2['time_posted']);
			$obja->setValue("notified", $row2['notified_log']);
			$obja->addAssociation("discussion", $did, "associations");
			$obja->addAssociation("project", $row['project_id'], "associations");

			// Get Type id
			$res3 = $dbh_acc->Query("select id from activity_types where name='comment';");
			if ($dbh_acc->GetNumberRows($res3))
			{
				$obja->setValue("type_id", $dbh_acc->GetValue($res3, 0, "id"));
			}

			$cid = $obja->save(false);

			echo "Added comment ".($j+1)." of $num2\n";
		}
	}
?>
