<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/Email.php");
	require_once("userfiles/file_functions.awp");
	require_once("lib/CAntFs.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_REQUEST['function'];

	switch ($FUNCTION)
	{
	/*************************************************************************
	*	Function(s):	group_*
	*
	*	Purpose:	Mange groups
	**************************************************************************/
	case "group_set_color":
		$gid = $_REQUEST['gid'];
		$color = $_REQUEST['color'];

		if ($gid && $color)
		{
			$dbh->Query("update user_notes_categories set color='$color' where id='$gid'");
			$retval = $color;
		}
		break;
	case "group_rename":
		$gid = $_REQUEST['gid'];
		$name = rawurldecode($_REQUEST['name']);

		if ($gid && $name)
		{
			$dbh->Query("update user_notes_categories set name='".$dbh->Escape($name)."' where id='$gid'");
			$retval = $name;
		}
		break;
	case "group_delete":
		$gid = $_REQUEST['gid'];

		if ($gid)
		{
			$dbh->Query("delete from user_notes_categories where id='$gid'");
			$retval = $gid;
		}
		break;
	case "group_add":
		$pgid = ($_REQUEST['pgid'] && $_REQUEST['pgid'] != "null") ? "'".$_REQUEST['pgid']."'" : "NULL";
		$name = rawurldecode($_REQUEST['name']);
		$color = rawurldecode($_REQUEST['color']);

		if ($name && $color)
		{
			$query = "insert into user_notes_categories(parent_id, name, color, user_id) 
					  values($pgid, '".$dbh->Escape($name)."', '".$dbh->Escape($color)."', '$USERID');
					  select currval('user_notes_categories_id_seq') as id;";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);

				$retval = $row['id'];
			}
			else
				$retval = "-1";
		}
		break;	
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
	echo "<response>";
	echo "<retval>" . rawurlencode($retval) . "</retval>";
	echo "<cb_function>".$_GET['cb_function']."</cb_function>";
	echo "</response>";
?>
