<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("contact_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_REQUEST['function'];

	switch ($FUNCTION)
	{
	/*************************************************************************
	*	Function:	contact_get_name
	*
	*	Purpose:	Get the display name of a contact
	**************************************************************************/
	case "contact_get_name":
		if ($_GET['cid'])
			$retval = ContactGetName($dbh, $_GET['cid']);
		else
			$retval = -1;
		break;
	/*************************************************************************
	*	Function:	sync_customers
	*
	*	Purpose:	Sync contacts to customers
	**************************************************************************/
	case "sync_customers":
		if ($_POST['obj_type'] && (is_array($_POST['objects']) || $_POST['all_selected']))		// Update specific event
		{
			$olist = new CAntObjectList($dbh, $_POST['obj_type'], $USER);
			$olist->processFormConditions($_POST);
			$olist->getObjects();
			$num = $olist->getNumObjects();
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $olist->getObject($i);
				if ($obj->dacl->checkAccess($USER, "Edit", ($USER->id==$obj->getValue("user_id"))?true:false))
				{
					CustSyncContact($dbh, $USERID, NULL, $obj->id, "create_customer");
				}
			}
			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;
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
			$dbh->Query("update contacts_personal_labels set color='$color' where id='$gid'");
			$retval = $color;
		}
		break;
	case "group_rename":
		$gid = $_REQUEST['gid'];
		$name = rawurldecode($_REQUEST['name']);

		if ($gid && $name)
		{
			$dbh->Query("update contacts_personal_labels set name='".$dbh->Escape($name)."' where id='$gid'");
			$retval = $name;
		}
		break;
	case "group_delete":
		$gid = $_REQUEST['gid'];

		if ($gid)
		{
			$dbh->Query("delete from contacts_personal_labels where id='$gid'");
			$retval = $gid;
		}
		break;
	case "group_add":
		$pgid = ($_REQUEST['pgid'] && $_REQUEST['pgid'] != "null") ? "'".$_REQUEST['pgid']."'" : "NULL";
		$name = rawurldecode($_REQUEST['name']);
		$color = rawurldecode($_REQUEST['color']);

		if ($name && $color)
		{
			$query = "insert into contacts_personal_labels(parent_id, name, color, user_id) 
					  values($pgid, '".$dbh->Escape($name)."', '".$dbh->Escape($color)."', '$USERID');
					  select currval('public.contacts_personal_labes_id_seq') as id;";
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
	case "group_delete_share":
		$gid = $_REQUEST['gid'];

		if ($gid)
		{
			$dbh->Query("delete from contacts_personal_label_share where label_id='$gid' and user_id='$USERID'");
			$retval = $gid;
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
