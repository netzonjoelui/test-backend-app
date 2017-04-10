<?php
	// Update all object definitions
	$result = $dbh_acc->Query("select id, name from app_object_types;");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$obja = new CAntObject($dbh_acc, $row['name']);
	}

	// Set all associations
	$result = $dbh_acc->Query("select id, name, type_id from app_object_type_fields;");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		//$dbh_acc->Query("update app_object_associations set field_id='".$row['id']."' where type_id='".$row['type_id']."' and field='".$row['name']."';");
	}
?>
