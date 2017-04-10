<?php 
require_once("../../lib/AntConfig.php");
require_once("ant.php");
require_once("lib/AntUser.php");
require_once("lib/AntFs.php");

if (!$_REQUEST['fid'])
	exit;

$user = new AntUser($ANT->dbh, USER_ANONYMOUS);
$antfs = new AntFs($ANT->dbh, $user);
$file = $antfs->openFileById($_REQUEST['fid']);
$cache_seconds = 172800; // 2 days

if (!$file->id)
	exit;

$maxWidth = $_REQUEST['w'];
$maxHeight = $_REQUEST['h'];

$file = $antfs->openFileById($_REQUEST['fid']);


// Set the file name, may pass alternate name through param 'fname'
if ($_REQUEST['fname'])
	$filename = $_REQUEST['fname'];
else
	$filename = $file->getValue("name");

if (!$filename)
	$filename = "Untitiled";
header("Content-Disposition: inline; filename=\"".str_replace("'", '', $filename)."\"");
header("Content-Type: " . $file->getContentType());
header("Cache-Control: max-age=$cache_seconds");
header('Pragma: cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_seconds) . ' GMT');
header("Content-Transfer-Encoding: binary");


/**
 * Check if the file has been modified since the last time it was downloaded
 */
if(array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER) && $file->getValue("ts_updated"))
{
	if(strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) == strtotime($file->getValue("ts_updated")))
	{
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', strtotime($file->getValue("ts_updated"))).' GMT', true, 304);
		exit();
	}
}

header('Last-Modified: ' . gmdate('D, d M Y H:i:s', strtotime($file->getValue("ts_updated"))) . ' GMT');

// If resizing then download locally and resize
if ($maxWidth || $maxHeight)
{
	// Create temp resied thumbnail
	$thumbFile = $file->resizeImage($maxWidth, $maxHeight);

	header("Content-Length: " . filesize($thumbFile));
	flush(); // force headers flush for faster response

	// Send browser to client
	readfile($thumbFile);

	// Cleanup
	unlink($thumbFile);
}
else
{
	// No resize, just stream raw file
	if ($file->getValue("file_size"))
		header("Content-Length: " . $file->getValue("file_size"));
	flush(); // force headers flush for faster response

	$file->stream();
}

// Make sure we resized successfully
//if (!$thumbFile || !file_exists($thumbFile))
	//return;
