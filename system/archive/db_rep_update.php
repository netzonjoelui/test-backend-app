<?php
require_once("../lib/AntConfig.php");
require_once("../lib/CDatabase.awp");

// Only set this to true if you are joining the cluster or restoring
// It will expect everything in local_query_cache to be same as remote
if ("sync" == $argv[1])
	$restoring_db = true;
else
	$restoring_db = false;

if (!$settings_db_rep_on)
	exit;
	
// Get local DB handle	
$dbh = new CDatabase();
// Get remote(master) db handle
$dbhRemote = new CDatabase($settings_db_rep_master_host, $settings_db_rep_master_db, 
						   $settings_db_rep_master_user, $settings_db_rep_master_password);

// Do not record these transactions (would be circular)
$dbh->cache_query = false; 
$dbhRemote->cache_query = false;


// Get last updated timestamp from local
$result = $dbh->Query("select time_executed from app_remote_query_log order by time_executed DESC limit 1");
if ($dbh->GetNumberRows($result))
{
	$row = $dbh->GetNextRow($result, 0);
	$last_update = $row['time_executed'];
}
$dbh->FreeResults($result);

// Query remote server for cached queries with last update >= local last updated
$query = "select id, time_executed, query from app_local_query_cache ";
if ($last_update)
	$query .= " where time_executed >= '$last_update'::timestamp";

$result = $dbhRemote->Query($query);
$num = $dbhRemote->GetNumberRows($result);
for ($i = 0; $i < $num; $i++)
{
	$row = $dbhRemote->GetNextRow($result, $i);

	// Make sure this query has not already been executed
	if ($restoring_db) // Assume query cache is from remote server, not local
		$cond_query  =  "select id from app_local_query_cache where id='".$row['id']."'";
	else
		$cond_query  =  "select id from app_remote_query_log where remote_query_id='".$row['id']."'";
	if (!$dbh->GetNumberRows($dbh->Query($cond_query)))
	{
		$dbh->Query($row['query']);
		$dbh->Query("insert into app_remote_query_log(remote_query_id, time_executed) 
						values('".$row['id']."', '".$row['time_executed']."')");	
	}

	// Log the final entry if restoring db
	if ($restoring_db)
	{
		$dbh->Query("insert into app_remote_query_log(remote_query_id, time_executed) 
						values('".$row['id']."', '".$row['time_executed']."')");
	}
}
$dbhRemote->FreeResults($result);

if ($restoring_db)
	$dbh->Query("delete from app_local_query_cache");
	
?>
