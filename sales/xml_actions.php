<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/Email.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("security/security_functions.php");
	

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_REQUEST['function'];

	switch ($FUNCTION)
	{
	/*************************************************************************
	*	Function:	invoice_bill
	*
	*	Purpose:	Bill an invoice out
	**************************************************************************/
	case "invoice_bill":
		if ($ANT->settingsGet("/general/merchant")!=1)
		{
			$_REQUEST['billmethod'] = "";
			$retval = "-2";
			$message = "There are no active merchant accounts or payment gateways";
		}

		switch ($_REQUEST['billmethod'])
		{
		case 'credit':
			$process = true;

			if ($_REQUEST['ccid'])
			{
				$result = $dbh->Query("select ccard_number, ccard_exp_month, ccard_exp_year, ccard_type, 
										customer_id, enc_ver, ccard_name from customer_ccards where id='".$_REQUEST['ccid']."'");
				if ($dbh->GetNumberRows($result))
				{
					$row = $dbh->GetNextRow($result, 0);
					$ccard_name = $row['ccard_name'];
					$ccard_exp_year = $row['ccard_exp_year'];
					$ccard_exp_month = $row['ccard_exp_month'];
					$ccard_number = decrypt($row['ccard_number']);
				}
				$dbh->FreeResults($result);
			}
			else
			{
				$ccard_name = $_REQUEST['ccard_name'];
				$ccard_exp_year = $_REQUEST['ccard_exp_year'];
				$ccard_exp_month = $_REQUEST['ccard_exp_month'];
				$ccard_number = $_REQUEST['ccard_number'];
			}

			if ($_REQUEST['customer_id'])
			{
				$obj = new CAntObject($dbh, "customer", $_REQUEST['customer_id']);
				$billing_address = $obj->getValue("business_street");
				if ($obj->getValue("business_street2"))
					$billing_address .= "\n".$obj->getValue("business_street2");
				$billing_city = $obj->getValue("business_city");
				$billing_state = $obj->getValue("business_state");
				$billing_zip = $obj->getValue("business_zip");
			}

			if (!$ccard_number)
			{
				$process = false;
				$message = "Credit card number not provided or valid";
			}

			if (!$ccard_exp_month || !$ccard_exp_year)
			{
				$process = false;
				$message = "Expiration month and year are required";
			}
				
			if ($process)
			{	
				// Get billing first and last name
				if (strpos($ccard_name, ' '))
				{
					$cc_f_name = substr($ccard_name, 0, strrpos($ccard_name, ' '));
					$cc_l_name = substr($ccard_name, strrpos($ccard_name, ' ') + 1);
				}

				$exp_month = ($ccard_exp_month < 10 && substr($ccard_exp_month, 0, 1) != '0') 
								? '0'.$ccard_exp_month : $ccard_exp_month;
				$exp_year = ($ccard_exp_year < 10 && substr($ccard_exp_year, 0, 1) != '0') 
								? '0'.$ccard_exp_year : $ccard_exp_year;

				// Bill User
				// ================================================================
				$auth_net_login_id			= $ANT->settingsGet("/general/merchant/auth_net_login_id");
				if ($auth_net_login_id) 
					$auth_net_login_id = decrypt($auth_net_login_id);
				$auth_net_tran_key			= $ANT->settingsGet("/general/merchant/auth_net_tran_key");
				if ($auth_net_tran_key) 
					$auth_net_tran_key = decrypt($auth_net_tran_key);
				$auth_net_url 				= "https://secure.authorize.net/gateway/transact.dll";

				// Get price
				$price = $_REQUEST['price'];
				if (strpos($price, ".") === false)
					$price .= ".00";

				//$settings_billing_testauthurl = "https://certification.authorize.net/gateway/transact.dll";
				//  Uncomment the line ABOVE for shopping cart test accounts or BELOW for live merchant accounts
				//$settings_billing_authurl = "https://secure.authorize.net/gateway/transact.dll";
				
				
				$authnet_values = array
				(
					"x_login"				=> $auth_net_login_id,
					"x_version"				=> "3.1",
					"x_delim_char"			=> "|",
					"x_delim_data"			=> "TRUE",
					"x_url"					=> "FALSE",
					"x_type"				=> "AUTH_CAPTURE",
					"x_method"				=> "CC",
					"x_tran_key"			=> $auth_net_tran_key,
					"x_relay_response"		=> "FALSE",
					"x_card_num"			=> str_replace(" ", '', str_replace("-", '', $ccard_number)),
					"x_exp_date"			=> $exp_month.$exp_year,
					"x_description"			=> "Aereus Corp",
					"x_amount"				=> "$price",
					"x_first_name"			=> $cc_f_name,
					"x_last_name"			=> $cc_l_name,
					"x_address"				=> $billing_address,
					"x_city"				=> $billing_city,
					"x_state"				=> $billing_state,
					"x_zip"					=> $billing_zip
				);

				$fields = "";
				foreach ($authnet_values as $key=>$value) $fields .= "$key=".urlencode($value)."&";

				$ch = curl_init($auth_net_url); // URL of gateway for cURL to post to
				curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
				curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
				### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
				$resp = curl_exec($ch); //execute post and get results
				curl_close ($ch);
				
				$text = $resp;	
				$h = substr_count($text, "|");
				$h++;

				$restxt = $text;

				$resparts = explode("|", $restxt);

				// Result
				$ccard_result = $resparts[0];

				// Reason
				$message = $resparts[3];
				if (is_array($resvals))
				{
					$transaction_id = $resparts[6];
					//$resvals['transaction_id'] = $resparts[6];
				}

				if ($ccard_result)
					$retval = $ccard_result;
				else
					$retval = "-1";

				$objInv = new CAntObject($dbh, "invoice", $_REQUEST['invoice_id'], $USER);
				$objInv->addActivity("Credit Card Transaction", "Amount: $price, Transaction ID: $transaction_id, Message:$message");
				/*
				if (1 == $ccard_result)
				{
					//return 0;	
				}
				else
				{
					//return $ccard_result;
				}
				 */
			}
			else
			{
				$retval = "-1";
				// $message is set above
			}

			break;
		case 'eft':
			break;
		default: // Cash
			break;
		}

		break;

	/*************************************************************************
	*	Function:	invoice_bill
	*
	*	Purpose:	Bill an invoice out
	**************************************************************************/
	case "customer_get_ccards":
		if ($_REQUEST['customer_id'])
		{
			$retval = "[";

			$result = $dbh->Query("select id, ccard_number, ccard_exp_month, ccard_exp_year, ccard_type, f_default,
									customer_id, enc_ver, ccard_name from customer_ccards where customer_id='".$_REQUEST['customer_id']."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$ccard_name = $row['ccard_name'];
				$ccard_exp_year = $row['ccard_exp_year'];
				$ccard_exp_month = $row['ccard_exp_month'];
				$ccard_number = decrypt($row['ccard_number']);
				$ccard_type = $row['ccard_type'];
				$last4 = substr($ccard_number, -4, 4);

				if ($i) $retval .= ", ";
				$retval .= "{id:\"".$row['id']."\", last_four:\"".$last4."\", type:\"".$ccard_type."\", default:\"".(($row['f_default']=='t')?true:false)."\"}";
			}
			$dbh->FreeResults($result);

			$retval .= "]";
		}
		else
		{
			$retval = "-1";
		}
		break;

	/*************************************************************************
	*	Function:	invoice_get_detail
	*
	*	Purpose:	Bill an invoice out
	**************************************************************************/
	case "invoice_get_detail":
		$retval = "[";
		if ($_REQUEST['invoice_id'])
		{
			$result = $dbh->Query("select id, quantity, amount, name from customer_invoice_detail where invoice_id='".$_REQUEST['invoice_id']."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				if ($i) $retval .= ", ";
				$retval .= "{id:\"".$row['id']."\", quantity:\"".$row['quantity']."\", amount:\"".$row['amount']."\", name:\"".$row['name']."\"}";
			}
			$dbh->FreeResults($result);
		}
		$retval .= "]";
		break;

	/*************************************************************************
	*	Function:	order_get_detail
	*
	*	Purpose:	Get details of an order
	**************************************************************************/
	case "order_get_detail":
		$retval = "[";
		if ($_REQUEST['order_id'])
		{
			$result = $dbh->Query("select id, quantity, amount, name from sales_order_detail where order_id='".$_REQUEST['order_id']."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				if ($i) $retval .= ", ";
				$retval .= "{id:\"".$row['id']."\", quantity:\"".$row['quantity']."\", amount:\"".$row['amount']."\", name:\"".$row['name']."\"}";
			}
			$dbh->FreeResults($result);
		}
		$retval .= "]";
		break;

	/*************************************************************************
	*	Function:	invoice_save_detail
	*
	*	Purpose:	Bill an invoice out
	**************************************************************************/
	case "invoice_save_detail":
		$INVID = $_REQUEST['invoice_id'];
		if ($INVID)
		{
			$dbh->Query("delete from customer_invoice_detail where invoice_id='$INVID'");

			if (is_array($_POST['entries']) && count($_POST['entries']))
			{
				for ($i = 0; $i < count($_POST['entries']); $i++)
				{
					$quantity = $_POST['ent_quantity_'.$i];
					$name = $_POST['ent_name_'.$i];
					$amount = $_POST['ent_amount_'.$i];

					if ($quantity)
					{
						$dbh->Query("insert into customer_invoice_detail(invoice_id, quantity, name, amount)
										values('$INVID', ".$dbh->EscapeNumber($quantity).", 
												'".$dbh->Escape($name)."', ".$dbh->EscapeNumber($amount).");");
					}
				}
			}

			$retval = 1;
		}
		else
			$retval = "-1";
		break;

	/*************************************************************************
	*	Function:	order_save_detail
	*
	*	Purpose:	Save the details for this order
	**************************************************************************/
	case "order_save_detail":
		$OID = $_REQUEST['order_id'];
		if ($OID)
		{
			$dbh->Query("delete from sales_order_detail where order_id='$OID'");

			if (is_array($_POST['entries']) && count($_POST['entries']))
			{
				for ($i = 0; $i < count($_POST['entries']); $i++)
				{
					$quantity = $_POST['ent_quantity_'.$i];
					$name = $_POST['ent_name_'.$i];
					$amount = $_POST['ent_amount_'.$i];
					$pid = $_POST['ent_pid_'.$i];

					if ($quantity)
					{
						$dbh->Query("insert into sales_order_detail(order_id, product_id, quantity, name, amount)
										values('$OID', ".$dbh->EscapeNumber($pid).", ".$dbh->EscapeNumber($quantity).", 
												'".$dbh->Escape($name)."', ".$dbh->EscapeNumber($amount).");");
					}
				}
			}

			$retval = 1;
		}
		else
			$retval = "-1";
		break;

	/*************************************************************************
	*	Function:	order_save_detail
	*
	*	Purpose:	Save the details for this order
	**************************************************************************/
	case "order_create_invoice":
		$OID = $_REQUEST['order_id'];
		if ($OID)
		{
		}
		break;
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
	echo "<response>";
	echo "<retval>" . rawurlencode($retval) . "</retval>";
	echo "<message>" . rawurlencode($message) . "</message>";
	echo "<cb_function>".$_GET['cb_function']."</cb_function>";
	echo "</response>";
?>
