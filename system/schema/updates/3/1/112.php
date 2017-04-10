<?php
/**
 * Make sure the event coord attendees table exists
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

if (!$dbh->TableExists("calendar_event_coord_att_times"))
{
	$dbh->Query("CREATE TABLE calendar_event_coord_att_times
				 (
				  att_id integer,
				  time_id integer,
				  response integer,
				  CONSTRAINT calendar_event_coord_att_times_tid_fkey FOREIGN KEY (time_id)
					  REFERENCES calendar_event_coord_times (id) MATCH SIMPLE
					  ON UPDATE CASCADE ON DELETE CASCADE
				   );");
}
