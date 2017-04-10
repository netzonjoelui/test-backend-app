<?php
/**
* Sales actions.
*/
require_once(dirname(__FILE__).'/../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../security/security_functions.php');
require_once(dirname(__FILE__).'/../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../lib/Object/SalesOrder.php');
require_once(dirname(__FILE__).'/../lib/Object/Invoice.php');
require_once(dirname(__FILE__).'/../lib/PaymentGatewayManager.php');
require_once(dirname(__FILE__).'/../lib/Controller.php');

/**
* Actions for interacting with Ant Sales
*/
class SalesController extends Controller
{
    /**
    * Invoice Bill
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function invoiceBill($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        $method = null;
        
        if(isset($params['billmethod']))        
            $method = $params['billmethod'];
        else if(isset($params['method']))
            $method = $params['method'];
        
        if($method)
        {
            $ret = array("error"=>"$method is not a valid method.");

            switch($method)
            {
                case 'credit':
                    $process = true;

                    if ($params['ccid'])
                    {
                        $query = "select ccard_number, ccard_exp_month, ccard_exp_year, ccard_type, 
                                                customer_id, enc_ver, ccard_name from customer_ccards where id='".$params['ccid']."'";
                        $result = $dbh->Query($query);
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
                        if (strpos($params['ccard_name'], " ")!== false)
                        {
                            $first_name = substr($params['ccard_name'], 0, strpos($params['ccard_name'], " "));
                            $last_name = substr($params['ccard_name'], (strpos($params['ccard_name'], " ")+1)); 
                        }            
                        else
                        {
                            $first_name = $params['ccard_name'];
                            $last_name = "";
                        }

                        $ccard_name = $params['ccard_name'];
                        $ccard_exp_year = $params['ccard_exp_year'];
                        $ccard_exp_month = $params['ccard_exp_month'];
                        $ccard_number = $params['ccard_number'];
                    }

                    if ($params['customer_id'])
                    {
                        $obj = new CAntObject($dbh, "customer", $params['customer_id']);
                        $billing_address = $obj->getValue("business_street");
                        if ($obj->getValue("business_street2"))
                            $billing_address .= "\n".$obj->getValue("business_street2");
                            
                        $first_name = $obj->getValue("first_name");
                        $last_name = $obj->getValue("last_name");                
                        $billing_city = $obj->getValue("business_city");
                        $billing_state = $obj->getValue("business_state");
                        $billing_zip = $obj->getValue("business_zip");
                    }

                    if (!$ccard_number)
                    {
                        $process = false;                
                        $ret = array("error"=>"Credit card number not provided or valid");
                    }

                    if (!$ccard_exp_month || !$ccard_exp_year)
                    {
                        $process = false;
                        $ret = array("error"=>"Expiration month and year are required");                
                    }
                        
                    if ($process)
                    {    
                        // Get gateway
                        if ($params['testmode'] == 1)
                            $gw = PaymentGatewayManager::getGateway($dbh, PMTGW_TEST); // Force test type
                        else
                            $gw = PaymentGatewayManager::getGateway($dbh);
                        
                        $invid = $params['invoice_id'];
                        $inv = new CAntObject_Invoice($dbh, $invid, $this->user);
                        
                        // Set fake credit card info
                        $cardData = array(
                            "number" => $ccard_number,
                            "exp_month" => $ccard_exp_month,
                            "exp_year" => $ccard_exp_year,
                        );

                        // Set customer data
                        $custData = array(
                            "first_name" => $first_name,
                            "last_name" => $last_name,
                            "street" => $billing_address,
                            "city" => $billing_city,
                            "state" => $billing_state,
                            "zip" => $billing_zip,
                        );
                        
                        $ret = $inv->payWithCard($gw, $cardData, $custData);
                    }
                    else
                    {
                        // $message is set above
                    }

                    break;
                case 'eft':
                    break;
                default: // Cash
                    break;
            }
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }

	/**
    * Process a payment through the payment gateway configured for this account
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function paymentProcess($params)
    {
        $dbh = $this->ant->dbh;
		$ret = -1;
        
        switch ($params['method'])
        {
            case 'credit':
                $process = true;

                if ($params['ccid'])
                {
                    $result = $dbh->Query("select ccard_number, ccard_exp_month, ccard_exp_year, ccard_type, 
                                            customer_id, enc_ver, ccard_name from customer_ccards where id='".$params['ccid']."'");
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
                    $ccard_name = $params['ccard_name'];
                    $ccard_exp_year = $params['ccard_exp_year'];
                    $ccard_exp_month = $params['ccard_exp_month'];
                    $ccard_number = $params['ccard_number'];
                }

                if ($params['customer_id'])
                {
                    $obj = new CAntObject($dbh, "customer", $params['customer_id']);
                    $billing_address = $obj->getValue("business_street");
                    if ($obj->getValue("business_street2"))
                        $billing_address .= "\n".$obj->getValue("business_street2");
                        
                    $first_name = $obj->getValue("first_name");
                    $last_name = $obj->getValue("last_name");                
                    $billing_city = $obj->getValue("business_city");
                    $billing_state = $obj->getValue("business_state");
                    $billing_zip = $obj->getValue("business_zip");
				}
				else
				{
					if (strpos($params['ccard_name'], " ")!== false)
					{
						$first_name = substr($params['ccard_name'], 0, strpos($params['ccard_name'], " "));
						$last_name = substr($params['ccard_name'], (strpos($params['ccard_name'], " ")+1)); 
					}			
					else
					{
						$first_name = $params['ccard_name'];
						$last_name = "";
					}

		   			$billing_address = $params['billing_address'];		
                    $billing_city = $params['billing_city'];
                    $billing_state = $params['billing_state'];
                    $billing_zip = $params['billing_zip'];
				}

                if (!$ccard_number)
                {
                    $process = false;                
                    $ret = array("error"=>"Credit card number not provided or valid");
                }

                if (!$ccard_exp_month || !$ccard_exp_year)
                {
                    $process = false;
                    $ret = array("error"=>"Expiration month and year are required");                
                }
                    
                if ($process)
                {    
                    // Get gateway
					if ($params['testmode'] == 1)
                    	$gw = PaymentGatewayManager::getGateway($dbh, PMTGW_TEST); // Force test type
					else
                    	$gw = PaymentGatewayManager::getGateway($dbh);

                    $orderid = $params['order_id'];
                    $order = new CAntObject_SalesOrder($dbh, $orderid, $this->user);
                    
                    // Set fake credit card info
                    $cardData = array(
                        "number" => $ccard_number,
						"exp_month" => $ccard_exp_month,
						"exp_year" => $ccard_exp_year,
                    );

                    // Set customer data
                    $custData = array(
                        "first_name" => $first_name,
                        "last_name" => $last_name,
                        "street" => $billing_address,
                        "city" => $billing_city,
                        "state" => $billing_state,
                        "zip" => $billing_zip,
                    );
                    
                    $result = $order->payWithCard($gw, $cardData, $custData);

					if ($result)
					{
						$ret = array('status'=>"success", 'transaction_id'=>$gw->respTransId, 'message'=>$gw->respReason);
					}
					else
					{
						$ret = array('status'=>"fail", 'transaction_id'=>$gw->respTransId, 'message'=>$gw->respReason);
					}
                }

                break;
            case 'eft':
                break;
            default: // Cash
                break;
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Customer Get Ccards
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function customerGetCcards($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['customer_id'])
        {
            $ret = array();
            $result = $dbh->Query("select id, ccard_number, ccard_exp_month, ccard_exp_year, ccard_type, f_default,
                                    customer_id, enc_ver, ccard_name from customer_ccards where customer_id='".$params['customer_id']."'");
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

                $ret[] = array("id" => $row['id'], "last_four" => $last4, "type" => $ccard_type, "default" => (($row['f_default']=='t')?true:false));
            }
            $dbh->FreeResults($result);
            
        }
        else
            $ret = array("error"=>"customer_id is a required param");
                
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save the details of an invoice
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function invoiceSaveDetail($params)
    {
        $dbh = $this->ant->dbh;
        $INVID = $params['invoice_id'];
        
        if ($INVID)
        {
            $dbh->Query("delete from customer_invoice_detail where invoice_id='$INVID'");

            if (is_array($params['entries']) && count($params['entries']))
            {
                for ($i = 0; $i < count($params['entries']); $i++)
                {
                    $quantity = $params['ent_quantity_'.$i];
                    $name = $params['ent_name_'.$i];
                    $amount = $params['ent_amount_'.$i];

                    if ($quantity)
                    {
                        $dbh->Query("insert into customer_invoice_detail(invoice_id, quantity, name, amount)
                                        values('$INVID', ".$dbh->EscapeNumber($quantity).", 
                                                '".$dbh->Escape($name)."', ".$dbh->EscapeNumber($amount).");
                                        select currval('customer_invoice_detail_id_seq') as id;");
                                                
                        $ret = 1;
                    }
                }
            }
            else
                $ret = array("error"=>"Error occurred while saving invoice detail.");
        }
        else
            $ret = array("error"=>"invoice_id is a required param");
                
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Invoice Get Detail
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function invoiceGetDetail($params)
    {        
        $dbh = $this->ant->dbh;
        
        if ($params['invoice_id'])
        {
            $ret = array();
            $result = $dbh->Query("select id, quantity, amount, name from customer_invoice_detail where invoice_id='".$params['invoice_id']."'");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                
                $ret[] = array("id" => $row['id'], "quantity" => $row['quantity'], "amount" => $row['amount'], "name" => $row['name']);
            }
            $dbh->FreeResults($result);
        }
        else
            $ret = array("error"=>"invoice_id is a required param");
                
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Order Save Detail
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */    
    public function orderGetDetail($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['order_id'])
        {
            $ret = array();
            
            $result = $dbh->Query("select id, quantity, amount, name from sales_order_detail where order_id='".$params['order_id']."'");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                                
                $ret[] = array("id" => $row['id'], "quantity" => $row['quantity'], "amount" => $row['amount'], "name" => $row['name']);
            }
            $dbh->FreeResults($result);
        }
        else
            $ret = array("error"=>"order_id is a required param");
                
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Order Save Detail
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function orderSaveDetail($params)
    {
        $dbh = $this->ant->dbh;
        $OID = $params['order_id'];
        
        if ($OID)
        {
            $dbh->Query("delete from sales_order_detail where order_id='$OID'");

            if (is_array($params['entries']) && count($params['entries']))
            {
                for ($i = 0; $i < count($params['entries']); $i++)
                {
                    $quantity = $params['ent_quantity_'.$i];
                    $name = $params['ent_name_'.$i];
                    $amount = $params['ent_amount_'.$i];
                    $pid = $params['ent_pid_'.$i];

                    if ($quantity)
                    {                        
                        $dbh->Query("insert into sales_order_detail(order_id, product_id, quantity, name, amount)
                                        values('$OID', ".$dbh->EscapeNumber($pid).", ".$dbh->EscapeNumber($quantity).", 
                                                '".$dbh->Escape($name)."', ".$dbh->EscapeNumber($amount).");");
                    }
                }
            }

            $ret = $OID;
        }
        else
            $ret = array("error"=>"order_id is a required param");
                
        $this->sendOutputJson($ret);
        return $ret;
    }
}
