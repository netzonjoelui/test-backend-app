<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/CAntObject.php");
	require_once("contacts/contact_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$FUNCTION = $_GET['function'];
	
	$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

	$objList = new CAntObjectList($dbh, "contact_personal", $USER);
	$objList->addCondition("and", "user_id", "is_equal", $USER->id);
	if ($search)
		$objList->addConditionText($search);
	$objList->getObjects();
	$num = $objList->getNumObjects();
	for ($i = 0; $i < $num; $i++)
	{
		$obj = $objList->getObject($i);

		$dsp_name = "";
		
		if ($obj->getValue('nick_name'))
			$dsp_name = $obj->getValue('nick_name');
		else if ($obj->getValue('first_name') || $obj->getValue('last_name'))
			$dsp_name = $obj->getValue('first_name')." ".$obj->getValue('last_name');
		else if ($obj->getValue('company'))
			$dsp_name = $obj->getValue('company');

		// array = value(id), plain text search, html display, autocomplete html
		if ($obj->getValue('email'))
		{
			if ($dsp_name)
			{
				$response[] = array($obj->getValue('email'), "\"".$dsp_name."\" <".$obj->getValue('email').">", 
									$dsp_name, "\"".$dsp_name."\" &lt;".$obj->getValue('email')."&gt;");
			}
			else
				$response[] = array($obj->getValue('email'), $obj->getValue('email'), null, null);
		}
		if ($obj->getValue('email2')) 
		{
			if ($dsp_name)
			{
				$response[] = array($obj->getValue('email2'), "\"".$dsp_name."\" <".$obj->getValue('email2').">", 
									$dsp_name, "\"".$dsp_name."\" &lt;".$obj->getValue('email2')."&gt;");
			}
			else
				$response[] = array($obj->getValue('email2'), $obj->getValue('email2'), null, null);
		}
		if ($obj->getValue('email_spouse'))
		{
			if ($obj->getValue('spouse_name'))
				$dsp_name = $obj->getValue('spouse_name');
				
			if ($dsp_name)
			{
				$response[] = array($obj->getValue('email_spouse'), "\"".$dsp_name."\" <".$obj->getValue('email_spouse').">", 
									$dsp_name, "\"".$dsp_name."\" &lt;".$obj->getValue('email_spouse')."&gt;");
			}
			else
				$response[] = array($obj->getValue('email_spouse'), $obj->getValue('email_spouse'), null, null);
		}

	}

	header('Content-type: application/json');
	echo json_encode($response);
?>
