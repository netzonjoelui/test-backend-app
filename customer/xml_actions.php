<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/Email.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("customer_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_REQUEST['function'];

	// Make sure we have default values
	CustSetDefStage($dbh, $ACCOUNT);
	CustSetDefStatus($dbh, $ACCOUNT);
	CustSetDefRelTypes($dbh);
	CustInvSetDefStatus($dbh, $ACCOUNT);

	switch ($FUNCTION)
	{
	/*************************************************************************
	*	Function:	cust_getname
	*
	*	Purpose:	Get the display name of a customer
	**************************************************************************/
	case "cust_get_name":
		if ($_GET['custid'])
			$retval = CustGetName($dbh, $_GET['custid']);
		else
			$retval = -1;
		break;
	/*************************************************************************
	*	Function:	cust_lead_get_name
	*
	*	Purpose:	Get the display name of a customer
	**************************************************************************/
	case "cust_lead_get_name":
		if (is_numeric($_GET['lead_id']))
		{
			$result = $dbh->Query("select first_name, last_name, company from customer_leads where id='".$_GET['lead_id']."'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$retval = stripslashes($row['first_name'])." ".stripslashes($row['last_name']);
			}
			else
				$retval = -1;
		}
		else
			$retval = -1;
		break;
	
	/*************************************************************************
	*	Function:	cust_lead_convert
	*
	*	Purpose:	Convert lead to a customer
	**************************************************************************/
	case "cust_lead_convert":
		if (is_numeric($_POST['lead_id']))
		{
			$lead = new CAntObject($dbh, "lead", $_POST['lead_id'], $USER);
			$cname = $lead->getValue('company');

			// First create account
			if ($cname)
			{
				$cust = new CAntObject($dbh, "customer", null, $USER);
				$cust->setValue("name", $cname);
				$cust->setValue("phone_work", $lead->getValue('phone'));
				$cust->setValue("phone_home", $lead->getValue('phone2'));
				$cust->setValue("phone_cell", $lead->getValue('phone3'));
				$cust->setValue("phone_fax", $lead->getValue('fax'));
				$cust->setValue("job_title", $lead->getValue('job_title'));
				$cust->setValue("website", $lead->getValue('website'));
				$cust->setValue("notes", $lead->getValue('notes'));
				$cust->setValue("email2", $lead->getValue('email'));
				$cust->setValue("email_default", "email2");
				$cust->setValue("owner_id", $USERID);
				$cust->setValue("type_id", CUST_TYPE_ACCOUNT);
				$cust->setValue("business_street", $lead->getValue('street'));
				$cust->setValue("business_street2", $lead->getValue('street2'));
				$cust->setValue("business_city", $lead->getValue('city'));
				$cust->setValue("business_state", $lead->getValue('state'));
				$cust->setValue("business_zip", $lead->getValue('zip'));
				$cust_id = $cust->save();
			}

			// Now create first contact and relate
			$cust2 = new CAntObject($dbh, "customer", null, $USER);
			$cust2->setValue("first_name", $lead->getValue('first_name'));
			$cust2->setValue("last_name", $lead->getValue('last_name'));
			$cust2->setValue("phone_work", $lead->getValue('phone'));
			$cust2->setValue("phone_home", $lead->getValue('phone2'));
			$cust2->setValue("phone_cell", $lead->getValue('phone3'));
			$cust2->setValue("phone_fax", $lead->getValue('fax'));
			$cust2->setValue("job_title", $lead->getValue('job_title'));
			$cust2->setValue("website", $lead->getValue('website'));
			$cust2->setValue("notes", $lead->getValue('notes'));
			$cust2->setValue("email2", $lead->getValue('email'));
			$cust2->setValue("email_default", "email2");
			$cust2->setValue("owner_id", $USERID);
			$cust2->setValue("type_id", CUST_TYPE_CONTACT);
			$cust2->setValue("business_street", $lead->getValue('street'));
			$cust2->setValue("business_street2", $lead->getValue('street2'));
			$cust2->setValue("business_city", $lead->getValue('city'));
			$cust2->setValue("business_state", $lead->getValue('state'));
			$cust2->setValue("business_zip", $lead->getValue('zip'));
			$cust2_id = $cust2->save();

			if ($cust_id && $cust2_id)
				CustAssoc($dbh, $cust_id, $cust2_id, '');

			// Create opportunity
			if ($_POST['f_createopp']=='t')
			{
				if (!$_POST['opportunity_name']) $_POST['opportunity_name'] = "LID: ".$_POST['lead_id'];

				$opp = new CAntObject($dbh, "opportunity", null, $USER);
				$opp->setValue("owner_id", $USERID);
				$opp->setValue("customer_id", $cust2_id);
				$opp->setValue("lead_source_id", $lead->getValue('source_id'));
				$opp->setValue("name", $_POST['opportunity_name']);
				$opp->setValue("notes", $lead->getValue('notes'));
				$opp->setValue("lead_id", $_POST['lead_id']);
				$oid = $opp->save();
			}

			if (!$cust_id)
				$cust_id = $cust2_id;

			$retval = $cust_id;
		}
		else
			$retval = -1;
		break;

	/*************************************************************************
	*	Function:	cust_opp_get_name
	*
	*	Purpose:	Get the display name of a customer
	**************************************************************************/
	case "cust_opp_get_name":
		if (is_numeric($_GET['opportunity_id']))
		{
			$result = $dbh->Query("select name from customer_opportunities where id='".$_GET['opportunity_id']."'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$retval = stripslashes($row['name']);
			}
			else
				$retval = -1;
		}
		else
			$retval = -1;
		break;
	case "get_activity_types":
		$result = $dbh->Query("select id, name from customer_activity_types");
		$num = $dbh->GetNumberRows($result);
		$buf = "";
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			if ($buf) $buf .= ", ";
			$buf .= "[".$row['id'].", \"".$row['name']."\"]"; // Array is read from js via eval
		}
		$dbh->FreeResults($result);
		if ($buf)
			$retval .= "[$buf]";
		else
			$retval = -1;

		break;

	/*************************************************************************
	*	Function:	cust_get_zip_data
	*
	*	Purpose:	Get the display name of a customer
	**************************************************************************/
	case "cust_get_zip_data":
		if ($_GET['zipcode'])
		{
			if (strpos($_GET['zipcode'], "-")!==false)
			{
				$parts = explode("-", $_GET['zipcode']);
				$_GET['zipcode'] = $parts[0];
			}

			if (is_numeric($_GET['zipcode']))
			{
				$result = $dbh->Query("select city, state from app_us_zipcodes where zipcode='".$_GET['zipcode']."'");
				if ($dbh->GetNumberRows($result))
				{
					$row = $dbh->GetNextRow($result, 0);
					$retval = "[\"".stripslashes($row['state'])."\", \"".stripslashes($row['city'])."\"]";
				}
				else
					$retval = -1;
			}
			else
				$retval = -1;

		}
		else
			$retval = -1;
		break;
	/*************************************************************************
	*	Function:	save_publish
	*
	*	Purpose:	Save publish/public data for a customer
	**************************************************************************/
	case "save_publish":
		if ($_REQUEST['customer_id'])
		{
			$result = $dbh->Query("select customer_id from customer_publish where customer_id='".$_REQUEST['customer_id']."'");
			if ($dbh->GetNumberRows($result))
			{
				$query = "update customer_publish set username='".$dbh->Escape($_POST['username'])."', 
							f_files_view='".$_POST['f_files_view']."', 
							f_files_upload='".$_POST['f_files_upload']."', f_files_modify='".$_POST['f_files_modify']."' ";
				if ($_POST['password']!='    ')
					$query .= ", password=md5('".$dbh->Escape($_POST['password'])."') ";
				$query .= " where customer_id='".$_REQUEST['customer_id']."'";
			}
			else
			{
				$query = "insert into customer_publish(username, password, f_files_view, f_files_upload, f_files_modify, customer_id)
							values('".$dbh->Escape($_POST['username'])."', md5('".$dbh->Escape($_POST['password'])."'), '".$_POST['f_files_view']."',
								'".$_POST['f_files_upload']."', '".$_POST['f_files_modify']."', '".$_REQUEST['customer_id']."')";
			}

			$dbh->Query($query);
			$retval = 1;
		}
		else
			$retval =-1;
		break;
	
	/*************************************************************************
	*	Function:	get_publish
	*
	*	Purpose:	Save publish/public data for a customer
	**************************************************************************/
	case "get_publish":
		if ($_REQUEST['customer_id'])
		{
			$result = $dbh->Query("select username, password, f_files_view, f_files_upload, f_files_modify 
									from customer_publish where customer_id='".$_REQUEST['customer_id']."'");

			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetRow($result, 0);
				$retval = "{";
				$retval .= "username:\"".rawurlencode($row['username'])."\",";
			   	$retval .= "f_files_view:\"".(($row['f_files_view']=='t')?true:false)."\",";
			   	$retval .= "f_files_upload:\"".(($row['f_files_upload']=='t')?true:false)."\",";
			   	$retval .= "f_files_modify:\"".(($row['f_files_modify']=='t')?true:false)."\",";

				$antfs = new CAntFs($dbh, $USER);
				$pub_folder = $antfs->openFolder("/Customer Files/".$_REQUEST['customer_id']."/Published", true);
				$folder_id = $pub_folder->id;
				$retval .= "folder_id:\"".$pub_folder->id."\"";
				$retval .= "}";
			}
			else
			{
				$retval = "{";
				$retval .= "username:\"\",";
			   	$retval .= "f_files_view:\"f\",";
			   	$retval .= "f_files_upload:\"f\",";
			   	$retval .= "f_files_modify:\"f\",";

				$antfs = new CAntFs($dbh, $USER);
				$pub_folder = $antfs->openFolder("/Customer Files/".$_REQUEST['customer_id']."/Published", true);
				$folder_id = $pub_folder->id;
				$retval .= "folder_id:\"".$pub_folder->id."\"";
				$retval .= "}";
			}
		}
		else
			$retval = -1;
		break;

	/*************************************************************************
	*	Function:	save_relationships
	*
	*	Purpose:	Save relationships for a customer
	**************************************************************************/
	case "save_relationships":
		if ($_REQUEST['customer_id'])
		{
			if (is_array($_POST['delete']) && count($_POST['delete']))
			{
				for ($i = 0; $i < count($_POST['delete']); $i++)
				{
					$dbh->Query("delete from customer_associations where parent_id='".$_REQUEST['customer_id']."' 
									and customer_id='".$_POST['delete'][$i]."'");
				}
			}

			if (is_array($_POST['relationships']) && count($_POST['relationships']))
			{
				for ($i = 0; $i < count($_POST['relationships']); $i++)
				{
					// First add relationship to this customer
					$query = "select id from customer_associations where parent_id='".$_REQUEST['customer_id']."' 
									and customer_id='".$_POST['relationships'][$i]."'";
					if ($dbh->GetNumberRows($dbh->Query($query)))
					{
						$dbh->Query("update customer_associations set relationship_name='".$dbh->Escape($_POST['r_type_name_'.$_POST['relationships'][$i]])."',
										type_id=".$dbh->EscapeNumber($_POST['r_type_id_'.$_POST['relationships'][$i]])."
										where  parent_id='".$_REQUEST['customer_id']."' and customer_id='".$_POST['relationships'][$i]."'");
					}
					else
					{
						$dbh->Query("insert into customer_associations(customer_id, parent_id, relationship_name, type_id) values
										('".$_POST['relationships'][$i]."', '".$_REQUEST['customer_id']."', 
										 '".$dbh->Escape($_POST['r_type_name_'.$_POST['relationships'][$i]])."', 
										 ".$dbh->EscapeNumber($_POST['r_type_id_'.$_POST['relationships'][$i]]).")");
					}

					// Now make relationship two-way
					$query = "select id from customer_associations where parent_id='".$_POST['relationships'][$i]."' 
									and customer_id='".$_REQUEST['customer_id']."'";
					if (!$dbh->GetNumberRows($dbh->Query($query)))
					{
						$dbh->Query("insert into customer_associations(customer_id, parent_id, relationship_name, type_id) values
										('".$_REQUEST['customer_id']."', '".$_POST['relationships'][$i]."', 
										 '".$dbh->Escape($_POST['r_type_name_'.$_POST['relationships'][$i]])."', NULL)");
					}
				}
			}

			$retval = 1;
		}
		else
			$retval =-1;
		break;
	/*************************************************************************
	*	Function:	get_relationship_types
	*
	*	Purpose:	Get array of relationships types
	**************************************************************************/
	case "get_relationship_types":
		$retval = "[";
		$result = $dbh->Query("select id, name from customer_association_types order by name");
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
	*	Function:	save_relationship_type
	*
	*	Purpose:	Save relationship type
	**************************************************************************/
	case "save_relationship_type":
		if ($_REQUEST['name'])
		{
			if ($_REQUEST['id'])
			{
				$dbh->Query("update customer_association_types set name='".$dbh->Escape($_POST['name'])."' where id='".$_REQUEST['id']."'");
				$retval = $_REQUEST['id'];
			}
			else
			{
				$result = $dbh->Query("insert into customer_association_types(name) values('".$dbh->Escape($_POST['name'])."'); 
										 select currval('customer_association_types_id_seq') as id;");
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
	*	Function:	get_relationships
	*
	*	Purpose:	Save publish/public data for a customer
	**************************************************************************/
	case "get_relationships":
		if ($_REQUEST['customer_id'])
		{
			$retval = "[";
			$query = "select customer_associations.id, customer_associations.customer_id as cid, 
						customer_associations.relationship_name, customer_association_types.name as type_name,
						customer_association_types.id as rtype_id
						from customer_associations left outer join customer_association_types 
						on (customer_association_types.id = customer_associations.type_id)
							where customer_associations.parent_id='".$_REQUEST['customer_id']."'";
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
				$retval .= "{cid:\"".rawurlencode($row['cid'])."\", name:\"".rawurlencode($name)."\"";
				$retval .= ", email:\"".rawurlencode($email)."\", phone:\"".rawurlencode($phone)."\"";
				$retval .= ", title:\"".rawurlencode($title)."\", rtype_id:\"".$row['rtype_id']."\"";
				$retval .= ", rname:\"".rawurlencode($relationship)."\"}";
			}
			$dbh->FreeResults($result);
			$retval .= "]";
		}
		else
			$retval = -1;
		break;

	/*************************************************************************
	*	Function:	create_customer
	*
	*	Purpose:	Create a contact or an account
	**************************************************************************/
	case "create_customer":
		$name = rawurldecode($_POST['name']);
		if ($_POST['type_id'] && $name)
		{
			$obj = new CAntObject($dbh, "customer", null, $USER);
			if ($_POST['type_id'] == CUST_TYPE_CONTACT)
			{
				$obj->setValue("type_id", CUST_TYPE_CONTACT);

				$names = explode(" ", $name);

				if (count($names) >= 2)
				{
					$obj->setValue("name", $names[0]." ".$names[1]);
					$obj->setValue("first_name", $names[0]);
					$obj->setValue("last_name", $names[1]);
				}
				else
				{
					$obj->setValue("name", $names[0]);
					$obj->setValue("first_name", $names[0]);
					$obj->setValue("last_name", "");
				}
			}
			else // Account
			{
				$obj->setValue("type_id", CUST_TYPE_ACCOUNT);
				$vals['name'] = $name;
			}

			$retval = $obj->save();
		}
		else
		{
			$retval = -1;
		}

		break;

	/*************************************************************************
	*	Function:	create_customer
	*
	*	Purpose:	Create a contact or an account
	**************************************************************************/
	case "sync_customers":
		if ($_POST['obj_type'] && (is_array($_POST['objects']) || $_POST['all_selected']))		// Update specific event
		{
			$olist = new CAntObjectList($dbh, $_POST['obj_type'], $USER, $_POST, $_POST["order_by"]);
			$num = $olist->getNumObjects();
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $olist->getObject($i);
				if ($obj->dacl->checkAccess($USER, "Edit", ($USER->id==$obj->getValue("owner_id"))?true:false))
				{
					CustSyncContact($dbh, $USERID, $obj->id, NULL, "create");
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
	*	Function:	activity_save
	*
	*	Purpose:	Enter a new or update an existing activity
	**************************************************************************/
	case "activity_save":
		$aid = $_REQUEST['aid'];
		$name = stripslashes(rawurldecode($_REQUEST['name']));
		$notes = stripslashes(rawurldecode($_REQUEST['notes']));
		// Get the owner
		if (is_numeric($aid))
		{
			$dbh->Query("update customer_activity set name='".$dbh->Escape($name)."', 
							type_id=".db_CheckNumber($_REQUEST['type_id']).", notes='".$dbh->Escape($notes)."',  
							time_entered='".stripslashes(rawurldecode($_REQUEST['date']))." ".stripslashes(rawurldecode($_REQUEST['time']))."',
							public='".(($_REQUEST['f_public']=='t')?'t':'f')."', direction='".$dbh->Escape($_REQUEST['direction'])."'
							where id='$aid'");
			$retval = $aid;
		}
		else
		{
			if ($_REQUEST['customer_id'] || $_REQUEST['lead_id'] || $_REQUEST['opportunity_id'])
			{
				$result = $dbh->Query("insert into customer_activity(name, type_id, notes, customer_id, lead_id, opportunity_id, 
																	 time_entered, public, user_id, user_name, direction) 
										values('".$dbh->Escape($name)."', ".db_CheckNumber($_REQUEST['type_id']).", '".$dbh->Escape($notes)."', 
										".db_CheckNumber($_REQUEST['customer_id']).", ".db_CheckNumber($_REQUEST['lead_id']).", 
										".db_CheckNumber($_REQUEST['opportunity_id']).",
										'".stripslashes(rawurldecode($_REQUEST['date']))." ".stripslashes(rawurldecode($_REQUEST['time']))."',
										'".(($_REQUEST['f_public']=='t')?'t':'f')."', '$USERID', '$USERNAME', '".$dbh->Escape($_REQUEST['direction'])."');
										select currval('customer_activity_id_seq') as id;");
				if ($dbh->GetNumberRows($result))
					$retval = $dbh->GetValue($result, 0, "id");
				else
					$retval = -1;
			}
			else
				$retval = -1;
		}

		// Send notification
		// -------------------------------------------------
		if ($_REQUEST['lead_id'])
			$owner = CustGetOwner($dbh, $_REQUEST['lead_id'], "lead");
		else if ($_REQUEST['opportunity_id'])
			$owner = CustGetOwner($dbh, $_REQUEST['opportunity_id'], "opportunity");
		else if ($_REQUEST['customer_id'])
			$owner = CustGetOwner($dbh, $_REQUEST['customer_id'], "customer");

		if ($owner && $owner != $USERID)
		{
			// Create new email object
			$headers['From'] = $settings_no_reply;
			$body = "By: $USERNAME\r\n";
			$headers['Subject'] = ($aid) ? "Customer Activity Updated by $USERNAME" : "New Customer Activity by $USERNAME";
			if ($_REQUEST['lead_id'] && !$_REQUEST['opportunity_id'])
				$body .= "Lead: ".$_REQUEST['lead_id']." - ".CustLeadGetName($dbh, $_REQUEST['lead_id'])."\r\n";
			if ($_REQUEST['opportunity_id'])
				$body .= "Opportunity: ".$_REQUEST['opportunity_id']." - ".CustOptGetName($dbh, $_REQUEST['opportunity_id'])."\r\n";
			if ($_REQUEST['customer_id'])
				$body .= "Customer: ".$_REQUEST['customer_id']." - ".CustGetName($dbh, $_REQUEST['customer_id'])."\r\n";
			$body .= "Name: ".$name."\r\n";
			$body .= "Notes: ".$notes."\r\n";
			$email = new Email();
			$status = $email->send(UserGetEmail($dbh, $_POST['owner_id']), $headers, $body);
			unset($email);
		}

		// Update last/first contacted fields depending
		// -------------------------------------------------
		$contacted_flag = CustGetActTypeConFlag($dbh, $_REQUEST['type_id']);
		if ($contacted_flag && ($contacted_flag==$_REQUEST['direction'] || $contacted_flag=='a'))
		{
			$time = stripslashes(rawurldecode($_REQUEST['date']))." ".stripslashes(rawurldecode($_REQUEST['time']));
			if ($_REQUEST['customer_id'])
			{
				// Make sure this is the latest event
				$query = "select id from customer_activity where customer_id='".$_REQUEST['customer_id']."' and 
							(type_id is not null or email_id is not null) order by time_entered DESC limit 1";
				if ($retval == $dbh->GetValue($dbh->Query($query), 0, "id"))
				{
					$dbh->Query("update customers set 
									last_contacted='".$time."' 
									where id='".$_REQUEST['customer_id']."'");
				}
				$dbh->Query("update customers set 
					ts_first_contacted='".$time."' 
					where id='".$_REQUEST['customer_id']."' and ts_first_contacted is null");
			}

			if ($_REQUEST['lead_id'])
			{
				// Make sure this is the latest event
				$query = "select id from customer_activity where lead_id='".$_REQUEST['lead_id']."' and 
							(type_id is not null or email_id is not null) order by time_entered DESC limit 1";
				if ($retval == $dbh->GetValue($dbh->Query($query), 0, "id"))
				{
					$dbh->Query("update customer_leads set 
						ts_last_contacted='".$time."' 
						where id='".$_REQUEST['lead_id']."'");
				}
				$dbh->Query("update customer_leads set 
					ts_first_contacted='".$time."' 
					where id='".$_REQUEST['lead_id']."' and ts_first_contacted is null");
			}
		}
		break;
	case "group_set_color":
		$gid = $_REQUEST['gid'];
		$color = $_REQUEST['color'];

		if ($gid && $color)
		{
			$dbh->Query("update customer_labels set color='$color' where id='$gid'");
			$retval = $color;
		}
		break;
	case "group_rename":
		$gid = $_REQUEST['gid'];
		$name = rawurldecode($_REQUEST['name']);

		if ($gid && $name)
		{
			$dbh->Query("update customer_labels set name='".$dbh->Escape($name)."' where id='$gid'");
			$retval = $name;
		}
		break;
	case "group_delete":
		$gid = $_REQUEST['gid'];

		if ($gid)
		{
			$dbh->Query("delete from customer_labels where id='$gid'");
			$retval = $gid;
		}
		break;
	case "group_add":
		$pgid = ($_REQUEST['pgid'] && $_REQUEST['pgid'] != "null") ? "'".$_REQUEST['pgid']."'" : "NULL";
		$name = rawurldecode($_REQUEST['name']);
		$color = rawurldecode($_REQUEST['color']);

		if ($name && $color)
		{
			$query = "insert into customer_labels(parent_id, name, color) 
					  values($pgid, '".$dbh->Escape($name)."', '".$dbh->Escape($color)."');
					  select currval('customer_labels_id_seq') as id;";
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
	*	Function:	set_lead_converted
	*
	*	Purpose:	set lead status converted flag
	**************************************************************************/
	case "set_lead_converted":
		if ($_REQUEST['id'])
		{
			// TODO: veryify user has access to modify customer settings - typically only admins
			$dbh->Query("update customer_lead_status set f_closed='t', f_converted='t' where id='".$_REQUEST['id']."'");
			$dbh->Query("update customer_lead_status set f_converted='f' where id!='".$_REQUEST['id']."'");

			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;

	/*************************************************************************
	*	Function:	set_lead_closed
	*
	*	Purpose:	set lead status closed flag
	**************************************************************************/
	case "set_lead_closed":
		if ($_REQUEST['id'] && $_REQUEST['f_closed'])
		{
			// TODO: veryify user has access to modify customer settings - typically only admins
			$dbh->Query("update customer_lead_status set f_closed='".$dbh->Escape($_POST['f_closed'])."' where id='".$_REQUEST['id']."'");

			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;

	/*************************************************************************
	*	Function:	set_opp_closed
	*
	*	Purpose:	get stage and code flags
	**************************************************************************/
	case "set_opp_converted":
		if ($_REQUEST['id'] && $_REQUEST['f_won'])
		{
			// TODO: veryify user has access to modify customer settings - typically only admins
			$dbh->Query("update customer_opportunity_stages set f_closed='t', f_won='".$dbh->Escape($_POST['f_won'])."' where id='".$_REQUEST['id']."'");

			if ($_POST['f_won'] == 't')
				$dbh->Query("update customer_opportunity_stages set f_won='f' where id!='".$_REQUEST['id']."'");

			$retval = 1;
		}
		else
		{
			$retval = -1;
		}
		break;

	/*************************************************************************
	*	Function:	set_opp_closed
	*
	*	Purpose:	get stage and code flags
	**************************************************************************/
	case "set_opp_closed":
		if ($_REQUEST['id'] && $_REQUEST['f_closed'])
		{
			// TODO: veryify user has access to modify customer settings - typically only admins
			$dbh->Query("update customer_opportunity_stages set f_closed='".$dbh->Escape($_POST['f_closed'])."' where id='".$_REQUEST['id']."'");

			$retval = 1;
		}
		else
		{
			$retval = -1;
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
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
	echo "<response>";
	echo "<retval>" . rawurlencode($retval) . "</retval>";
	echo "<cb_function>".$_GET['cb_function']."</cb_function>";
	echo "</response>";
?>
