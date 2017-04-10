<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("lib/WorkFlow.php");
	require_once("email/email_functions.awp");
	require_once("../community/feed_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_GET['function'];

	ini_set("max_execution_time", "7200");	
	ini_set("max_input_time", "7200");	

	// Log activity - not idle
	UserLogAction($dbh, $USERID);

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	switch ($FUNCTION)
	{
	// ---------------------------------------------------------
	// feed_delete_field
	// ---------------------------------------------------------
	case "feed_add_field":
		if ($_POST['name'] && $_POST['type'] && $_REQUEST['fid'])
		{
			$col_title = $_POST['name'];
			$col_name = str_replace(" ", "_", $col_title);
			$col_name = str_replace("'", "", $col_name);
			$col_name = str_replace('"', "", $col_name);
			$col_name = str_replace('&', "", $col_name);
			$col_name = str_replace('%', "", $col_name);
			$col_name = str_replace('$', "", $col_name);
			$col_name = str_replace("\\", "", $col_name);
			/*
			$query = "insert into xml_feed_fields(col_name, col_type, col_title, feed_id)
						values('".strtolower($col_name)."', '".$_POST['type']."', '".$col_title."', '".$_REQUEST['fid']."')";
			$dbh->Query($query);
			*/

			$obj = new CAntObject($dbh, "content_feed_post", null, $USER);

			if ($_POST['type'] == "file")
			{
				$fdef = array('title'=>$col_title, 'type'=>'fkey', 'subtype'=>'user_files', 'system'=>false, 'use_when'=>"feed_id:".$_REQUEST['fid'],
							  'fkey_table'=>array("key"=>"id", "title"=>"file_title"));
			}
			else
			{
				$fdef = array('title'=>$col_title, 'type'=>$_POST['type'], 'subtype'=>'', 'system'=>false, 'use_when'=>"feed_id:".$_REQUEST['fid']);
			}
			$obj->addField(strtolower($col_name), $fdef);
			unset($obj);

			$retval = 1;
		}
		else
			$retval = "-1";

		break;

	// ---------------------------------------------------------
	// feed_delete_field
	// ---------------------------------------------------------
	case "feed_delete_field":

		if ($_REQUEST['fid'] && $_REQUEST['dfield'])
		{
			$obj = new CAntObject($dbh, "content_feed_post", null, $USER);
			$obj->fields->removeField($_REQUEST['dfield']);
			$retval = 1;
		}
		else
			$retval = "-1";
		break;

	// ---------------------------------------------------------
	// feed_get_fields
	// ---------------------------------------------------------
	case "feed_get_fields":
		$retval = "[";
		if ($_REQUEST['fid'])
		{
			$buf = "";
			$obj = new CAntObject($dbh, "content_feed_post", null, $USER);
			$ofields = $obj->fields->getFields();
			foreach ($ofields as $fname=>$field)
			{
				if ($field['use_when'] == "feed_id:".$_REQUEST['fid'])
				{
					if ($buf) $buf .= ", ";
					$buf .= "{id:\"".$field['id']."\", name:\"$fname\",  title:\"".addslashes($field['title'])."\", ";
					$buf .= "type:\"".addslashes($field['type'])."\", value:\"";
					if ($_REQUEST['pid'])
					{
						$buf .= addslashes(FeedFieldGetVal($dbh, $field['id'], $_REQUEST['pid']));
					}
					$buf .= "\"}";
				}
			}
			if ($buf)
				$retval .= $buf;
			/*
			$result = $dbh->Query("select id, col_name, col_type, col_title from xml_feed_fields where feed_id='".$_REQUEST['fid']."' order by col_name");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				if ($i) $retval .= ", ";
				$retval .= "{id:\"".$row['id']."\", name:\"".$row['col_name']."\",  title:\"".addslashes($row['col_title'])."\", ";
				$retval .= "type:\"".addslashes($row['col_type'])."\", value:\"";
				if ($_REQUEST['pid'])
				{
					$retval .= addslashes(FeedFieldGetVal($dbh, $row['id'], $_REQUEST['pid']));
				}
				$retval .= "\"}";
			}
			 */
		}
		$retval .= "]";
		break;
	
	// ---------------------------------------------------------
	// feed_delete_field
	// DEPRICATED
	// ---------------------------------------------------------
	case "feed_post_save_fields":
		/*
		if (is_array($_POST['fields']) && $_REQUEST['pid'])
		{
			foreach ($_POST['fields'] as $fieldId)
			{
				FeedFieldSetVal($dbh, $fieldId, $_REQUEST['pid'], $_POST['field_value_'.$fieldId]);
			}
			$retval = 1;
		}
		*/

		$retval = "1";
		break;

	// ---------------------------------------------------------
	// feed_post_publish
	// ---------------------------------------------------------
	case "feed_post_publish":
		if ($_REQUEST['fid'])
		{
			FeedPushUpdates($dbh, $_REQUEST['fid']);
		}

		$retval = "1";
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
			$dbh->Query("update xml_feed_groups set color='$color' where id='$gid'");
			$retval = $color;
		}
		break;
	case "group_rename":
		$gid = $_REQUEST['gid'];
		$name = rawurldecode($_REQUEST['name']);

		if ($gid && $name)
		{
			$dbh->Query("update xml_feed_groups set name='".$dbh->Escape($name)."' where id='$gid'");
			$retval = $name;
		}
		break;
	case "group_delete":
		$gid = $_REQUEST['gid'];

		if ($gid)
		{
			$dbh->Query("delete from xml_feed_groups where id='$gid'");
			$retval = $gid;
		}
		break;
	case "group_add":
		$pgid = ($_REQUEST['pgid'] && $_REQUEST['pgid'] != "null") ? "'".$_REQUEST['pgid']."'" : "NULL";
		$name = rawurldecode($_REQUEST['name']);
		$color = rawurldecode($_REQUEST['color']);

		if ($name && $color)
		{
			$query = "insert into xml_feed_groups(parent_id, name, color) 
					  values($pgid, '".$dbh->Escape($name)."', '".$dbh->Escape($color)."');
					  select currval('xml_feed_groups_id_seq') as id;";
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
	// ---------------------------------------------------------
	// feed_delete_field
	// ---------------------------------------------------------
	case "feed_add_category":
		if ($_POST['name'] && $_REQUEST['fid'])
		{
			$col_title = $_POST['name'];
			$query = "insert into xml_feed_post_categories(name, feed_id)
						values('".$dbh->Escape($_POST['name'])."', '".$_REQUEST['fid']."')";
			$dbh->Query($query);
			$retval = 1;
		}
		else
			$retval = "-1";

		break;

	// ---------------------------------------------------------
	// feed_delete_field
	// ---------------------------------------------------------
	case "feed_delete_category":

		if ($_REQUEST['fid'] && $_REQUEST['dcat'])
		{
			$dbh->Query("delete from xml_feed_post_categories where id='".$_REQUEST['dcat']."' and feed_id='".$_REQUEST['fid']."'");
			$retval = 1;
		}
		else
			$retval = "-1";
		break;

	// ---------------------------------------------------------
	// feed_get_fields
	// ---------------------------------------------------------
	case "feed_get_categories":
		$retval = "[";
		if ($_REQUEST['fid'])
		{
			$result = $dbh->Query("select id, name from xml_feed_post_categories where feed_id='".$_REQUEST['fid']."' order by name");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				if ($i) $retval .= ", ";
				$retval .= "{id:\"".$row['id']."\", name:\"".$row['name']."\"}";
			}
			$retval .= "]";
		}
		break;
	}

	// Check for RPC
	if ($retval)
	{
		echo "<response>";
		echo "<retval>" . rawurlencode($retval) . "</retval>";
		echo "<message>" . rawurlencode($message) . "</message>";
		echo "<cb_function>" . $_GET['cb_function'] . "</cb_function>";
		echo "</response>";
	}
?>
