<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectFields.php");
	require_once("lib/global_functions.php");
	require_once("lib/WorkFlow.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$LIMIT = $_GET['limit'];
	$email_uid = NULL;

	function localPrintFkey($dbh, $obj, $field, $parent="", $prefix="")
	{
		global $_REQUEST, $USERID, $USER, $USERNAME, $ACCOUNT;

		$query = "SELECT * FROM ".$field['subtype'];
		$cnd = "";
		if ($field['fkey_table']['filter'])
		{
			foreach ($field['fkey_table']['filter'] as $referenced_field=>$object_field)
			{
				if (($referenced_field=="user_id" || $referenced_field=="owner_id") && $_REQUEST[$object_field])
					$_REQUEST[$object_field] = $USERID;

				if ($_REQUEST[$object_field])
				{
					if ($cnd) $cnd .= " and ";

					// Check for parent
					$obj_rfield = $obj->fields->getField($object_field);
					if ($obj_rfield['fkey_table'] && $obj_rfield['fkey_table']['parent'])
					{
						if ($obj_rfield['type'] == "object")
						{
							$refo = new CAntObject($dbh, $obj_rfield['subtype']);
							$tbl = $refo->object_table;
						}
						else
							$tbl = $obj_rfield['subtype'];

						$root = objFldHeiarchRoot($dbh, $obj_rfield['fkey_table']['key'], 
													$obj_rfield['fkey_table']['parent'], 
													$tbl, $_REQUEST[$object_field]);
						if ($root && $root!=$_REQUEST[$object_field])
						{
							$cnd .= " ($referenced_field='".$_REQUEST[$object_field]."' or $referenced_field='".$root."')";
						}
						else
						{
							$cnd .= " $referenced_field='".$_REQUEST[$object_field]."' ";
						}
					}
					else
					{
						$cnd .= " $referenced_field='".$_REQUEST[$object_field]."' ";
					}
				}
			}
		}
		if (is_array($_REQUEST['conditions']))
		{
			if (!$_REQUEST['condition_condvalue_1'])
				$cnd .= " (".$_REQUEST['condition_fieldname_1']."='' or ".$_REQUEST['condition_fieldname_1']." is null) ";
			else
				$cnd .= " ".$_REQUEST['condition_fieldname_1']."='".$_REQUEST['condition_condvalue_1']."' ";
		}

		if ($obj->fields->isPrivate)
		{
			if ($dbh->ColumnExists($field['subtype'], "owner_id"))
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= "owner_id='".$USERID."' ";
			}
			else if ($dbh->ColumnExists($field['subtype'], "user_id"))
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= "user_id='".$USERID."' ";
			}
		}

		if ($field['fkey_table']['parent'])
		{
			if ($parent)
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= $field['fkey_table']['parent']."='".$parent."' ";
			}
			else
			{
				if ($cnd) $cnd .= " and ";
				$cnd .= $field['fkey_table']['parent']." is null ";
			}
		}

		if ($cnd)
			$query .= " WHERE $cnd ";
		if ($dbh->ColumnExists($field['subtype'], "sort_order"))
			$query .= " ORDER BY sort_order ";
		else
			$query .= " ORDER BY ".(($field['fkey_table']['title']) ? $field['fkey_table']['title'] : $field['fkey_table']['key']);

		if ($LIMIT) $query .= " LIMIT $LIMIT";
		//echo $query;
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);

			$viewname = str_replace(" ", "_", str_replace("/", "-", $row[$field['fkey_table']['title']]));
			if ($prefix)
				$viewname = $prefix.".".$viewname;

			echo "<value ";
			echo "id=\"".$row[$field['fkey_table']['key']]."\" ";
			echo "title=\"".escape($row[$field['fkey_table']['title']])."\" heiarch=\"";
			echo ($field['fkey_table']['parent']) ? "t" : "f";
			echo "\" parent_id=\"".$row[$field['fkey_table']['parent']]."\" ";
			echo "viewname=\"".escape($viewname)."\" ";
			echo "color=\"".escape($row['color'])."\" ";
			echo "system=\"".($row['f_system']=='t'?'t':'f')."\" ";
			echo ">";

			// Print children
			echo "<children>";
			if ($field['fkey_table']['parent'])
				localPrintFkey($dbh, $obj, $field, $row[$field['fkey_table']['key']], $viewname);
			echo "</children>";

			echo "</value>";
		}
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	
	echo "<values>";
	// Get optionval values
	if ($_GET['obj_type'] && $_GET['field'])
	{
		$obj = new CAntObject($dbh, $_GET['obj_type']);
		$field = $obj->fields->getField($_GET['field']);

		if (($field['type'] == "fkey" || $field['type'] == "fkey_multi") && is_array($field['fkey_table']) 
			&& $field['subtype'] != "user_file_categories" && $field['subtype'] != "user_files"
			&& $field['subtype'] != "customers" && $field['subtype'] != "customer")
		{
			localPrintFkey($dbh, $obj, $field);
		}
	}
	echo "</values>";
?>
