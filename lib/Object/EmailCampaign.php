<?php
/**
 * Aereus Object - email campaign
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * added functionality like creating a marketing 'campaign' for this email campaign
 * if the option is selected and the 'campaign' filed is null.
 *
 * @category	CAntObject
 * @package		EmailCampaign
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntFs.php");

/**
 * Object extensions for managing emails campaigns
 */
class CAntObject_EmailCampaign extends CAntObject
{
    /**
     * Flag used to set email message to test mode which will buffer but not send messages
     * 
     * @var bool
     */
    public $testMode = false;

	/**
	 * Skip background job to prevent endless loop
	 *
	 * This flag is also used by unit tests to separate the onsave event from the worker execution
	 *
	 * @var bool
	 */
	public $skipBackgroundJob = false;

	/**
	 * Marketing campaign objects
	 *
	 * @var CAntObject
	 */
	private $marketingCamp = null;

	/**
	 * Internal sent counter
	 *
	 * @var int
	 */
	public $numSent = 0;

	/**
	 * Internal processing timer / timestamp
	 *
	 * @var int
	 */
	public $processingStartTs = null;
    
	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh An active handle to a database connection
	 * @param int $id The attachment id we are editing - this is optional
	 * @param AntUser $user Optional current user
	 */
	function __construct($dbh, $id=null, $user=null)
	{
		parent::__construct($dbh, "email_campaign", $id, $user);
	}
	
	/**
	 * Function used for derrived classes to hook load event
	 */
	protected function loaded()
	{
	}

	/**
	 * Before we save set some require variables
	 */
	protected function beforesaved()
	{
		$this->getMarketingCamp(true); // Create if missing
	}

	/**
	 * Function used for derrived classes to hook save event
	 */
	protected function saved()
	{
		// Add background job
		if (!$this->skipBackgroundJob && $this->getValue("status") == 3) // Set to Pending which is queued for sending
		{
			$wman = new WorkerMan($this->dbh);

			$data = array(
				"user_id" => $this->user->id,
				"object_id" => $this->id,
			);

			$tsStart = $this->getValue("ts_start");
			if(empty($tsStart))
				$ret = $wman->runBackground("email/send_bulk", serialize($data));
			else if ($tsStart)
				$ret = $wman->scheduleBackground("email/send_bulk", serialize($data), $tsStart);

			// Clear past jobs if already queued
			if ($ret && $this->getValue("job_id"))
				$wman->cancelJob($this->getValue("job_id"));

			$this->setValue("job_id", $ret);
			$this->skipBackgroundJob = true; // Prevent endless loop
			$this->save(false); // False = not log or fire workflows
		}

		$campaignObj = $this->getMarketingCamp(true);
		if ($campaignObj && !$campaignObj->getValue("email_campaign_id"))
		{
			$campaignObj->setValue("email_campaign_id", $this->id);
			$campaignObj->save();
		}
	}

	/**
	 * Called after the object has been removed/deleted
	 *
	 * @param bool $hard true if it was a hard delete
	 */
	protected function removed($hard=false)
	{
		// Clear jobs if queued
		if ($this->getValue("job_id"))
		{
			$wman = new WorkerMan($this->dbh);
			$wman->cancelJob($this->getValue("job_id"));
		}
	}
    
    /**
     * Process the sending of bulk emails using email campaign info
	 *
	 * @param bool $test If true then we are sending a single test email
     */
    public function processEmailCampaign($test=false)
    {
		$this->processingStartTs = time();
		$this->setValue("status", 4); // 4 = In-Progress

		// Get recipients and send email (first param) to each
        $recipients = $this->getRecipients(true);

        // Send Confirmation if set
        if($this->getValue("f_confirmation")=="t")
			$this->sendConfirmationEmail();

		$this->setValue("status", 5); // 5 = sent

		// Update marketing_campaign
		$campaignObj = (!$test) ? $this->getMarketingCamp(true) : null;
		if ($campaignObj)
		{
			$campaignObj->setValue("num_sent", count($recipients));
			$campaignObj->setValue("date_end", "now");

			// Set status
			$grp = $campaignObj->getGroupingEntryByName("status_id", "Complete");
			if ($grp && $grp['id'])
				$campaignObj->setValue("status_id", $grp['id']);

			$campaignObj->save();
		}

		if (!$test)
			$this->save();
        
        return true;
    }

