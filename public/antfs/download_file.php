<?php 
require_once("../../lib/AntConfig.php");
require_once("ant.php");
require_once("lib/AntUser.php");
require_once("lib/AntFs.php");

if (!$_REQUEST['fid'])
	exit;

$cache_seconds = 172800; // 2 days
$user = new AntUser($ANT->dbh, USER_ANONYMOUS);
$antfs = new AntFs($ANT->dbh, $user);
$file = $antfs->openFileById($_REQUEST['fid']);

if (!$file->id)
	exit;

// Do not allow pulic to download a deleted file
if ($file->getValue("f_deleted")=='t')
	exit;

// Set the file name, may pass alternate name through param 'fname'
if ($_REQUEST['fname'])
	$filename = $_REQUEST['fname'];
else
	$filename = $file->getName();

if (!$filename)
	$filename = "Untitiled";

// Initialize range to read whole file by default
$start = 0;
$end = 0;

$disp = ($file->getContentType() == "audio/mpeg" && !$_REQUEST['download']) ? "inline" : "attachment";

header("Content-Disposition: $disp; filename=\"".str_replace("'", '', $filename)."\"");
header("Content-Type: " . $file->getContentType());
header("Pragma: public"); // required 
header('X-Pad: avoid browser bug');
//header('Cache-Control: no-cache');
header("Etag: " . md5($file->id . "-" . $filename));
/*
header("Content-Transfer-Encoding: binary"); 
header("Cache-Control: max-age=$cache_seconds");
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_seconds) . ' GMT');
header('Pragma: cache');
 */

if ($file->getValue("file_size"))
{
	$fullLength = $file->getValue("file_size");
	header("Accept-Ranges: bytes");

	/**
	 * Handle multi-range
	 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2k
	 */
	if (isset($_SERVER['HTTP_RANGE'])) 
	{
		$c_start = $start;
		$end   = $fullLength;

		// Extract the range string
		list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);


		// Make sure the client hasn't sent us a multibyte range
		if (strpos($range, ',') !== false) {

			// (?) Shoud this be issued here, or should the first
			// range be used? Or should the header be ignored and
			// we output the whole content?
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$fullLength");
			// (?) Echo some info to the client?
			exit;
		}

		// If the range starts with an '-' we start from the beginning
		// If not, we forward the file pointer
		// And make sure to get the end byte if spesified
		if ($range0 == '-') {

			// The n-number of the last bytes is requested
			$c_start = $fullLength - substr($range, 1);
		}
		else 
		{
			$range  = explode('-', $range);
			$c_start = $range[0];
			$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $fullLength - 1;
		}

		// Check the range and make sure it's treated according to the specs.
		// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
		// End bytes can not be larger than $end.
		$c_end = ($c_end > $end) ? $end : $c_end;

		// Validate the requested range and return an error if it's not correct.
		if ($c_start > $c_end || $c_start > $fullLength - 1 || $c_end >= $fullLength) 
		{

			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$fullLength");
			// (?) Echo some info to the client?
			exit;
		}

		$start  = $c_start;
		$end    = $c_end;
		fseek($fp, $start);
		header('HTTP/1.1 206 Partial Content');

		// Notify the client the byte range we'll be outputting
		header("Content-Range: bytes $start-$end/$fullLength");
		header("Content-Length: " . (($end - $start) + 1));
	}
	else
	{
		header("Content-Length: " . $file->getValue("file_size"));
		header("Content-Range: bytes 0-$fullLength/$fullLength");
	}
}


/**
 * Check if the file has been modified since the last time it was downloaded
 */
if(array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER) && $file->getValue("ts_updated") && !$start && !$end)
{
	$if_modified_since=strtotime(preg_replace('/;.*$/','',$_SERVER["HTTP_IF_MODIFIED_SINCE"]));

	if($if_modified_since >= strtotime($file->getValue("ts_updated")))
	{
		header("HTTP/1.0 304 Not Modified");
		exit();
	}
}

header('Last-Modified: ' . gmdate('D, d M Y H:i:s', strtotime($file->getValue("ts_updated"))) . ' GMT');

// Stream contents of file to browser
$file->stream(null, $start, $end);
