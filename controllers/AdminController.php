<?php
require_once("lib/Dacl.php");
require_once("lib/aereus.lib.php/CAntCustomer.php");
require_once("lib/CAntObjectFields.php");
require_once('security/security_functions.php');

/**
* Actions for interacting with Admin Controller
*/
class AdminController extends Controller
{
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;        
        UserLogAction($ant->dbh, $user->id);
        
    }

    /**
    * Add Domain
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function domainAdd($params)
    {        
        $domain = $params['name'];

        if ($domain)
        {
			$antsys = new AntSystem();
			$antsys->addEmailDomain($this->ant->accountId, $domain);
            $ret = $domain;
        }
        else
            $ret = array("error"=>"domain is a required param");
            
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Delete Domain
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function domainDelete($params)
    {
		$antsys = new AntSystem();
        $dbh = $this->ant->dbh;
        $did = $params['did'];

        if ($did)
        {
			// Make sure we are not deleting the default domain
			$def_domain = $this->ant->settingsGet("email/defaultdomain");
			if ($did == $def_domain)
				return $this->sendOutputJson(array("error"=>"You cannot delete the default domain"));

			// Remove local email accounts attached to this domain
			$dbh->Query("DELETE FROM email_accounts WHERE address like '%@$did';");

			// Delete the domain from the antsystem
			$antsys->deleteEmailDomain($this->ant->accountId, $did);
            $ret = 1;
        }
        else
            $ret = array("error"=>"did is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set Domain Default
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function domainSetDefault($params)
    {
        $domain = $params['name'];
        
        if ($domain)        // Update specific event
        {
            $this->ant->settingsSet("email/defaultdomain", $domain);
            $ret = $domain;
        }
        else
            $ret = array("error"=>"domain is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get Domains
    */
    public function getDomains()
    {
        $dbh = $this->ant->dbh;
        
        $ret = array();
        $result = $dbh->Query("select domain, default_domain from email_domains");
        $num = $dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetRow($result, $i);            
            $ret[] = array("domain" => $row['domain'], "default_domain" => $row['default_domain']);
        }
        $dbh->FreeResults($result);
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save General
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveGeneral($params)
    {
        if(isset($params['applicationLogoId']))
        $this->ant->settingsSet("general/header_image", $params['applicationLogoId']);
            
        if(isset($params['publicImageId']))
            $this->ant->settingsSet("general/header_image_public", $params['publicImageId']);
        
        if(isset($params['loginImageId']))
            $this->ant->settingsSet("general/login_image", $params['loginImageId']);
            
        if(isset($params['welcomeImageId']))
            $this->ant->settingsSet("general/welcome_image", $params['welcomeImageId']);
            
        if(isset($params['orgName']))
            $this->ant->settingsSet("general/company_name", $params['orgName']);
            
        if(isset($params['website']))
            $this->ant->settingsSet("general/company_website", $params['website']);

        $this->ant->settingsSet("email/noreply", $params['noreply']);
            
        if(isset($params['paymentGateway']))
            $this->ant->settingsSet("/general/paymentgateway", $params['paymentGateway']);
            
        if(isset($params['authDotNetLogin']))
            $this->ant->settingsSet("/general/paymentgateway/authdotnet/login", encrypt($params['authDotNetLogin']));
            
        if(isset($params['authDotNetKey']))
            $this->ant->settingsSet("/general/paymentgateway/authdotnet/key", encrypt($params['authDotNetKey']));
            
        if(isset($params['pmtLinkPointStore']))
            $this->ant->settingsSet("/general/paymentgateway/linkpoint/store", encrypt($params['pmtLinkPointStore']));
            
        if(isset($params['pmtLinkPointPem']))
            $this->ant->settingsSet("/general/paymentgateway/linkpoint/pem", encrypt($params['pmtLinkPointPem']));
        
        $ret = 1;
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
	 * @depriacted
    * Save Wizard Account
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveWizardAccount($params)
    {        
        $dbh = $this->ant->dbh;
        
        if(isset($params['company_name']))
            $this->ant->settingsSet("general/company_name", $params['company_name']);
        
        if(isset($params['company_website']))
            $this->ant->settingsSet("general/company_website", $params['company_website']);
            
        if(isset($params['login_file_id']))
            $this->ant->settingsSet("general/login_image", $params['login_file_id']);
            
        if(isset($params['welcome_file_id']))
            $this->ant->settingsSet("general/welcome_image", $params['welcome_file_id']);
            
        if(isset($params['email_inc_server']))
            $this->ant->settingsSet("email/incoming_server", $params['email_inc_server']);
            
        if(isset($params['email_mode']))
            $this->ant->settingsSet("email/mode", $params['email_mode']);
            
        $this->ant->settingsSet("general/acc_wizard_run", "t");

        if(isset($params['email_domain']))        // Update specific event
        {
			$this->ant->settingsSet("email/defaultdomain", $params['email_domain']);

            // Add to mail system if it does not exist
			$antsys = new AntSystem();
			$antsys->addEmailDomain($this->ant->accountId, $params['email_domain']);
        }
                
        if(isset($params['users']))
        {            
            foreach ($params['users'] as $usr)
            {
                $parts = explode("|", $usr);

                $query = "insert into users(name, full_name, password)
                            values('".$dbh->Escape($parts[0])."', '".$dbh->Escape($parts[0])."',
                                   '".$dbh->Escape(md5($parts[1]))."');";                                   
                
                $dbh->Query($query);
            }
        }
    
        // Save account questionnaire
        $custid = $this->user->getAereusCustomerId();
        $custapi = new AntApi_Object("aereus.ant.aereus.com", "administrator", "Password1", "customer");
        if ($custid)
            $custapi->open($custid);
        $notes = $custapi->getValue("notes");
        $notes .= "\n";
        
        if(isset($params['hear_about_us']))
        $notes .= "Heard about us: " . $params['hear_about_us'] . "\n";
        
        if(isset($params['business_do']))
        $notes .= "About: " . $params['business_do'] . "\n";
        
        if(isset($params['crm_currently_using']))
            $notes .= "Current CRM: " . $params['crm_currently_using'] . "\n";
            
        if(isset($params['access_export_csv']))
            $notes .= "Access to export to CSV: " . $params['access_export_csv'] . "\n";
        
        if(isset($params['database_consist_of']))    
            $notes .= "Database consists of: " . $params['database_consist_of'] . "\n";
            
        if(isset($params['how_many_crm_users']))
            $notes .= "Number of CRM users: " . $params['how_many_crm_users'] . "\n";
            
        if(isset($params['what_can_ant_do']))
            $notes .= "What ANT can do: " . $params['what_can_ant_do'] . "\n";
            
        if(isset($params['feature_interest']))
            $notes .= "Features interested in: " . $params['feature_interest'];
            
        $custapi->setValue("notes", $notes);
        $custapi->save();
        
        $ret = 1;
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save Wizard User
	* @depriacted because we no longer use the user wizard
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveWizardUser($params)
    {
        $dbh = $this->ant->dbh;
        
        if (isset($params['email_address']))        // Update specific event
        {
            $dbh->Query("delete from email_accounts where user_id='" . $this->user->id . "' and f_default='t'");            
            $dbh->Query("insert into email_accounts(user_id, name, address, reply_to, f_default) 
                        values('" . $this->user->id . "', '".$dbh->Escape($params['email_display_name'])."', 
                        '".$dbh->Escape($params['email_address'])."', '".$dbh->Escape($params['email_replyto'])."', 't');");
            $ret = 1;
        }
        else
            $ret = array("error"=>"Email Address is a required param", "step" => 2);
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save results and billing info for a renewed account
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function renewTestCcard($params)
    {
        $custid = $this->user->getAereusCustomerId();
        if ($custid)
        {            
            $custapi = new CAntCustomer("aereus.ant.aereus.com", "administrator", "Password1");
            $custapi->open($custid);
            $ret = $custapi->testCreditCard($params['ccard_name'], $params['ccard_number'], 
                                                 $params['ccard_exp_month'], $params['ccard_exp_year'], $params['ccard_type']);
            if ((int)$ret < 1)
                $ret = array("message"=>$custapi->lastErrorMessage);
            else
                $ret = array("message"=>"Card is valid");
        }
        else
        {
            $ret = array("error"=>"There was a problem with your account. Please contact Aereus Support at (800) 974-5061 for assistance.");            
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
	 * @depriacted
    * Save results and billing info for a renewed account
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function renewAccount($params)
    {
        $custid = $this->user->getAereusCustomerId();

        $custapi = new CAntCustomer("aereus.ant.aereus.com", "administrator", "Password1");
        if ($custid)
            $custapi->open($custid);
            
        $custapi->setAttribute("billing_street", $params['address_street']);
        $custapi->setAttribute("billing_street2", $params['address_street2']);
        $custapi->setAttribute("billing_city", $params['address_city']);
        $custapi->setAttribute("billing_state", $params['address_state']);
        $custapi->setAttribute("billing_zip", $params['address_zip']);
        $custapi->setAttribute("stage_id", 14); // Set stage to customer
        $custapi->addCreditCard($params['ccard_name'], $params['ccard_number'], $params['ccard_exp_month'], $params['ccard_exp_year'], $params['ccard_type']);
        $custapi->saveChanges();
                
        $this->ant->settingsSet("general/trial_expired", "f");

        if ($params['ccard_number'])
        {
            $headers = array();
            $headers['From'] = AntConfig::getInstance()->email['noreply'];
            $headers['To'] = "mike.mercer@aereus.com";
            $headers['Cc'] = "sky.stebnicki@aereus.com";
            $headers['Subject'] = "Trial Renewed";
            $message = "Customer number $custid has entered their credit card information";
            
            $email = new Email();
            $status = $email->send($headers['To'], $headers, $body);
            unset($email);
        }
        
        $ret = 1;
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Saves Billing Information from settings
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function updateBilling($params)
    {
        $custid = $this->ant->getAereusCustomerId();

		// Should never happen, but just in case
		if (!$custid)
        	return $this->sendOutputJson(0);

		$api = new AntApi(AntConfig::getInstance()->aereus['server'], 
							  AntConfig::getInstance()->aereus['user'],
							  AntConfig::getInstance()->aereus['password']);
		
		$custapi = $api->getCustomer($custid);
        $custapi->setValue("billing_street", $params['address_street']);
        $custapi->setValue("billing_street2", $params['address_street2']);
        $custapi->setValue("billing_city", $params['address_city']);
        $custapi->setValue("billing_state", $params['address_state']);
        $custapi->setValue("billing_zip", $params['address_zip']);
		$custapi->save();

		$custapi->addCreditCard(
			$params['ccard_name'], 
			$params['ccard_number'], 
			$params['ccard_exp_month'], 
			$params['ccard_exp_year'], 
			"" // no type required?
		);
			
		/*
        $custapi = new CAntCustomer("aereus.ant.aereus.com", "administrator", "Password1");
        if ($custid)
            $custapi->open($custid);
        $custapi->setAttribute("billing_street", $params['address_street']);
        $custapi->setAttribute("billing_street2", $params['address_street2']);
        $custapi->setAttribute("billing_city", $params['address_city']);
        $custapi->setAttribute("billing_state", $params['address_state']);
        $custapi->setAttribute("billing_zip", $params['address_zip']);
        $custapi->setAttribute("ant_billsusp", "f");
        //$custapi->setAttribute("stage_id", 14); // Set stage to customer
        $custapi->addCreditCard($params['ccard_name'], $params['ccard_number'], $params['ccard_exp_month'], $params['ccard_exp_year']);
        $custapi->saveChanges();
		 */
                
        $this->ant->settingsSet("general/suspended_billing", "f");

        return $this->sendOutputJson(1);
    }    
    
    /**
     * Gets the Account Setting
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getSetting($params)
    {        
        if (isset($params['get']))
		{
			if (is_array($params['get']))
			{
				$ret = array();
				foreach ($params['get'] as $key)
				{
            		$ret[$key] = $this->ant->settingsGet($key);
				}
			}
			else
			{
            	$ret = $this->ant->settingsGet($params['get']);
			}
		}
        else
            $ret = array("error"=>"get is a required param");
            
        $this->sendOutputJson($ret);
        return $ret;
    } 
    
    /**
     * Sets the Account Setting
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function setSetting($params)
    {   
        if (isset($params['set']))
			if (is_array($params['set']))
			{
				$ret = array();
				foreach ($params['set'] as $key)
				{
            		$ret[$key] = $this->ant->settingsSet($key, $params[$key]);
				}
			}
			else
			{
            	$ret = $this->ant->settingsSet($params['set'], $params['val']);
			}
        else
            $ret = array("error"=>"set is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get the applications
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getApplications()
    {
        $dbh = $this->ant->dbh;
        
        $query = "select * from applications order by sort_order, scope";
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            
            $DACL = new Dacl($dbh, "applications/".$row['name']);
            // Grant default permissions
            if (!$DACL->id)
            {
                $DACL = new Dacl($dbh, "applications/".$row['name'], true);
                $DACL->grantGroupAccess(GROUP_ADMINISTRATORS, "Full Control"); // Make sure administrators have full control
                $DACL->grantGroupAccess(GROUP_EVERYONE, "Load Application"); // Give read access to everyone, admin and creator owner added already
                $DACL->grantGroupAccess(GROUP_CREATOROWNER, "Load Application"); // Give read access to everyone, admin and creator owner added already
				$DACL->save();
            }
            
            $id = $row['id'];
            $ret[$id] = array("id" => $id, "title" => $row['title'], "name" => $row['name'], "dacl" => $DACL->id, "scope" => $row['scope']);
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Create application
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function createApplication($params)
    {
        $dbh = $this->ant->dbh;
        
        $title = $params['title'];
        $shortTitle = substr($title, 0, 16);
        $name = strtolower($title) . "_app";
        $name = preg_replace("[^a-z0-9]", "_", $name);
        
        $query = "select * from applications where name = '$name'";
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        
        if($num==0)
        {
            $result = $dbh->Query("insert into applications(name, short_title, title, scope, f_system, user_id) 
                                values('".$dbh->Escape($name)."', '".$dbh->Escape($shortTitle)."', '".$dbh->Escape($title)."',
                                'user', 'f', ". $this->user->id .");
                                select currval('applications_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $id = $dbh->GetValue($result, 0, "id");
                $ret = array("id" => $id, "title" => $title, "name" => $name, "scope" => 'user');
            }                
        }
        else
        {
            // Return the application info if already exist
            $row = $dbh->GetNextRow($result, 0);
            $ret = $row;
            $ret["scope"] = $row["user"];
        }
        
        $dbh->FreeResults($result);
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Delete Application
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteApplication($params)
    {
        $dbh = $this->ant->dbh;
        $appId = $params['appId'];
        
        if($appId)
        {
            $query = "delete from applications where id = '$appId'";
            $dbh->Query($query);
            $ret = 1;
        }
        else
            $ret = array("error"=>"appId is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get general settings
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getGeneralSetting()
    {
        $dbh = $this->ant->dbh;
        
        $ret['applicationLogoId'] = $this->ant->settingsGet("general/header_image");
        $ret['publicImageId'] = $this->ant->settingsGet("general/header_image_public");
        $ret['loginImageId'] = $this->ant->settingsGet("general/login_image");
        $ret['welcomeImageId'] = $this->ant->settingsGet("general/welcome_image");
        $ret['orgName'] = $this->ant->settingsGet("general/company_name");
        $ret['website'] = $this->ant->settingsGet("general/company_website");
        $ret['noreply'] = $this->ant->getEmailNoReply();
        $ret['paymentGateway'] = $this->ant->settingsGet("/general/paymentgateway");
        $ret['themeName'] = $this->user->themeName;
        
        $ret['authDotNetLogin'] = decrypt($this->ant->settingsGet("/general/paymentgateway/authdotnet/login"));
        $ret['authDotNetKey'] = decrypt($this->ant->settingsGet("/general/paymentgateway/authdotnet/key"));

        $ret['pmtLinkPointStore'] = decrypt($this->ant->settingsGet("/general/paymentgateway/linkpoint/store"));
        $ret['pmtLinkPointPem'] = decrypt($this->ant->settingsGet("/general/paymentgateway/linkpoint/pem"));
        
        $ret['pmtgw'][] = array("id" => PMTGW_AUTHDOTNET, "name" => "Authorize.net");
        $ret['pmtgw'][] = array("id" => PMTGW_LINKPOINT, "name" => "LinkPoint / FirstData Global Gateway");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Add alias entry
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function aliasAdd($params)
    {
        if(isset($params['address']))
        {
            $address = $params['address'];
            $antsys = new AntSystem();
            
            $insertMode = true;
            if(empty($params['insertMode']))
            {
                $insertMode = false;
                
                // remove the existing alias if not insert mode
                $antsys->deleteAlias($this->user->accountId, $params['currentAlias']);
            }            
            
            $ret = $antsys->addAlias($this->user->accountId, $address, $params['gotoAddress'], $insertMode);
        }
        else
            $ret = array("error"=>"address is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Delete alias entry
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function aliasDelete($params)
    {
        if(isset($params['address']))
        {
            $address = $params['address'];
            $antsys = new AntSystem();
            $ret = $antsys->deleteAlias($this->user->accountId, $address);
        }
        else
            $ret = array("error"=>"address is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

	/**
    * Get edition and usage
    */
    public function getEditionAndUsage()
    {
		$ret = array("edition" => $this->ant->getEdition(), 
					 "editionName" => $this->ant->getEditionName(), 
					 "editionDesc" => $this->ant->getEditionDesc(),
					 "usageDesc" => "Users: ". $this->ant->getNumUsers(),

		);
        
        return $this->sendOutputJson($ret);
    }

	/**
    * Change edition for this account
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function setAddition($params)
    {
		if (!is_numeric($params['edition']))
			return $this->sendOutputJson(array("error"=>"Edition required"));

		// TODO: check security
		$ret = $this->ant->setEdition($params['edition']);

		return $this->sendOutputJson(array("success"=>"Edition has been updated"));
	}

	/**
	 * Get the no-reply address for this account
	 *
	 * @param array $params An associative array of params passed to all action
	 */
	public function getNoReply($params)
	{
		$this->sendOutput($this->ant->getEmailNoReply());
	}
}
