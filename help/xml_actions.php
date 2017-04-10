<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/aereus.lib.php/CAntCase.php");
	require_once("lib/aereus.lib.php/CAntCustomer.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$ACCOUNT_NAME = $USER->accountName;
	$THEME_NAME = $USER->themeName;

	$FUNCTION = $_GET['function'];

	// Log activity - not idle
	UserLogAction($dbh, $USERID);

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	switch ($FUNCTION)
	{
	// ---------------------------------------------------------
	// Submit Case
	// ---------------------------------------------------------
	case "submit_case":
		if ($_POST['subject'] && $_POST['description'])
		{
			$caseapi = new CAntCase("ant.aereus.com", "administrator", "Password1");
			if ($_POST['id'])
				$caseapi->open($_POST['id']);
			$caseapi->setAttribute("title", $_POST['subject']);
			$caseapi->setAttribute("description", $_POST['description']);
			$caseapi->setAttribute("status_id", 3000265); // New - Unanswered
			$caseapi->setAttribute("project_id", 1656);
			$caseapi->setAttribute("severity_id", 1); // Low
			$caseapi->setAttribute("created_by", $USERNAME);
			$caseapi->setAttribute("customer_id", $USER->getAereusCustomerId());
			$retval = $caseapi->save();
		}
		else
		{
			$retval = "-1";
		}
		break;
	// ---------------------------------------------------------
	// Get cases
	// ---------------------------------------------------------
	case "get_cases":
		$custid = $USER->getAereusCustomerId();
		if ($custid)
		{
			$custapi = new CAntCustomer("ant.aereus.com", "administrator", "Password1");
			$custapi->open($custid);
			$cases = $custapi->getCases();
			$retval = "[";

			for ($i = 0; $i < count($cases); $i++)
			{
				if ($i)
					$retval .= ", ";
				
				$retval .= "{id:\"".$cases[$i]['id']."\", name:\"".$cases[$i]['title']."\", customer_id:\"".$custid."\",";
				$retval .= "timeEntered:\"".$cases[$i]['ts_entered']."\", statusName:\"".$cases[$i]['status']."\"}";
			}
			$retval .= "]";
		}
		else
			$retval = "[]";
		break;
	}

	// Check for RPC
	if ($retval)
	{
		echo "<response>";
		echo "<retval>" . rawurlencode($retval) . "</retval>";
		echo "<cb_function>" . $_GET['cb_function'] . "</cb_function>";
		echo "</response>";
	}
?>
