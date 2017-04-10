<?php

	//require_once("../../validate_user.php");

	require_once("../lib/AntConfig.php");

	require_once("../settings/settings_functions.php");		

	require_once("../users/user_functions.php");

	// ANT LIB

	require_once("../lib/CToolMenu.awp");

	require_once("../lib/content_table.awp");

	require_once("../lib/WindowFrame.awp");

	require_once('../lib/CDropdownMenu.awp');

	require_once("../lib/CDatabase.awp");

	require_once("../lib/Button.awp");

	require_once("../lib/CAutoComplete.awp");

	// Customer and contacts

	require_once("../customer/customer_functions.awp");

	require_once("../contacts/contact_functions.awp");

	// Application

	require_once("../email/email_functions.awp");

	

	$dbh = new CDatabase();

	$USERNAME = $_SESSION["USER_NAME"];

	$USERID = $_SESSION["USER_ID"];



	$result = $dbh->Query("select id, orig_header from email_messages where orig_header like '%\n\n%'");

	$num = $dbh->GetNumberRows($result);

	for ($i = 0; $i < $num; $i++)

	{

		$row = $dbh->GetNextRow($result, $i);

		$id = $row['id'];

		$body = $row['orig_header'];

		

		if ($body)

		{

			$parts = explode("\n\n", $body);

			

			$dbh->Query("update email_messages set orig_header='".$dbh->Escape($parts[0])."' where id='$id'");

		}

	}

	$dbh->FreeResults($result);

?>