	/**
	 * Send message to a recipient
	 *
	 * @param CAntObject $obj The object we are sending to
	 * @param string $emailAddress If no object is defined then send to email address
	 * @return bool true on success, false on failure
	 */
	public function sendEmail($obj, $email=null)
	{
		$sendTo = ($email) ? $email : $obj->getDefaultEmail();

		// Create new email
		$emailObj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$emailObj->setupSMTP(null, true);
		$emailObj->setHeader("subject", $this->getValue("subject"));

		// Get marketing campaign if set
		$marketingCamp = $this->getMarketingCamp();

		if ($this->getValue("body_plain"))
		{
			$body = $this->getValue("body_plain");
			$bodyType = "plain";
		}

		if ($this->getValue("body_html"))
		{
			$body = $this->getValue("body_html");
			$bodyType = "html";
		}

		// Add campaign tracking image
		$trackImg =  $this->getAccBaseUrl() . "/public/email/campimg/" . $this->id;
		$trackImg .= ($obj) ? "/" . $obj->id : "&eml=" . base64_encode($email);
		if ($marketingCamp && "html" == $bodyType)
			$body .= "<img src=\"$trackImg\" />";

		/* Moved to CAntObject_EmailMessage::processCampaignTemplate
		// Add open in new window
		$newWindowLink =  $this->getAccBaseUrl() . "/public/email/campaign/" . $this->id;
		$newWindowLink .= ($obj) ? "/" . $obj->id : "&eml=" . base64_encode($email);
		if ("html" == $bodyType)
		{
			$body .= "<br /><div style='text-align:center;'>Trouble viewing this message? <a href='$newWindowLink'>";
			$body .= "Click here to open in a new window</a>.</div>";
		}
		else
			$body .= "\r\n\r\nTrouble viewing this message? Click here to load in browser: $newWindowLink";

		// Add unsubscribe
		$unsubLink =  $this->getAccBaseUrl() . "/public/email/unsubscribe.php?eid=" . $this->id;
		$unsubLink .= ($obj) ? "&cid=" . $obj->id : "&eml=" . base64_encode($email);
		if ("html" == $bodyType)
		{
			$body .= "<br /><div style='text-align:center;'><a href=\"$unsubLink\">";
			$body .= "Click here to unsubscribe from this mailing list</a>.</div>";
		}
		else
			$body .= "\r\n\r\nClick this link to unsubscribe from this list: $unsubLink";
		 */

		// Set body
		$emailObj->setBody($body, $bodyType);

		// Check if we are in test mode. No emails will actually be sent.
		$emailObj->testMode = $this->testMode;
			
		// From
		$emailObj->setHeader("from", '"' . $this->getValue("from_name") . '" <' . $this->getValue("from_email") . ">");

		// To
		$emailObj->setHeader("to", $sendTo);

		// Handle merge fields like <%first_name%>
		$emailObj->processCampaignTemplate($this, $obj);
		
		$finished = $emailObj->send(false); // false = do not save a full copy of this message if sending bulk

		$this->numSent++;

		if ($obj)
		{
			// Only add activity if this is not a temporary customer
			if ($obj->id)
			{
				$act = $obj->addActivity("sent", "Email campaign sent - " . $this->getValue('name'), "Email was sent to $sendTo", 
										 null, null, 't', null, 4);
                $act->setValue("verb_object", "email_campaign:" . $this->id);
                
                if ($marketingCamp)
                    $act->addAssociation("marketing_campaign", $marketingCamp->id, "associations");
                
                $act->save();
			}

			
		}
		else if ($marketingCamp)
		{
			$act = $marketingCamp->addActivity("sent", "Bulk Email - " . $this->getValue('name'), "Email was sent to $sendTo", null, null, 't');
		}


		unset($emailObj);

		return $finished;
	}

