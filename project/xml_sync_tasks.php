<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("contacts/contact_functions.awp");
	require_once("customer/customer_functions.awp");
	require_once("project_functions.awp");
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$ACCOUNT_NAME = $USER->accountName;

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8"
	  standalone="yes"?>'; 
	
	echo "<tasks>\n";

	$recur_processed = array();

	$objList = new CAntObjectList($dbh, "task", $USER);
	$objList->addMinField("ts_updated");
	$objList->addMinField("recur_id");
	$objList->addCondition("and", "user_id", "is_equal", $USERID);
	$objList->addCondition("and", "ts_updated", "is_not_equal", null);
	$objList->getObjects();
	$num = $objList->getNumObjects();
	for ($i = 0; $i < $num; $i++)
	{
		$row = $objList->getObjectMin($i);
		$print = true;

		if ($row['recur_id'])
		{
			$tr_query = "select id from project_tasks_recurring_ex where recurring_id='".$row['recur_id']."' and task_id='".$row['id']."'";
			if (in_array($row['recur_id'], $recur_processed) && !$dbh->GetNumberRows($dbh->Query($tr_query)))
				$print = false;

			$recur_processed[] = $row['recur_id'];
		}

		if ($print)
		{
			print("<task>");
			print("<id>".$row['id']."</id>");
			print("<ts_updated>".rawurlencode($row['ts_updated'])."</ts_updated>");
			print("</task>");
		}
	}
	echo "</tasks>";
?>
