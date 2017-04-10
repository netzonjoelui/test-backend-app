<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/Email.php");
	require_once("email/email_functions.awp");
	require_once("project_functions.awp");
	require_once("lib/CAntObject.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$ACCOUNT_NAME = $USER->accountName;

	$FUNCTION = $_REQUEST['function'];

	// Return XML
	header("Content-type: text/xml");

	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	switch ($FUNCTION)
	{
	case 'save_case':
		if ($_REQUEST['title'])
		{
			$obj = new CAntObject($dbh, "case", $_REQUEST['id']);
			$ofields = $obj->fields->getFields();
			foreach ($ofields as $fname=>$field)
			{
				if ($field['type']=='fkey_multi' || $field['type']=='object_multi')
				{
					// Purge
					$obj->removeMValues($fname);

					if (is_array($_POST[$fname]) && count($_POST[$fname]))
					{
						// Add new
						foreach ($_POST[$fname] as $val)
							$obj->setMValue($fname, $val);
					}
				}
				else
				{
					$obj->setValue($fname, $_POST[$fname]);
				}
			}
			$retval = $obj->save();
		}
		else
		{
			$retval = "-1";
		}

		break;
	case 'add_case_comment':
		if ($_POST['cid'] && $_POST['comment'] && $_POST['username'])
		{
			$obj = new CAntObject($dbh, 'comment', null, $USER);
			$obj->setValue("obj_reference", "case:".$_POST['cid']);
			$obj->setValue("comment", $_POST['comment']);
			$obj->addAssociation("case", $_POST['cid']);
			$obj->save();

			/*
			$result = $dbh->Query("insert into project_bug_comments(user_name_cache, bug_id, body, time_posted)

							       values('".$dbh->Escape($_POST['username'])."', '".$_POST['cid']."', '".$dbh->Escape($_POST['comment'])."', 'now');");
			 */

			$retval = "1";
		}
		else
			$retaval = "-1";
		break;
	case 'add_bug':
		if ($_REQUEST['pid'])
		{
			$bug_title = rawurldecode($_REQUEST['title']);

			$query = "insert into project_bugs(title, status_id, date_reported, ts_entered, notify_email, 
						description, project_id, created_by, severity_id, type_id)
					  values (
					  '".$dbh->Escape($bug_title)."',
					  ".db_CheckNumber(rawurldecode($_REQUEST['status'])).", 
					  'now', 'now',
					  '".rawurldecode($_REQUEST['external_notify_email'])."', 
					  '".rawurldecode($_REQUEST['description'])."', 
					  ".$_REQUEST['pid'].", 
					  '".$dbh->Escape($USER)."',
					  ".db_CheckNumber(rawurldecode($_REQUEST['severity'])).",
					  ".db_CheckNumber(rawurldecode($_REQUEST['type'])).");
					  select currval('project_bugs_id_seq') as id;";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);

				$retval = $row['id'];

				if ($_REQUEST['notify_email'])
				{
					$to = rawurldecode($_REQUEST['notify_email']);
					$headers['From']  = $settings_no_reply;
					$headers['To']  = $to;
					if ($_REQUEST['notify_subject'])
						$headers['Subject']  = rawurldecode($_REQUEST['notify_subject']);
					else
						$headers['Subject']  = "New Quality Control Issue ".date('m/d/Y');
					$headers["Date"] =  date('D, j M Y H:i:s ', mktime()) . EmailTimeZone();
					$email_body = "A new QA issue has been submitted: ".$bug_title."\n";
					$email_body .= "\nIssue Id: ".$retval;
					$email_body .= "\nIssue Name: ".stripslashes($bug_title);
					$email_body .= "\nProject: ".GetProjectName($dbh, $_REQUEST['pid']);
					$email_body .= "\nSent by: ".$USER;
					$email_body .= "\n\n\nDescription:\n".$_POST['description'];
					// Create new email object
					$email = new Email();
					$status = $email->send($to, $headers, $email_body);
					unset($email);
				}

				// Log new issue
				ProjInsertLog($dbh, $_REQUEST['pid'], "quality", "New QA Issue - ".$bug_title, 
					  		  "By: ".$_REQUEST['username']." - ".substr(rawurldecode($_REQUEST['description']), 0, 128), $row['id']);
			}
			else
				$retval = "-1";
		}
		else
		{
			$retval = "Define a project id with pid";
		}
		break;
	case 'add_bug_comment':
		if ($_GET['bid'] && $_GET['comment'] && $_GET['username'])
		{

			$obj = new CAntObject($dbh, 'comment', null, $USER);
			$obj->setValue("obj_reference", "case:".$_POST['bid']);
			$obj->setValue("comment", $_POST['comment']);
			$obj->addAssociation("case", $_POST['bid']);
			$obj->save();
			/*
			$result = $dbh->Query("insert into project_bug_comments(user_name_cache, bug_id, body, time_posted)
							       values('".$dbh->Escape($_GET['username'])."', '".$_GET['bid']."', '".$dbh->Escape($_GET['comment'])."', 'now');");
			 */
			echo "<retval>1</retval>";
		}
		break;
	case 'get_bugs':
		if ($_GET['pid'])
		{
			echo "<bugs>";

			$query = "select project_bugs.id, title, status_id, severity_id, assigned_to, date_reported, 
						to_char(ts_entered, 'MM/DD/YYYY HH12:MI:SS am') as time_entered,
						project_bug_status.name as status_name, project_bug_severity.name as severity_name,
						project_bugs.project_id, type_id, description, created_by
						from project_bugs, project_bug_severity, project_bug_status 
						where project_bugs.severity_id=project_bug_severity.id
						and project_bugs.status_id=project_bug_status.id and
						project_bugs.project_id='".$_GET['pid']."' ";
			if ($_GET['bid'])
				$query .= " and project_bugs.id='".$_GET['bid']."' ";
			$query .= " order by status_id, title";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				
				$type_name = ($row['type_id']) ? ProjGetBugTypeName($dbh, $row['type_id']) : "None";

				echo "<bug>\n";
				echo "<id>".rawurlencode($row['id'])."</id>";
				echo "<title>".rawurlencode($row['title'])."</title>";
				echo "<description>".rawurlencode($row['description'])."</description>";
				echo "<status_id>".rawurlencode($row['status_id'])."</status_id>";
				echo "<severity_id>".rawurlencode($row['severity_id'])."</severity_id>";
				echo "<assigned_to>".rawurlencode($row['assigned_to'])."</assigned_to>";
				echo "<date_reported>".rawurlencode($row['date_reported'])."</date_reported>";
				echo "<time_entered>".rawurlencode($row['time_entered'])."</time_entered>";
				echo "<status_name>".rawurlencode($row['status_name'])."</status_name>";
				echo "<type_name>".rawurlencode($type_name)."</type_name>";
				echo "<severity_name>".rawurlencode($row['severity_name'])."</severity_name>";
				echo "<created_by>".rawurlencode($row['created_by'])."</created_by>";

				// Get comments
				$cres = $dbh->Query("select project_bug_comments.id, title, body, user_name_cache, 
									   to_char(time_posted, 'MM/DD/YYYY HH12:MI:SS am') as time,
									   users.name as username from project_bug_comments left outer join users
									   on (users.id = project_bug_comments.user_id)
									   where bug_id='".$row['id']."'");
				$cnum = $dbh->GetNumberRows($cres);
				if ($cnum) echo "<comments>";
				for ($j = 0; $j < $cnum; $j++)
				{
					$crow = $dbh->GetNextRow($cres, $j);
					$comid = $crow['id'];
					
					// Collapsed container
					echo "<comment>";
					echo "<username>".rawurlencode($crow['username'])."</username>";
					echo "<user_name_cache>".rawurlencode($crow['user_name_cache'])."</user_name_cache>";
					echo "<time>".rawurlencode($crow['time'])."</time>";
					echo "<body>".rawurlencode(str_replace("\n", "<br />", stripslashes($crow['body'])))."</body>";
					echo "</comment>";
				}
				$dbh->FreeResults($cres);
				if ($cnum) echo "</comments>";

				echo "</bug>";
			}
			
			echo "</bugs>";
		}
		else
		{
			$retval = "Define a project id with pid";
		}
		break;
	case 'get_project_details':
		if ($_GET['pid'])
		{
			$result = $dbh->Query("select * from projects where id='".$_GET['pid']."'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);

				echo "<project>\n";
				echo "<id>".rawurlencode($row['id'])."</id>";
				echo "<name>".rawurlencode($row['name'])."</name>";
				echo "<notes>".rawurlencode($row['notes'])."</notes>";
				echo "<news>".rawurlencode($row['news'])."</news>";
				echo "<date_deadline>".rawurlencode($row['date_deadline'])."</date_deadline>";
				echo "<date_started>".rawurlencode($row['date_started'])."</date_started>";
				echo "</project>";
			}
			else
				$retval = "Project id not found";
		}
		else
		{
			$retval = "Define a project id with pid";
		}
		break;
	//=========================================================================================
	// Task functions
	//=========================================================================================
	case 'task_delete':
		if ($_GET['tid'])
		{
			$result = $dbh->Query("select recur_id from project_tasks where id='".$_GET['tid']."' and user_id='$USERID'");
			$recur_id = $dbh->GetValue($result, 0, "recur_id");

			$dbh->Query("delete from project_tasks where id='".$_GET['tid']."' and user_id='$USERID'");
			if ($recur_id)
				$dbh->Query("delete from project_tasks_recurring where id='".$recur_id."'");
			$retval = 1;
		}
		else
		{
			$retval = -1;
			$retmsg = "Event id must be passed";
		}
		break;
	case 'task_get':
		$EID = $_GET['eid'];
		if ($EID)
		{
			$query = "select date_completed, deadline, name, start_date, recur_id from project_tasks where id='$EID'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);

				echo "<task>";

				echo "<is_recur>".(($row['recur_id'])?'t':'f')."</is_recur>";
				// See if this event is recurring
				if ($row['recur_id'])
				{
					// First check to see if we are an exception
					$res2 = $dbh->Query("select id from project_tasks_recurring_ex where task_id='$EID'");
					if ($dbh->GetNumberRows($res2))
					{
						$row2 = $dbh->GetNextRow($res2, 0);
						echo "<recur_exception_id>".$row2['id']."</recur_exception_id>";
					}
					$dbh->FreeResults($res2);
					
					// Now print recurrence info
					$res2 = $dbh->Query("select *, week_days[1] as day1,
									  week_days[2] as day2, week_days[3] as day3, week_days[4] as day4, week_days[5] as day5,
									  week_days[6] as day6, week_days[7] as day7 
									  from project_tasks_recurring where id='".$row['recur_id']."'");
					if ($dbh->GetNumberRows($res2))
					{
						$row2 = $dbh->GetNextRow($res2, 0);
						echo "<recur_interval>".$row2['interval']."</recur_interval>";
						echo "<recur_day>".$row2['day']."</recur_day>";
						echo "<recur_month>".$row2['month']."</recur_month>";
						echo "<recur_relative_type>".$row2['relative_type']."</recur_relative_type>";
						echo "<recur_relative_section>".$row2['relative_section']."</recur_relative_section>";
						echo "<recur_type>".$row2['type']."</recur_type>";

						echo "<recur_day1>".$row2['day1']."</recur_day1>";
						echo "<recur_day2>".$row2['day2']."</recur_day2>";
						echo "<recur_day3>".$row2['day3']."</recur_day3>";
						echo "<recur_day4>".$row2['day4']."</recur_day4>";
						echo "<recur_day5>".$row2['day5']."</recur_day5>";
						echo "<recur_day6>".$row2['day6']."</recur_day6>";
						echo "<recur_day7>".$row2['day7']."</recur_day7>";
						echo "<recur_date_start>".$row2['date_start']."</recur_date_start>";
						echo "<recur_date_end>".$row2['date_end']."</recur_date_end>";
					}
					$dbh->FreeResults($res2);
				}

				echo "<id>".rawurlencode(stripslashes($row['id']))."</id>";
				echo "<name>".rawurlencode(stripslashes($row['name']))."</name>";
				echo "<notes>".rawurlencode($row['notes'])."</notes>";
				echo "<f_completed>".rawurlencode(($row['date_completed'])?'t':'f')."</f_completed>";
				echo "<date_start>".rawurlencode(stripslashes($row['start_date']))."</date_start>";
				echo "<date_due>".rawurlencode($row['deadline'])."</date_due>";

				echo "</task>";
			}
		}
		else
			$retval = "-1";

		break;
	case 'task_save':
		$EID = $_POST['eid'];
		$name = $_POST["name"];
		$notes = utf8_encode($_POST["notes"]);
		$done = ($_POST["f_completed"]=='t') ? 't' : 'f';	

		// Check if we are to update or to edit
		if($EID && $USERID)
		{
			$result = $dbh->Query("select * from project_tasks where id='".$EID."'");
			if ($dbh->GetNumberRows($result))
				$PVALS = $dbh->GetRow($result, 0);

			$query = "update project_tasks set done='$done',";
			if ($_POST['f_completed']  == 't' && !$PVALS['date_completed'])
				$query .= "date_completed = 'now', ";
			else if ($_POST['f_completed']  == 'f' && $PVALS['date_completed'])
				$query .= "date_completed = NULL, ";
			$query .= " ts_updated='now',
					    name='".$dbh->Escape($_POST["name"])."',
					    notes='".$dbh->Escape($notes)."',
					    user_id='$USERID',
					    deadline= ".db_UploadDate($_POST["deadline"]).",
					    start_date= ".db_UploadDate($_POST["start_date"])."
					    where id='".$EID."'";

			$result = $dbh->Query($query);

			if (!$PVALS['date_completed'] && $_POST['f_completed'] == 't' && $USERNAME!=$PVALS['entered_by'])
			{
				$creator_id = UserGetIdFromName($dbh, $PVALS['entered_by'], $ACCOUNT);

				if ($creator_id)
				{
					$headers['Subject'] = "Task Completed By [$USERNAME]";
					$headers['From'] = $settings_no_reply;
					$headers['To'] = UserGetEmail($dbh, $creator_id);
					$body = "Task Name: $name\r\n";
					$body .= "$USERNAME completed task.\r\n";
					$email = new Email();
					$status = $email->send($headers['To'], $headers, $body);
					unset($email);
				}
			}

			if ($PVALS['recur_id'] && $_POST['recur_type'])
			{
				$dbh->Query("delete from project_tasks where recur_id='".$PVALS['recur_id']."' and id!='".$EID."'");

				$query = "update project_tasks_recurring set
							type='".$_POST['recur_type']."',
							name='".$dbh->Escape($_POST['name'])."', 
							notes='".$dbh->Escape($notes)."', 
							user_id='".$USERID."', 
							project=".$dbh->EscapeNumber($project).", 
							date_start=".db_UploadDate($_POST['recur_date_start']).", 
							date_end=".db_UploadDate($_POST['recur_date_end']).",
							interval=".$dbh->EscapeNumber($_POST['recur_interval']).",
							day=".$dbh->EscapeNumber($_POST['recur_day']).",
							priority=".$dbh->EscapeNumber($_POST['priority']).",
							month=".$dbh->EscapeNumber($_POST['recur_month']).",
							relative_type=".$dbh->EscapeNumber($_POST['recur_relative_type']).",
							relative_section=".$dbh->EscapeNumber($_POST['recur_relative_section']).",
							week_days[1]='".(($_POST['recur_day1'] == '1') ? 't' : 'f')."',
							week_days[2]='".(($_POST['recur_day2'] == '1') ? 't' : 'f')."',
							week_days[3]='".(($_POST['recur_day3'] == '1') ? 't' : 'f')."',
							week_days[4]='".(($_POST['recur_day4'] == '1') ? 't' : 'f')."',
							week_days[5]='".(($_POST['recur_day5'] == '1') ? 't' : 'f')."',
							week_days[6]='".(($_POST['recur_day6'] == '1') ? 't' : 'f')."',
							week_days[7]='".(($_POST['recur_day7'] == '1') ? 't' : 'f')."'
							where id='".$PVALS['recur_id']."'";
				$dbh->Query($query);
			}
			else if ($PVALS['recur_id'] && !$_POST['recur_type'])
			{
				// Removed recurrance
				$dbh->Query("delete from project_tasks_recurring where id='".$PVALS['recur_id']."'");
			}
		}
		else
		{
			if ((!$PVALS['date_completed'] && $_POST['f_completed'] == 't') ||
					($PVALS['date_completed'] && $_POST['completed'] == 'f'))
			{
				$comp_head = ", date_completed";
				
				if (!$PVALS['date_completed'] && $_POST['f_completed'] == 't')
					$comp_val = ", 'now'";
				else if ($PVALS['date_completed'] && $_POST['f_completed'] == 'f')
					$comp_val = ", NULL";
			}
			$query = "insert into project_tasks (name, notes, user_id, done, date_entered, start_date, ts_updated,
					    entered_by, deadline $comp_head)
					    values
					    ('".$dbh->Escape($name)."', '".$dbh->Escape($notes)."', '$USERID', 
				  	    '$done', '".date("m/d/Y")."', ".db_UploadDate($_POST["start_date"]).", 'now', '$USERNAME', 
					    ".db_UploadDate($_POST["deadline"])." 
						$comp_val);
						select currval('project_tasks_id_seq') as id;";

			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$EID = $row['id'];
			}
		}

		if ($EID)
		{
			$result = $dbh->Query("select to_char(ts_updated, 'MM/DD/YYYY HH12:MI:SS AM') as time_updated from project_tasks where id='$EID'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetRow($result, 0);
				echo "<response>";
				echo "<retval>1</retval>";
				echo "<task>";
				echo "<id>".$EID."</id>";
				echo "<ts_updated>".rawurlencode($row['time_updated'])."</ts_updated>";
				echo "</task>";
				echo "</response>";
			}
			$dbh->FreeResults($result);
		}
		else
			$retval = "-1";
		break;
	default:
		$retval = "-1";
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
