<?php
	require_once("lib/AntUser.php");

	$USER = $ANT->getUser();

	// Validate user
	if (!$USER->id)
	{
		$full_page = $_SERVER['PHP_SELF'];
		$page_pre = substr(substr($full_page, strrpos($full_page, "/")+1), 0, 4);
		if ($page_pre == "xml_") // Use for AJAX xml pages because redirecting to login page could create problems
		{
			header("Content-type: text/xml");			// Returns XML document
			echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
			echo "<result><error>noauth</error><message>User is not logged in</message></result>";
		}
		else if ($full_page == "/controllerLoader.php")
		{
			if ($_GET['controller'] == "User" && $_GET['function'] == "userCheckin")
				echo json_encode("autherror");
			else
				echo ""; // Return null
		}
		else
		{
			header("Location: /login?p=".base64_encode($_SERVER['REQUEST_URI']));
		}
		exit();
	}
	else
	{
		if ($USER->timezoneName)
			date_default_timezone_set($USER->timezoneName);
	}
?>
