<?php
	// First copy all object types as activity types
	$result = $dbh_acc->Query("select name, title from app_object_types;");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		if (!$dbh_acc->GetNumberRows($dbh_acc->Query("select id from activity_types where obj_type='".$dbh_acc->Escape($row['name'])."';")))
		{
			$dbh_acc->Query("insert into activity_types(name, obj_type) 
								values('".$dbh_acc->Escape($row['title'])."', '".$dbh_acc->Escape($row['name'])."');");
		}
	}

	// Then copy all activity types
	$result = $dbh_acc->Query("select name, direction from customer_activity_types;");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		if (!$dbh_acc->GetNumberRows($dbh_acc->Query("select id from activity_types where lower(name)=lower('".$dbh_acc->Escape($row['name'])."');")))
		{
			$dbh_acc->Query("insert into activity_types(name) values('".$dbh_acc->Escape($row['name'])."');");
		}
	}

	// now copy all activities
	$result = $dbh_acc->Query("select customer_activity.name, type_id, notes, customer_id, 
								to_char(time_entered, 'MM/DD/YYYY HH12:MI AM') as ts_entered, user_id, lead_id, 
								f_readonly, email_id, customer_activity.direction, opportunity_id, customer_activity_types.name as type_name
								from customer_activity, customer_activity_types
								where customer_activity.type_id=customer_activity_types.id;");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		$obja = new CAntObject($dbh_acc, "activity", null);
		$obja->setValue("name", $row['name']);
		$obja->setValue("notes", $row['notes']);
		$obja->setValue("direction", $row['direction']);
		$obja->setValue("f_readonly", $row['f_readonly']);
		$obja->setValue("user_id", $row['user_id']);
		$obja->setValue("ts_entered", $row['ts_entered']);

		// Get Type id
		if (!$row['type_name']) $row['type_name'] = "Notes";
		$res2 = $dbh_acc->Query("select id from activity_types where lower(name)=lower('".$dbh_acc->Escape($row['type_name'])."');");
		if ($dbh_acc->GetNumberRows($res2))
		{
			$obja->setValue("type_id", $dbh_acc->GetValue($res2, 0, "id"));
		}

		// Add associations
		if ($row['customer_id'])
		{
			$obja->addAssociation("customer", $row['customer_id'], "associations");
			$obja->setValue("obj_reference", "customer:".$row['customer_id']);
		}
		if ($row['opportunity_id'])
		{
			$obja->addAssociation("opportunity", $row['opportunity_id'], "associations");
			$obja->setValue("obj_reference", "opportunity:".$row['opportunity_id']);
		}
		if ($row['lead_id'])
		{
			$obja->addAssociation("lead", $row['lead_id'], "associations");
			$obja->setValue("obj_reference", "lead:".$row['lead_id']);
		}
		if ($row['email_id'])
		{
			$obja->addAssociation("email_message", $row['email_id'], "associations");
			$obja->setValue("obj_reference", "email_message:".$row['email_id']);
		}

		$obja->save();
	}
?>
