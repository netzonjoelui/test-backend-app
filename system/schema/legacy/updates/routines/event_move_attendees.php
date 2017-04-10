<?php
/**
 * Move old attendees to new members
 */
$result = $dbh_acc->Query("select * from calendar_events_attendees where event_id is not null order by event_id;");
$num = $dbh_acc->GetNumberRows($result);
for ($i = 0; $i < $num; $i++)
{
	$row = $dbh_acc->GetRow($result, $i);

	$obj = new CAntObject($dbh_acc, "member");

	// Cleanup cache problem with events
	if (!$obj->getValue("recur_id"))
		$obj->setValue("recur_id", $rid);


	$obj->save(false);

	echo "Moved ".($i+1)." of $num - $rid to ".$rp->id."\n";
	unset($obj);
}
