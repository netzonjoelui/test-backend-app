<?php
	// Create some default reports

	$obj = new CAntObject($dbh_acc, "report");

	$obj->setValue("obj_reference", "case:".$row['bug_id']);
	$obj->setValue("owner_id", $row['user_id']);
	$obj->setValue("comment", $row['body']);
	$obj->setValue("ts_entered", $row['time_posted']);
	$obj->save(false);
	$obj->setUniqueName("project/task_burndown");
?>
