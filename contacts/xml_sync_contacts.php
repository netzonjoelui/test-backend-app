<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("contact_functions.awp");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$ACCOUNT_NAME = $USER->accountName;

	$CID = $_GET['cid'];
	$LABEL = $_GET['lid'];
	$ORDERBY = ($_GET['order']) ? $_GET['order'] : 'first_name';
	$SORT = $_GET['sort'];
	$START = ($_GET['start']) ? $_GET['start'] : 0;
	$GET_QUERY = $_GET['get_query'];
	
	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8"
	  standalone="yes"?>'; 
	
	echo "<contacts>\n";

	$objList = new CAntObjectList($dbh, "contact_personal", $USER);
	$objList->addMinField("first_name");
	$objList->addMinField("last_name");
	$objList->addMinField("date_changed");
	$objList->addMinField("company");
	$objList->addMinField("nick_name");
	$objList->addMinField("phone_home");
	$objList->addCondition("and", "user_id", "is_equal", $USERID);
	$objList->getObjects();
	$num = $objList->getNumObjects();
	for ($i = 0; $i < $num; $i++)
	{
		$row = $objList->getObjectMin($i);

		$name = $row['first_name']." ".$row['last_name'];
		
		if ($row['nick_name'])
			$name = str_replace("<", "&lt;", str_replace(">", "&gt;", $row['nick_name']));
		else if ($row['first_name'] == "" && $row['last_name'] == "")
			$name = $row['company'];

		print("<contact>\n");
		print("<id>".rawurlencode($row['id'])."</id>");
		print("<name>".rawurlencode(stripslashes($name))."</name>");
		print("<first_name>".rawurlencode(stripslashes($row['first_name']))."</first_name>");
		print("<last_name>".rawurlencode(stripslashes($row['last_name']))."</last_name>");
		print("<company>".rawurlencode(stripslashes($row['company']))."</company>");
		print("<number>".rawurlencode($row['phone_home'])."</number>");
		print("<email>".rawurlencode(GetDefaultEmail($dbh, $row['id'])."&nbsp;")."</email>");
		print("<ts_updated>".rawurlencode($row['date_changed'])."</ts_updated>");
		print("</contact>");
	}
	
	echo "</contacts>";
?>
