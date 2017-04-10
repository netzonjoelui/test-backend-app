<?php
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("lib/WorkFlow.php");
	require_once("email/email_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_GET['function'];

	ini_set("max_execution_time", "7200");	
	ini_set("max_input_time", "7200");	
	ini_set("memory_limit", "500M");	

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	switch ($FUNCTION)
	{
	// ---------------------------------------------------------
	// Save Object
	// ---------------------------------------------------------
	case "save_object":
		
		if ($_REQUEST['obj_type'])
		{
			$ant_obj = new CAntObject($dbh, $_REQUEST['obj_type'], $_REQUEST['oid'], $USER);
			if ($ant_obj->getValue("f_readonly") != 't') // Do not write to read-only
			{
				
				$ofields = $ant_obj->fields->getFields();
				foreach ($ofields as $fname=>$field)
				{
					if ($field['type']=='fkey_multi' || $field['type']=='object_multi')
					{
						// Purge
						$ant_obj->removeMValues($fname);

						if (is_array($_POST[$fname]) && count($_POST[$fname]))
						{
							// Add new
							foreach ($_POST[$fname] as $val)
								$ant_obj->setMValue($fname, $val);
						}
					}
					else
					{
						$ant_obj->setValue($fname, $_POST[$fname]);
					}
				}
				
				// Set comments associations to all directly associated objects
				if ($_REQUEST['obj_type'] == "comment" && $_POST['obj_reference'])
				{
					$parts = explode(":", $_POST['obj_reference']);
					if (count($parts) > 1)
					{
						$objref = new CAntObject($dbh, $parts[0], $parts[1], $USER);
						$objref->setHasComments();
						$ofields = $objref->fields->getFields();
						foreach ($ofields as $fname=>$field)
						{
							if ($field['type']=='object' && ($field['subtype'] || $fname=="obj_reference"))
							{
								$val = $objref->getValue($fname);
								if ($val)
								{
									if ($field['subtype'])
									{
										$ant_obj->setMValue("associations", $field['subtype'].":".$val);
									}
									else if (count(explode(":", $val))>1)
									{
										$ant_obj->setMValue("associations", $val);
									}
								}
							}
						}
					}

					if (!$_POST['sent_by'])
						$ant_obj->setValue("sent_by", "user:$USERID"); 
				}
				
				// Set recurrence if exists
				if ($_POST['save_recurrence_pattern']) // whatever the posted variable name is from CANtObject.js::save
				{
					
					$rp_newvobj =  json_decode($_POST['objpt_json']);
					
					if ($rp_newvobj->save_type == "exception")
					{
						$ant_obj->recurrenceException = true;
					}
					else
					{
						//$rp = $ant_obj->getRecurrencePattern();
						//$rp = new CRecurrencePattern($dbh); // the above will bind it automatically to this object
						//$rp->id = $rp_newvobj->id; // calling getRecurrencePattern from object will automatically set id
						//$rp->object_type_id = $rp_newvobj->object_type_id;  // same as above
						//$rp->object_type = $rp_newvobj->object_type;   // same as above
						//$rp->dateProcessedTo = $rp_newvobj->dateProcessedTo;   // same as above
						//$rp->parentId = $retval; // same as above
						//$rp->timeStart = $rp_newvobj->timeStart; // set by parent class, this is currently no longer used
						//$rp->timeEnd = $rp_newvobj->timeEnd; // same as above
						//$rp->fActive = $rp_newvobj->fActive; // Automatically set by CAntObject class
						
						$ant_obj->getRecurrencePattern();
						
						$ant_obj->recurrencePattern->type = $rp_newvobj->type; 
						$ant_obj->recurrencePattern->interval = $rp_newvobj->interval;
						$ant_obj->recurrencePattern->dateStart = $rp_newvobj->dateStart;  
						$ant_obj->recurrencePattern->dateEnd = $rp_newvobj->dateEnd; 
						$ant_obj->recurrencePattern->fAllDay = $rp_newvobj->fAllDay; 
						$ant_obj->recurrencePattern->dayOfMonth = $rp_newvobj->dayOfMonth; 
						$ant_obj->recurrencePattern->monthOfYear = $rp_newvobj->monthOfYear;  
						$ant_obj->recurrencePattern->instance = $rp_newvobj->insdatetance;
						
						if ($rp_newvobj->day1 == 't')
							$ant_obj->recurrencePattern->dayOfWeekMask = $ant_obj->recurrencePattern->dayOfWeekMask | WEEKDAY_SUNDAY;
						if ($rp_newvobj->day2 == 't')
							$ant_obj->recurrencePattern->dayOfWeekMask = $ant_obj->recurrencePattern->dayOfWeekMask | WEEKDAY_MONDAY;
						if ($rp_newvobj->day3 == 't')
							$ant_obj->recurrencePattern->dayOfWeekMask = $ant_obj->recurrencePattern->dayOfWeekMask | WEEKDAY_TUESDAY;
						if ($rp_newvobj->day4 == 't')
							$ant_obj->recurrencePattern->dayOfWeekMask = $ant_obj->recurrencePattern->dayOfWeekMask | WEEKDAY_WEDNESDAY;
						if ($rp_newvobj->day5 == 't')
							$ant_obj->recurrencePattern->dayOfWeekMask = $ant_obj->recurrencePattern->dayOfWeekMask | WEEKDAY_THURSDAY;
						if ($rp_newvobj->day6 == 't')
							$ant_obj->recurrencePattern->dayOfWeekMask = $ant_obj->recurrencePattern->dayOfWeekMask | WEEKDAY_FRIDAY;
						if ($rp_newvobj->day7 == 't')
							$ant_obj->recurrencePattern->dayOfWeekMask = $ant_obj->recurrencePattern->dayOfWeekMask | WEEKDAY_SATURDAY;
							
						
					}
				}
				
				$retval = $ant_obj->save();
				

				if (!$retval) // Insufficient permissions
					$retval = -2;
			}
		}
		else
		{
			$retval = -1;
		}
		break;
		
	// ---------------------------------------------------------
	// Delete Object
	// ---------------------------------------------------------
	case "delete_object":
		if ($_POST['obj_type'] && $_POST['oid'])		// Update specific event
		{
			$ant_obj = new CAntObject($dbh, $_POST['obj_type'], $_POST['oid'], $USER);
			if ($ant_obj->remove())
			{
				$retval = "1";
			}
			else
			{
				$retval = "-1";
			}
		}
		else
		{
			$retval = -1;
		}
		break;

	// ---------------------------------------------------------
	// Save Form
	// ---------------------------------------------------------
	case "save_form":
		$obj_type = $_POST['obj_type'];
		$otid = objGetAttribFromName($dbh, $obj_type, "id");
		if ($obj_type)
		{
			$scope = "";
			$default = $_POST['default'];
			$mobile = $_POST['mobile'];
			$team_id = $_POST['team_id'];
			$user_id = $_POST['user_id'];
			
			if($default != null)
			{
				$scope = "default";
				if(!$dbh->GetNumberRows($dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='default'")))
				{
					$dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, form_layout_xml) values
									('$otid', '$scope', '".$dbh->Escape($_POST['form_layout_xml'])."');");	
				}
				else
				{
					$dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$dbh->Escape($_POST['form_layout_xml'])."' 
									where type_id='$otid' and scope='default'");
				}
			}
			if($mobile != null)
			{
				$scope = "mobile";
				if(!$dbh->GetNumberRows($dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='mobile'")))
				{
					$dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, form_layout_xml) values
									('$otid', '$scope', '".$dbh->Escape($_POST['form_layout_xml'])."');");	
				}
				else
				{
					$dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$dbh->Escape($_POST['form_layout_xml'])."' 
									where type_id='$otid' and scope='mobile'");
				}
			}
			if($team_id != null)
			{
				$scope = "team";
				if(!$dbh->GetNumberRows($dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='team' and team_id='$team_id'")))
				{
					$dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, team_id, form_layout_xml) values
									('$otid', '$scope', '$team_id', '".$dbh->Escape($_POST['form_layout_xml'])."');");	
				}
				else
				{
					$dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$dbh->Escape($_POST['form_layout_xml'])."' 
									where type_id='$otid' and scope='team' and team_id='$team_id'");
				}
			}
			if($user_id != null)
			{
				$scope = "user";
				if(!$dbh->GetNumberRows($dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='user' and user_id='$user_id'")))
				{
					$dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, user_id, form_layout_xml) values
									('$otid', '$scope', '$user_id', '".$dbh->Escape($_POST['form_layout_xml'])."');");	
				}
				else
				{
					$dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$dbh->Escape($_POST['form_layout_xml'])."' 
									where type_id='$otid' and scope='user' and user_id='$user_id'");
				}
			}
			$retval = 1;
		}
		else
			$retval = "-1";
		break;
		
	// ---------------------------------------------------------
	// Delete Form
	// ---------------------------------------------------------
	case "delete_form":
		$obj_type = $_POST['obj_type'];
		$otid = objGetAttribFromName($dbh, $obj_type, "id");
		if ($obj_type)
		{
			$default = $_POST['default'];
			$mobile = $_POST['mobile'];
			$team_id = $_POST['team_id'];
			$user_id = $_POST['user_id'];
			
			if($default != null)
				$dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='default'");
			
			if($mobile != null)
				$dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='mobile'");
			
			if($team_id != null)
				$dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='team' and team_id='$team_id'");
			
			if($user_id != null)
				$dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='user' and user_id='$user_id'");
			
			$retval = 1;
		}
		else
			$retval = "-1";
		break;
		
	// ---------------------------------------------------------
	// Load Form
	// ---------------------------------------------------------
	case "load_form":
		$retval = null;
		$obj_type = $_REQUEST['obj_type'];
		$otid = objGetAttribFromName($dbh, $obj_type, "id");
		
		echo "<response>";
		if ($obj_type)
		{
			$default = $_REQUEST['default'];
			$mobile = $_REQUEST['mobile'];
			$team_id = $_REQUEST['team_id'];
			$user_id = $_REQUEST['user_id'];
			$result = $dbh->Query("select type_id, scope, team_id, user_id, form_layout_xml from app_object_type_frm_layouts order by id");
			$num = $dbh->GetNumberRows($result);
		
			if($default == null && $mobile == null && $team_id == null && $user_id == null)
			{
				// default static form
				$obj = new CAntObjectFields($dbh, $obj_type);
				echo "<form>" . $obj->default_form_xml . "</form>";
				echo "<form_layout_text>" . rawurlencode($obj->default_form_xml) . "</form_layout_text>";
			}
			if($default == null && $mobile == 0 && $team_id == null && $user_id == null)
			{
				// default static mobile form
				$obj = new CAntObjectFields($dbh, $obj_type);
				echo "<form>" . $obj->default_form_mobile_xml . "</form>";
				echo "<form_layout_text>" . rawurlencode($obj->default_form_mobile_xml) . "</form_layout_text>";
			}
		
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				if($default != null)
				{
					if($otid == $row['type_id'] && "default" == $row['scope'])
					{
						echo "<form>" . $row['form_layout_xml'] . "</form>";
						echo "<form_layout_text>" . rawurlencode($row['form_layout_xml']) . "</form_layout_text>";
					}
				}
				if($mobile != null && $mobile != 0)
				{
					if($otid == $row['type_id'] && "mobile" == $row['scope'])
					{
						echo "<form>" . $row['form_layout_xml'] . "</form>";
						echo "<form_layout_text>" . rawurlencode($row['form_layout_xml']) . "</form_layout_text>";
					}
				}
				if($team_id != null)
				{
					if($otid == $row['type_id'] && "team" == $row['scope'] && $team_id == $row['team_id'])
					{
						echo "<form>" . $row['form_layout_xml'] . "</form>";
						echo "<form_layout_text>" . rawurlencode($row['form_layout_xml']) . "</form_layout_text>";
					}
				}
				if($user_id != null)
				{
					if($otid == $row['type_id'] && "user" == $row['scope'] && $user_id == $row['user_id'])
					{
						echo "<form>" . $row['form_layout_xml'] . "</form>";
						echo "<form_layout_text>" . rawurlencode($row['form_layout_xml']) . "</form_layout_text>";
					}
				}
			}
		}
		echo "<message>" . rawurlencode($message) . "</message>";
		echo "<cb_function>" . $_GET['cb_function'] . "</cb_function>";
		echo "</response>";
		break;
		
	// ---------------------------------------------------------
	// Get Forms
	// ---------------------------------------------------------
	case "get_forms":
		$retval = "";
		$obj_type = $_REQUEST['obj_type'];
		$otid = objGetAttribFromName($dbh, $obj_type, "id");
		
		if($obj_type)
		{
			$result = $dbh->Query("select type_id, scope, user_id, team_id from app_object_type_frm_layouts order by id");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
					
				// only return forms with matching type_id
				if($otid == $row['type_id'])
				{
					if ($retval) 
						$retval .= ", ";
				
					$retval .= "[\"".$row['type_id']."\", \"".$row['scope']."\", \"".$row['team_id']."\", \"".UserGetTeamName($dbh, $row['team_id'])."\", \"".$row['user_id']."\"]";
				}
			}
			$dbh->FreeResults($result);
			$retval = "[".$retval."]";
		}
		else
			$retval = "-1";
		break;
	
	// ---------------------------------------------------------
	// Undelete Object
	// ---------------------------------------------------------
	case "undelete_object":
		if ($_POST['obj_type'] && $_POST['oid'])		// Update specific event
		{
			$ant_obj = new CAntObject($dbh, $_POST['obj_type'], $_POST['oid'], $USER);
			$ant_obj->setValue("f_deleted", "f");
			$ant_obj->save();
			$retval = $_POST['oid'];
		}
		else
		{
			$retval = -1;
		}
		break;
		
	// ---------------------------------------------------------
	// Delete Objects
	// ---------------------------------------------------------
	case "delete_objects":
		if ($_POST['obj_type'] && is_array($_POST['objects']) || $_POST['all_selected'])		// Update specific event
		{
			$olist = new CAntObjectList($dbh, $_POST['obj_type'], $USER);
			$olist->processFormConditions($_POST);
			$olist->getObjects();
			$num = $olist->getNumObjects();
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $olist->getObject($i);
				if ($obj->remove())
				{
					if ($retval) $retval .= ",";
					$retval .= $obj->id;
				}
				$olist->unsetObject($i);
			}
			$retval = "[".$retval."]";
		}
		else
		{
			$retval = -1;
		}
		break;

	// ---------------------------------------------------------
	// Mass-edit objects
	// ---------------------------------------------------------
	case "edit_objects":
		if ($_POST['obj_type'] && (is_array($_POST['objects']) || $_POST['all_selected']))		// Update specific event
		{
			$olist = new CAntObjectList($dbh, $_POST['obj_type'], $USER);
			$olist->processFormConditions($_POST);
			$olist->getObjects();
			$num = $olist->getNumObjects();
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $olist->getObject($i);
				$field = $obj->fields->getField($_POST['field_name']);

				if ($field)
				{
					if ($field['type'] == "fkey_multi")
					{
						if ($_POST['action'] == 'remove')
						{
							$obj->removeMValue($_POST['field_name'], $_POST['value']);
						}
						else // add
						{
							$obj->setMValue($_POST['field_name'], $_POST['value']);
						}
					}
					else
					{
						$val = $_POST['value'];
						$all_fields = $obj->fields->getFields();
						foreach ($all_fields as $fname=>$fdef)
						{
							if ($fdef['type'] != "object_multi" && $fdef['type'] != "fkey_multi")
							{
								if ($val == "<%".$fname."%>")
									$val = $obj->getValue($fname);
							}
						}

						$obj->setValue($_POST['field_name'], $val);
					}

					$obj->save();
				}

				$olist->unsetObject($i);
			}
			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;

	// ---------------------------------------------------------
	// Get all Objects
	// ---------------------------------------------------------
	case "get_objects":
		$retval = "";
		$result = $dbh->Query("select name, title, object_table, f_system from app_object_types order by title");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			$objdef = new CAntObject($dbh, $row['name']);
			
			if ($retval) $retval .= ", ";

			if ($_REQUEST['json'])
			{
				$obj = new stdClass();
				$obj->name = $row['name'];
				$obj->title = $row['title'];
				$obj->object_table = $row['object_table'];
				$obj->fullTitle = $objdef->fullTitle;
				$obj->listTitle = $row['listTitle'];
				$obj->fSystem = ($row['f_system'] == 't') ? true : false;
				$retval .= json_encode($obj);
			}
			else
				$retval .= "[\"".$row['name']."\", \"".$row['title']."\", \"".$row['object_table']."\", \"".$objdef->fullTitle."\", \"".$objdef->fields->listTitle."\"]";
		}
		$dbh->FreeResults($result);
		$retval = "[".$retval."]";
		break;


	// ---------------------------------------------------------
	// Get plugins
	// ---------------------------------------------------------
	case "get_plugins":

		// Load all plugins in the objects/ofplugins/[object_type] directory
		$path = "ofplugins/".$_REQUEST['obj_type'];
		if (file_exists($path))
		{
			$retval = "[";
			$dir_handle = opendir($path);
			if ($dir_handle)
			{
				$plbuf = "";
				while($file = readdir($dir_handle))
				{
					if(!is_dir($path."/".$file) && $file != '.' && $file != '..' && substr($file, -3)==".js")
					{
						if ($plbuf) $plbuf .= ",";
						$plbuf .= "\"".rawurlencode(file_get_contents($path."/".$file))."\"";

					}
				}
				closedir($dir_handle);
				$retval .= $plbuf;
			}
			$retval .= "]";
		}

		if (!$retval)
			$retval = "[]";

		/*
		switch ($_REQUEST['obj_type'])
		{
		case 'customer':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../customer/publish.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../customer/relationships.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../customer/followup.js"))."\"";
			$retval .= "]";
			break;
		case 'opportunity':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../customer/followup.js"))."\"";
			$retval .= "]";
			break;
		case 'lead':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../customer/lead_convert.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../customer/followup.js"))."\"";
			$retval .= "]";
			break;
		case 'case':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../project/case_att.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../project/case_taskowner.js"))."\"";
			$retval .= "]";
			break;
		case 'discussion':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../objects/discussion_notify.js"))."\"";
			$retval .= "]";
			break;
		case 'task':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../project/task_logtime.js"))."\"";
			$retval .= "]";
			break;
		case 'project':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../project/project_members.js"))."\",";
			//$retval .= "\"".rawurlencode(file_get_contents("../project/project_templated.js"))."\"";
			$retval .= "\"".rawurlencode(file_get_contents("../project/project_onsave.js"))."\"";
			$retval .= "]";
			break;
		case 'contact_personal':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../contacts/customer_link.js"))."\"";
			$retval .= "]";
			break;
		case 'content_feed':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../content/display_link.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../content/feed_fields.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../content/feed_categories.js"))."\"";
			$retval .= "]";
			break;
		case 'content_feed_post':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../content/post_fields.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../content/post_publish.js"))."\"";
			$retval .= "]";
			break;
		case 'invoice':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../sales/invoice_details.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../sales/invoice_checkout.js"))."\"";
			$retval .= "]";
			break;
		case 'sales_order':
			$retval = "[";
			$retval .= "\"".rawurlencode(file_get_contents("../sales/order_details.js"))."\"";
			$retval .= "]";
			break;
		case 'calendar_event':
			$retval = "[";
            $retval .= "\"".rawurlencode(file_get_contents("../calendar/event_timevalidator.js"))."\",";
            $retval .= "\"".rawurlencode(file_get_contents("../calendar/event_cal.js"))."\",";
            $retval .= "\"".rawurlencode(file_get_contents("../calendar/event_presence.js"))."\",";
			$retval .= "\"".rawurlencode(file_get_contents("../calendar/reminder.js"))."\"";
			$retval .= "]";
			break;
		default:
			$retval = "[]";
			break;
		}
		 */

		break;

	// ---------------------------------------------------------
	// Save view
	// ---------------------------------------------------------
	case "save_view":
		if ($_POST['name'] && $_POST['obj_type'])
		{
			$obj = new CAntObject($dbh, $_POST['obj_type'], null, $USER);
			$otid = $obj->object_type_id;
			if ($otid)
			{
				$result = $dbh->Query("insert into app_object_views(name, description, filter_key, user_id, object_type_id, report_id)
										values('".$dbh->Escape($_POST['name'])."', '".$dbh->Escape($_POST['description'])."', 
											   '".$dbh->Escape($_POST['filter_key'])."', '$USERID', '$otid', ".$dbh->EscapeNumber($_POST['report_id']).");
										select currval('app_object_views_id_seq') as id;");
				if ($dbh->GetNumberRows($result))
					$view_id = $dbh->GetValue($result, 0, "id");

				if ($view_id)
				{
					if ($_POST['conditions'] && is_array($_POST['conditions']))
					{
						foreach ($_POST['conditions'] as $id)
						{
							$field = $obj->fields->getField($_POST['condition_fieldname_'.$id]);

							if ($field)
							{
								$dbh->Query("insert into app_object_view_conditions(view_id, field_id, blogic, operator, value)
												values('$view_id', '".$field['id']."', '".$_POST['condition_blogic_'.$id]."', 
													   '".$_POST['condition_operator_'.$id]."', '".$_POST['condition_condvalue_'.$id]."')");
							}
						}
					}

					if ($_POST['sort_order'] && is_array($_POST['sort_order']))
					{
						$sort_order = 1;
						foreach ($_POST['sort_order'] as $id)
						{
							$field = $obj->fields->getField($_POST['sort_order_fieldname_'.$id]);

							if ($field)
							{
								$dbh->Query("insert into app_object_view_orderby(view_id, field_id, order_dir, sort_order)
											 values('$view_id', '".$field['id']."', '".$_POST['sort_order_order_'.$id]."', '$sort_order')");
							}
							$sort_order++;
						}
					}

					if ($_POST['view_fields'] && is_array($_POST['view_fields']))
					{
						$sort_order = 1;
						foreach ($_POST['view_fields'] as $id)
						{
							$field = $obj->fields->getField($_POST['view_field_fieldname_'.$id]);

							if ($field)
							{
								$dbh->Query("insert into app_object_view_fields(view_id, field_id, sort_order)
											 values('$view_id', '".$field['id']."', '$sort_order')");
							}
							$sort_order++;
						}
					}
				}

				if ($_POST['vid'])
				{
					$dbh->Query("delete from app_object_views where id='".$_POST['vid']."'");
				}
			}

			$retval = $view_id;
		}
		else
		{
			$retval = -1;
		}
		break;
	// ---------------------------------------------------------
	// Delete view
	// ---------------------------------------------------------
	case "delete_view":
		if ($_REQUEST['dvid'])
		{
			$dbh->Query("delete from app_object_views where id='".$_REQUEST['dvid']."'");
			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;
	// ---------------------------------------------------------
	// Set Default View
	// ---------------------------------------------------------
	case "set_default_view":
		if ($_REQUEST['view_id'] && $_POST['obj_type'])
		{
			if ($_POST['filter_key'])
				UserSetPref($dbh, $USERID, "/objects/views/default/".$_POST['filter_key']."/".$_POST['obj_type'], $_REQUEST['view_id']);
			else
				UserSetPref($dbh, $USERID, "/objects/views/default/".$_POST['obj_type'], $_REQUEST['view_id']);
			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;
	// ---------------------------------------------------------
	// Merge Objects
	// ---------------------------------------------------------
	case "merge_objects":
		$retval = 1;
		
		if ($_POST['obj_type'] && isset($_POST['objects']) && is_array($_POST['objects']))
		{
			$objs = array();
			// Create array of objects but skip first one
			for ($i = 1; $i < count($_POST['objects']); $i++)
			{
				$objs["o_".$_POST['objects'][$i]] = new CAntObject($dbh, $_POST['obj_type'], $_POST['objects'][$i], $USER);
			}

			// All objects will be merged into thie first = 0
			$ant_obj = new CAntObject($dbh, $_POST['obj_type'], $_POST['objects'][0], $USER);
			$ofields = $ant_obj->fields->getFields();
			foreach ($ofields as $fname=>$field)
			{
				// Only update if field is drawing from another object
				if (isset($_POST['fld_use_'.$fname]) && is_numeric($_POST['fld_use_'.$fname]) && $_POST['fld_use_'.$fname]!=$ant_obj->id)
				{
					$val = $objs["o_".$_POST['fld_use_'.$fname]]->getValue($fname);

					if ($field['type']=='fkey_multi')
					{
						// Purge
						$ant_obj->removeMValues($fname);

						if (is_array($val) && count($val))
						{
							// Add new
							foreach ($val as $ent_val)
								$ant_obj->setMValue($fname, $ent_val);
						}
					}
					else if ($field['type']=='object_multi' || ($field['type']=='object' && !$field['subtype']))
					{
						// This will be updated via associations below
					}
					else
					{
						$ant_obj->setValue($fname, $val);
					}
				}

			}

			// Save changes
			$retval = $ant_obj->save();

			// Now update references to this object
			$result = $dbh->Query("select id, name, object_table from app_object_types 
									where id in (select type_id from app_object_type_fields where type='fkey' 
													and subtype='".$ant_obj->object_table."')");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				$odef = new CAntObject($dbh, $row['name'], null, $USER);
				$ofields = $odef->fields->getFields();

				// Loop though the merged objects - skip first object of course - to search for references
				// If this funciton was run a lot, it might be better to add multiple conditions with the or operator
				foreach ($objs as $chkobj)
				{
					foreach ($ofields as $fname=>$field)
					{
						if ($field['type'] == 'fkey' && $field['subtype']==$ant_obj->object_table)
						{
							$olist = new CAntObjectList($dbh, $row['name'], $USER);
							$olist->addCondition("and", $fname, "is_equal", $chkobj->id);
							$olist->getObjects();
							$num2 = $olist->getNumObjects();
							for ($m = 0; $m < $num2; $m++)
							{
								$refobj = $olist->getObject($m);
								$refobj->setValue($fname, $ant_obj->id);
								$refobj->save();
							}
						}
					}
				}
			}
			$dbh->FreeResults($result);

			// Set object reference code
			foreach ($objs as $chkobj)
			{
				if ($ant_obj->object_type_id && $chkobj->id)
				{
					// Move everything objects reference
					$result = $dbh->Query("update object_associations set object_id='".$ant_obj->id."' where 
												type_id='".$ant_obj->object_type_id."' and object_id='".$chkobj->id."'");
					// Move everything that references objects
					$result = $dbh->Query("update object_associations set assoc_object_id='".$ant_obj->id."' where 
												assoc_type_id='".$ant_obj->object_type_id."' and assoc_object_id='".$chkobj->id."'");
				}
			}

			// Delete all but the first object
			foreach ($objs as $chkobj)
			{
				$chkobj->remove();
			}

			// return value of main object
			$retval = $ant_obj->id;
		}
		else
		{
			$retval = -1;
		}

		/*
		for (var i = 0; i < this.objects.length; i++)
		{
			args[args.length] = ["objects[]", this.objects[i].id];
		}

		// Send list of fields and which object id they will be pulled from
		for (var i = 0; i < this.fields.length; i++)
		{
			args[args.length] = ["fld_use_"+this.fields[i].name, this.fields[i].object_id];
		}
		 */

		break;
	// ---------------------------------------------------------
	// Get fkey val name
	// ---------------------------------------------------------
	case "get_fkey_val_name":
		if ($_POST['obj_type'] && $_POST['field'] && is_numeric($_POST['id']))
		{
			$obj = new CAntObject($dbh, $_POST['obj_type'], null, $USER);
			$field = $obj->fields->getField($_POST['field']);

			if ($field['type'] == "object" && $field['subtype'])
			{
				$obj = new CAntObject($dbh, $field['subtype'], $_POST['id'], $USER);
				$retval = $obj->getName();

				if (!$retval)
					$retval = -1;
			}
			else if (($field['type'] == "fkey" || $field['type'] == "fkey_multi") && is_array($field['fkey_table']) 
						&& $field['subtype'] != "user_file_categories" && $field['subtype'] != "user_files"
						&& $field['subtype'] != "customers" && $field['subtype'] != "customer")
			{
				$query = "select ".$field['fkey_table']['key']." as key";
				if ($field['fkey_table']['title'])
					$query .= ", ".$field['fkey_table']['title']." as title";
				if ($field['fkey_table']['parent'])
					$query .= ", ".$field['fkey_table']['parent']." as parent";
				$query .= " from ".$field['subtype'];
				$query .= " where ".$field['fkey_table']['key']."='".$_POST['id']."'";
				$result = $dbh->Query($query);
				if ($dbh->GetNumberRows($result))
				{
					$row = $dbh->GetRow($result, $i);
					$retval = $row['title'];
				}
			}
		}

		if (!$retval)
		{
			$retval = -1;
		}
		break;

	// ---------------------------------------------------------
	// Get fkey default - get the default or first value of fkey/obj
	// ---------------------------------------------------------
	case "get_fkey_default":
		if ($_POST['obj_type'] && $_POST['field'])
		{
			$obj = new CAntObject($dbh, $_POST['obj_type'], null, $USER);
			$field = $obj->fields->getField($_POST['field']);
			if (($field['type'] == "object" && $field['subtype']=="user") || (($_POST['field']=="user_id" || $_POST['field']=="owner_id") && $field['type'] == "fkey"))
			{
				// Set current user
				$retval = "{id:\"$USERID\", name:\"$USERNAME\"}";
			}
			else if ($field['type'] == "object" && $field['subtype'])
			{
				$olist = new CAntObjectList($dbh, $field['subtype'], $USER);
				$olist->processFormConditions($_POST);
				$olist->getObjects(0, 1); // only get the top result - offset 0 limit 1
				$num = $olist->getNumObjects();
				if ($num)
				{
					$obj = $olist->getObject(0);	
					$retval = "{id:\"".$obj->id."\", name:\"".$obj->getName()."\"}";
				}
			}
			else if (($field['type'] == "fkey" || $field['type'] == "fkey_multi") && is_array($field['fkey_table']) 
						&& $field['subtype'] != "user_file_categories" && $field['subtype'] != "user_files"
						&& $field['subtype'] != "customers" && $field['subtype'] != "customer")
			{
				$query = "select ".$field['fkey_table']['key']." as key";
				if ($field['fkey_table']['title'])
					$query .= ", ".$field['fkey_table']['title']." as title";
				if ($field['fkey_table']['parent'])
					$query .= ", ".$field['fkey_table']['parent']." as parent";
				$query .= " from ".$field['subtype'];
				$result = $dbh->Query($query);
				if ($dbh->GetNumberRows($result))
				{
					$row = $dbh->GetRow($result, $i);
					$retval = "{id:\"".$row['key']."\", name:\"".$row['title']."\"}";
				}
			}
		}

		if (!$retval)
		{
			$retval = -1;
		}
		break;

	// ---------------------------------------------------------
	// Get object name
	// ---------------------------------------------------------
	case "get_obj_name":
		if ($_POST['obj_type'] && $_POST['id'])
		{
			$obj = new CAntObject($dbh, $_POST['obj_type'], $_POST['id'], $USER);
			$retval = $obj->getName();

			if (!$retval)
				$retval = -1;
		}

		if (!$retval)
		{
			$retval = -1;
		}
		break;


	// ---------------------------------------------------------
	// Add association
	// ---------------------------------------------------------
	case "association_add":
		if ($_POST['obj_type'] && $_POST['obj_id'] && $_POST['assoc_obj_type'] && $_POST['assoc_obj_id'])
		{
			$obj = new CAntObject($dbh, $_POST['obj_type'], $_POST['obj_id'], $USER);
			$retval = $obj->addAssociation($_POST['assoc_obj_type'], $_POST['assoc_obj_id']);
		}

		if (!$retval)
		{
			$retval = -1;
		}
		break;

	// ---------------------------------------------------------
	// Get referenced folder and create if auto-create is set
	// ---------------------------------------------------------
	case "get_folder_id":
		if ($_REQUEST['field'] && $_REQUEST['obj_type'] && $_REQUEST['oid'])
		{
			$obj = new CAntObject($dbh, $_REQUEST['obj_type'], $_REQUEST['oid'], $USER);
			$field = $obj->fields->getField($_REQUEST['field']);
			$folder_id = $obj->getValue($_REQUEST['field']);

			if (!$folder_id && is_array($field["fkey_table"]) && $field["fkey_table"]["autocreate"] 
				&& $field["fkey_table"]["autocreatebase"] && $field["fkey_table"]["autocreatename"])
			{
				$antfs = new CAntFs($dbh, $USER);
				$path = $field["fkey_table"]["autocreatebase"]."/".$obj->getValue($field["fkey_table"]["autocreatename"]);
				$folder = $antfs->openFolder($path, true);
				if ($folder)
					$folder_id = $folder->id;
			}
			
			if ($folder_id)
				$retval = $folder_id;
			else
				$retval = -1;
		}
		else
		{
			$retval = -1;
		}
		break;

	/*************************************************************************
	*	Function:	cust_opp_get_name
	*
	*	Purpose:	Get the display name of a customer
	**************************************************************************/
	case "get_activity_types":
		$retval = "[";
		$result = $dbh->Query("select id, name, obj_type from activity_types order by name");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			if ($i) $retval .= ", ";
			$retval .= "{id:\"".$row['id']."\", name:\"".$row['name']."\", obj_type:\"".$row['obj_type']."\"}";
		}
		$retval .= "]";
		break;

	/*************************************************************************
	*	Function:	save_activity_type
	*
	*	Purpose:	Save activity type
	**************************************************************************/
	case "save_activity_type":
		if ($_REQUEST['name'])
		{
			if ($_REQUEST['id'])
			{
				$dbh->Query("update activity_types set name='".$dbh->Escape($_POST['name'])."' where id='".$_REQUEST['id']."'");
				$retval = $_REQUEST['id'];
			}
			else
			{
				$result = $dbh->Query("insert into activity_types(name) values('".$dbh->Escape($_POST['name'])."'); 
										 select currval('activity_types_id_seq') as id;");
				if ($dbh->GetNumberRows($result))
					$retval = $dbh->GetValue($result, 0, "id");
			}

		}

		if (!$retval)
		{
			$retval = -1;
		}
		break;

	/*************************************************************************
	*	Function:	comment_notify
	*
	*	Purpose:	Notify users when a comment is posted
	**************************************************************************/
	case "comment_notify":
		if (is_array($_POST['notify']))
		{
			foreach ($_POST['notify'] as $sendto)
			{
				$eml = "";
				$full_name = "";
				$link = "";
				$referenced_obj = null;

				if ($_POST['obj_reference'])
					$referenced_obj = objSplitValue($_POST['obj_reference']);

				if (!$sendto)
					continue;

				if (strpos($sendto, ":") === false) // plain
				{
					if (strpos($sendto, "@") !== false) // email
					{
						$eml = $sendto;
						$full_name = $sendto;
					}
				}
				else
				{
					$obj_parts = objSplitValue($sendto);
					if ($obj_parts)
					{
						switch ($obj_parts[0])
						{
						case 'user':
							$eml = UserGetEmail($dbh, $obj_parts[1]);
							$full_name = UserGetFullName($dbh, $obj_parts[1]);
							if ($referenced_obj)
								$link = "https://".AntConfig::getInstance()->localhost."/obj/".$referenced_obj[0]."/".$referenced_obj[1];
							break;
						case 'customer':
							$eml = CustGetDefaultEmail($dbh, $obj_parts[1]);
							$full_name = CustGetName($dbh, $obj_parts[1]);

							if ($referenced_obj)
							{
								switch($referenced_obj[0])
								{
								case 'case':
									$link = "https://".AntConfig::getInstance()->localhost."/project/pubcase/".$obj_parts[1]."/".$referenced_obj[1];
									break;
								}
							}
							break;
						default:
							// Disallow any other types for now
							break;
						}
					}
				}

				if ($eml)
				{
					$com = new CAntObject($dbh, "comment", $_POST['cid']);
					$notified = $com->getValue("notified");
					$notified .= ($notified) ? ", ".$full_name : $full_name;
					$com->setValue("notified", $notified);
					$com->save(false);

					// Create new email object
					$headers["X-ANT-ACCOUNT-NAME"] =  $ANT->detectAccount();
					/*
					$ref = "comment:".$_POST['cid'];
					if ($_POST['obj_reference'])
						$ref .= ",".$_POST['obj_reference'];
					$headers["X-ANT-ASSOCIATIONS"] = $ref;
					 */
					if ($_POST['obj_reference'])
						$headers["X-ANT-ASSOCIATIONS"] = $_POST['obj_reference'];
					$headers['From'] = AntConfig::getInstance()->email['noreply'];
					$headers['To'] = $eml;
					$headers['Subject'] = "Added Comment";
					$body = "By: $USERNAME\r\n-----------------------------------------------------\r\n\r\n";
					$body .= $com->getValue("comment");
					if ($link)
						$body .= "\r\n\r\nClick below for more details:\r\n$link";

					$email = new Email();
					$status = $email->send($headers['To'], $headers, $body);
					unset($email);
				}
			}
		}
		$retval = 1;
		break;

	/*************************************************************************
	*	Function:	discussion_notify
	*
	*	Purpose:	Notify users when a new discussion is created
	**************************************************************************/
	case "discussion_notify":
		if (is_array($_POST['notify']))
		{
			foreach ($_POST['notify'] as $sendto)
			{
				$eml = "";

				if (strpos($sendto, ":") === false) // plain
				{
					if (strpos($sendto, "@") !== false) // email
					{
						$eml = $sendto;
						$full_name = $sendto;
					}
				}
				else
				{
					$obj_parts = objSplitValue($sendto);
					if ($obj_parts)
					{
						switch ($obj_parts[0])
						{
						case 'user':
							$eml = UserGetEmail($dbh, $obj_parts[1]);
							$full_name = UserGetFullName($dbh, $obj_parts[1]);
							if ($referenced_obj)
								$link = "https://".AntConfig::getInstance()->localhost."/obj/".$referenced_obj[0]."/".$referenced_obj[1];
							break;
						case 'customer':
							$eml = CustGetDefaultEmail($dbh, $obj_parts[1]);
							$full_name = CustGetName($dbh, $obj_parts[1]);

							if ($referenced_obj)
							{
								switch($referenced_obj[0])
								{
								case 'case':
									$link = "https://".AntConfig::getInstance()->localhost."/project/pubcase/".$obj_parts[1]."/".$referenced_obj[1];
									break;
								}
							}
							break;
						default:
							// Disallow any other types for now
							break;
						}
					}
				}

				if ($eml)
				{
					//$eml = UserGetEmail($dbh, $uid);
					//$full_name = UserGetFullName($dbh, $uid);
					$com = new CAntObject($dbh, "discussion", $_POST['did']);
					$notified = $com->getValue("notified");
					$notified .= ($notified) ? ", ".$full_name : $full_name;
					$com->setValue("notified", $notified);
					$com->save(false);

					// Create new email object
					$headers["X-ANT-ACCOUNT-NAME"] =  $ANT->detectAccount();
					/*
					$ref = "comment:".$_POST['cid'];
					if ($_POST['obj_reference'])
						$ref .= ",".$_POST['obj_reference'];
					$headers["X-ANT-ASSOCIATIONS"] = $ref;
					 */
					$headers["X-ANT-ASSOCIATIONS"] = "discussion:".$_POST['did'];
					if ($_POST['obj_reference'])
						$headers["X-ANT-ASSOCIATIONS"] .= ",".$_POST['obj_reference'];
					$headers['From'] = AntConfig::getInstance()->email['noreply'];
					$headers['To'] = $eml;
					$headers['Subject'] = "New Discussion Started";
					$body = "By: $USERNAME\r\n-----------------------------------------------------\r\n\r\n";
					$body .= $com->getValue("message");
					$body .= "\r\n\r\nNote: If you do not see a link above for this discussion, copy and paste the following into your browser:\r\n";
					$body .= "https://".AntConfig::getInstance()->localhost."/obj/discussion/".$_POST['did'];

					$email = new Email();
					$status = $email->send($headers['To'], $headers, $body);
					unset($email);
				}
			}
		}
		$retval = 1;
		break;
		
	/*************************************************************************
	*	Function:	save_recurrencepattern
	*
	*	Purpose:	Notify users when a new discussion is created
	**************************************************************************/
	case "save_recurrencepattern":
	
		$rp_newvobj =  json_decode(stripslashes($_POST['objpt_json']));
		
		if( is_object($rp_newvobj) )
		{
			if( $rp_newvobj->parentId<1 )
			{  
				$retval = -1;
			}
			else
			{ 
				$ant_obj = new CAntObject($dbh, $rp_newvobj->object_type, $rp_newvobj->parentId, $USER); 
			}		

			$rp = new CRecurrencePattern($dbh);

			$rp->id = $rp_newvobj->id;
			$rp->object_type_id = $rp_newvobj->object_type_id; 
			$rp->object_type = $rp_newvobj->object_type; 
			$rp->dateProcessedTo = $rp_newvobj->dateProcessedTo; 
			$rp->parentId = $rp_newvobj->parentId; 
			$rp->type = $rp_newvobj->type; 
			$rp->interval = $rp_newvobj->interval;
			$rp->dateStart = $rp_newvobj->dateStart; 
			$rp->dateEnd = $rp_newvobj->dateEnd; 
			$rp->timeStart = $rp_newvobj->timeStart; 
			$rp->timeEnd = $rp_newvobj->timeEnd; 
			$rp->fAllDay = $rp_newvobj->fAllDay; 
			$rp->dayOfMonth = $rp_newvobj->dayOfMonth; 
			$rp->monthOfYear = $rp_newvobj->monthOfYear; 
			$rp->fActive = $rp_newvobj->fActive;
			$rp->instance = $rp_newvobj->insdatetance;
			
			if ($rp_newvobj->day1 == 't')
				$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SUNDAY;
			if ($rp_newvobj->day2 == 't')
				$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_MONDAY;
			if ($rp_newvobj->day3 == 't')
				$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_TUESDAY;
			if ($rp_newvobj->day4 == 't')
				$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY;
			if ($rp_newvobj->day5 == 't')
				$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_THURSDAY;
			if ($rp_newvobj->day6 == 't')
				$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_FRIDAY;
			if ($rp_newvobj->day7 == 't')
				$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SATURDAY;
			
			$retval = $rp->save();

			// Save recurrence id to object
			if ($rp->id && $ant_obj && !$ant_obj->getValue($ant_obj->fields->recurRules['field_recur_id']))
			{
				$ant_obj->setValue($ant_obj->fields->recurRules['field_recur_id'], $rp->id);
				$ant_obj->save(false);
			}
		}
		else
		{
			$retval = -3;
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
