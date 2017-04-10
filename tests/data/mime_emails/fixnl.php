<?php
$files = array("text-mail.txt", "attachments-mail.txt");

foreach ($files as $file)
{
	/*
	$rfc833Msg = file_get_contents($file);
	$rfc822Msg = preg_replace("/(?<!\\n)\\r+(?!\\n)/", "\r\n", $rfc833Msg);
	//echo $rfc833Msg;
	file_put_contents($file, $rfc822Msg);
	*/
	$handle = fopen($file, "r");
	$lines = array();
	if ($handle) 
	{
		while (($buffer = fgets($handle, 4096)) !== false) 
			$lines[] = $buffer;

		fclose($handle);
		unlink($file);
		$handle = fopen($file, "w");
		foreach ($lines as $line)
			fwrite($handle, preg_replace('/\r?\n$/', '', $line)."\r\n");

		fclose($handle);
	}
}
