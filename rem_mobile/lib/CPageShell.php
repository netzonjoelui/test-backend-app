<?php
class CPageShell
{
	function CPageShell()
	{
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" 
				\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
			  <html>
				<head>
					<title>ANT Mobile</title>
					<meta HTTP-EQUIV=\"content-type\" CONTENT=\"text/html; charset=UTF-8\">
				</head>
				<body style='margin: 0px; padding: 0px;'>";

		// Print header
		echo "<div style='background-color:black; font-weight: bold; color: white;padding: 3px;'>
				ANT - Aereus Network Tools
			  </div>";
		
		// Print body container
		echo "<div style='padding: 3px;'>";
	}

	function __destruct()
	{
		echo "</div>"; // close body container
		echo "</body></html>";
	} 
}
?>
