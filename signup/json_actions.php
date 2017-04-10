<?php
	require_once("../lib/AntConfig.php");
	require_once("lib/AntSystem.php");
	require_once("lib/Ant.php");

	$FUNCTION = $_REQUEST['function'];

	switch ($FUNCTION)
	{
	/**
	 * Make sure an account does not exist
	 */
	case "check_account":
		$account_name = $_REQUEST['account_name'];
		if ($account_name)
		{
			// First check for valid name
			if (preg_match( "[~|@|\|\S|[^a-zA-Z0-9_]]", $account_name)) 
				$retval = 2;
			else
				$retval = 0;

			if ($retval == 0)
			{
				$antsys = new AntSystem();

				// Check if account exists
				$info = $antsys->getAccountInfoByName($account_name);
				if ($info['id'] != -1)
				{
                    $ret = 1;
				}
				else
				{
                    $ret = 0; // OK to use
				}
			}
		}
		else
			$retval = -1;

		break;

	/**
	 * Make sure a user name is valid
	 */
	case "check_user_name":
		$username = $_REQUEST['username'];
		if ($username)
		{
			// First check for valid name
			if (preg_match( "[~|@|\|\S|[^a-zA-Z0-9_.]]", $username)) 
				$retval = 2;
			else
				$retval = 0;
		}
		else
			$retval = -1;
		break;
	}

	header('Cache-Control: no-cache, must-revalidate');
	header('Content-type: application/json');
	echo json_encode($retval);
?>
