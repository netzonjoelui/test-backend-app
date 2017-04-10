<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php"); 
	require_once("ant_user.php");
	require_once("customer_functions.awp");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("security/security_functions.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name; 
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_GET['function'];
	$message = "";
 
	// Return XML
	header("Content-type: text/xml");

	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	switch ($FUNCTION)
	{
	// Register Customer
	case 'customer_register':
	
		
		$s_xml .= '<customer_register>';
	
		if( $_GET['onlychange']==1 )
		{
			// update customer record
			// check duplicates
			$result = $dbh->Query("select customer_id, username from customer_publish where 
									username='".$dbh->Escape($_POST['username'])."' and customer_id!='".$dbh->Escape($_POST['customer_id'])."'");
			if( $dbh->GetNumberRows($result)<=0 )
			{
				// Customer name existed
				$s_xml .= '<result_code>-1</result_code>';	
				$s_xml .= '<error_message>Account with the specified user name already exists!</error_message>';
			
			}else{
				// update record
				$dbh->Query("update customer_publish set username='".$dbh->Escape($_POST['username'])."', password='".$_POST['password']."'
								where customer_id = '".$dbh->Escape($_POST['customer_id'])."'");		
				$s_xml .= '<result_code>'.$_POST['customer_id'].'</result_code>';
			}
		}
		else if ($_POST['username'])
		{
			
			// insert customer
			$result = $dbh->Query("select customer_id, username from customer_publish where username='".$dbh->Escape($_POST['username'])."'");
			if($dbh->GetNumberRows($result)>0)
			{
				// Customer name existed
				$s_xml .= '<result_code>-10</result_code>';	
				$s_xml .= '<error_message>Unable to update customer record. The username is already in use</error_message>';
			}
			else
			{
				
								
				/*$olist = new CAntObjectList($dbh, "customer", $USER);
				$olist->addCondition('and', "email", "is_equal", $_POST['username']);
				$olist->addCondition('or', "email2", "is_equal", $_POST['username']);
				$olist->addOrderBy("type_id"); // sort contacts first
				$olist->getObjects();
				if(false && $olist->getNumObjects())
				{
					$obj = $olist->getObjectMin(0);
					$new_customer_id = $obj['id'];
					
					$s_xml .= "exist!";	
				}*/
				
				$rs_cust = $dbh->Query("select id FROM customers where email='".$dbh->Escape($_POST['username'])."' OR email2='".$dbh->Escape($_POST['username'])."'");
				
				if($dbh->GetNumberRows($rs_cust)>0)
				{
					$new_customer_id = $dbh->GetValue($rs_cust, 0, "id")	;
				}
				else
				{
					// insert new record
					$cust = new CAntObject($dbh, "customer", null, $USER);
					$cust->setValue('first_name', $_REQUEST['first_name']);
					$cust->setValue('last_name', $_REQUEST['last_name']);
					$cust->setValue('email', $_REQUEST['username']);
					$new_customer_id = $cust->save();

					/*
					$values = $_POST;
					# set email to be the same as customer_publish.username
					$values['email'] = $values['username'];
					
					$new_customer_id = CustCreate($dbh, $USER, null, $values);
					 */
					
				}
				if( $new_customer_id<=0 )
				{
					// failed creating customer lead
					$s_xml .= '<result_code>-1</result_code>';
					$s_xml .= '<error_message>Unable to create customer record</error_message>';
				}
				else
				{
						$dbh->Query("insert into customer_publish(customer_id,username,password)
									values ('".$new_customer_id."','".$dbh->Escape($_POST['username'])."', '".$_POST['password']."')");
					
					$s_xml .= '<result_code>'.$new_customer_id.'</result_code>';	
				}
			}
		}
		
		$s_xml .= '</customer_register>';
		echo $s_xml; 
		
	break;
	
	
	// Authenticate Customer
	case 'auth_challenge':
		
		$s_xml = '';
		$s_xml .= '<auth_challenge>';
		
		
		$result = $dbh->Query("select customer_id, username, password from customer_publish where username='".$dbh->Escape($_POST['username'])."'");
		if( $dbh->GetNumberRows($result)<=0 )
		{
			// account not found
			$s_xml .= '<result_code>-1</result_code>';
			$s_xml .= '<error_message>'.rawurlencode('Invalid username.').'</error_message>';

		}
		else
		{
			$row = $dbh->GetNextRow($result, 0);
			if($_POST['password'] != $row['password'])
			{
				// invalid password
				$s_xml .= '<result_code>-10</result_code>';
				$s_xml .= '<error_message>'.rawurlencode('Invalid password.').'</error_message>';

			}
			else
			{
				// valid username and password
				$s_xml .= '<result_code>1</result_code>';
				$s_xml .= '<customer_id>'.rawurlencode($row['customer_id']).'</customer_id>';
			}
		}
		
		$s_xml .= '</auth_challenge>';
		echo $s_xml; 
		
		break;
		
	/*************************************************************************************
	*	Function:		auth_get_custid
	*
	*	Description:	Get a customer id from a user name
	*
	*	Arguments:		1. username - POST the user_name for the customer
	**************************************************************************************/
	case 'auth_get_custid':
		$result = $dbh->Query("select customer_id from customer_publish where username='".$dbh->Escape($_POST['username'])."'");
		if($dbh->GetNumberRows($result)<=0)
			$retval = "-1";
		else
			$retval = $dbh->GetValue($result, 0, "customer_id");
		break;

	/*************************************************************************************
	*	Function:		auth_set_password
	*
	*	Description:	set password for a user-name
	*
	*	Arguments:		1. username - POST the user_name for the customer
	*					2. password - POST the md5 encoded password
	**************************************************************************************/
	case 'auth_set_password':
		if (is_numeric($_POST['customer_id']) && $_POST['password'])
			$dbh->Query("update customer_publish set password='".$dbh->Escape($_POST['password'])."' where customer_id='".$_POST['customer_id']."'");
		$retval = 1;
		break;
	
	/*************************************************************************************
	*	Function:		get_cases
	*
	*	Description:	Get cases for a customer
	*
	*	Arguments:		1. customer_id - POST the unique id of the customer
	**************************************************************************************/
	case 'get_cases':
		if ($_REQUEST['customer_id'])
		{
			$s_xml = '';
			$s_xml .= '<cases>';

			$conds = array("conditions"=>array(1), "condition_blogic_1"=>"and", "condition_fieldname_1"=>"customer_id", 
							"condition_operator_1"=>"is_equal", "condition_condvalue_1"=>$_REQUEST['customer_id']);
			$olist = new CAntObjectList($dbh, "case", $USER, $conds);
			$num = $olist->getNumObjects();
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $olist->getObject($i);
				$s_xml .= "<case>
							<id>".$obj->id."</id>
							<title>".rawurlencode($obj->getValue("title"))."</title>
							<ts_entered>".rawurlencode($obj->getValue("ts_entered"))."</ts_entered>
							<status>".rawurlencode($obj->getForeignValue("status_id"))."</status>
						   </case>";
			}

			$s_xml .= '</cases>';
			echo $s_xml; 
		}
		else
		{
			$retval = "-1";
		}
		
		break;

	/*************************************************************************************
	*	Function:		get_projects
	*
	*	Description:	Get projects for a customer
	*
	*	Arguments:		1. cid - POST the unique id of the customer
	**************************************************************************************/
	case 'get_projects':
		if ($_GET['cid'])
		{
			echo "<customer_projects>\n";

			$query = "select id, name, date_started, date_completed, user_id from projects where customer_id='".$_GET['cid']."'";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i=0; $i<$num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$name = $row['name'];

				echo "<project>
						<id>".$row['id']."</id>
						<name>".rawurlencode($name)."</name>
					  </project>";
			}
			echo "</customer_projects>";
		}
		else
		{
			$retval = "Define a project id with pid";
		}
		break;

	case 'lead_save':
		if ($_REQUEST['first_name'] == "" and $_REQUEST['last_name'] == "")
			$_REQUEST['first_name'] = "No Name";

		$lead = new CAntObject($dbh, "lead", null, $USER);
		$lead->setValue('queue_id', $_REQUEST['queue_id']);
		$lead->setValue('owner_id', $_REQUEST['owner_id']);
		$lead->setValue('source_id', $_REQUEST['source_id']);
		$lead->setValue('class_id', $_REQUEST['class_id']);
		$lead->setValue('status_id', $_REQUEST['status_id']);
		$lead->setValue('rating_id', $_REQUEST['rating_id']);
		$lead->setValue('first_name', $_REQUEST['first_name']);
		$lead->setValue('last_name', $_REQUEST['last_name']);
		$lead->setValue('email', $_REQUEST['email']);
		$lead->setValue('company', $_REQUEST['company']);
		$lead->setValue('title', $_REQUEST['title']);
		$lead->setValue('website', $_REQUEST['website']);
		$lead->setValue('phone', $_REQUEST['phone']);
		$lead->setValue('street', $_REQUEST['street']);
		$lead->setValue('street2', $_REQUEST['street2']);
		$lead->setValue('city', $_REQUEST['city']);
		$lead->setValue('state', $_REQUEST['state']);
		$lead->setValue('zip', $_REQUEST['zip']);
		$lead->setValue('country', $_REQUEST['country']);
		$lead->setValue('notes', $_REQUEST['notes']);
		$lead->setValue('customer_id', $_REQUEST['customer_id']);
		$retval = $lead->save();

		break;

	case 'customer_save':
	
	
		$ant_obj = new CAntObject($dbh, "customer", $_REQUEST['id']);
		$ofields = $ant_obj->def->getFields();
		
		
		foreach ($ofields as $fname=>$field)
		{
			if ($field->type=='fkey_multi' || $field->type=='object_multi')
			{
				// Purge
				if (!$_GET['onlychange'])
					$ant_obj->removeMValues($fname);

				if (is_array($_POST[$fname]) && count($_POST[$fname]))
				{
					// Add new
					foreach ($_POST[$fname] as $val)
					{
						if ($val)
							$ant_obj->setMValue($fname, $val);
					}
				}
			}
			else
			{
				if ($_POST[$fname])
					$ant_obj->setValue($fname, $_POST[$fname]);
			}
		}

		$retval = $ant_obj->save();


		break;
	
		if (is_array($_POST['relationships']) && $retval)
		{
			foreach ($_POST['relationships'] as $rel)
			{
				CustAssoc($dbh, $retval, $rel, '');
			}
		}
		break;

	case 'opportunity_save':
		$ant_obj = new CAntObject($dbh, "opportunity", $_POST['id']);
		$ofields = $ant_obj->def->getFields();
		foreach ($ofields as $fname=>$field)
		{
			if ($field->type=='fkey_multi')
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
		$retval = $ant_obj->save();
		break;
	
	/*************************************************************************************
	*	Function:		billing_test_ccard
	*
	*	Description:	Test a credit card number for validity
	*
	*	Arguments:		1. ccard_name - POST full name on card
	*					2. ccard_number - POST credit card number
	*					3. ccard_type - POST "visa", "mastercard", "amex"
	*					4. ccard_exp - POST credit card expiration mmyy
	*					5. ccard_ccid - POST credit card verification number
	*					6. amount - POST the amount to test for
	**************************************************************************************/
	case 'billing_test_ccard':
		if (!$_POST['ccard_number'] || $_POST['ccard_number']=='1')
		{
			$retval = "-1";
			$message = "A valid credit card number is required";
		}
		else
			$retval = 1; // success
		break;

	/*************************************************************************************
	*	Function:		billing_save_ccard
	*
	*	Description:	Save a credit card
	*
	*	Arguments:		1. ccard_name - POST full name on card
	*					2. ccard_number - POST credit card number
	*					3. ccard_type - POST "visa", "mastercard", "amex"
	*					4. ccard_exp_month - POST credit card expiration mmyy
	*					5. ccard_exp_year - POST credit card expiration mmyy
	*					6. ccard_ccid - POST credit card verification number
	*					7. customer_id - POST the customer_id
	**************************************************************************************/
	case 'billing_save_ccard':
		if (is_numeric($_POST['customer_id']) && is_numeric($_POST['ccard_exp_month']) && is_numeric($_POST['ccard_exp_year']))
		{
			// Clean out existing cards
			$dbh->Query("delete from customer_ccards where customer_id='".$_POST['customer_id']."'");

			// Insert new card
			$result = $dbh->Query("insert into customer_ccards(ccard_name, ccard_number, ccard_exp_month, ccard_exp_year, 
															 ccard_type, customer_id, enc_ver, f_default)
									values('".$dbh->Escape($_POST['ccard_name'])."', 
											'".$dbh->Escape(encrypt($_POST['ccard_number']))."', 
											'".$_POST['ccard_exp_month']."', 
											'".$_POST['ccard_exp_year']."', 
											'".$dbh->Escape($_POST['ccard_type'])."', 
											".$dbh->EscapeNumber($_POST['customer_id']).", 
											'1', 't'); select currval('customer_ccards_id_seq') as id;");
			$id = $dbh->GetValue($result, 0, "id");
			if ($id)
				$retval = $id;
			else
				$retval = -2;
		}
		else
			$retval = -1;
		break;

	/*************************************************************************************
	*	Function:		billing_charge_ccard
	*
	*	Description:	Charge a credit card
	*
	*	Arguments:		1. customer_id - POST the unique id of the customer
	*					2. card_id - POST the unique id of the credit card number returned by billing_save_ccard above
	*					3. amount - POST the amount to post to this transaction
	**************************************************************************************/
	case 'billing_charge_ccard':
		if (is_numeric($_POST['customer_id']) && is_numeric($_POST['card_id']) && is_numeric($_POST['amount']))
		{
			// TODO: pull ccard info from customer_id and card_id and execute transaction with payment gateway
			$retval = 1;
		}
		else
			$retval = -1;
		break;


	/*************************************************************************************
	*	Function:		billing_get_ccards
	*
	*	Description:	Get existing credit cards for this customer
	*
	*	Arguments:		1. customer_id - POST the unique id of the customer
	**************************************************************************************/
	case 'billing_get_ccards':
		$s_xml = '';
		$s_xml .= '<cards>';

		if ($_POST['customer_id'])
		{
			$result = $dbh->Query("select id, ccard_number, ccard_exp_month, ccard_exp_year, ccard_type, enc_ver, f_default from customer_ccards 
									where customer_id='".$dbh->Escape($_POST['customer_id'])."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				$last_four = "";
				$ccard_num = decrypt($row['ccard_number']);
				if ($row['ccard_number'])
					$last_four = substr($ccard_num, strlen($ccard_num)-4);

				// ccard_type should be "visa" "master card" "american express"
				$s_xml .= "<card>
							<id>".$row['id']."</id>
							<type>".$row['ccard_type']."</type>
							<last_four>".$last_four."</last_four>
						   </card>";
			}
		}
		else
		{
			$s_xml .= '<result_code>-10</result_code>';
			$s_xml .= '<error_message>'.rawurlencode('Invalid username and/or password.').'</error_message>';
		}
			
		$s_xml .= '</cards>';
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
		echo "<message>" . rawurlencode($message) . "</message>";
		echo "<cb_function>".$_GET['cb_function']."</cb_function>";
		echo "</response>";
	}
?>
