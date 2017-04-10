<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("security/security_functions.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_GET['function'];
 
	// Return XML
	header("Content-type: text/xml");

	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	switch ($FUNCTION)
	{
	/*************************************************************************************
	*	Function:		feed_get_categories
	*
	*	Description:	Get categories for a feed
	*
	*	Arguments:		1. feed_id - POST the unique id of the feed
	**************************************************************************************/
	case 'feed_get_categories':
		$s_xml = '';
		$s_xml .= '<categories>';

		if ($_REQUEST['feed_id'])
		{
			$result = $dbh->Query("select id, name from xml_feed_post_categories where feed_id='".$_REQUEST['feed_id']."' order by name");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				$s_xml .= "<category>
							<id>".$row['id']."</id>
							<name>".rawurlencode($row['name'])."</name>
						   </category>";
			}
		}
		else
		{
			$retval = "-1";
		}
			
		$s_xml .= '</categories>';
		echo $s_xml; 
		break;

	default:
		echo "<ant_feed>\n";
		echo "</ant_feed>";
		break;
	}

	if ($retval)
	{
		echo "<response>";
		echo "<retval>" . rawurlencode($retval) . "</retval>";
		echo "<cb_function>".$_GET['cb_function']."</cb_function>";
		echo "</response>";
	}
?>
