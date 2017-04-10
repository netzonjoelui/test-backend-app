<?php
	/**
	 * This file is only used for the login dialog that pops up when the session has expired in ANT
	 *
	 * We need to eventually move this to a common login script on the root (authenticate.php) where
	 * the login for mobile, desktop, and dialog all call the same script which will return ajax response and
	 * we will leave it up to the client to handle the redirection rather than having authenticate do it.
	 *
	 * For now we will leave this in place because it appears to be working well
	 */

	require_once("../lib/AntConfig.php");
	require_once("../ant.php");
	require_once("../lib/CDatabase.awp");
	require_once("../lib/Email.php");
	require_once("../email/email_functions.awp");
	require_once("../lib/Ant.php");
	require_once("../lib/AntUser.php");
	require_once("../lib/global_functions.php");

	$dbh = $ANT->dbh;

	$FUNCTION = $_REQUEST['function'];

	switch ($FUNCTION)
	{
	case 'login':
		if ($_GET['auth'])
		{
			$USERID = AntUser::authenticateEnc($_GET['auth'], $dbh);
			if ($USERID)
			{
				//$USER = new AntUser(&$dbh, $USERID, UserGetNameFromId($dbh, $USERID), $ACCOUNT);
				$retval = "OK";
			}
			else
			{
				$retval = "FAILED";
			}
		}
		else if ($_REQUEST['name'] && $_REQUEST['password'])
		{
			$uid = AntUser::authenticate($_REQUEST['name'], $_REQUEST['password'], $dbh);
			if ($uid)
			{
				$user = new AntUser($dbh, $uid);
				$retval = "OK";

				Ant::setSessionVar('uname', $_REQUEST['name']);
				Ant::setSessionVar('uid', $uid);
				Ant::setSessionVar('aid', $user->accountId);
				Ant::setSessionVar('aname', $user->accountName);
			}
			else
			{
				$retval = "FAILED";
			}
		}
		break;
	default:
		$retval = "-1";
		break;
	}

	// Return XML
	header("Content-type: text/xml");

	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	if ($retval)
	{
		echo "<response>";
		echo "<retval>" . rawurlencode($retval) . "</retval>";
		echo "<cb_function>".$_GET['cb_function']."</cb_function>";
		echo "</response>";
	}
?>