	/**
	 * Get recipients array
	 *
	 * @param bool $sendEmail If set to true then emails will be sent to each recipient
	 * @return array Email addresses sent to
	 */
	public function getRecipients($sendEmail=false)
	{
        $recipients = array();

		// Get Recipients
		// ----------------------------------------
        switch($this->getValue("to_type"))
        {
		case "manual":
			$emailParts = explode(",", $this->getValue("to_manual"));
			foreach($emailParts as $email)
			{
				$recipients[] = trim($email);

				if ($sendEmail)
					$this->sendEmail(null, trim($email));
			}

			return $recipients; // no further processing needed

		case "view":
			$obj = CAntObject::factory($this->dbh, "customer", null, $this->user);        
	
			$obj->loadViews(null, $this->getValue("to_view"));
			$num = $obj->getNumViews();
			if($num > 0)
			{
				$view = $obj->getView(0);
				$conditionObj = $view->conditions;
			}
			break;

		case "condition":
			$jsonCondition = $this->getValue("to_conditions");
			$conditionObj = json_decode($jsonCondition);
			break;
        }
        
		// Now pull messages from the object list
		$customerList = new CAntObjectList($this->dbh, "customer", $this->user);
		
		if(is_array($conditionObj) && count($conditionObj) > 0)
		{
			foreach($conditionObj as $cond)
			{
				if(!isset($cond->condValue))
					$cond->condValue = $cond->value;
	
				$customerList->addCondition($cond->blogic, $cond->fieldName, $cond->operator, $cond->condValue);
			}
		}
		
		$offset = 0;
		$customerList->getObjects($offset, 200);
		$num = $customerList->getNumObjects();
		for ($i = 0; $i < $num; $i++)
		{            
			$cust = $customerList->getObject($i);
			
			$emailDefault = $cust->getValue("email_default");
			if(empty($emailDefault))
				$emailDefault = "email";
				
			$email = $cust->getValue($emailDefault);

			if (empty($email))
				$email = $cust->getValue("email");

			if (empty($email))
				$email = $cust->getValue("email2");

			if($email && $cust->getValue("f_noemailspam") != 't' && $cust->getValue("f_nocontact") != 't' && !$this->alreadySent($cust, $email, $recipients))
			{
				$recipients[] = $email;

				// Send email to recipient
				if ($sendEmail)
					$this->sendEmail($cust);
			}

			// Get next page if more than one
			if (($i+1) == $num && ($num+$offset) < $customerList->getTotalNumObjects())
			{
				$offset += 199;
				$customerList->getObjects($offset, 200); // get next 100 objects
				// Reset counters
				$i = 0;
				$num = $customerList->getNumObjects($offset, 200);
			}
            
            // If throttling to only send x number of emails per session
            if (is_numeric($this->getValue("throttle_number")) && $this->getValue("throttle_number") >= count($recipients))
                break;
		}

		unset($customerList);

		return $recipients;
	}
    
    /**
     * Determine if this campaign was already sent to a given customer
     */
    public function alreadySent($cust, $email, &$recipientsThisPass)
    {
        if (in_array($email, $recipientsThisPass))
                return true;
        
        // Now query activities with verb_object reference to make sure same customer
        // does not receive the campaign twice.
        if ($this->id)
        {
            $activityList = new CAntObjectList($this->dbh, "activity", $this->user);
            $activityList->addCondition("and", "obj_reference", "is_equal", "customer:" . $cust->id);
            $activityList->addCondition("and", "verb_object", "is_equal", "email_campaign:" . $this->id);
            if ($activityList->getNumObjects()>0)
                return true;
        }
        
        return false;
    }

	/**
	 * Send email to creator of campaign letting them know the campaign was sent
	 */
	public function sendConfirmationEmail()
	{
		if (!$this->getValue("confirmation_email"))
			return false;

		$body = "Email Campaign " . $this->getValue("name") . " was successfully sent to ";
		$body .= $this->numSent . " recipients in " . (time() - $this->processingStartTs) . " seconds";
		$mcamp = $this->getMarketingCamp();
		if ($mcamp)
			$body .= "\r\n\r\nClick for info: " . $this->getAccBaseUrl() . "/obj/marketing_campaign/" . $mcamp->id;

		$emailObj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$emailObj->setHeader("Subject", "Campaign Email: " . $this->getValue("name"));
		$emailObj->setBody($body, "plain");

		// Check if we are in test mode. No emails will actually be sent.
		$emailObj->testMode = $this->testMode;
			
		// From
		$emailObj->setHeader("From", AntConfig::getInstance()->email["noreply"]);

		// To
		$emailObj->setHeader("To", $this->getValue("confirmation_email"));
		
		$finished = $emailObj->send(false); // Do not save a full copy of this message if sending bulk
	}

	/**
	 * Get marketing campaign if set
	 *
	 * @return CAntObject if set, otherwise null
	 */
	public function getMarketingCamp($createIfMissing=false)
	{
		if($createIfMissing && $this->getValue("f_trackcamp")=="t" && !$this->getValue("campaign_id"))
        {
            $marketingObj = CAntObject::factory($this->dbh, "marketing_campaign", null, $this->user);
			$marketingObj->setValue("name", $this->getValue("name"));
			$marketingObj->setValue("date_start", "now");

			// Set type
			$grp = $marketingObj->getGroupingEntryByName("type_id", "Email");
			if ($grp && $grp["id"])
				$marketingObj->setValue("type_id", $grp['id']);

            $this->setValue("campaign_id", $marketingObj->save());
		}

		if ($this->getValue("campaign_id"))
   			$this->marketingCamp = CAntObject::factory($this->dbh, "marketing_campaign", $this->getValue("campaign_id"), $this->user);

		return $this->marketingCamp;
	}
}