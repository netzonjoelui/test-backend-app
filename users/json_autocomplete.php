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

	$query = "select id, name, full_name from users where active is not false ";
	if ($search)
	{
		$query .= "	and (lower(full_name) like lower('".$search."%') or lower(name) like lower('".$search."%'))";
	}

	$result = $dbh->Query($query);
	$num = $dbh->GetNumberRows($result);
	//echo "<item>$query: $num</item>\n";
	for ($i=0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);
		$dsp_name = "";
		
		if ($row['full_name'])
			$dsp_name = $row['full_name'];
		else
			$dsp_name = $row['name'];

		$ac_html = "<table><tr valign='top'><td style='width:55px;text-align:center;'>";
		$img = UserGetImage($dbh, $row['id']);
		if ($img)
			$ac_html .= "<img src='/files/images/$img/48/48'>";
		$ac_html .= "</td><td>$dsp_name</td></tr></table>";

		// array = value(id), plain text search, html display, autocomplete html
		$response[] = array("user:".$row['id'], $row['name']." ".$dsp_name, $dsp_name, $ac_html);
	}
	$dbh->FreeResults($result);	

	header('Content-type: application/json');
	echo json_encode($response);
?>
