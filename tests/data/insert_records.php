<?php
	require_once('../../lib/AntConfig.php');
	require_once('../../lib/CDatabase.awp');
	require_once('../../lib/AntUser.php');
	require_once('../../lib/CAntObject.php');
	require_once('../../lib/aereus.lib.php/CAntObjectApi.php');
	
	// Timer
	$mtime = microtime();
	$mtime = explode(" ", $mtime);
	$mtime = $mtime[1] + $mtime[0];
	$starttime = $mtime;
	
	$numToInsert = 1000;
	//$objectType = "customer";
	$objectType = "customer";
	$owner = USER_SYSTEM;
	$dbh = new CDatabase();
	$user = new AntUser($dbh, $owner);
	
	switch($objectType)
	{
		case "customer":
			for($i = 0; $i < $numToInsert; $i++)
			{
				$obj = new CAntObject($dbh, $objectType, null, $user);
				$obj->setValue("owner_id", $owner);
				$obj->setValue("name", "Regression test ".$i);
				$obj->save();
				echo "Added $objectType ".$i." of $numToInsert\n";
			}
			break;
		case "contact_personal":
			for($i = 0; $i < $numToInsert; $i++)
			{
				$obj = new CAntObject($dbh, $objectType, null, $user);
				$obj->setValue("user_id", $owner);
				$obj->setValue("first_name", "Regression ");
				$obj->setValue("last_name", "test ".$i);
				$obj->save();
			}
			break;
	}
	
	$mtime = microtime();
	$mtime = explode(" ", $mtime);
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = number_format(($endtime - $starttime), 2);
	echo "\nTotal time: " .$totaltime. " seconds";
?>
