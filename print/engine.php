<?php
/**
 * This page handles printing objects
 */
require_once("../lib/AntConfig.php");
require_once("ant.php");
require_once("ant_user.php");
require_once('lib/pdf/class.ezpdf.php'); 
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");

ini_set("max_execution_time", "7200");	

$OBJ_TYPE = $_REQUEST['obj_type'];
$FMT = ($_REQUEST['format']) ? $_REQUEST['format'] : "PDF";

if (!$OBJ_TYPE)
	die("No object type defined");

// Get which eignine plugin to use
if (!file_exists($OBJ_TYPE . "_" . strtolower($FMT) . ".php"))
{
	$alt = (strtolower($FMT) == "pdf") ? "html" : "pdf";
	if (file_exists($OBJ_TYPE . "_" . strtolower($alt) . ".php"))
		$FMT = $alt;
}

$usePlugin = $OBJ_TYPE . "_" . strtolower($FMT) . ".php";
if (!file_exists($OBJ_TYPE . "_" . strtolower($FMT) . ".php"))
	$usePlugin = "default_" . strtolower($FMT) . ".php";


// Print headers and create buffers
// ------------------------------------------
switch (strtolower($FMT))
{
case "pdf":
	$pdf =& new Cezpdf();
	$pdf->selectFont('../lib/pdf/fonts/Helvetica.afm');
	$pdf->ezSetMargins(50,45,40,40);

	// Start numbering pages
	$pdf->ezStartPageNumbers(520+30,760,10,'','',1);
	break;

case "html":
default:
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' . "\n";
	echo '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html><head>';
	echo "<title>Print</title>";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
	echo '<script language="javascript" type="text/javascript">	function load() { window.print(); } </script>';
	echo '</head><body onload="load()">';
	break;
}

/**
 * Get list based on variables
 */
$objf = new CAntObjectFields($ANT->dbh, $OBJ_TYPE);
$ofields = $objf->getFields();
$olist = new CAntObjectList($ANT->dbh, $OBJ_TYPE, $USER);
$olist->processFormConditions($_REQUEST);
$olist->getObjects();
$num = $olist->getNumObjects();
for ($i = 0; $i < $num; $i++)
{
	$obj = $olist->getObject($i);

	include($usePlugin);
}

// Print footers and print buffers
// ------------------------------------------
switch (strtolower($FMT))
{
case "pdf":
	$pdf->ezStream();
	break;

case "html":
default:
	echo "</body></html>";
	break;
}
