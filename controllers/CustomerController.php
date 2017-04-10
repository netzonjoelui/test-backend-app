<?php
/**
 * Customer application actions
 */
require_once(dirname(__FILE__).'/../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../lib/Controller.php');
require_once(dirname(__FILE__).'/../security/security_functions.php');
require_once(dirname(__FILE__).'/../customer/customer_functions.awp');


/**
 * Class for controlling customer functions
 */
class CustomerController extends Controller
{    
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;
        
        $this->server = AntConfig::getInstance()->aereus['server'];
        $this->userName = AntConfig::getInstance()->aereus['user'];
        $this->password = AntConfig::getInstance()->aereus['password'];

		$this->api = new AntApi(AntConfig::getInstance()->aereus['server'], 
								  AntConfig::getInstance()->aereus['user'],
								  AntConfig::getInstance()->aereus['password']);
        
        // Make sure we have default values
        CustSetDefStage($ant->dbh, $this->user->accountId);
        CustSetDefStatus($ant->dbh, $this->user->accountId);
        CustSetDefRelTypes($ant->dbh);
        CustInvSetDefStatus($ant->dbh, $this->user->accountId);
    }
    
    /**
     * get the customer name
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function custGetName($params)
    {        
        if ($params['custid'])
            $ret = CustGetName($this->ant->dbh, $params['custid']);
        else
            $ret = array("error"=>"custid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get the customer lead name
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function custLeadGetName($params)
    {
        $dbh = $this->ant->dbh;
        if (is_numeric($params['lead_id']))
        {
            $result = $dbh->Query("select first_name, last_name, company from customer_leads where id='".$params['lead_id']."'");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $ret = stripslashes($row['first_name'])." ".stripslashes($row['last_name']);
            }
            else
                $ret = -1;
        }
        else
            $ret = array("error"=>"lead_id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Customer Lead Convert
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function custLeadConvert($params)
    {
        $dbh = $this->ant->dbh;
        $cust_id = null;
        
        if ($params['lead_id'])
        {
			$createOpp = (isset($params['f_createopp']) && $params['f_createopp']=='t') ? true : false;

            $lead = CAntObject::factory($dbh, "lead", $params['lead_id'], $this->user);
			$ret = $lead->convert($createOpp, $params['opportunity_name'], $params['per_id'], $params['org_id']);

			/*

            // First create account
            if ($cname)
            {
                $cust = new CAntObject($dbh, "customer", null, $this->user);
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
                $cust->setValue("owner_id", $this->user->id);
                $cust->setValue("type_id", CUST_TYPE_ACCOUNT);
                $cust->setValue("business_street", $lead->getValue('street'));
                $cust->setValue("business_street2", $lead->getValue('street2'));
                $cust->setValue("business_city", $lead->getValue('city'));
                $cust->setValue("business_state", $lead->getValue('state'));
                $cust->setValue("business_zip", $lead->getValue('zip'));
                $cust_id = $cust->save();
            }

            // Now create first contact and relate
            $cust2 = new CAntObject($dbh, "customer", null, $this->user);
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
            $cust2->setValue("owner_id", $this->user->id);
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
            if (isset($params['f_createopp']) && $params['f_createopp']=='t')
            {
                if (isset($params['opportunity_name']) && !$params['opportunity_name']) 
                    $params['opportunity_name'] = "LID: " . $params['lead_id'];

                $opp = new CAntObject($dbh, "opportunity", null, $this->user);
                $opp->setValue("owner_id", $this->user->id);
                $opp->setValue("customer_id", $cust2_id);
                $opp->setValue("lead_source_id", $lead->getValue('source_id'));
                $opp->setValue("notes", $lead->getValue('notes'));
                $opp->setValue("lead_id", $params['lead_id']);
                
                if(isset($params['opportunity_name']))
                    $opp->setValue("name", $params['opportunity_name']);
                
                $oid = $opp->save();
            }

            if (!$cust_id)
				$cust_id = $cust2_id;

            $ret = $cust_id;
			*/
        }
        else
            $ret = array("error"=>"lead_id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Customer Opportunity get name
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function custOppGetName($params)
    {
        $dbh = $this->ant->dbh;
        
        if (is_numeric($params['opportunity_id']))
        {
            $result = $dbh->Query("select name from customer_opportunities where id='".$params['opportunity_id']."'");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $ret = stripslashes($row['name']);
            }
            else
                $ret = -1;
        }
        else
            $ret = array("error"=>"opportunity_id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get Activity Types
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function getActivityTypes()
    {
        $dbh = $this->ant->dbh;
        
        $olist = new CAntObjectList($dbh, "activity", $this->user);
        $olist->addCondition("and", "type_id", "is_equal", 12);
        $olist->getObjects();
        $num = $olist->getNumObjects();
        for ($i = 0; $i < $num; $i++)
        {
            $obj = $olist->getObject($i);
            $ret[] = array("id" => $obj->id, "name" => $obj->getValue("name"));
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Customer get zip data
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function custGetZipData($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['zipcode'])
        {
            $ret = array();
            if (strpos($params['zipcode'], "-")!==false)
            {
                $parts = explode("-", $params['zipcode']);
                $params['zipcode'] = $parts[0];
            }

            if (is_numeric($params['zipcode']))
            {
                $query = "select city, state from app_us_zipcodes where zipcode='".$params['zipcode']."'";
                $result = $dbh->Query($query);
                if ($dbh->GetNumberRows($result))
                {
                    $row = $dbh->GetNextRow($result, 0);                    
                    $ret = array("state" => stripslashes($row['state']), "city" => stripslashes($row['city']));
                }
                else
                    $ret = array("error" => "No records found.");
            }
        }
        else
            $ret = array("error"=>"zipcode is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Save Publish
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function savePublish($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['customer_id'])
        {
            $result = $dbh->Query("select customer_id from customer_publish where customer_id='".$params['customer_id']."'");
            if ($dbh->GetNumberRows($result))
            {
                $query = "update customer_publish set username='".$dbh->Escape($params['username'])."', 
                            f_files_view='".$params['f_files_view']."', 
                            f_files_upload='".$params['f_files_upload']."', f_files_modify='".$params['f_files_modify']."' ";
                            
                if ($params['password']!='    ')
                    $query .= ", password=md5('".$dbh->Escape($params['password'])."') ";
                    
                $query .= " where customer_id='".$params['customer_id']."'";
            }
            else
            {
                $query = "insert into customer_publish(username, password, f_files_view, f_files_upload, f_files_modify, customer_id)
                            values('".$dbh->Escape($params['username'])."', md5('".$dbh->Escape($params['password'])."'), '".$params['f_files_view']."',
                                '".$params['f_files_upload']."', '".$params['f_files_modify']."', '".$params['customer_id']."')";
            }

            $dbh->Query($query);
            $ret = 1;
        }
        else
            $ret = array("error"=>"customer_id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get Publish
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function getPublish($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['customer_id'])
        {
            $ret = array();
            
            $query = "select username, password, f_files_view, f_files_upload, f_files_modify 
                                    from customer_publish where customer_id='".$params['customer_id']."'";
            $result = $dbh->Query($query);

            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetRow($result, 0);
                $ret = array("username" => rawurlencode($row['username']), 
                                "f_files_view" => (($row['f_files_view']=='t')?true:false),
                                "f_files_upload" => (($row['f_files_upload']=='t')?true:false),
                                "f_files_modify" => (($row['f_files_modify']=='t')?true:false),
                                "folder_id" => null);
            }
            else
            {
                $ret = array("username" => "",
                                "f_files_view" => "f",
                                "f_files_upload" => "f",
                                "f_files_modify" => "f",
                                "folder_id" => null);
            }
        }
        else
            $ret = array("error"=>"customer_id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Save Relationship
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function saveRelationships($params)
    {
        $dbh = $this->ant->dbh;
        
        if (isset($params['customer_id']) && $params['customer_id'])
        {
            if (isset($params['delete']) && is_array($params['delete']) && count($params['delete']))
            {
                for ($i = 0; $i < count($params['delete']); $i++)
                {
                    $dbh->Query("delete from customer_associations where parent_id='".$params['customer_id']."' 
                                    and customer_id='".$params['delete'][$i]."'");
                }
            }

            if (is_array($params['relationships']) && count($params['relationships']))
            {
                for ($i = 0; $i < count($params['relationships']); $i++)
                {
                    // First add relationship to this customer
                    $query = "select id from customer_associations where parent_id='".$params['customer_id']."' 
                                    and customer_id='".$params['relationships'][$i]."'";
                    if ($dbh->GetNumberRows($dbh->Query($query)))
                    {
                        $dbh->Query("update customer_associations set relationship_name='".$dbh->Escape($params['r_type_name_'.$params['relationships'][$i]])."',
                                        type_id=".$dbh->EscapeNumber($params['r_type_id_'.$params['relationships'][$i]])."
                                        where  parent_id='".$params['customer_id']."' and customer_id='".$params['relationships'][$i]."'");
                    }
                    else
                    {
                        $dbh->Query("insert into customer_associations(customer_id, parent_id, relationship_name, type_id) values
                                        ('".$params['relationships'][$i]."', '".$params['customer_id']."', 
                                         '".$dbh->Escape($params['r_type_name_'.$params['relationships'][$i]])."', 
                                         ".$dbh->EscapeNumber($params['r_type_id_'.$params['relationships'][$i]]).")");
                    }

                    // Now make relationship two-way
                    $query = "select id from customer_associations where parent_id='".$params['relationships'][$i]."' 
                                    and customer_id='".$params['customer_id']."'";
                    if (!$dbh->GetNumberRows($dbh->Query($query)))
                    {
                        $dbh->Query("insert into customer_associations(customer_id, parent_id, relationship_name, type_id) values
                                        ('".$params['customer_id']."', '".$params['relationships'][$i]."', 
                                         '".$dbh->Escape($params['r_type_name_'.$params['relationships'][$i]])."', NULL)");
                    }
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"customer_id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get Relationship
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function getRelationshipTypes()
    {
        $dbh = $this->ant->dbh;
        
        $ret = array();
        $result = $dbh->Query("select id, name from customer_association_types order by name");
        $num = $dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetRow($result, $i);            
            $ret[] = array("id" => $row['id'], "name" => $row['name']);
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Save Relationship Type
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function saveRelationshipType($params)
    {
        $dbh = $this->ant->dbh;
        
        if (isset($params['name']) && $params['name'])
        {
            if (isset($params['id']) && $params['id'])
            {
                $dbh->Query("update customer_association_types set name='".$dbh->Escape($params['name'])."' where id='".$params['id']."'");
                $ret = $params['id'];
            }
            else
            {
                $result = $dbh->Query("insert into customer_association_types(name) values('".$dbh->Escape($params['name'])."'); 
                                         select currval('customer_association_types_id_seq') as id;");
                                         
                if ($dbh->GetNumberRows($result))
                    $ret = $dbh->GetValue($result, 0, "id");
            }

        }
        else
            $ret = array("error"=>"name is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Removes Relationship Type
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function removeRelationshipType($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['id'])
        {
            $dbh->Query("delete from customer_association_types where id='".$params['id']."'");
            $ret = $params['id'];
        }
        else
            $ret = array("error"=>"id is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Save Relationship Type
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function getRelationships($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['customer_id'])
        {
            $ret = array();
            $query = "select customer_associations.id, customer_associations.customer_id as cid, 
                        customer_associations.relationship_name, customer_association_types.name as type_name,
                        customer_association_types.id as rtype_id
                        from customer_associations left outer join customer_association_types 
                        on (customer_association_types.id = customer_associations.type_id)
                            where customer_associations.parent_id='".$params['customer_id']."'";
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
                
                $ret[] = array("cid" => rawurlencode($row['cid']), "name" => rawurlencode($name),
                                "email" => rawurlencode($email), "phone" => rawurlencode($phone),
                                "title" => rawurlencode($title), "rtype_id" => $row['rtype_id'],
                                "rname" => rawurlencode($relationship));
            }
            $dbh->FreeResults($result);            
        }
        else
            $ret = array("error"=>"name is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Create Customer
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function createCustomer($params)
    {
        $dbh = $this->ant->dbh;
        
        $name = rawurldecode($params['name']);
        if ($params['type_id'] && $name)
        {
            $obj = new CAntObject($dbh, "customer", null, $this->user);
            if ($params['type_id'] == CUST_TYPE_CONTACT)
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

            $ret = $obj->save();
        }
        else
            $ret = array("error"=>"name and type_id are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Sync Customer
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function syncCustomers($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user, $params, $params["order_by"]);
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                if ($obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->getValue("owner_id"))?true:false))
                {
                    CustSyncContact($dbh, $this->user->id, $obj->id, NULL, "create");
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type and objects are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Activity Save
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function activitySave($params)
    {
        $dbh = $this->ant->dbh;
        
        $aid = $params['aid'];
        $name = stripslashes(rawurldecode($params['name']));
        $notes = stripslashes(rawurldecode($params['notes']));
        // Get the owner
        if (is_numeric($aid))
        {
            $dbh->Query("update customer_activity set name='".$dbh->Escape($name)."', 
                            type_id=".db_CheckNumber($params['type_id']).", notes='".$dbh->Escape($notes)."',  
                            time_entered='".stripslashes(rawurldecode($params['date']))." ".stripslashes(rawurldecode($params['time']))."',
                            public='".(($params['f_public']=='t')?'t':'f')."', direction='".$dbh->Escape($params['direction'])."'
                            where id='$aid'");
            $ret = $aid;
        }
        else
        {
            if ($params['customer_id'] || $params['lead_id'] || $params['opportunity_id'])
            {
                $result = $dbh->Query("insert into customer_activity(name, type_id, notes, customer_id, lead_id, opportunity_id, 
                                                                     time_entered, public, user_id, user_name, direction) 
                                        values('".$dbh->Escape($name)."', ".db_CheckNumber($params['type_id']).", '".$dbh->Escape($notes)."', 
                                        ".db_CheckNumber($params['customer_id']).", ".db_CheckNumber($params['lead_id']).", 
                                        ".db_CheckNumber($params['opportunity_id']).",
                                        '".stripslashes(rawurldecode($params['date']))." ".stripslashes(rawurldecode($params['time']))."',
                                        '".(($params['f_public']=='t')?'t':'f')."', '" . $this->user->id . "', '" . $this->user->name . "', '".$dbh->Escape($params['direction'])."');
                                        select currval('customer_activity_id_seq') as id;");
                if ($dbh->GetNumberRows($result))
                    $ret = $dbh->GetValue($result, 0, "id");
                else
                    $ret = -1;
            }
            else
                $ret = -1;
        }

        // Send notification
        // -------------------------------------------------
        if ($params['lead_id'])
            $owner = CustGetOwner($dbh, $params['lead_id'], "lead");
        else if ($params['opportunity_id'])
            $owner = CustGetOwner($dbh, $params['opportunity_id'], "opportunity");
        else if ($params['customer_id'])
            $owner = CustGetOwner($dbh, $params['customer_id'], "customer");

        if ($owner && $owner != $this->user->id)
        {
            // Create new email object
            $headers['From'] = AntConfig::getInstance()->email['noreply'];
            $body = "By: " . $this->user->name . "\r\n";
            $headers['Subject'] = ($aid) ? "Customer Activity Updated by " . $this->user->name . "" : "New Customer Activity by " . $this->user->name . "";
            if ($params['lead_id'] && !$params['opportunity_id'])
                $body .= "Lead: ".$params['lead_id']." - ".CustLeadGetName($dbh, $params['lead_id'])."\r\n";
            if ($params['opportunity_id'])
                $body .= "Opportunity: ".$params['opportunity_id']." - ".CustOptGetName($dbh, $params['opportunity_id'])."\r\n";
            if ($params['customer_id'])
                $body .= "Customer: ".$params['customer_id']." - ".CustGetName($dbh, $params['customer_id'])."\r\n";
            $body .= "Name: ".$name."\r\n";
            $body .= "Notes: ".$notes."\r\n";
            $email = new Email();
            $status = $email->send(UserGetEmail($dbh, $params['owner_id']), $headers, $body);
            unset($email);
        }

        // Update last/first contacted fields depending
        // -------------------------------------------------
        $contacted_flag = CustGetActTypeConFlag($dbh, $params['type_id']);
        if ($contacted_flag && ($contacted_flag==$params['direction'] || $contacted_flag=='a'))
        {
            $time = stripslashes(rawurldecode($params['date']))." ".stripslashes(rawurldecode($params['time']));
            if ($params['customer_id'])
            {
                // Make sure this is the latest event
                $query = "select id from customer_activity where customer_id='".$params['customer_id']."' and 
                            (type_id is not null or email_id is not null) order by time_entered DESC limit 1";
                if ($ret == $dbh->GetValue($dbh->Query($query), 0, "id"))
                {
                    $dbh->Query("update customers set 
                                    last_contacted='".$time."' 
                                    where id='".$params['customer_id']."'");
                }
                $dbh->Query("update customers set 
                    ts_first_contacted='".$time."' 
                    where id='".$params['customer_id']."' and ts_first_contacted is null");
            }

            if ($params['lead_id'])
            {
                // Make sure this is the latest event
                $query = "select id from customer_activity where lead_id='".$params['lead_id']."' and 
                            (type_id is not null or email_id is not null) order by time_entered DESC limit 1";
                if ($ret == $dbh->GetValue($dbh->Query($query), 0, "id"))
                {
                    $dbh->Query("update customer_leads set 
                        ts_last_contacted='".$time."' 
                        where id='".$params['lead_id']."'");
                }
                $dbh->Query("update customer_leads set 
                    ts_first_contacted='".$time."' 
                    where id='".$params['lead_id']."' and ts_first_contacted is null");
            }
        }
    }
    
    /**
     * Group Set Color
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function groupSetColor($params)
    {
        $dbh = $this->ant->dbh; 
       
        $gid = $params['gid'];
        $color = $params['color'];

        if ($gid && $color)
        {
            $dbh->Query("update customer_labels set color='$color' where id='$gid'");
            $ret = $color;
        }
        else
            $ret = array("error"=>"gid and color are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group Rename
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function groupRename($params)
    {
        $dbh = $this->ant->dbh; 
        
        $gid = $params['gid'];
        $name = rawurldecode($params['name']);

        if ($gid && $name)
        {
            $dbh->Query("update customer_labels set name='".$dbh->Escape($name)."' where id='$gid'");
            $ret = $name;
        }
        else
            $ret = array("error"=>"gid and color are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group Delete
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function groupDelete($params)
    {
        $dbh = $this->ant->dbh; 
        
        $gid = $params['gid'];

        if ($gid)
        {
            $dbh->Query("delete from customer_labels where id='$gid'");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"gid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group Add
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function groupAdd($params)
    {
        $dbh = $this->ant->dbh; 
        $pgid = null;
        $name = rawurldecode($params['name']);
        $color = rawurldecode($params['color']);
        
        if(isset($params['pgid']))
            $pgid = $params['pgid'];

        if ($name && $color)
        {
            $query = "insert into customer_labels(parent_id, name, color) 
                      values(" . $dbh->EscapeNumber($pgid) . ", '".$dbh->Escape($name)."', '".$dbh->Escape($color)."');
                      select currval('customer_labels_id_seq') as id;";
                      
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);

                $ret = $row['id'];
            }
            else
                $ret = -1;
        }
        else
            $ret = array("error"=>"name and color are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group Add
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function setLeadConverted($params)
    {
        $dbh = $this->ant->dbh; 
        
        if ($params['id'])
        {
            // TODO: veryify user has access to modify customer settings - typically only admins
            $dbh->Query("update customer_lead_status set f_closed='t', f_converted='t' where id='".$params['id']."'");
            $dbh->Query("update customer_lead_status set f_converted='f' where id!='".$params['id']."'");

            $ret = 1;
        }
        else
            $ret = array("error"=>"id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Set Lead Closed
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function setLeadClosed($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['id'] && $params['f_closed'])
        {
            // TODO: veryify user has access to modify customer settings - typically only admins
            $dbh->Query("update customer_lead_status set f_closed='".$dbh->Escape($params['f_closed'])."' where id='".$params['id']."'");

            $ret = 1;
        }
        else
            $ret = array("error"=>"id and f_closed are required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Set opportunity Converted
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function setOppConverted($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['id'] && $params['f_won'])
        {
            // TODO: veryify user has access to modify customer settings - typically only admins
            $dbh->Query("update customer_opportunity_stages set f_closed='t', f_won='".$dbh->Escape($params['f_won'])."' where id='".$params['id']."'");

            if ($params['f_won'] == 't')
                $dbh->Query("update customer_opportunity_stages set f_won='f' where id!='".$params['id']."'");

            $ret = 1;
        }
        else
            $ret = array("error"=>"id and f_closed are required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Set opportunity Closed
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function setOppClosed($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['id'] && $params['f_closed'])
        {
            // TODO: veryify user has access to modify customer settings - typically only admins
            $dbh->Query("update customer_opportunity_stages set f_closed='".$dbh->Escape($params['f_closed'])."' where id='".$params['id']."'");

            $ret = 1;
        }
        else
            $ret = array("error"=>"id and f_closed are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get Codes
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function getCodes($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['tbl'])        // Update specific event
        {            
            $ret = array();
            $result = $dbh->Query("select * from ".$params['tbl']." order by sort_order");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i, PGSQL_ASSOC);
                $cntr = 0;
                foreach ($row as $name=>$val)
                    $ret[] = array($name => $val);
            }
            $dbh->FreeResults($result);
        }
        else
            $ret = array("error"=>"tbl is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Save Codes
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function saveCode($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['tbl'])        // Update specific event
        {
            // TODO: veryify user has access to modify customer settings - typically only admins

            // Sort order
            if ($params['id'] && $params['sorder'])
            {
                $result = $dbh->Query("select sort_order from ".$params['tbl']." where id='".$params['id']."'");
                if ($dbh->GetNumberRows($result))
                    $cur_order = $dbh->GetValue($result, 0, "sort_order");

                if ($cur_order && $cur_order!=$params['sorder'])
                {
                    // Moving up or down
                    if ($cur_order < $params['sorder'])
                        $direc = "down";
                    else
                        $direc = "up";

                    $result = $dbh->Query("select id  from ".$params['tbl']." where id!='".$params['id']."'
                                            and sort_order".(($direc=="up")?">='".$params['sorder']."'":"<='".$params['sorder']."'")." order by sort_order");
                    $num = $dbh->GetNumberRows($result);
                    for ($i = 0; $i < $num; $i++)
                    {
                        $id = $dbh->GetValue($result, $i, "id");
                        $newval = ("up" == $direc) ? $params['sorder']+1+$i : $i+1;
                        $dbh->Query("update ".$params['tbl']." set sort_order='$newval' where id='".$id."'");
                    }
                    $dbh->Query("update ".$params['tbl']." set sort_order='".$params['sorder']."' where id='".$params['id']."'");
                }
            }

            // Color
            if ($params['id'] && $params['color'])
            {
                $dbh->Query("update ".$params['tbl']." set color='".$params['color']."' where id='".$params['id']."'");
            }

            // Name and enter new
            if ($params['name'])
            {
                if ($params['id'])
                {
                    $dbh->Query("update ".$params['tbl']." set name='".$dbh->Escape($params['name'])."' where id='".$params['id']."'");
                }
                else 
                {
                    $result = $dbh->Query("select sort_order from ".$params['tbl']." order by sort_order DESC limit 1");
                    if ($dbh->GetNumberRows($result))
                        $sorder = $dbh->GetValue($result, 0, "sort_order");

                    $dbh->Query("insert into ".$params['tbl']."(name, sort_order) 
                                    values('".$dbh->Escape($params['name'])."', '".($sorder+1)."');");
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"tbl is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Delete Codes
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function deleteCode($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['tbl'])        // Update specific event
        {
            // TODO: veryify user has access to modify customer settings - typically only admins

            // Sort order
            if ($params['id'])
                $dbh->Query("delete from ".$params['tbl']." where id='".$params['id']."'");
                
            $ret = 1;
        }
        else
            $ret = array("error"=>"tbl is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Billing Test Credt Card
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function billingTestCcard($params)
    {        
        if (!$params['ccard_number'] || $params['ccard_number']=='1')            
            $ret = array("error"=>"A valid credit card number is required");
        else
            $ret = 1;
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Billing Save Credt Card
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function billingSaveCcard($params)
    {
        $dbh = $this->ant->dbh;

        if(isset($params['customer_id']))
            $cid = $params['customer_id'];
        else
            $cid = $this->user->getAereusCustomerId();
                
        if (is_numeric($cid) && is_numeric($params['ccard_exp_month']) && is_numeric($params['ccard_exp_year']))
        {
            // Clean out existing cards
            $dbh->Query("delete from customer_ccards where customer_id=".$dbh->EscapeNumber($cid));
            
            // Insert new card
            $result = $dbh->Query("insert into customer_ccards(ccard_name, ccard_number, ccard_exp_month, ccard_exp_year, ccard_type, customer_id, enc_ver, f_default)
                                    values('".$dbh->Escape($params['ccard_name'])."', 
                                            '".$dbh->Escape(encrypt($params['ccard_number']))."', 
                                            '".$params['ccard_exp_month']."', 
                                            '".$params['ccard_exp_year']."', 
                                            '".$dbh->Escape($params['ccard_type'])."', 
                                            ".$dbh->EscapeNumber($cid).", 
                                            '1', 't'); select currval('customer_ccards_id_seq') as id;");
            $id = $dbh->GetValue($result, 0, "id");
            if ($id)
                $ret = $id;
            else
                $ret = -2;
        }
        else
            $ret = 0;

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Billing Get Credt Card
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function billingGetCcards($params)
    {
        $dbh = $this->ant->dbh;
        
        if(isset($params['customer_id']))
            $cid = $params['customer_id'];
        else
            $cid = $this->user->getAereusCustomerId();
            
        $maskedCc = null;
        $ret = array();
        if ($cid)
        {
            $result = $dbh->Query("select id, ccard_number, ccard_name, ccard_exp_month, ccard_exp_year, ccard_type, enc_ver, f_default from customer_ccards 
                                    where customer_id='".$dbh->Escape($cid)."'");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i);
                $last_four = "";
                $ccard_num = decrypt($row['ccard_number']);
                if ($row['ccard_number'])
                    $last_four = substr($ccard_num, strlen($ccard_num)-4);
                    
                for($x=0; $x<=strlen($ccard_num); $x++)
                {
                    if($x >= (strlen($ccard_num) - 4))
                        $maskedCc .= substr($ccard_num, $x, 1);
                    else
                        $maskedCc .= "*";
                }
                
                $ret[] = array("id" => $row['id'], "type" => $row['ccard_type'], "last_four" => $last_four, 
                                "number" => decrypt($row['ccard_number']), "ccard_exp_month" => $row['ccard_exp_month'], 
                                "ccard_exp_year" => $row['ccard_exp_year'], "ccard_name" => $row['ccard_name'], "maskedCc" => $maskedCc);
            }
        }
        else
            $ret = array("error"=>"customer_id is a required param");
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get Cases
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function getCases($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['customer_id'])
        {
            $ret = array();            
            $olist = new CAntObjectList($dbh, "case", $this->user);
            $olist->addCondition("and", "customer_id", "is_equal", $params['customer_id']);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);                
                $ret[] = array("id" => $obj->id, "title" => rawurlencode($obj->getValue("title")), "ts_entered" => rawurlencode($obj->getValue("ts_entered")),
                                "status" => rawurlencode($obj->getForeignValue("status_id")));
            }
        }
        else
            $ret = array("error"=>"customer_id is a required param");
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Authenticate User
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function authChallenge($params)
    {
        $dbh = $this->ant->dbh;
        
        $result = $dbh->Query("select customer_id, username, password from customer_publish where username='".$dbh->Escape($params['username'])."'");
        if( $dbh->GetNumberRows($result)<=0 )
        {
            // account not found
            $ret = array("result_code" => -1, "ret_val" => -1, "error"=>"Invalid username.");
        }
        else
        {
            $row = $dbh->GetNextRow($result, 0);
            if($params['password'] != $row['password'])
            {
                // invalid password
                $ret = array("result_code" => -10, "ret_val" => -2, "error"=>"Invalid password.");
            }
            else
            {
                // valid username and password
                $ret = array("result_code" => 1, "ret_val"=>$row['customer_id'], "error" => "");
            }
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Customer Register
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function customerRegister($params)
    {
        $dbh = $this->ant->dbh;
        
        if( $params['onlychange']==1 )
        {
            // update customer record
            // check duplicates
            $result = $dbh->Query("select customer_id, username from customer_publish where 
                                    username='".$dbh->Escape($params['username'])."' and customer_id!='".$dbh->Escape($params['customer_id'])."'");
            if( $dbh->GetNumberRows($result)<=0 )
            {
                // Customer name existed
                $ret = array("result_code" => -1, "ret_val" => -1, "error"=>"Account with the specified user name already exists!");
            }
            else{
                // update record
                $dbh->Query("update customer_publish set username='".$dbh->Escape($params['username'])."', password='".$params['password']."'
                                where customer_id = '".$dbh->Escape($params['customer_id'])."'");
                $ret = array("result_code" => 1, "ret_val" => $params['customer_id'], "error"=>"");
            }
        }
        else if ($params['username'])
        {            
            // insert customer
            $result = $dbh->Query("select customer_id, username from customer_publish where username='".$dbh->Escape($params['username'])."'");
            if($dbh->GetNumberRows($result)>0)
            {
                // Customer name existed
                $ret = array("result_code" => 10, "ret_val" => -2, "error"=>"Unable to update customer record. The username is already in use");                
            }
            else
            {
                $rs_cust = $dbh->Query("select id FROM customers where email='".$dbh->Escape($params['username'])."' OR email2='".$dbh->Escape($params['username'])."'");
                
                if($dbh->GetNumberRows($rs_cust)>0)
                {
                    $new_customer_id = $dbh->GetValue($rs_cust, 0, "id")    ;
                }
                else
                {
                    // insert new record
                    $cust = new CAntObject($dbh, "customer", null, $USER);
                    $cust->setValue('first_name', $_REQUEST['first_name']);
                    $cust->setValue('last_name', $_REQUEST['last_name']);
                    $cust->setValue('email', $_REQUEST['username']);
                    $new_customer_id = $cust->save();
                }
                if( $new_customer_id<=0 )
                {
                    // failed creating customer lead                    
                    $ret = array("result_code" => -1, "ret_val" => -1, "error"=>"Unable to create customer record");
                }
                else
                {
                        $dbh->Query("insert into customer_publish(customer_id,username,password)
                                    values ('".$new_customer_id."','".$dbh->Escape($params['username'])."', '".$params['password']."')");                    
                    
                    $ret = array("result_code" => 1, "ret_val" => $new_customer_id, "error"=>"");
                }
            }
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Authenticate - Get Customer Id
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function authGetCustId($params)
    {
        $dbh = $this->ant->dbh;
        
        $result = $dbh->Query("select customer_id from customer_publish where username='".$dbh->Escape($params['username'])."'");
        if($dbh->GetNumberRows($result)<=0)
            $ret = -1;
        else
            $ret = $dbh->GetValue($result, 0, "customer_id");
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Authenticate - Get Customer Id
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function authSetPassword($params)
    {
        $dbh = $this->ant->dbh;
        
        if (is_numeric($params['customer_id']) && $params['password'])
        {
            $dbh->Query("update customer_publish set password='".$dbh->Escape($params['password'])."' where customer_id='".$params['customer_id']."'");
            $ret = 1;
        }            
        else
            $ret = array("error"=>"customer_id and password are required params");
            
        $this->sendOutputJson($ret);        
        return $ret;        
    }
    
    /**
    * Get Billing Address    
    */
    public function getBillingAddress()
    {
        $cid = $this->ant->getAereusCustomerId();

        $objApi = new AntApi_Object($this->server, $this->userName, $this->password, "customer");
        $objApi->open($cid);
        $ret = array("street" => $objApi->getValue("billing_street"),
                    "street2" => $objApi->getValue("billing_street2"),
                    "city" => $objApi->getValue("billing_city"),
                    "state" => $objApi->getValue("billing_state"),
                    "zip" => $objApi->getValue("billing_zip"));
        
        return $this->sendOutputJson($ret);
    }
    
    /**
    * Get Billing Address    
    */
    public function saveBillingAddress($params)
    {
        $cid = $this->ant->getAereusCustomerId();
        
        $objApi = new AntApi_Object($this->server, $this->userName, $this->password, "customer");
        $objApi->open($cid);
        $objApi->setValue("billing_street", $params["street"]);
        $objApi->setValue("billing_street2", $params["street2"]);
        $objApi->setValue("billing_city", $params["city"]);
        $objApi->setValue("billing_state", $params["state"]);
        $objApi->setValue("billing_zip", $params["zip"]);
        $ret = $objApi->save();
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save Credit Card
    */
    public function saveCreditCard($params)
    {
        $cid = $this->ant->getAereusCustomerId();

		// Should never happen but just in case
		if (!$cid)
        	return $this->sendOutputJson(0);
        
        $custApi = new AntApi_Customer($this->server, $this->userName, $this->password);
        $custApi->open($cid);
        $ret = $custApi->addCreditCard($params['ccard_name'], $params['ccard_number'], $params['ccard_exp_month'], $params['ccard_exp_year'], 'visa');
        
        return $this->sendOutputJson($ret);
    }
    
    /**
    * Get Credit Card
    */
    public function getCreditCard($params)
    {
        $cid = $this->ant->getAereusCustomerId();
        
        $custApi = new AntApi_Customer($this->server, $this->userName, $this->password);
        $custApi->open($cid);
        $ret = $custApi->getCreditCards();
        
        if(sizeof($ret)==0 || $ret==false)
            $ret[] = array();
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
	 * DEPRICATED - use Admin/getEditionAndUsage
    * Get Edition
    public function getEdition()
    {
        $objList = new AntApi_ObjectList($this->server, $this->userName, $this->password, "user");
        $objList->addCondition("and", "active", "is_equal", "t");        
        $objList->addCondition("and", "id", "is_greater", "0");
        $objList->getObjects();
        $num = $objList->getNumObjects();        
        $ret = array("num" => $num, "name" => $this->ant->getEditionName(), "desc" => $this->ant->getEditionDesc());
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    */
    
    public function accountHistory()
    {
        $dbh = $this->ant->dbh;
        //new AntApi_ObjectList()
        
        // invoice status
        $query = "select * from customer_invoice_status";
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetRow($result, $i);
            
            $id = $row['id'];
            $invoiceStatus[$id] = $row['name'];
        }
        
        $dbh->FreeResults($result);
        
        // account history
        $ret = array();
        $cid = $this->ant->getAereusCustomerId();
        
        $objList = new AntApi_ObjectList($this->server, $this->userName, $this->password, "invoice");        
        $objList->addCondition("and", "customer_id", "is_equal", "$cid");        
        $objList->addSortOrder("ts_updated", "DESC");
        $objList->getObjects(0, 12);
        $num = $objList->getNumObjects();
        
        for ($i = 0; $i < $num; $i++)
        {
            $obj = $objList->getObject($i);
            
            $id = $obj->getValue['id'];
            $name = $obj->getValue['name'] . " - " . date("m/d/y", strtotime($obj->getValue['ts_updated']));
            $statusId = $obj->getValue['status_id'];
            $price = $obj->getValue['amount'];
            if(!$price)
                $price = 0;
            $ret[$id] = array("name" => $name, "status" => $invoiceStatus[$statusId], "price" => $price);
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
	
	/**
     * Customer Register
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function sendEmail($params)
    {
        $dbh = $this->ant->dbh;

		$cust = null;
		if ($params['customer_id'])
			$cust = CAntObject::factory($dbh, "customer", $params['customer_id'], $this->user);

		$sendTo = ($cust) ? $cust->getDefaultEmail() : $params['to'];

		if (!$sendTo)
			return $this->sendOutput(array("error"=>"No recipient defined!"));

		// Create new email
		$emailObj = CAntObject::factory($dbh, "email_message", null, $this->user);
		$emailObj->setupSMTP(null, true);
		$emailObj->setHeader("subject", $params['subject']);

		if ($params['template_id'])
		{
			$template = CAntObject::factory($dbh, "html_template", $params['template_id'], $this->user);

			if ($template->getValue("body_html"))
				$emailObj->setBody($template->getValue("body_html"), "html");
			else
				$emailObj->setBody($template->getValue("body_plain"), "plain");
		}
		else if ($params['body_plain'])
		{
			$emailObj->setBody($params['body_plain'], "plain");
		}
		else if ($params['body_html'])
		{
			$emailObj->setBody($params['body_html'], "html");
		}

		// Check if we are in test mode. No emails will actually be sent.
		$emailObj->testMode = ($params['testmode']=='t') ? true : false;
			
		// From
		if ($params['from'])
			$emailObj->setHeader("from", $params['from']);
		else
			$emailObj->setHeader("from", $this->ant->getEmailNoReply(true));

		// To
		$emailObj->setHeader("to", $sendTo);

		// Cc
		if ($params['cc'])
			$emailObj->setHeader("cc", $params['cc']);

		// Bcc
		if ($params['bcc'])
			$emailObj->setHeader("bcc", $params['bcc']);


		// Handle merge fields like <%first_name%>
		$emailObj->setMergeFields($cust, $params);
		
		// Check for unsub
		$send = true;
		if ($cust)
			if ($cust->getValue("f_noemailspam") == 't' || $cust->getValue("f_nocontact") == 't')
				$send = false;

		if ($send)
			$finished = $emailObj->send(false); // false = do not save a full copy of this message if sending bulk

		if ($cust && $cust->id)
		{
			$emailObj->addActivity("sent", "Automated Email Sent - " . $emailObj->getValue('subject'), "Email was sent to $sendTo", 
										 null, null, 't', null, 4);

		}

		if ($params['testmode'])
			return $this->sendOutput($emailObj->testModeBuf);
		else
			return $this->sendOutput(1);
	}
}
