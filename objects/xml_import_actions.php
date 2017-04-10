<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/AntUser.php");
	require_once("lib/AntFs.php");
	require_once("lib/CAntObjectList.php");
	require_once("lib/WorkFlow.php");
	require_once("email/email_functions.awp");
	require_once("lib/WorkerMan.php");

	ini_set("max_execution_time", "7200");	
	ini_set("max_input_time", "7200");	

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$FUNCTION = $_GET['function'];

	$antfs = new AntFs($dbh, $USER);

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	switch ($FUNCTION)
	{
	// ---------------------------------------------------------
	// Get column headers for csv file
	// ---------------------------------------------------------
	/* DEPRICATED: Use object controller
	case "get_csv_headers":
		if ($_REQUEST['data_file_id'])
		{
			$headers_buf = "";

			$file = $antfs->openFileById($_REQUEST['data_file_id']);

			$tmpfname = $file->copyToTemp();

			if ($tmpfname)
			{
				$fh = fopen($tmpfname, "r");

				$data = fgetcsv($fh, 1024, ',', '"');
				$num = count($data);
				for ($i = 0; $i < $num; $i++)
				{
					if ($headers_buf) $headers_buf .= ", ";
					$headers_buf .= "\"".$data[$i]."\"";
				}

				fclose($fh);
				unlink($tmpfname);
			}

			if ($headers_buf)
				$retval = "[$headers_buf]";
			else
				$retval = -1;
		}
		else
		{
			$retval = -1;
		}
		break;
	*/
	// ---------------------------------------------------------
	// Get column headers for csv file
	// ---------------------------------------------------------
	case "save_template":
		if ($_REQUEST['save_template_name'] && $_POST['obj_type'])
		{
			$obj_def = new CAntObject($dbh, $_POST['obj_type']);

			if ($_POST['save_template_changes']=='t' && $_POST['template_id'])
			{
				$dbh->Query("update app_object_imp_templates set name='".$dbh->Escape($_POST['save_template_name'])."' 
								where id='".$_POST['template_id']."'");
				$tid = $_POST['template_id'];
			}
			else
			{
				$result = $dbh->Query("insert into app_object_imp_templates(type_id, name, user_id) 
										values('".$obj_def->object_type_id."', '".$dbh->Escape($_POST['save_template_name'])."', '$USERID');
										select currval('app_object_imp_templates_id_seq') as id;");
				if ($dbh->GetNumberRows($result))
					$tid = $dbh->GetValue($result, 0, "id");
			}

			if ($tid)
				$dbh->Query("delete from app_object_imp_maps where template_id='$tid'");

			if (is_array($_POST['maps']) && $tid)
			{
				foreach ($_POST['maps'] as $map)
				{
					$parts = explode(":::", $map);
					$dbh->Query("insert into app_object_imp_maps(template_id, col_name, property_name) 
									values('$tid', '".$dbh->Escape($parts[0])."', '".$dbh->Escape($parts[1])."')");
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
	*	Function:	get_templates
	*
	*	Purpose:	Get import templates
	**************************************************************************/
	case "get_templates":
		echo "<templates>";
		if ($_GET['obj_type'])
		{
			$obj_def = new CAntObject($dbh, $_GET['obj_type']);
			$result = $dbh->Query("select id, name 
									from app_object_imp_templates where type_id='".$obj_def->object_type_id."' 
									and (user_id='$USERID' or user_id is null)
									order by name");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);

				echo "<template>";
				echo "<id>".$row['id']."</id>";
				echo "<name>".rawurlencode($row['name'])."</name>";
				echo "<maps>";
				$res2 = $dbh->Query("select col_name, property_name from app_object_imp_maps where template_id='".$row['id']."'");
				$num2 = $dbh->GetNumberRows($res2);
				for ($j = 0; $j < $num2; $j++)
				{
					$row2 = $dbh->GetRow($res2, $j);
					echo "<map>";
					echo "<col_name>".rawurlencode($row2['col_name'])."</col_name>";
					echo "<property_name>".rawurlencode($row2['property_name'])."</property_name>";
					echo "</map>";
				}
				echo "</maps>";
				echo "</template>";
			}
		}
		echo "</templates>";

		break;

	// ---------------------------------------------------------
	// Save Template
	// ---------------------------------------------------------
	/* DEPRICATED: Use object controller
	case "import";
		if ($_REQUEST['data_file_id'] && $_REQUEST['obj_type'])
		{
			$jobdata = array("account_id"=>$ACCOUNT, "send_notifaction_to"=>UserGetEmail($dbh, $USER->id));
			foreach ($_GET as $vname=>$vval)
				$jobdata[$vname] = $vval;
			foreach ($_POST as $vname=>$vval)
				$jobdata[$vname] = $vval;

			$wp = new WorkerMan($dbh);
			$jobid = $wp->runBackground("object_import", serialize($jobdata));
			$retval = 1;
		}
		else
		{
			$retval = -1;
		}

		break;
	 */
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
