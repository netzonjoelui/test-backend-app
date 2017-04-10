<?php
	// Set all associations
	$result = $dbh_acc->Query("select event_id, customer_id, contact_id, opportunity_id, lead_id from calendar_event_associations;");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		$obja = new CAntObject($dbh_acc, "calendar_event", $row['event_id']);
		// Add associations
		if ($row['customer_id'])
		{
			$obja->addAssociation("customer", $row['customer_id'], "associations");
		}
		if ($row['opportunity_id'])
		{
			$obja->addAssociation("opportunity", $row['opportunity_id'], "associations");
		}
		if ($row['lead_id'])
		{
			$obja->addAssociation("lead", $row['lead_id'], "associations");
		}
		if ($row['contact_id'])
		{
			$obja->addAssociation("contact_personal", $row['contact_id'], "associations");
		}

		if ($row['customer_id'] || $row['opportunity_id'] || $row['lead_id'] || $row['contact_id'])
			$obja->save(false);
	}
?>
