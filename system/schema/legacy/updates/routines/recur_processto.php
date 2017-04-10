<?php
	$query = "select id from object_recurrence where f_active is true and 
				date_processed_to<now() and (date_end is null or date_end>=now())";
	$result = $dbh_acc->Query($query);
	$num = $dbh_acc->GetNumberRows($result);
	echo "Processing $num patterns...\n\n";
	for ($i = 0; $i < $num; $i++)
	{
		$rid = $dbh_acc->GetValue($result, $i, "id");
		$rp = new CRecurrencePattern($dbh_acc, $rid);
		//$rp->debug = true;

		echo "Creating instances for $rid\t";
		$numCreated = $rp->createInstances($processTo, true); // Create instances up to today
		echo "created $numCreated\n";
		
	}
	$dbh_acc->FreeResults($result);
?>
