<?php
require_once(dirname(__FILE__).'/../lib/Email.php');
require_once(dirname(__FILE__).'/../lib/AntMail/Account.php');
require_once(dirname(__FILE__).'/../lib/Controller.php');
require_once('AdminController.php');

class EmailController extends Controller
{
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;        
        $this->emailUserName = $user->getEmailUserName();
    }
    
    /**
    * save the general settings        
    */
    public function getEmailSettings($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        $themeName = ($userId) ? UserGetTheme($dbh, $userId, 'name') : "ant_skygrey";
        
        $ret = array();        
        $ret["gsSaveData"]["retVal"] = 1;
                
        // get checkbox data - general settings
        $ret["gsSaveData"]["checkSpelling"] = $this->user->getSetting("email/compose/checkspelling");
        $ret["gsSaveData"]["addRecipients"] = $this->user->getSetting("email/compose/addrecipients");
        
        // get default font -  general settings
        $ret["gsSaveData"]["fontFaceCompose"] = $this->user->getSetting("email/compose/font_face");
        $ret["gsSaveData"]["fontSizeCompose"] = $this->user->getSetting("email/compose/font_size");
        $ret["gsSaveData"]["fontColorCompose"] = $this->user->getSetting("email/compose/font_color");
        $ret["gsSaveData"]["fontFaceRead"] = $this->user->getSetting("email/compose/read_font_face");
        $ret["gsSaveData"]["fontSizeRead"] = $this->user->getSetting("email/compose/read_font_size");
        $ret["gsSaveData"]["fontColorRead"] = $this->user->getSetting("email/compose/read_font_color");
        
        // get forwarding data - general settings
        $ret["gsSaveData"]["forwarding"] = $this->user->getSetting("email/forwarding");
        $ret["gsSaveData"]["forwardingTo"] = $this->user->getSetting("email/forwarding/to");
        $ret["gsSaveData"]["forwardingAction"] = $this->user->getSetting("email/forwarding/action");
        
        // get email accounts
		$accounts = $this->user->getEmailAccounts(null, true);
		foreach ($accounts as $acctData)
		{
            if($acctData["f_default"])
                $acctData["sf_default"] = "Yes";
            else
                $acctData["sf_default"] = "No";

            $ret["accounts"][$acctData["id"]] = $acctData;
		}
        
        // get move to folder - filter settings
        $result = $dbh->Query("select id, name from email_mailboxes where user_id='$userId'  order by flag_special DESC, name");
        $num = $dbh->GetNumberRows($result);
        $ret["fsSaveData"]["moveToFolder"][] = array("id" => "", "name" => "None");
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $ret["fsSaveData"]["moveToFolder"][] = $row;
        }
        $dbh->FreeResults($result);
        
        // get current filters - filter settings
        $result = $dbh->Query("select id, name, kw_subject, kw_to, kw_from, kw_body, act_mark_read, act_move_to from email_filters where user_id='$userId'");
        $num = $dbh->GetNumberRows($result);
        $ret["fsSaveData"]["currentFilters"] = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $row["theme_name"] = "$themeName";
            
            if($row["act_mark_read"]=="t")
                $row["act_mark_read"] = 1;
            else
                $row["act_mark_read"] = 0;
            
            $ret["fsSaveData"]["currentFilters"][$row["id"]] = $row;
        }
        $dbh->FreeResults($result);
        
        // get signature - signature settings
        $result = $dbh->Query("select id, name, use_default, signature from email_signatures where user_id='$userId'");
        $num = $dbh->GetNumberRows($result);
        $ret["ssSaveData"]["mySignatures"] = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $row["theme_name"] = "$themeName";
            
            if($row["use_default"]=="t")
                $row["use_default"] = "Yes";
            else
                $row["use_default"] = "No";
                
            //$row['signature'] = htmlentities(stripslashes($row['signature']), ENT_COMPAT, "UTF-8");
            $row['signature'] = stripslashes($row['signature']);
            $ret["ssSaveData"]["mySignatures"][$row["id"]] = $row;
        }
        $dbh->FreeResults($result);
        
        // get Video Email Theme - theme settings
        $result = $dbh->Query("select * from email_video_message_themes where (user_id='$userId' or scope='global')");        
        $num = $dbh->GetNumberRows($result);
        $ret["tsSaveData"]["themes"] = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $row["theme_name"] = "$themeName";
            
            if($row['header_file_id'])
                $row['header_file_name'] = " " . UserFilesGetFileName($dbh, $row['header_file_id']);
            
            if($row['footer_file_id'])
                $row['footer_file_name'] = " " . UserFilesGetFileName($dbh, $row['footer_file_id']);
                
            if($row['button_off_file_id'])
                $row['button_file_name'] = " " . UserFilesGetFileName($dbh, $row['button_off_file_id']);
            
            $ret["tsSaveData"]["themes"][$row["id"]] = $row;
        }
        $dbh->FreeResults($result);
        
        // get Blaclist and Whitelist - Spam Settings
        $result = $dbh->Query("select * from email_settings_spam where user_id='$userId' order by value");
        $num = $dbh->GetNumberRows($result);
        $ret["msSaveData"]["blacklist_from"] = array();
        $ret["msSaveData"]["whitelist_from"] = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            
            // Preference either "blacklist_from" or "whitelist_from"
            $ret["msSaveData"][$row["preference"]][$row["value"]] = $row;
        }
        $dbh->FreeResults($result);
        
        $this->sendOutputJson($ret);
        return true;
    }
    
    /**
    * save the general settings        
    */
    public function saveGeneralSettings($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        
        $this->user->setSetting("email/compose/font_face", $params['fontFaceCompose']);
        $this->user->setSetting("email/compose/font_size", $params['fontSizeCompose']);
        $this->user->setSetting("email/compose/font_color", $params['fontColorCompose']);
        $this->user->setSetting("email/compose/read_font_face", $params['fontFaceRead']);
        $this->user->setSetting("email/compose/read_font_size", $params['fontSizeRead']);
        $this->user->setSetting("email/compose/read_font_color", $params['fontColorRead']);        

        $this->user->setSetting("email/compose/checkspelling", $params['checkSpelling']);
        $this->user->setSetting("email/compose/addrecipients", $params['addRecipients']);
        
        // Forwarding
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        $userDefaultEmail = $this->user->getEmail();
        
        if((string)$params['forwardMessage']=="true")
        {
            $params["address"] = $userDefaultEmail;
            $params["currentAlias"] = $userDefaultEmail;
            
            if($params['forwardingAction']=="keep_inbox")
                $params["gotoAddress"] = "$userDefaultEmail, " . $params['forwardingTo'];
            else
                $params["gotoAddress"] = $params['forwardingTo'];
            
            $adminController->aliasAdd($params);
            $forwarding = "on";
        }            
        else
        {
            $params["address"] = $userDefaultEmail;
            $adminController->aliasDelete($params);
            $forwarding = "off";
        }
        
        $this->user->setSetting("email/forwarding", $forwarding);
        $this->user->setSetting("email/forwarding/to", $params['forwardingTo']);
        $this->user->setSetting("email/forwarding/action", $params['forwardingAction']);

        $this->sendOutputJson($params);
        return true;
    }
    
    /**
    * save the account settings        
    */
    public function saveAccountSettings($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        //$themeName = ($userId) ? UserGetTheme($dbh, $userId, 'name') : "ant_skygrey"; // TODO: I don't think we need this at all
        $id = null;

		// Update all existing email accounts to not be the default for this user if this is the default
        if($params['f_default']==1)
        {
            //$dbh->Query("update email_accounts set f_default='f' where user_id='$userId'");

            // Setup the new service locator
            $sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
            $entityLoader = $sl->get("EntityLoader");

            // Create the query for email_account
            $query = new \Netric\EntityQuery("email_account");

            // Filter the query that we will only get the email accounts for specific $userId
            $query->where("user_id")->equals($userId);

            // Execute the query
            $results = $index->executeQuery($query);
            $totalNum = $results->getTotalNum();

            // Loop over total num - the results will paginate as needed
            for ($i = 0; $i < $totalNum; $i++) {

                // Get each filterd email accounts
                $entity = $results->getEntity($i);

                // If we have an entity, then let's set the f_default to false
                if ($entity) {
                    $entity->setValue("f_default", false);
                    $entityLoader->save($entity);
                }
            }
        }

        
        // Instantiate Email Account
		$aid = (isset($params['accountId']) && $params['accountId']>0) ? $params['accountId'] : null;
        $accountObj = new AntMail_Account($dbh, $aid);

		// Adding a custom account - not a system account
		if (!$aid)
			$accountObj->fSystem = false;

    	if(isset($params['type']))
			$accountObj->type = $params['type'];
    	if(isset($params['name']))
			$accountObj->name = $params['name'];
    	if(isset($params['email_address']))
			$accountObj->emailAddress = $params['email_address'];
    	if(isset($params['reply_to']))
			$accountObj->replyTo = $params['reply_to'];
    	if(isset($params['signature']))
			$accountObj->signature = $params['signature'];
        if($params['f_default']==1)
            $accountObj->fDefault = true;

		// Incoming settings
    	if(isset($params['type']))
            $accountObj->type = $params['type'];
    	if(isset($params['username']))
			$accountObj->username = $params['username'];
    	if(isset($params['password']))
			$accountObj->password = $params['password'];
    	if(isset($params['host']))
            $accountObj->host = $params['host'];
    	if(isset($params['port']))
            $accountObj->port = $params['port'];
        if($params['ssl']==1)
            $accountObj->ssl = true;
	
		// Outbound smtp settings
    	if(isset($params['host_out']))
            $accountObj->hostOut = $params['host_out'];
        if($params['ssl_out']==1)
            $accountObj->sslOut = true;
    	if(isset($params['port_out']))
            $accountObj->portOut = $params['port_out'];
    	if(isset($params['username_out']))
            $accountObj->usernameOut = $params['username_out'];
    	if(isset($params['password_out']))
			$accountObj->passwordOut = $params['password_out'];
        
		$accountObj->userId = $this->user->id;
        $accountObj->save();

        $this->sendOutputJson($accountObj->toArray());
        return $accountId;
    }
    
    /**
     * Retrieves the email account information
     */
    public function retrieveEmailAccount($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        $whereClause = null;
        
        // Instantiate  Email Account
        $accountObj = new AntMail_Account($dbh, $this->user);
        //$ret = $accountObj->retrieveEmailAccount($params);
		$ret = $this->user->getEmailAccounts(null, true);
        
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
     * Get only the default account
     */
    public function getDefaultEmailAddress($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        $whereClause = null;
        
        // Instantiate  Email Account
        $accountObj = new AntMail_Account($dbh, $this->user);
		$ret = $this->user->getEmailAccounts(array("f_default"=>'t'), true);
        
		if (count($ret))
        	return $this->sendOutputJson($ret[0]['address']);
		else
        	return $this->sendOutputJson(array("error"=>"Default account not found"));
    }
    
    /**
    * delete the account settings        
    */
    public function deleteSendFromAccount($params)
    {
        $dbh = $this->ant->dbh;

        // Instantiate  Email Account
		if(isset($params['accountId']) && $params['accountId'] > 0)
		{
			$accountObj = new AntMail_Account($dbh, $params['accountId']);
			$ret = $accountObj->remove();
			//$ret = $accountObj->deleteEmailAccount($params);
		}
        
        $this->sendOutputJson($params);
        return $ret;
    }
    
    /**
    * save the filter setting
    */
    public function saveFilterSettings($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        $themeName = ($userId) ? UserGetTheme($dbh, $userId, 'name') : "ant_skygrey";
        
        if($params['filterId']>0)
        {
            $sql = "update email_filters set name='".$dbh->Escape($params['filterName'])."', 
                    kw_subject='".$dbh->Escape($params['subjectContains'])."', kw_to='".$dbh->Escape($params['toContains'])."', 
                    kw_from='".$dbh->Escape($params['fromContains'])."', kw_body='".$dbh->Escape($params['bodyContains'])."', 
                    act_mark_read='".(($params['markRead']==1) ? 't' : 'f')."', act_move_to=".$dbh->EscapeNumber($params['moveToFolder']).", 
                    user_id='$userId' where id='".$params['filterId']."';";                    
            
        }
        else
        {
            $sql = "insert into email_filters(name, kw_subject, kw_to, kw_from, kw_body, act_mark_read, act_move_to, user_id)
                    values('".$dbh->Escape($params['filterName'])."', '".$dbh->Escape($params['subjectContains'])."', '".$dbh->Escape($params['toContains'])."',
                            '".$dbh->Escape($params['fromContains'])."', '".$dbh->Escape($params['bodyContains'])."', 
                            '".(($params['markRead']==1) ? 't' : 'f')."', ".$dbh->EscapeNumber($params['moveToFolder']).", '$userId');
                            select currval('email_filters_id_seq') as id;";
        }
        
        $result = $dbh->Query($sql);
        
        if ($dbh->GetNumberRows($result))
        {
            $id = $dbh->GetValue($result, 0, "id");            
            $params['filterId'] = $id;
        }
        
        $dbh->FreeResults($result);
        $params['themeName'] = $themeName;
        $this->sendOutputJson($params);        
        return true;
    }
    
    /**
    * delete the email filter
    */
    public function deleteEmailFilter($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        
        $filterId = $params['filterId'];
        $dbh->Query("delete from email_filters where id='$filterId' and user_id='$userId'");
        
        $this->sendOutputJson($params);
        return true;
    }
    
    /**
    * save the signature setting
    */
    public function saveSignatureSettings($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        $themeName = ($userId) ? UserGetTheme($dbh, $userId, 'name') : "ant_skygrey";
        
        if($params['defaultSignature']==1)
            $dbh->Query("update email_signatures set use_default='f' where user_id='$userId'");
        
        if($params['signatureId']>0)
        {
            $sql = "update email_signatures set signature='".$dbh->Escape($params['signature'])."', 
                    name='".$dbh->Escape($params['signatureName'])."', use_default='".(($params['defaultSignature']==1) ? 't' : 'f')."' 
                    where id='".$dbh->Escape($params['signatureId'])."'";                    
            
        }
        else
        {
            $sql = "insert into email_signatures(name, signature, user_id, use_default) 
                    values('".$dbh->Escape($params['signature'])."', '".$dbh->Escape($params['signatureName'])."','$userId', '".(($params['defaultSignature']==1) ? 't' : 'f')."');
                    select currval('email_signatures_id_seq') as id;";
        }
        
        $result = $dbh->Query($sql);
        
        if ($dbh->GetNumberRows($result))
        {
            $id = $dbh->GetValue($result, 0, "id");            
            $params['signatureId'] = $id;
        }
        
        $dbh->FreeResults($result);
        $params['themeName'] = $themeName;
        
        $this->sendOutputJson($params);        
        return true;
    }
    
    /**
    * delete the signature
    */
    public function deleteSignature($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        
        $signatureId = $params['signatureId'];
        $dbh->Query("delete from email_signatures where id='$signatureId'");
        
        $this->sendOutputJson($params);
        return true;
    }
    
    /**
    * save the video email theme
    */
    public function saveThemeSettings($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        $themeName = ($userId) ? UserGetTheme($dbh, $userId, 'name') : "ant_skygrey";
        
        if($params['themeId']>0)
        {
            $sql = "update email_video_message_themes set name='".$dbh->Escape($params['name'])."', 
                    header_file_id=".$dbh->EscapeNumber($params['headerImageId']).",
                    footer_file_id=".$dbh->EscapeNumber($params['footerImageId']).",
                    button_off_file_id=".$dbh->EscapeNumber($params['buttonImageId']).",
                    scope='".$dbh->EscapeNumber($params['scope'])."',
                    background_color='".$dbh->Escape($params['backgroundColor'])."',
                    html='".$dbh->Escape(stripslashes($params['customHtml']))."'
                    where id='{$params['themeId']}'";
            
        }
        else
        {
            $sql = "insert into email_video_message_themes(name, header_file_id, footer_file_id, button_off_file_id, scope, html, background_color, user_id) 
                    values('".$dbh->Escape($params['name'])."', ".$dbh->EscapeNumber($params['headerImageId']).", 
                            ".$dbh->EscapeNumber($params['footerImageId']).", ".$dbh->EscapeNumber($params['buttonImageId']).",
                            '".$dbh->EscapeNumber($params['scope'])."', '".$dbh->Escape(stripslashes($params['customHtml']))."', 
                            '".$dbh->Escape($params['backgroundColor'])."', '$userId');
                    select currval('email_video_message_themes_id_seq') as id;";
        }
        
        $result = $dbh->Query($sql);
        
        if ($dbh->GetNumberRows($result))
        {
            $id = $dbh->GetValue($result, 0, "id");            
            $params['themeId'] = $id;
        }
        
        $dbh->FreeResults($result);
        $params['themeName'] = $themeName;
        
        $this->sendOutputJson($params);        
        return true;
    }
    
    /**
    * delete the Video Email Theme
    */
    public function deleteTheme($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        
        $themeId = $params['themeId'];
        $dbh->Query("delete from email_video_message_themes where id='$themeId'");
        
        $this->sendOutputJson($params);
        return true;
    }
    
    /**
    * save the spam setting - blacklist and whitelist
    */
    public function saveSpam($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        
        if(!empty($params['blacklist']))
        {
            $sql = "delete from email_settings_spam where user_id='$userId' and value='".$dbh->Escape($params['blacklist'])."'";
            $dbh->Query($sql);
            $sql = "insert into email_settings_spam(user_id, preference, value) values('$userId', 'blacklist_from', '".$dbh->Escape($params['blacklist'])."');
                    select currval('email_settings_spam_id_seq') as id;";
        }
        else if(!empty($params['whitelist']))
        {
            $sql = "delete from email_settings_spam where user_id='$userId' and value='".$dbh->Escape($params['whitelist'])."'";
            $dbh->Query($sql);
            $sql = "insert into email_settings_spam(user_id, preference, value) values('$userId', 'whitelist_from', '".$dbh->Escape($params['whitelist'])."');
                    select currval('email_settings_spam_id_seq') as id;";            
        }
        
        $result = $dbh->Query($sql);
        
        if ($dbh->GetNumberRows($result))
        {
            $id = $dbh->GetValue($result, 0, "id");
            $params['spamId'] = $id;
        }
        
        $this->sendOutputJson($params);        
        return true;
    }
    
    /**
    * delete the spam setting - blacklist and whitelist
    */
    public function deleteSpam($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        
        $spamId = $params['spamId'];
        $dbh->Query("delete from email_settings_spam where id='$spamId' and user_id='$userId'");
        
        $this->sendOutputJson($params);
        return true;
    }
    
    /**
    * Save Default Email
    */
    public function saveDefaultEmail($params)
    {
        $dbh = $this->ant->dbh;
        
        if(isset($params['emailAddress']) && $params['emailAddress'])        // Update specific event
        {            
            $uid = ($params['uid']) ? $params['uid'] : $this->user->id;
            $dbh->Query("delete from email_accounts where user_id='$uid' and f_default='t'");            

			$accountObj = new AntMail_Account($dbh);
			$accountObj->fDefault = true;
			$accountObj->userId = $uid;

			if(isset($params['type']))
				$accountObj->type = $params['type'];
			if(isset($params['displayName']))
				$accountObj->name = $params['displayName'];
			if(isset($params['emailAddress']))
				$accountObj->emailAddress = $params['emailAddress'];
			if(isset($params['replyTo']))
				$accountObj->replyTo = $params['replyTo'];

			$ret = $accountObj->save();

			/*
            $result = $dbh->Query("insert into email_accounts(user_id, name, address, reply_to, f_default) 
                        values('$uid', '".$dbh->Escape($params['displayName'])."', 
                                '".$dbh->Escape($params['emailAddress'])."', 
                                '".$dbh->Escape($params['replyTo'])."', 't');
                        select currval('email_accounts_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
                $ret = $dbh->GetValue($result, 0, "id");
			 */
        }
        else
            $ret = array("error"=>"email_address is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get email data that can be used to convert to other data types when creating new object from email
    */
    public function getConvFields($params)
    {
        $dbh = $this->ant->dbh;
        
        $MID = $params['mid'];
        if ($MID)
        {
			$msg = CAntObject::factory($dbh, "email_message", $MID, $this->user);

			$msgBody = $msg->getBody();
			$sent_from = $msg->getValue("sent_from");
			$sent_from_addr = EmailAdressGetDisplay($msg->getValue("sent_from"), 'address');
			$sent_from_uid = UserGetUserIdFromAddress($dbh, $sent_from_addr);
			$sent_from_uname = UserGetFullName($dbh, $user_id);
			$display_from = ($sent_from_uid) ? UserGetFullName($dbh, $sent_from_uid) : EmailAdressGetDisplay($sent_from, 'name');

			if (strpos($display_from, " ")!== false)
			{
				$parts = explode(" ", $display_from);
				$fname = $parts[0];
				$lname = $parts[1];
			}
			else
			{
				$fname = $display_from;
				$lname = "";
			}
            
            $ret = array(
                "subject" => $msg->getValue("subject"), 
                "body" => $msgBody, 
                "body_txt" => htmlToPlainText($msgBody), 
                "first_name" => $fname, 
                "last_name" => $lname, 
                "email" => $sent_from_addr,
                "customer_id" => "",
            );
            
            // Customers
            // --------------------------------------------------------------
            if ($sent_from_addr)
            {
                $olist = new CAntObjectList($dbh, "customer", $this->user);
                $olist->addCondition('and', "email", "contains", $sent_from_addr);
                $olist->addCondition('or', "email2", "contains", $sent_from_addr);
                $olist->getObjects(0, 10);
                for ($m = 0; $m < $olist->getNumObjects(); $m++)
                {
                    $obj = $olist->getObject($m);
                    $ret["customer_id"] = array($obj->id);
                }
            }
        }
        else
            $ret = array("error"=>"email_address is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Thread Get Messages
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function threadGetMessages($params)
    {        
        $dbh = $this->ant->dbh;
        
        if ($params['tid'])
        {
            $olist = new CAntObjectList($dbh, "email_message", $this->user);
			$olist->addMinField("id");
			$olist->addMinField("sent_from");
			$olist->addMinField("subject");
			$olist->addMinField("message_date");
			$olist->addMinField("flag_seen");
			$olist->addCondition("and", "thread", "is_equal", $params['tid']);
			$olist->addOrderBy("message_date", "asc");
			$olist->getObjects();
			// If not active then look for deleted
			if (!$olist->getNumObjects())
			{
				$olist->addCondition("and", "f_deleted", "is_equal", 't');
				$olist->getObjects();
			}
			for ($i = 0; $i < $olist->getNumObjects(); $i++)
			{
				$row = $olist->getObjectMin($i);

				$ret[] = array(
					"id" => $row['id'], 
					"from" => $row['sent_from'], 
					"flag_seen" => $row['flag_seen'],
					"subject" => $row['subject'], 
					"message_date" => $row['message_date'],
				);
			}
        }
        else
            $ret = array("error"=>"tid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Mark Read
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function markRead($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                if ($obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->getValue("owner_id"))?true:false))
                {
					$obj->markRead();
					/*
                    if ($params['obj_type'] == "email_thread")
                        EmailMarkThread($dbh, $obj->id, "read");
                    else
                        EmailMarkMessageRead($dbh, $obj->id);
					 */
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type, objects are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Mark Unread
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function markUnread($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                if ($obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->getValue("owner_id"))?true:false))
                {
					$obj->markRead(false);
					/*
                    if ($params['obj_type'] == "email_thread")
                        EmailMarkThread($dbh, $obj->id, "unread");
                    else
                        EmailMarkMessageUnread($dbh, $obj->id);
					 */
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type, objects are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Mark Flag
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function markFlag($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                if ($obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->getValue("owner_id"))?true:false))
                {
					$obj->markFlag();
					/*
                    if ($params['obj_type'] == "email_thread")
                        EmailMarkThread($dbh, $obj->id, "flagged");
                    else
                        EmailMarkMessageFlagged($dbh, $obj->id);
					 */
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type, objects are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Mark Unflag
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function markUnflagged($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                if ($obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->getValue("owner_id"))?true:false))
                {
					$obj->markFlag(false);
					/*
                    if ($params['obj_type'] == "email_thread")
                        EmailMarkThread($dbh, $obj->id, "unflagged");
                    else
                        EmailMarkMessageUnflagged($dbh, $obj->id);
					 */
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type, objects are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Mark Junk
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function markJunk($params)
    {
        $dbh = $this->ant->dbh;        
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
				$obj->markSpam();

				$spamGrp = $obj->getGroupingEntryByName("mailbox_id", "Junk Mail");

				if ($spamGrp['id'])
				{
					$obj->move($spamGrp['id']);
				}
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type, objects are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Mark Not Junk
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function markNotjunk($params)
    {
        $dbh = $this->ant->dbh;        
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            // Get special groups/mailboxes
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
				$obj->markSpam(false);

				$spamGrp = $obj->getGroupingEntryByName("mailbox_id", "Junk Mail");
				$inboxGrp = $obj->getGroupingEntryByName("mailbox_id", "Inbox");

				if ($inboxGrp['id'])
				{
					$obj->move($inboxGrp['id'], $spamGrp['id']);
				}
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type, objects are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Thread - Get Groups
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function threadGetGroups($params)
    {        
        $dbh = $this->ant->dbh;        
        if ($params['tid'])
        {
            $ret = array();
            $result = $dbh->Query("select email_thread_mailbox_mem.mailbox_id, email_mailboxes.name, 
                                    email_mailboxes.color, email_mailboxes.flag_special  from email_thread_mailbox_mem, email_mailboxes
                                    where email_thread_mailbox_mem.thread_id='".$params['tid']."' 
                                    and email_mailboxes.id=email_thread_mailbox_mem.mailbox_id");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i);                
                
                $ret[] = array("id" => $row['mailbox_id'], "name" => $row['name'], 
                                "color" => $row['color'], "flag_special" => $row['flag_special']);
            }
        }
        else
            $ret = array("error"=>"tid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Thread - Add Group
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function threadAddGroup($params)
    {
        $dbh = $this->ant->dbh;        
        
        if ($params['tid'] && $params['gid'])
        {
            $obj = new CAntObject($dbh, "email_thread", $params['tid'], $this->user);
            $obj->setMValue("mailbox_id", $params['gid']);
            $obj->save();
            $groups = $obj->getValue("mailbox_id");
            $ret = $params['tid']." - ".var_export($groups, true);
        }
        else
            $ret = array("error"=>"tid, gid are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Thread Delete Group
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function threadDeleteGroup($params)
    {
        $dbh = $this->ant->dbh;        
            
        if ($params['tid'] && $params['gid'])
        {
            // Instantiate Object
            $obj = new CAntObject($dbh, "email_thread", $params['tid'], $this->user);
            
            // Get special groups/mailboxes
            $trashGrp = $obj->getGroupingEntryByName("mailbox_id", "Trash");
            $sentGrp = $obj->getGroupingEntryByName("mailbox_id", "Sent");
            
            if ($params['gid'] == $sentGrp["id"]) // cannot remove from sent
                $ret = array("error"=>"Sent group cannot be removed");
            else
            {
                $obj->removeMValue("mailbox_id", $params['gid']);
                $obj->save(false);
                
                // Now move messages if the thread is a member of multiple groups fallback to one of those groups
                $groups = $obj->getValue("mailbox_id");
                if (is_array($groups) && count($groups))
                {
                    // Now move any messages that are in this mailbox
                    foreach ($groups as $gid)
                    {
                        $move_to = $gid;
                        if ($gid != $trashGrp['id'] && $gid!=$sentGrp['id'])
                        {
                            $move_to = 0;
                            break;
                        }
                    }

                    if ($move_to)
                    {
						$olist = new CAntObjectList($dbh, "email_message", $this->user);
						$olist->addMinField("id");
						$olist->addCondition("and", "thread", "is_equal", $params['tid']);
						$olist->addCondition("and", "mailbox_id", "is_equal", $params['gid']);
						$olist->getObjects();
						for ($i = 0; $i < $olist->getNumObjects(); $i++)
						{
							$email = $olist->getObject($i);
                            $email->move($move_to);
						}
                    }
                    $ret = 1;
                }
            }
        }
        else
            $ret = array("error"=>"tid, gid are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get Associations
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getAssociations($params)
    {        
        $dbh = $this->ant->dbh;
        
        if ($params['mid'])
        {
            $ret = array();
            $msg = new CAntObject($dbh, "email_message", $params['mid']);
            $sent_from_addr = strtolower(EmailAdressGetDisplay($msg->getValue("sent_from"), 'address'));
            $headers = EmailGetHeaders($dbh, $params['mid'], "X-ANT-");

            if ($headers['X-ANT-ASSOCIATIONS'])
            {
                $assoc_arr = explode(",", $headers['X-ANT-ASSOCIATIONS']);

                foreach ($assoc_arr as $assoc)
                {
                    $parts = explode(":", $assoc);
                    $obj = new CAntObject($dbh, $parts[0], $parts[1], $this->user);
                    
                    $ret[] = array("label" => $obj->title.": ".$obj->getName(), "obj_ref" => $parts[0].":".$parts[1]);
                }
            }
            // Single object associations
            if ($headers['X-ANT-OBJ'] && $headers['X-ANT-OID'])
            {
                $obj = new CAntObject($dbh, $headers['X-ANT-OBJ'], $headers['X-ANT-OID'], $this->user);                
                $ret[] = array("label" => $obj->title.": ".$obj->getName(), "obj_ref" => $headers['X-ANT-OBJ'].":".$headers['X-ANT-OID']);
            }

            // Now look for address matches
            if ($sent_from_addr)
            {
                // Contacts
                // --------------------------------------------------------------
                $olist = new CAntObjectList($dbh, "contact_personal", $this->user);
                $olist->addCondition('and', "user_id", "is_equal", $this->user->id);
                $olist->addCondition('and', "email", "contains", $sent_from_addr);
                $olist->addCondition('or', "email2", "contains", $sent_from_addr);
                $olist->getObjects(0, 10);
                $a_num = $olist->getNumObjects();
                for ($m = 0; $m < $a_num; $m++)
                {
                    $obj = $olist->getObject($m);                    
                    $ret[] = array("label" => $obj->title.": ".$obj->getName(), "obj_ref" => "contact_personal:".$obj->id);
                }

                // Customers
                // --------------------------------------------------------------
                $olist = new CAntObjectList($dbh, "customer", $this->user);
                $olist->addCondition('and', "email", "contains", $sent_from_addr);
                $olist->addCondition('or', "email2", "contains", $sent_from_addr);
                $olist->getObjects(0, 10);
                $a_num = $olist->getNumObjects();
                for ($m = 0; $m < $a_num; $m++)
                {
                    $obj = $olist->getObject($m);
                    $ret[] = array("label" => $obj->title.": ".$obj->getName(), "obj_ref" => "customer:".$obj->id);
                }
            }
        }
        else
            $ret = array("error"=>"mid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get User Image
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getEmailUserImage($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['email'])
        {
            $sent_from_addr = strtolower(EmailAdressGetDisplay($params['email'], 'address'));

            // First check for user
            $sent_from_uid = UserGetUserIdFromAddress($dbh, $sent_from_addr);
            if ($sent_from_uid)
                $ret = UserGetImage($dbh, $sent_from_uid);

            // Now check for contact
            if (empty($ret))
            {
                $sent_from_cid = ContactGetIdFromAddress($dbh, $this->user->id, $sent_from_addr);
                if ($sent_from_cid)
                    $ret = ContactGetImage($dbh, $sent_from_cid);
            }
            
            if(empty($ret))
                $ret = array("error"=>"Email user image was not found.");
        }
        else
            $ret = array("error"=>"email is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save Video Mail
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveVideoMail($params)
    {
        $dbh = $this->ant->dbh;
        
        $result = $dbh->Query("insert into email_video_messages(user_id, file_id, logo_file_id, title, f_template_video, subtitle, message, footer, theme, name, facebook, twitter)
                               values('".$this->user->id."', ".$dbh->EscapeNumber($params['video_file_id']).", ".$dbh->EscapeNumber($params['logo_file_id']).",
                                      '".$dbh->Escape($params['title'])."', '".(($params['f_template_video']=='t')?'t':'f')."',
                                       '".$dbh->Escape($params['subtitle'])."', '".$dbh->Escape($params['message'])."',
                                      '".$dbh->Escape($params['footer'])."', '".$dbh->Escape($params['theme'])."', '".$dbh->Escape($params['save_template_name'])."',
                                      '".$dbh->Escape($params['facebook'])."', '".$dbh->Escape($params['twitter'])."');
                                select currval('email_video_messages_id_seq') as id;");
        if ($dbh->GetNumberRows($result))
        {
            $ret = $dbh->GetValue($result, 0, "id");

            if ($params['template_id'] && $params['save_template_changes']=='t' && $ret)
            {
                $res2 = $dbh->Query("select name from email_video_messages where id='".$params['template_id']."';");
                if ($dbh->GetNumberRows($res2))
                {
                    $name = stripslashes($dbh->GetValue($res2, 0, "name"));

                    if ($name)
                    {
                        $dbh->Query("update email_video_messages set name=NULL where id='".$params['template_id']."';");
                        $dbh->Query("update email_video_messages set name='".$dbh->Escape($name)."' where id='$ret';");
                    }
                }
            }

            // Move temp file
            if ($params['save_template_name'] && $params['f_template_video'] == 't')
                $path = "%emailattachments%/vmail/templates";
            else
                $path = "%emailattachments%/vmail/hosted";

            $folder = $antfs->openFolder($path, true);
            if ($folder && $params['video_file_id'] && $params['f_video_is_tmp']=='t')
                $antfs->moveTmpFile($params['video_file_id'], $folder);

            if ($params['buttons'])
            {
                foreach ($params['buttons'] as $btn)
                {
                    $parts = explode("|", $btn);
                    $dbh->Query("insert into email_video_message_buttons(label, link, message_id) 
                                    values('".$dbh->Escape($parts[0])."', '".$dbh->Escape($parts[1])."', '$ret');");
                }
            }
        }
        else
            $ret = array("error"=>"Error occurred while saving video email.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get Video Mail Templates
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getVmailTemplates()
    {
        $dbh = $this->ant->dbh;
        
        $result = $dbh->Query("select id, file_id, logo_file_id, f_template_video, title, subtitle, message, footer, theme, name, facebook, twitter 
                                from email_video_messages where user_id='" . $this->user->id . "' and name is not null and name!=''
                                order by name");
        $num = $dbh->GetNumberRows($result);
        $ret = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetRow($result, $i);
            
            $res2 = $dbh->Query("select id, label, link from email_video_message_buttons where message_id='".$row['id']."'");
            $num2 = $dbh->GetNumberRows($res2);
            $buttons = array();
            for ($j = 0; $j < $num2; $j++)
            {
                $row2 = $dbh->GetRow($res2, $j);
                $buttons[] = array("id" => $row2['id'], 
                                    "label" => $row2['label'],
                                    "link" => $row2['link']);
            }
            
            $ret[] = array("id" => $row['id'], 
                            "file_id" => $row['file_id'], 
                            "file_name" => UserFilesGetFileName($dbh, $row['file_id']),
                            "logo_file_id" => $row['logo_file_id'], 
                            "log_file_name" => UserFilesGetFileName($dbh, $row['logo_file_id']),
                            "title" => $row['title'], 
                            "subtitle" => $row['subtitle'],
                            "message" => $row['message'],
                            "facebook" => $row['facebook'],
                            "twitter" => $row['twitter'],
                            "footer" => $row['footer'],
                            "theme" => $row['theme'],
                            "name" => $row['name'],
                            "buttons" => $buttons);
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Delete Video Mail Template
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteVmailTemplate($params)
    {
        $dbh = $this->ant->dbh;
        
        $tid = $params['tid'];
        if ($tid)
        {
            $dbh->Query("update email_video_messages set name=null where id='".$tid."'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"tid is a required param.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get Video Mail Themes
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getVmailThemes()
    {
        $dbh = $this->ant->dbh;
        
        $result = $dbh->Query("select id, name from email_video_message_themes where (user_id='" . $this->user->id . "' or scope='global')");
        $num = $dbh->GetNumberRows($result);
        
        $ret = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetRow($result, $i);            
            $ret[] = array("id" => $row['id'], "name" => $row['name']);
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Move Message
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function moveMessage($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['mailbox_id'] && $params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            // Get special groups/mailboxes
            //$junkid = EmailGetSpecialBoxId($dbh, $this->user->id, "Junk Mail");
            //$sentid = EmailGetSpecialBoxId($dbh, $this->user->id, "Sent");
            //$trashid = EmailGetSpecialBoxId($dbh, $this->user->id, "Trash");

            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
				$obj->move($params['mailbox_id']);

				/*
                $un_ident = ($params['obj_type'] == "email_thread") ? "thread" : "id";

                if ($params['mailbox_id'] == $trashid)
                {
                    $obj->remove();
                }
                else
                {
                    EmailMoveMessage($dbh, $un_ident, $obj->id, $params['mailbox_id'], $sentid, $trashid);
                }
				 */
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"mailbox_id, obj_type, and objects are required params.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
	 * DEPRICATED: use generic object groupings now
    * remove the associated mailbox
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    public function removeMailbox($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        $mailId = $params['mailId'];
        $mailboxId = $params['mailboxId'];
        
        if($mailId > 0 && $mailboxId > 0)
        {
            $obj = new CAntObject($dbh, "email_thread", $mailId);        
            $obj->removeMValue('mailbox_id', $mailboxId);
            $obj->save(false);
            $ret = 1;
        }
        else
            $ret = array("error"=>"mailId and mailboxId are required params.");
            
        $this->sendOutput(json_encode($ret));
    }
    */
    
    /**
	 * DEPRICATED: use generic object groupings now
    * Group Set Color
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    public function groupSetColor($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];
        $color = $params['color'];

        if ($gid && $color)
        {
            $dbh->Query("update email_mailboxes set color='$color' where id='$gid'");
            $ret = $color;
        }
        else
            $ret = array("error"=>"gid, and color are required params.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    */
    
    /**
	 * DEPRICATED: use generic object groupings now
    * Group Rename
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    public function groupRename($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];
        $name = rawurldecode($params['name']);

        if ($gid && $name)
        {
            $dbh->Query("update email_mailboxes set name='".$dbh->Escape($name)."' where id='$gid'");
            $ret = $name;
        }
        else
            $ret = array("error"=>"gid, and name are required params.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    */
    
    /**
	 * DEPRICATED: use generic object groupings now
    * Group Delete
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    public function groupDelete($params)
    {
        $dbh = $this->ant->dbh;
        $gid = $params['gid'];

        if ($gid)
        {
            $dbh->Query("delete from email_mailboxes where id='$gid'");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"gid is a required param.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    */
    
    /**
	 * DEPRICATED: use generic object groupings now
    * Group Add
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    public function groupAdd($params)
    {
        $dbh = $this->ant->dbh;
        
        $pgid = ($params['pgid'] && $params['pgid'] != "null") ? "'".$params['pgid']."'" : "NULL";
        $name = rawurldecode($params['name']);

        if ($name)
        {
            $query = "insert into email_mailboxes(parent_box, name, color, user_id) 
                      values($pgid, '".$dbh->Escape($name)."', '".$params['color']."', '".$this->user->id."');
                      select currval('email_mailboxes_id_seq') as id;";
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);

                $ret = $row['id'];
            }
            else
                $ret = array("error"=>"Error occured while adding group");
        }
        else
            $ret = array("error"=>"name is a required param.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    */
    
    /**
    * Accept Calendar Share
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function acceptCalShare($params)
    {
        $dbh = $this->ant->dbh;
        $share_id = $params['share_id'];
        
        if ($share_id)
        {
            if ($dbh->GetNumberRows($dbh->Query("select id from calendar_sharing where calendar='".$share_id."' and user_id='".$this->user->id."'")))
            {
                $dbh->Query("update calendar_sharing set accepted='t', f_view='t' where 
                             calendar='".$share_id."' and user_id='".$this->user->id."'");
            }
            else
            {
                $dbh->Query("insert into calendar_sharing(accepted, f_view, calendar, user_id)
                             values('t', 't', '".$share_id."', '".$this->user->id."')");
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"share_id is a required param.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Accept Congrp Share
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function acceptCongrpShare($params)
    {
        $dbh = $this->ant->dbh;
        $share_id = $params['share_id'];
        
        if ($share_id)
        {
            $dbh->Query("insert into contacts_personal_label_share(user_id, label_id)
                                 values('".$this->user->id."', '".$share_id."')");
            $ret = 1;
        }
        else
            $ret = array("error"=>"share_id is a required param.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Association with customer
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function assocWithObj($params)
    {
        $dbh = $this->ant->dbh;
        $emailUsername = $this->emailUserName;
        
        $MID = $params['mid'];
        $OID = $params['object_id'];
        $obj_type = $params['obj_type'];
        
        if ($MID && $OID && $obj_type)
        {
            $msg = CAntObject::factory($dbh, "email_message", $MID, $this->user);
            $direction = ($emailUsername == $msg->getValue("sent_from")) ? 'o' : 'i';

            $obja = new CAntObject($dbh, "activity");
            $obja->setValue("name", $msg->getValue("subject"));
            $obja->setValue("notes", $msg->getPlainTextBody());
            $obja->setValue("direction", $direction);
            $obja->setValue("f_readonly", 't');
            $obja->setValue("user_id", $this->user->id);
            $obja->addAssociation($obj_type, $OID, "associations");
            $obja->addAssociation("email_message", $MID, "associations");
            $obja->setValue("obj_reference", "email_message:".$MID);
            $obja->setValue("type_id", $msg->getActivityTypeFromObj());
            $obja->setValue("level", '5');
            $obja->save();

            $ret = 1;
        }
        else
            $ret = array("error"=>"mid, object_id and obj_type are required params.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Send Email
    *
    * @param array $params    An assocaitive array of parameters passed to this function.
    */
    public function sendEmail($params)
    {        
        $dbh = $this->ant->dbh;
        
        if(!empty($params['objects']))
            $params['objects'] = explode(",", $params['objects']);
        
        if(!empty($params['using']))
            $params['using'] = explode(",", $params['using']);
            
        if(!empty($params['uploaded_file']))
            $params['uploaded_file'] = explode(",", $params['uploaded_file']);
        
        if(!empty($params['email_attachments']))
        {
            $params['email_attachments'] = explode(",", $params['email_attachments']);
            
            if(is_array($params['uploaded_file']))
                $params['uploaded_file'] = array_merge($params['uploaded_file'], $params['email_attachments']);
            else
                $params['uploaded_file'] = $params['email_attachments'];
        }
        
        $data = array(
                "user_id" => $this->user->id,
                "use_account" => $params['use_account'],
                "cmp_to" => $params['cmp_to'],
                "cmp_cc" => $params['cmp_cc'],
                "cmp_bcc" => $params['cmp_bcc'],
                "cmp_subject" => $params['cmp_subject'],
                "cmpbody" => $params['cmpbody'],
                "uploaded_file" => $params['uploaded_file'],
                "objects" => $params['objects'],
                "obj_type" => $params['obj_type'],
                "using" => $params['using'],
                "in_reply_to" => $params['in_reply_to'],
                "message_id" => $params['message_id'],
                "TESTING" => $params['testing'],
                );

        $wp = new WorkerMan($dbh);
        $ret = $wp->run("email/send", serialize($data));

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save Email
    *
    * @param array $params    An assocaitive array of parameters passed to this function.
    */
    public function saveEmail($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        $sentFrom = EmailGetUserName($dbh, $userId, 'address');
        $replyTo = EmailGetUserName($dbh, $userId, 'reply_to');        
        $mid = $params['mid'];
        $fileId = $params['fid'];

        $emailObj = CAntObject::factory($dbh, "email_message", $mid, $this->user);
        
		// TODO: handle email template
        // if fid is set, get the mid using file_id
        if(!$mid && $fileId > 0)
            $mid = $emailObj->getEmailIdUsingFileId($fileId);
        
        $emailObj->setGroup("Drafts"); 
        $emailObj->setHeader("Message-ID", $params['message_id']);
        $emailObj->setHeader("Subject", $params['cmp_subject']);        
        $emailObj->setHeader("To", $params['cmp_to']);        
        $emailObj->setHeader("Cc", $params['cmp_cc']);
        $emailObj->setHeader("Bcc", $params['cmp_bcc']);
        $emailObj->setHeader("Return-path", $replyTo);        
        $emailObj->setHeader("From", $sentFrom);
        
		// TODO handle email template
        //$emailObj->fid = $fileId;
		$emailObj->setValue("flag_seen", 't');
		$emailObj->setValue("flag_draft", 't');
        //$emailObj->f_seen = "t";        
        $emailObj->setBody($params['cmpbody'], "html");
        
        if(!empty($params['uploaded_file']))
            $params['uploaded_file'] = explode(",", $params['uploaded_file']);
        
        if(!empty($params['email_attachments']))
        {
            $params['email_attachments'] = explode(",", $params['email_attachments']);
            
            if(is_array($params['uploaded_file']))
                $params['uploaded_file'] = array_merge($params['uploaded_file'], $params['email_attachments']);
            else
                $params['uploaded_file'] = $params['email_attachments'];
        }
        
        if (is_array($params['uploaded_file']) && count($params['uploaded_file']))
        {
            foreach ($params['uploaded_file'] as $fid)
            {
                //$emailObj->tmp_file_attachments[] = $fid;
				$emailObj->addAttachmentAntFsTmp($fid);
            }
        }
        
        $mid = $emailObj->save();
        
        $ret = array("mid" => $mid, "tid" => $emailObj->getValue("thread"));
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get Reply/Forward Data
    *
	* @depricated No longer used with new CAntObject_Email object taking its place
    * @param array $params    An assocaitive array of parameters passed to this function.
	 */
    public function getReplyForward($params)
    {
        $dbh = $this->ant->dbh;        
        $userId = $this->user->id;
        $replyTo = $params['reply_mid'];
        $replyType = ($params['reply_type']) ? $params['reply_type'] : 'reply';        
        $ret = array();
        
        if($replyTo)
        {
			$msg = CAntObject::factory($dbh, "email_message", $replyTo, $this->user);
			$tmp_body = $msg->getBody();
			
			$ctype = ($msg->getValue('content_type')) ? $msg->getValue('content_type') : 'text/html';
			$ret['in_reply_to'] = $msg->getValue('message_id');

			$rawSentFrom = $msg->getValue('sent_from');
			$rawSendTo = $msg->getValue('send_to');
			$rawReplyTo = $msg->getValue('reply_to');
			
			$ret["t_reply_to"] = $rawSentFrom;
			$ret["t_send_to"] = $rawSendTo;                
			$ret["t_sent_from"] = $rawReplyTo;
			
			$decodedSentFrom = EmailDecodeMimeStr($rawSentFrom);
			$decodedSendTo = EmailDecodeMimeStr($rawSendTo);
			$decodedReplyTo = EmailDecodeMimeStr($rawReplyTo);
			
			if(empty($reply_to))
				$cmp_to = $decodedSentFrom;
			else
				$cmp_to = $decodedReplyTo;
			
			$send_to = EmailAdressGetDisplay($decodedSendTo, 'address');
			$sent_from = EmailAdressGetDisplay($decodedSentFrom, 'address');
			$subject = EmailDecodeMimeStr($msg->getValue('subject'));
			$return_path = $msg->getValue('return_path');
			$date_str = $msg->getValue('message_date');
			
			//if ($msg->body_type == "plain")
			if ($msg->getValue("body_type") == "plain" || $msg->getValue("body_type") == "text/plain")
				$tmp_body = str_replace("\n", "<br />", $tmp_body);

			if ($replyType == "reply" || $replyType == "reply_all")
			{
				$ret['body'] = "<br><br>
							<div style='color:#000000;'>
							----Original Message----<br>
							<blockquote style=\"border-left: 1px solid #0000FF; margin: 0pt 0pt 0pt 0.8ex; padding-left: 1ex;\">
							From: $sent_from<br>
							Date: $date_str<br>
							Subject: $subject<br>
							To: $send_to<br><br>
							$tmp_body
							</blockquote>
							</div>";
				$ret['subject'] = (strpos(strtolower($subject), "re:") === false) ? "RE: ".$subject : $subject;
				$ret['send_to'] = $send_to;
				
				// Populate To and CC if applicable                    
				if ($replyType == "reply_all")
				{
					
					$ret['cmp_to'] = "$cmp_to, $send_to";
					$ret['cmp_cc'] = $msg->getValue('cc');
					$ret['cmp_bcc'] = $msg->getValue('bcc');
				}
				else
				{
					$ret['cmp_to'] = $cmp_to;
					
					// Loop thru email accounts to check sent_from
					$emailAccounts = $this->getEmailAccounts();
					foreach($emailAccounts as $key=>$emailAccount)
					{
						if($emailAccount["email_address"] == $sent_from)
						{
							// sent_to will be used as "to" and sent_from will be used as sender's email
							$ret['cmp_to'] = $decodedSendTo;
							$ret['send_to'] = $sent_from;
							break;
						}
					}
				}
								
				// Get attachments form
				$attachments = $msg->getAttachments();
				foreach ($attachments as $att)
					$ret['attachment'][] = array("value" => $att->getValue('file_id'), "name" => $att->getValue("filename"));
			}

			if ($replyType == "forward")
			{
				$ret['body'] = "<br><br>
							<div style='color:#000000;'>
							----Forwarded Message----<br>
							From: $sent_from<br>
							Date: $date_str<br>
							Subject: $subject<br>
							To: $send_to<br><br>
							$tmp_body</div>";
							
				$ret['subject'] = "FWD: ".$subject;
								
				// Get attachments form
				$attachments = $msg->getAttachments();
				foreach ($attachments as $att)
				{
					// TODO: work with ms-tnf below?
					$ret['attachment'][] = array("value" => $att->getValue('file_id'), "name" => $att->getValue("filename"));
				}

				/*
				$query = "select id, filename, name, content_type, '' as attached_data, file_id from email_message_attachments where message_id='$replyTo' 
							and (disposition='attachment' or file_id is not null) and content_type!='application/ms-tnef'
							union all
							select id, filename, name, content_type, attached_data, file_id from email_message_attachments where message_id='$replyTo' 
							and file_id is null and content_type='application/ms-tnef'
							order by id";
							
				$att_result = $dbh->Query($query);
				$num = $dbh->GetNumberRows($att_result);
				for ($i = 0; $i < $num; $i++)
				{
					$row = $dbh->GetNextRow($att_result, $i);
					$name = ($row['filename']) ? $row['filename'] : $row['name'];

					if ($row['content_type'] == "application/ms-tnef")
					{
						$tnef = base64_decode($row['attached_data']);
						$attachment = new TnefAttachment(false);
						$fresult = $attachment->decodeTnef($tnef);
						$tnef_files = $attachment->getFilesNested();
						//print_r($tnef_files); // See the format of the returned array
						for ($m = 0; $m < count($tnef_files); $m++)
						{
							$file = $tnef_files[$m];

							if ($file->getType() != "application/rtf")
							{                                
								$ret['attachment'][] = array("value" => $row['file_id'], "name" => $file->getName());
							}
						}
					}
					else
					{                        
						$ret['attachment'][] = array("value" => $row['file_id'], "name" => $name);
					}

				}
				$dbh->FreeResults($att_result);
				 */
			}    
            
            // Make sure cmp is clean        
            $ret['cmp_to'] = str_replace("\n", '', $ret['cmp_to']);
            $ret['cmp_to'] = str_replace("\t", '', $ret['cmp_to']);
        }
        
        return $ret;
    }
    
    public function getDefaultSignature($params)
    {
        $dbh = $this->ant->dbh;        
        
        if (!$params['fid'] && !$params['mid'])
        {   
            $result = $dbh->Query("select signature from email_signatures where user_id='".$this->user->id."'
                                and use_default='t'");
                                
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $dbh->FreeResults($result);
                            
                $ret = stripslashes($row['signature']);
            }
            else
                $ret = array("error"=>"no signature found.");
        }        
        else
            $ret = array("error"=>"mid and fid are required params.");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get Email Details
    *
    * @param array $params    An assocaitive array of parameters passed to this function.
    */
    public function getEmailDetails($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        $ret['messageId'] = '<' . $_SERVER['REMOTE_PORT'] . '.' . $_SERVER['REMOTE_ADDR'] . '.' . time() . '.antmail@' . $_SERVER['SERVER_NAME'] .'>';
        $ret['userId'] = $this->user->id;

        $replyForward = $this->getReplyForward($params);
        $savedEmail = $this->getSavedEmail($params);

        if(sizeof($replyForward) > 0)
            $ret['messageDetails'] = $replyForward;
        else
            $ret['messageDetails'] = $savedEmail;
        
        if(!empty($params['objects']))
            $params['objects'] = explode(",", $params['objects']);
        
        if(!empty($params['using']))
            $params['using'] = explode(",", $params['using']);
        
        // Get Email Accounts
        $ret["emailAccounts"] = $this->getEmailAccounts();

        $ret['emailAddress'] = array();
        if ((is_array($params['objects']) || $params['all_selected']) && $params['obj_type'] && $params['send_method']==0)
        {
            if (is_array($params['using']))
            {
                $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
                $olist->processFormConditions($params);
                $olist->getObjects();
                $num = $olist->getNumObjects();
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $olist->getObject($i);

                    foreach ($params['using'] as $use)
                    {
                        $emailAddress = $obj->getValue($use, true);
                        
                        if($emailAddress == "email")
                            continue 2;

                        if(!empty($emailAddress))
                        {
                            if(!in_array($emailAddress, $ret['emailAddress']))
                                $ret['emailAddress'][] = $emailAddress;
                        }                            
                    }
                }
            }
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    private function getEmailAccounts()
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        // email account
		$accounts = $this->user->getEmailAccounts(null, true);
        $ret[0] = array('id' => 0, 'num' => count($accounts));
		foreach ($accounts as $acctData)
		{
            $ret[$acctData['id']] = $acctData;
		}
		/*
        $result = $dbh->Query("select id, name, address, reply_to, f_default, signature
                                from email_accounts where user_id='". $this->user->id ."' order by name");
        $num = $dbh->GetNumberRows($result);
        $ret[0] = array('id' => 0, 'num' => $num);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $id = $row['id'];            
            $ret[$id] = $row;
        }
        $dbh->FreeResults($result);
		*/
        
        return $ret;
    }
    
    /**
     * Get Saved Email
     *
     * @param array $params An assocaitive array of parameters passed to this function.
	 */
    public function getSavedEmail($params)
    {
        $fid = $params['fid'];
        $mid = $params['mid'];
        $cid = $params['cid'];
        $custid = $params['custid'];
        $automate = $params['automate'];
        $bulk = (is_array($params['customers']) || is_array($params['contacts']) || is_array($params['objects']) || $params['all_selected']) ? true : false;
        $dbh = $this->ant->dbh;
        
        $ret = array();
        if($fid || $mid)
        {
			$msg = CAntObject::factory($dbh, "email_message", $mid, $this->user);
			$antFs = new AntFs($dbh);
			$ret['cmp_to'] = $msg->getValue('send_to');
			$ret['cmp_cc'] = $msg->getValue('cc');
			$ret['subject'] = $msg->getValue('subject');
			$ret['tid'] = $msg->getValue('thread');
			$ctype = ($msg->getValue('content_type')) ? $msg->getValue('content_type') : 'text/html';
			
			$m_body = $msg->getBody();
			if ($msg->getValue("body_type") == "plain" || $msg->getValue("body_type") == "text/plain")
				$m_body = str_replace("\n", "<br />", $m_body);
				
			$ret['body'] = $m_body;
			
			// Get attachments form
			$attachments = $msg->getAttachments();
			foreach ($attachments as $att)
			{
				$ret['attachment'][] = array("value" => $att->getValue("file_id"), "name" => $att->getValue("filename"));
			}

			/*
			$query = "select id, filename, name, content_type, '' as attached_data, file_id from email_message_attachments where message_id='$mid' 
						and (disposition='attachment' or file_id is not null) and content_type!='application/ms-tnef'
						union all
						select id, filename, name, content_type, attached_data, file_id from email_message_attachments where message_id='$mid' 
						and file_id is null and content_type='application/ms-tnef'
						order by id";
			
			$att_result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($att_result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($att_result, $i);
				$name = ($row['filename']) ? $row['filename'] : $row['name'];

				if ($row['content_type'] == "application/ms-tnef")
				{
					$tnef = base64_decode($row['attached_data']);
					$attachment = new TnefAttachment(false);
					$fresult = $attachment->decodeTnef($tnef);
					$tnef_files = $attachment->getFilesNested();
					//print_r($tnef_files); // See the format of the returned array
					for ($m = 0; $m < count($tnef_files); $m++)
					{
						$file = $tnef_files[$m];

						if ($file->getType() != "application/rtf")   
							$ret['attachment'][] = array("value" => $row['id'], "name" => $file->getName());
					}
				}
				else
				{
					$file = $antFs->openFileById($row['file_id']);
					$fileId = $file->id;
					$fileName = $file->getValue("name");
					if($fileId > 0)
						$ret['attachment'][] = array("value" => $fileId, "name" => $fileName);
				}
			}
			$dbh->FreeResults($att_result);
			 */
                
			if ($automate && ($cid || $custid) && !$bulk)
			{
				if ($cid)
					$object = new CAntObject($dbh, "contact_personal", $custid, $cid);
				else if ($custid)
					$object = new CAntObject($dbh, "customer", $custid, $this->user->id);
				
				if ($object)
				{

					$matches = array();
					$iterations = 0; // for safety
					while (preg_match("/&lt;%(.*?)%&gt;/", $body, $matches))
					{
						$pull_var = $matches[1];

						// Check if this is an associated variable
						if (strpos($pull_var, '.') === false)
						{
							$type = $object->getFieldType($pull_var);

							if ($type['type'] == 'fkey' && $type['subtype'] == "users" && ($varname=='to' || $varname=='cc' || $varname=='bcc'))
							{
								$cmp_to = str_replace("<%$pull_var%>", UserGetEmail($this->dbh, $object->getValue($pull_var)), $cmp_to);
								$cmp_to = str_replace("&lt;%$pull_var%&gt;", UserGetEmail($this->dbh, $object->getValue($pull_var)), $cmp_to);
								$cmp_subject = str_replace("<%$pull_var%>", UserGetEmail($this->dbh, $object->getValue($pull_var)), $cmp_subject);
								$cmp_subject = str_replace("&lt;%$pull_var%&gt;", UserGetEmail($this->dbh, $object->getValue($pull_var)), $cmp_subject);
								$m_body = str_replace("<%$pull_var%>", UserGetEmail($this->dbh, $object->getValue($pull_var)), $m_body);
								$m_body = str_replace("&lt;%$pull_var%&gt;", UserGetEmail($this->dbh, $object->getValue($pull_var)), $m_body);
							}
							else
							{
								$cmp_to = str_replace("<%$pull_var%>", $object->getValue($pull_var, ($type['type'] == 'alias')?true:false), $cmp_to);
								$cmp_to = str_replace("&lt;%$pull_var%&gt;", $object->getValue($pull_var, ($type['type'] == 'alias')?true:false), $cmp_to);
								$cmp_subject = str_replace("<%$pull_var%>", $object->getValue($pull_var, ($type['type'] == 'alias')?true:false), $cmp_subject);
								$cmp_subject = str_replace("&lt;%$pull_var%&gt;", $object->getValue($pull_var, ($type['type'] == 'alias')?true:false), $cmp_subject);
								$m_body = str_replace("<%$pull_var%>", $object->getValue($pull_var, ($type['type'] == 'alias')?true:false), $m_body);
								$m_body = str_replace("&lt;%$pull_var%&gt;", $object->getValue($pull_var, ($type['type'] == 'alias')?true:false), $m_body);
							}
															
							$ret['cmp_to'] = $cmp_to;
							$ret['cmp_subject'] = $cmp_subject;
							$ret['body'] = $m_body;
						}

						// Prevent infinite loop
						$iterations++;
						if ($iterations > 5000)
							break;
					}

					if ($cmp_to)
						$ret['cmp_to'] = $object->getValue("email_default", true);
				}
        	}
        }
        return $ret;
    }
    
    /**
    * Get the email domains
    *
    * @param array $params    An assocaitive array of parameters passed to this function.
    */
    public function getEmails()
    {
        global $ANT;
        $antsys = new AntSystem();
        
        $dbh = $this->ant->dbh;
        
        $ret['defaultDomain'] = $this->ant->settingsGet("email/defaultdomain");
        $ret['themeName'] = UserGetTheme($dbh, $this->user->id, 'name');
        $ret['domains'] = $antsys->getEmailDomains($this->user->accountId);
        $ret['alias'] = $antsys->getEmailAliases($this->user->accountId);
        
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Get message body
    *
    * @param array $params    An assocaitive array of parameters passed to this function.
    */
    public function getMessageBody($params)
    {
        $dbh = $this->ant->dbh;

		if (!$params['mid'])
			return $this->sendOutputJson("message not found");

		$ret = array("body"=>"", "attachments"=>array());

		$msg = CAntObject::factory($dbh, "email_message", $params['mid'], $this->user);
		$m_body = $msg->getBody(true);

        $ret['cleanBody'] = $m_body;
		$ret['body'] = $m_body;
        
		$attachments = $msg->getAttachments();
		if (count($attachments))
		{
			foreach ($attachments as $att)
			{
				if ($att->getValue('content_id') && $att->getValue('disposition') == "inline")
					continue; // Hide, should be included in embedded images above

				// Get Name
				if (strlen($att->getValue('filename')))
					$attname = $att->getValue('filename');
				else
					$attname = $att->getValue('name');

				if (!$attname && !$parent_id)
				{
					$attname = EmailGetAttBodyDesc($dbh, $MID);
					if ($attname)
						$dbh->Query("update email_message_attachments set name='".$dbh->Escape($attname)."' where id=".$att->getValue('id')."");
				}
				else if (!$attname)
					$attname = "Untitiled";

				switch ($att->getValue('content_type'))
				{
				case 'application/ms-tnef':
					$contents = EmailGetAttachmentData($dbh, $att->getValue('id'));
					$attachment = new TnefAttachment(false);
					$fresult = $attachment->decodeTnef($contents);
					$tnef_files = $attachment->getFilesNested();
					for ($m = 0; $m < count($tnef_files); $m++)
					{
						$file = $tnef_files[$m];

						if ($file->getType() != "application/rtf")
						{
							$attData = array(
								'name' => $file->getName(),
								'size' => number_format($file->getSize()/1000, 0)."k",
								'link_download' => "/email/attachment.awp?attid=".$att->getValue('id')."&tnefatt=$m&disposition=attachment",
								'link_view' => "",
								'preview' => "<img border='0' src='".EmailGetAttachmentIcon($file->getName())."'>",
							);

							$ret['attachments'][] = $attData;
						}
					}
					break;
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
				case 'image/pjpeg':
					if (!$att->getValue('file_id'))
					{
						$lnk = "/email/attachment.awp?attid=".$att->getValue('id');
						$lnk_thumb = "/email/attachment_image.awp?attid=".$att->getValue('id');
					}
					else
					{
						$lnk = "/antfs/".$att->getValue('file_id')."/".rawurlencode($attname); // send file name
						$lnk_thumb = "/userfiles/getthumb_by_id.awp?fid=".$att->getValue('file_id')."&iw=100";
					}
					
					$attData = array(
						'name' => $att->getValue('filename'),
						'size' => number_format($att->getValue('size')/1000, 0)."k",
						'link_download' => $lnk,
						'link_view' => $lnk,
						'preview' => "<img border='0' src='".$lnk_thumb."'>",
					);

					$ret['attachments'][] = $attData;

					break;
				default:

					if (!$att->getValue('file_id'))
						$lnk = "/email/attachment.awp?attid=" . $att->getValue('id');
					else
						$lnk = "/antfs/" . $att->getValue('file_id') . "/".rawurlencode($attname); // send file name
					
					$attData = array(
						'name' => $attname,
						'size' => number_format($att->getValue('size')/1000, 0)."k",
						'link_download' => $lnk,
						'link_view' => "",
						'preview' => "<img border='0' src='".EmailGetAttachmentIcon($attname)."'>",
					);

					$ret['attachments'][] = $attData;

					break;
				}
			}
		}


		// Mark read if this is a new image
		/*
		if ($msg->mainObject->GetValue('flag_seen') != 't')
			EmailMarkMessageRead($dbh, $params['mid']);
		 */

		// Mark read if this is a new image
		if ($msg->getValue("flag_seen") != 't')
			$msg->markRead();

		return $this->sendOutputJson($ret);
	}
    
    /**
    * Move the email message to another thread
	*
	* TODO: I think this function may be DEPRICATED
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function moveEmailThread($params)
    {
        $dbh = $this->ant->dbh;
        $threadId = $params['threadId'];
        $mailboxId = $params['mailboxId'];
        
        if($threadId > 0 and $mailboxId > 0)
        {
			$thread = CAntObject::factory($dbh, "email_thread", $threadId, $this->user);
			$thread->move($mailboxId);
        }
        else
        {
            $ret = array("error" => "Thread Id and Mailbox Id are required params");
        }
        
		$this->sendOutputJson($ret);
    }

    /**
    * Reparse a message
	*
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function reparse($params)
    {
        $dbh = $this->ant->dbh;
        $mid = $params['mid'];
        
		$message = CAntObject::factory($dbh, "email_message", $mid, $this->user);
		$message->reparse();
			
		$this->sendOutputJson(true);
    }
    
    /**
    * Gets the html template data
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getHtmlTemplateData($params)
    {
        $ret = array();
        if(isset($params['id']) && $params['id'] > 0)
        {
            $templateObj = CAntObject::factory($this->ant->dbh, "html_template", $params['id'], $this->user);
            $ret['plain'] = $templateObj->getValue("body_plain");
            $ret['html'] = $templateObj->getValue("body_html");
        }
        else
        {
            $ret = array("error" => "Id is a required param.");
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
	
	/**
	 * Test processing/sending email campaign to a single address
	 *
     * @param array $params An assocaitive array of parameters passed to this function.
	 */
    public function testProcessEmailCampaign($params)
    {
        $dbh = $this->ant->dbh;
        $user = $this->user;

        // Create Test Email Campaign
        $emailCamp = CAntObject::factory($dbh, "email_campaign", null, $user);
        $emailCamp->setValue("body_html", $params['body_html']);
        $emailCamp->setValue("f_confirmation", "f");
        $emailCamp->setValue("f_trackcamp", "f");
        $emailCamp->setValue("from_email", $params['from_email']);
        $emailCamp->setValue("from_name", $params['from_name']);
        $emailCamp->setValue("subject", $params['subject']);
        $emailCamp->setValue("to_type", "manual");
        $emailCamp->setValue("to_manual", $params['test_email']);

		// Send email to test contact
        $cust = CAntObject::factory($dbh, "customer", null, $user);
		$cust->setValue("first_name", "Test");
		$cust->setValue("last_name", "Contact");
		$cust->setValue("email", $params['test_email']);
        $result = $emailCamp->sendEmail($cust);

		return $this->sendOutput(($result)?1:0);
    }

	/**
	 * Get number of recipients for a campaign
	 *
     * @param array $params An assocaitive array of parameters passed to this function.
	 */
    public function getEmailCampaignNumRec($params)
    {
		$ret = 0;
		$conditionObj = null;

		switch($params['to_type'])
        {
		case "manual":
			return $this->sendOutput(count(explode(",", $params['to_manual'])));
		case "view":
			$obj = CAntObject::factory($this->ant->dbh, "customer", null, $this->user);        
			$obj->loadViews(null, $params['to_view']);
			$num = $obj->getNumViews();
			if($num > 0)
			{
				$view = $obj->getView(0);
				$conditionObj = $view->conditions;
			}
			break;

		case "condition":
			$jsonCondition = $params['to_conditions'];
			$conditionObj = json_decode($jsonCondition);
			break;
		}

		$customerList = new CAntObjectList($this->ant->dbh, "customer", $this->user);
		if(is_array($conditionObj) && count($conditionObj) > 0)
		{
			foreach($conditionObj as $cond)
			{
				if(!isset($cond->condValue))
					$cond->condValue = $cond->value;
	
				$customerList->addCondition($cond->blogic, $cond->fieldName, $cond->operator, $cond->condValue);
			}
		}
		$customerList->getObjects(0, 1);
		$ret = $customerList->getTotalNumObjects();

		return $this->sendOutput($ret);
	}
}
