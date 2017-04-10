<?php
/**
 * This file populates a datacube called 'test'
 */
require_once('../../lib/AntConfig.php');
require_once('../../lib/CDatabase.awp');
require_once('../../lib/AntUser.php');
require_once('../../lib/CAntObject.php');
require_once('../../lib/Olap.php');
require_once('../../lib/aereus.lib.php/CAntObjectApi.php');

// Timer
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;

//$objectType = "customer";
$objectType = "contact_personal";
$owner = USER_SYSTEM;
$dbh = new CDatabase();
$user = new AntUser($dbh, $owner);

$olap = new Olap($dbh);
$olap->deleteCube('test'); // Purge cube if already exists
// Get new cube
$cube = $olap->getCube("test");

// Record an entry for each quarter
$data = array(
	'page' => "/index.php",
	'country' => "us",
	'time' => "1/1/2012",
);
$measures = array("hits" => 100);
$cube->writeData($measures, $data);
$data = array(
	'page' => "/about.php",
	'country' => "us",
	'time' => "4/1/2012",
);
$measures = array("hits" => 75);
$cube->writeData($measures, $data);

$data = array(
	'page' => "/about.php",
	'country' => "us",
	'time' => "7/1/2012",
);
$measures = array("hits" => 50);
$cube->writeData($measures, $data);
$data = array(
	'page' => "/about.php",
	'country' => "us",
	'time' => "10/1/2012",
);
$measures = array("hits" => 25);
$cube->writeData($measures, $data);

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = number_format(($endtime - $starttime), 2);
echo "\nTotal time: " .$totaltime. " seconds";
