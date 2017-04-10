<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/Email.php");
	require_once("userfiles/file_functions.awp");
	require_once("lib/CAntFs.awp");
	require_once("project_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_REQUEST['function'];

	switch ($FUNCTION)
	{
	/*************************************************************************
	*	Function:	activity_save
	*
	*	Purpose:	Enter a new or update an existing activity
	**************************************************************************/
	case "delete_attachment":
		$aid = $_REQUEST['aid'];
		// Get the owner
		if (is_numeric($aid))
		{
			$dbh->Query("delete from project_files where id='$aid'");
			$retval = $aid;
		}
		break;

	/*************************************************************************
	*	Function:	get_templates
	*
	*	Purpose:	Get list of templates
	**************************************************************************/
	case "get_templates":
		$retval = "[";
		$result = $dbh->Query("select id, name from project_templates order by name");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			if ($i) $retval .= ", ";
			$retval .= "{id:\"".$row['id']."\", name:\"".$row['name']."\"}";
		}
		$retval .= "]";

		break;


	/*************************************************************************
	*	Function:	delete_template
	*
	*	Purpose:	Delete a project template
	**************************************************************************/
	case "delete_template":
		$tid = $_REQUEST['template_id'];
		// Get the owner
		if (is_numeric($tid))
		{
			$result = $dbh->Query("delete from project_templates where id='$tid'");
			$retval = 1;
		}
		else
			$retval = -1;

		break;

	/*************************************************************************
	*	Function:	get_template_description
	*
	*	Purpose:	Get the description of a project template
	**************************************************************************/
	case "get_template_notes":
		$tid = $_REQUEST['tid'];
		// Get the owner
		if (is_numeric($tid))
		{
			$result = $dbh->Query("select notes from project_templates where id='$tid'");
			if ($dbh->GetNumberRows($result))
			{
				$retval = $dbh->GetValue($result, 0, "notes");
				if (!$retval)
					$retval = -1;
			}
			else
				$retval = -1;
		}
		else
			$retval = -1;
		break;

	/*************************************************************************
	*	Function:	case_save_attachments
	*
	*	Purpose:	Save attachments for a case
	**************************************************************************/
	case "case_save_attachments":
		$CASEID = $_REQUEST['case_id'];
		// Get the owner
		if (is_numeric($CASEID) && is_array($_POST['uploaded_file']))
		{
			$antfs = new CAntFs($dbh, $USER);
			if ($_POST['project_id'])
				$path = "/Project Files/".$_POST['project_id'];
			else
				$path = "/Project Files/Tickets/$CASEID";
			$proj_folder = $antfs->openFolder($path, true);

			foreach ($_POST['uploaded_file'] as $fid)
			{
				$antfs->moveTmpFile($fid, $proj_folder);
				$dbh->Query("insert into project_files(file_id, project_id, bug_id) 
							 values('$fid', ".db_CheckNumber($_POST['project_id']).", '$CASEID');");
			}

			$retval = 1;
		}
		else
			$retval = -1;
		break;


	/*************************************************************************
	*	Function:	case_remove_attachment
	*
	*	Purpose:	Remove attachments for a case
	**************************************************************************/
	case "case_remove_attachment":
		$CASEID = $_REQUEST['case_id'];
		$FID = $_REQUEST['fid'];
		// Get the owner
		if (is_numeric($CASEID) && is_numeric($FID))
		{
			$antfs = new CAntFs($dbh, $USER);

			$dbh->Query("delete from project_files where file_id='$FID' and bug_id='$CASEID'");

			$ret = $antfs->removeFileById($FID);
			if ($ret)
				$retval = "1";
			else
				$retval = "-1";
		}
		else
			$retval = -1;
		break;
		
	/*************************************************************************
	*	Function:	case_save_attachments
	*
	*	Purpose:	Save attachments for a case
	**************************************************************************/
	case "case_get_attachments":
		$CASEID = $_REQUEST['case_id'];
		// Get the owner
		if (is_numeric($CASEID))
		{
			$antfs = new CAntFs($dbh, $USER);
			$retval = "[";
			$query = "select file_title, file_size, user_files.id, project_files.id as aid from project_files, user_files
								where project_files.file_id=user_files.id and project_files.bug_id='$CASEID'";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i=0; $i<$num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$name = $row['file_title'];

				if ($i)
					$retval .= ",";
				$retval .= "{fid:\"".rawurlencode($row['id'])."\", name:\"".rawurlencode($row['file_title'])."\"}";
			}
			$dbh->FreeResults($result);
			$retval .= "]";
		}
		else
			$retval = -1;
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
			$dbh->Query("update project_groups set color='$color' where id='$gid'");
			$retval = $color;
		}
		break;
	case "group_rename":
		$gid = $_REQUEST['gid'];
		$name = rawurldecode($_REQUEST['name']);

		if ($gid && $name)
		{
			$dbh->Query("update project_groups set name='".$dbh->Escape($name)."' where id='$gid'");
			$retval = $name;
		}
		break;
	case "group_delete":
		$gid = $_REQUEST['gid'];

		if ($gid)
		{
			$dbh->Query("delete from project_groups where id='$gid'");
			$retval = $gid;
		}
		break;
	case "group_add":
		$pgid = ($_REQUEST['pgid'] && $_REQUEST['pgid'] != "null") ? "'".$_REQUEST['pgid']."'" : "NULL";
		$name = rawurldecode($_REQUEST['name']);
		$color = rawurldecode($_REQUEST['color']);

		if ($name && $color)
		{
			$query = "insert into project_groups(parent_id, name, color) 
					  values($pgid, '".$dbh->Escape($name)."', '".$dbh->Escape($color)."');
					  select currval('project_groups_id_seq') as id;";
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
	/*************************************************************************
	*	Function:	getCodes
	*
	*	Purpose:	get stage and code flags
	**************************************************************************/
	case "get_codes":
		if ($_REQUEST['tbl'])		// Update specific event
		{
			$retval = "[";
			$result = $dbh->Query("select * from ".$_REQUEST['tbl']." order by sort_order");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i, PGSQL_ASSOC);
				$cntr = 0;
				if ($i) $retval .= ",";
				$retval .= "{";
				foreach ($row as $name=>$val)
				{
					if ($cntr) $retval .= ",";
					$retval .= "$name:\"$val\"";
					$cntr++;
				}
				$retval .= "}";
			}
			$dbh->FreeResults($result);
			$retval .= "]";
		}
		else
		{
			$retval = -1;
		}
		break;
	/*************************************************************************
	*	Function:	save_code
	*
	*	Purpose:	Create a contact or an account
	**************************************************************************/
	case "save_code":
		if ($_POST['tbl'])		// Update specific event
		{
			// TODO: veryify user has access to modify customer settings - typically only admins

			// Sort order
			if ($_POST['id'] && $_POST['sorder'])
			{
				$result = $dbh->Query("select sort_order from ".$_POST['tbl']." where id='".$_POST['id']."'");
				if ($dbh->GetNumberRows($result))
					$cur_order = $dbh->GetValue($result, 0, "sort_order");

				if ($cur_order && $cur_order!=$_POST['sorder'])
				{
					// Moving up or down
					if ($cur_order < $_POST['sorder'])
						$direc = "down";
					else
						$direc = "up";

					$result = $dbh->Query("select id  from ".$_POST['tbl']." where id!='".$_POST['id']."'
											and sort_order".(($direc=="up")?">='".$_POST['sorder']."'":"<='".$_POST['sorder']."'")." order by sort_order");
					$num = $dbh->GetNumberRows($result);
					for ($i = 0; $i < $num; $i++)
					{
						$id = $dbh->GetValue($result, $i, "id");
						$newval = ("up" == $direc) ? $_POST['sorder']+1+$i : $i+1;
						$dbh->Query("update ".$_POST['tbl']." set sort_order='$newval' where id='".$id."'");
					}
					$dbh->Query("update ".$_POST['tbl']." set sort_order='".$_POST['sorder']."' where id='".$_POST['id']."'");
				}
			}

			// Color
			if ($_POST['id'] && $_POST['color'])
			{
				$dbh->Query("update ".$_POST['tbl']." set color='".$_POST['color']."' where id='".$_POST['id']."'");
			}

			// Name and enter new
			if ($_POST['name'])
			{
				if ($_POST['id'])
				{
					$dbh->Query("update ".$_POST['tbl']." set name='".$dbh->Escape($_POST['name'])."' where id='".$_POST['id']."'");
				}
				else 
				{
					$result = $dbh->Query("select sort_order from ".$_POST['tbl']." order by sort_order DESC limit 1");
					if ($dbh->GetNumberRows($result))
						$sorder = $dbh->GetValue($result, 0, "sort_order");

					$dbh->Query("insert into ".$_POST['tbl']."(name, sort_order) 
									values('".$dbh->Escape($_POST['name'])."', '".($sorder+1)."');");
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
	*	Function:	delete_code
	*
	*	Purpose:	Delete a code
	**************************************************************************/
	case "delete_code":
		if ($_POST['tbl'])		// Update specific event
		{
			// TODO: veryify user has access to modify customer settings - typically only admins

			// Sort order
			if ($_POST['id'])
			{
				$dbh->Query("delete from ".$_POST['tbl']." where id='".$_POST['id']."'");
			}
			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;
	/*************************************************************************
	*	Function:	set_case_stat_closed
	*
	*	Purpose:	get stage and code flags
	**************************************************************************/
	case "set_case_stat_closed":
		if ($_REQUEST['id'] && $_REQUEST['f_closed'])
		{
			// TODO: veryify user has access to modify customer settings - typically only admins
			$dbh->Query("update project_bug_status set f_closed='".$dbh->Escape($_POST['f_closed'])."' where id='".$_REQUEST['id']."'");

			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;

	/*************************************************************************
	*	Function:	save_members
	*
	*	Purpose:	Save members for a project 
	**************************************************************************/
	case "save_members":
		$PID = $_REQUEST['project_id'];
		if ($PID)
		{
			$obj = new CAntObject($dbh, "project", $PID, $USER);
			if (is_array($_POST['delete']) && count($_POST['delete']))
			{
				for ($i = 0; $i < count($_POST['delete']); $i++)
					$obj->removeMValue("members", $_POST['delete'][$i]);

					//$dbh->Query("delete from project_membership where id='".$_POST['delete'][$i]."'");
			}

			if (is_array($_POST['members']) && count($_POST['members']))
			{
				for ($i = 0; $i < count($_POST['members']); $i++)
				{
					$obj->setMValue("members", $_POST['members'][$i]);
				}

				$obj->save(false);

				// Save positions
				for ($i = 0; $i < count($_POST['members']); $i++)
				{
					$dbh->Query("update project_membership set position_id=".$dbh->EscapeNumber($_POST['m_position_id_'.$_POST['members'][$i]])."
										where project_id='".$PID."' and user_id='".$_POST['members'][$i]."'");
					/*
					$query = "select id from project_membership where project_id='".$PID."' and user_id='".$_POST['members'][$i]."'";
					if ($dbh->GetNumberRows($dbh->Query($query)))
					{
						$dbh->Query("update project_membership set position_id=".$dbh->EscapeNumber($_POST['m_position_id_'.$_POST['members'][$i]])."
											where project_id='".$PID."' and user_id='".$_POST['members'][$i]."'");
					}
					else
					{
						$dbh->Query("insert into project_membership(project_id, user_id, accepted, position_id) values
										('$PID', '".$_POST['members'][$i]."', 't', 
										 ".$dbh->EscapeNumber($_POST['m_position_id_'.$_POST['members'][$i]]).")");
					}
					 */
				}
			}

			$retval = 1;
		}
		else
			$retval = -1;
		break;

	/*************************************************************************
	*	Function:	get_members
	*
	*	Purpose:	Get array of members for a project
	**************************************************************************/
	case "get_members":
		$PID = $_REQUEST['project_id'];
		if ($PID)
		{
			$retval = "[";
			$query = "select project_membership.id, project_membership.user_id, users.name as username, project_positions.name as position_name,
							project_membership.accepted, project_membership.position_id from users, project_membership 
							left outer join project_positions on (project_membership.position_id = project_positions.id)
							where project_membership.project_id='$PID' and project_membership.user_id=users.id
							
							order by username";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i=0; $i<$num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$name = CustGetName($dbh, $row['cid']);
				$email = CustGetEmail($dbh, $row['cid']);
				$phone = CustGetPhone($dbh, $row['cid']);
				$title = CustGetColVal($dbh, $row['cid'], "job_title");
				$rname = $row['relationship_name'];
				$relationship = ($row['type_name']) ? $row['type_name'] : $row['relationship_name'];

				if ($i)
					$retval .= ",";
				$retval .= "{id:\"".rawurlencode($row['id'])."\", user_id:\"".rawurlencode($row['user_id'])."\"";
				$retval .= ", username:\"".rawurlencode($row['username'])."\", position_name:\"".rawurlencode($row['position_name'])."\"";
				$retval .= ", position_id:\"".rawurlencode($row['position_id'])."\"}";
			}
			$dbh->FreeResults($result);
			$retval .= "]";
		}
		else
			$retval = -1;
		break;

	/*************************************************************************
	*	Function:	get_positions
	*
	*	Purpose:	Get array of positions types
	**************************************************************************/
	case "get_positions":
		$PID = $_REQUEST['project_id'];
		if ($PID)
		{
			$retval = "[";
			$result = $dbh->Query("select id, name from project_positions where project_id='$PID' order by name");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				if ($i) $retval .= ", ";
				$retval .= "{id:\"".$row['id']."\", name:\"".$row['name']."\"}";
			}
			$retval .= "]";
		}
		else
			$retval = -1;

		break;
	/*************************************************************************
	*	Function:	position_add
	*
	*	Purpose:	Add a new position
	**************************************************************************/
	case "position_add":
		$PID = $_REQUEST['project_id'];
		if ($PID)
		{
			$result = $dbh->Query("insert into project_positions(name, project_id) values('".$dbh->Escape($_REQUEST['name'])."', '$PID'); 
									select currval('project_positions_id_seq') as id;");
			if ($dbh->GetNumberRows($result))
				$retval = $dbh->GetValue($result, 0, "id");
		}

		if (!$retval)
			$retval = -1;

		break;
	/*************************************************************************
	*	Function:	position_delete
	*
	*	Purpose:	Delete a new position
	**************************************************************************/
	case "position_delete":
		$pid = $_REQUEST['pid'];
		if ($pid)
		{
			$dbh->Query("delete from project_positions where id='$pid'");
			$retval = 1;
		}

		if (!$retval)
			$retval = -1;

		break;

	/*************************************************************************
	*	Function:	update_project
	*
	*	Purpose:	Handle onsave hooks for things like updating tasks/cases
	**************************************************************************/
	case "update_project_hooks":
		$pid = $_REQUEST['project_id'];
		$date_completed = rawurldecode($_REQUEST['date_completed']);
		if ($pid && $date_completed && $date_completed!="null")
		{
			// Update tasks
			$olist = new CAntObjectList($dbh, "task", $USER);
			$olist->addCondition("and", "done", "is_not_equal", 't');
			$olist->addCondition("and", "date_completed", "is_equal", '');
			$olist->addCondition("and", "project", "is_equal", $pid);
			$olist->getObjects();
			$num = $olist->getNumObjects();
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $olist->getObject($i);
				$obj->setValue("done", 't');
				$obj->setValue("date_completed", $date_completed);
				$obj->save();
			}

			// Update milestones
			$olist = new CAntObjectList($dbh, "project_milestone", $USER);
			$olist->addCondition("and", "f_completed", "is_not_equal", 't');
			$olist->addCondition("and", "project_id", "is_equal", $pid);
			$olist->getObjects();
			$num = $olist->getNumObjects();
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $olist->getObject($i);
				$obj->setValue("f_completed", 't');
				$obj->save();
			}
		}

		// Fall through to update_templated_project below to manage templates
	
	/*************************************************************************
	*	Function:	update_templated_project
	*
	*	Purpose:	Update a project that is attached to a template - tasks and such
	**************************************************************************/
	case "update_templated_project":
		$pid = $_REQUEST['project_id'];
		$tid = $_REQUEST['template_id'];
		if ($pid && $tid)
		{
			$obj = new CAntObject($dbh, "project", $pid, $USER);
			$date_started = $obj->getValue("date_started");
			$date_deadline = $obj->getValue("date_deadline");

			$query = "select id, name, start_interval, start_count, due_interval, due_count, 
						 timeline, type, file_id, user_id, timeline_date_begin, timeline_date_due from
						 project_template_tasks where template_id='".$tid."'";
			$task_result = $dbh->Query($query);
			$task_num = $dbh->GetNumberRows($task_result);
			for ($j = 0; $j < $task_num; $j++)
			{
				$task_row = $dbh->GetNextRow($task_result, $j);
				$tl_date_begin = ($task_row['timeline_date_begin']) ? $task_row['timeline_date_begin'] : 'date_deadline';
				$tl_date_due = ($task_row['timeline_date_due']) ? $task_row['timeline_date_due'] : 'date_deadline';
				
				if (($tl_date_begin == "date_deadline" || $tl_date_due == "date_deadline") && $obj->getValue("date_deadline")=="")
					continue; // Skip over, deadline is not provided

				$query = "update project_tasks set ts_updated='now', revision=revision+1,
							start_date='".ProjectGetExeTime($dbh, $obj->getValue($tl_date_begin), $task_row['start_count'], 
							$task_row['start_interval'], $task_row['timeline'])."', 
							deadline='".ProjectGetExeTime($dbh, $obj->getValue($tl_date_begin), $task_row['due_count'], 
							$task_row['due_interval'], $task_row['timeline'])."'
							where template_task_id='".$task_row['id']."' and project='".$pid."'";
				$dbh->Query($query);
			}
			$dbh->FreeResults($task_result);
		}

		$retval = 1;

		break;
	
	/*************************************************************************
	*	Function:	create_project
	*
	*	Purpose:	Create a project
	**************************************************************************/
	case "create_project":
		if ($_POST['name'])
		{
			$obj = new CAntObject($dbh, "project", null, $USER);
			$obj->setValue("user_id", $USERID);
			$obj->setValue("name", $_POST['name']);
			$obj->setValue("date_started", $_POST['date_started']);
			$obj->setValue("date_deadline", $_POST['date_deadline']);
			$obj->setValue("template_id", $_POST['template_id']);
			$obj->setValue("priority", 1);
			$obj->setValue("notes", $_POST['notes']);
			$id = $obj->save();

			/*
			$query = "insert into projects(user_id, name, priority, notes, date_started, template_id, ts_created)
						values('$USERID', '".$dbh->Escape($_POST['name'])."', '1', '".$dbh->Escape($_POST['notes'])."', 
						now(), ".db_CheckNumber($_POST['template_id']).", now());
						select currval('projects_id_seq') as id;";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$id = $row['id'];
				$retval = $id;
				$dbh->FreeResults($result);
			 */
			if ($id)
			{
				$retval = $id;

				// Add user as the first member
				$dbh->Query("insert into project_membership(user_id, project_id, title, notes, accepted)
							 		values('$USERID', '$id', 'Project Creator', 'Created Project', 't');");
				
				// Copy parent membership and permissions
				/*
				if ($_POST['parent_id'])
				{
					ProjectCopyMembership($dbh, $id, $_POST['parent_id'], $USERID);
				}
				 */
				
				if (is_numeric($_POST['template_id']))
				{
					$query = "select id, name, start_interval, start_count, due_interval, due_count, notes, cost_estimated,
							  timeline, type, file_id, user_id, position_id, timeline_date_begin, timeline_date_due 
							  from project_template_tasks where template_id='".$_POST['template_id']."'";
								 
					$task_result = $dbh->Query($query);
					$task_num = $dbh->GetNumberRows($task_result);
					for ($j = 0; $j < $task_num; $j++)
					{
						$task_row = $dbh->GetNextRow($task_result, $j);
						$tl_date_begin = ($task_row['timeline_date_begin']) ? $task_row['timeline_date_begin'] : 'date_deadline';
						$tl_date_due = ($task_row['timeline_date_due']) ? $task_row['timeline_date_due'] : 'date_deadline';
						
						$query = "insert into project_tasks (name, user_id, position_id, done, date_entered, start_date,
								    entered_by, project, priority, deadline, type, notes, file_id, template_task_id, cost_estimated)
								    values
								    ('".$dbh->Escape($task_row['name'])."',  
									".db_CheckNumber($task_row['user_id']).", 
									".db_CheckNumber($task_row['position_id']).",
								    'f', '".date("m/d/Y")."', 
								    ".$dbh->EscapeDate(ProjectGetExeTime($dbh, $_POST[$tl_date_begin], $task_row['start_count'], 
														 $task_row['start_interval'], $task_row['timeline'])).", 
								    '$USERNAME', '$id', '1',
								    ".$dbh->EscapeDate(ProjectGetExeTime($dbh, $_POST[$tl_date_due], $task_row['due_count'], 
														 $task_row['due_interval'], $task_row['timeline'])).",
								    ".db_CheckNumber($task_row['type']).", 
								    '".$dbh->Escape(stripslashes($task_row['notes']))."',  
								    ".db_CheckNumber($task_row['file_id']).", 
									".db_CheckNumber($task_row['id']).",
									".db_CheckNumber($task_row['cost_estimated']).")";
						$dbh->Query($query);
					}
					$dbh->FreeResults($task_result);
					
					$query = "select user_id from project_template_members where template_id='".$_POST['template_id']."'";
					$task_result = $dbh->Query($query);
					$task_num = $dbh->GetNumberRows($task_result);
					for ($j = 0; $j < $task_num; $j++)
					{
						$task_row = $dbh->GetNextRow($task_result, $j);
						
						if ($task_row['user_id'] != $USERID)
						{
							$query = "insert into project_membership (user_id, project_id, title,
										invite_by, accepted)
										values
										('".$task_row['user_id']."', '$id', 
										'Invited Member', 
										'$USERNAME', 'f')";
							$dbh->Query($query);
						}
					}
					$dbh->FreeResults($task_result);

					$query = "select id, name from project_positions where template_id='".$_POST['template_id']."'";
					$task_result = $dbh->Query($query);
					$task_num = $dbh->GetNumberRows($task_result);
					for ($j = 0; $j < $task_num; $j++)
					{
						$task_row = $dbh->GetNextRow($task_result, $j);
						
						if ($task_row['id'])
						{
							$query = "insert into project_positions (name, project_id)
										values
										('".$dbh->Escape($task_row['name'])."', '$id');
									  select currval('project_positions_id_seq') as posid;";
							$pos_res = $dbh->Query($query);
							if ($dbh->GetNumberRows($pos_res))
							{
								$pos_row = $dbh->GetNextRow($pos_res, 0);
								$dbh->Query("update project_tasks set position_id='".$pos_row['posid']."' where 
											 project='$id' and position_id='".$task_row['id']."'");
							}
						}
					}					
					$dbh->FreeResults($task_result);

				}
			}
		}

		if (!$retval)
			$retval = -1;

		break;

	/*************************************************************************
	*	Function:	task_log_time
	*
	*	Purpose:	Log time spent on a task
	**************************************************************************/
	case "task_log_time":
		if ($_REQUEST['task_id'] && $_REQUEST['hours'] && $_REQUEST['date_applied'] && $_REQUEST['name'])
		{
			$objTime = new CAntObject($dbh, "time", null, $USER);
			$objTime->setValue("name", $_REQUEST['name']);
			$objTime->setValue("date_applied", $_REQUEST['date_applied']);
			$objTime->setValue("hours", $_REQUEST['hours']);
			$objTime->setValue("task_id", $_REQUEST['task_id']);
			$timeid = $objTime->save();

			// Get aggregated value
			$obj = new CAntObject($dbh, "task", $_REQUEST['task_id'], $USER);
			$retval = $obj->getValue("cost_actual");
		}
		else
		{
			$retval = "-1";
		}
		break;

	/*************************************************************************
	*	Function:	case_taskowner
	*
	*	Purpose:	Assig a task to the owner of this case
	**************************************************************************/
	case "case_taskowner":
		$case_id = $_REQUEST['cid'];
		$owner_id = $_REQUEST['owner_id'];
		$case_name = $_REQUEST['case_name'];
		// Get the owner
		if (is_numeric($case_id) && is_numeric($owner_id))
		{
			$task = new CAntObject($dbh, "task", null, $USER);
			$task->setValue("name", "Address Case: $case_name");
			$task->setValue("notes", "NOTE: Be sure and close the case when you complete this task");
			$task->setValue("user_id", $owner_id);
			$task->setValue("case_id", $case_id);
			$retval = $task->save();
		}
		else
			$retval = -1;

		break;
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
	echo "<response>";
	echo "<retval>" . rawurlencode($retval) . "</retval>";
	echo "<cb_function>".$_GET['cb_function']."</cb_function>";
	echo "</response>";
?>
