<?php
	// now copy all activities
	$result = $dbh_acc->Query("select user_id, bug_id, title, body, time_posted, user_name_cache, notified_log, f_public
							   from project_bug_comments");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		$obja = new CAntObject($dbh_acc, "comment", null);
		$obja->setValue("obj_reference", "case:".$row['bug_id']);
		$obja->setValue("owner_id", $row['user_id']);
		$obja->setValue("comment", $row['body']);
		$obja->setValue("ts_entered", $row['time_posted']);
		$obja->addAssociation("case", $row['bug_id'], "associations");

		// Get Type id
		/*
		$res2 = $dbh_acc->Query("select id from activity_types where name='comment';");
		if ($dbh_acc->GetNumberRows($res2))
		{
			$obja->setValue("type_id", $dbh_acc->GetValue($res2, 0, "id"));
		}
		 */

		$cid = $obja->save(false);
		echo "Added $cid for case ".$row['bug_id']."\n";
	}
?>