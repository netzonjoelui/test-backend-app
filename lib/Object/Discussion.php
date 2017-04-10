<?php
/**
 * Discussion object
 *
 * @category	CAntObject
 * @package		Discussion
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

// ANT includes
require_once("lib/CAntObject.php");

/**
 * Object extensions for managing comments
 */
class CAntObject_Discussion extends CAntObject
{
	/**
	 * Flag used to set email message to test mode which will buffer but not send messages
	 * 
	 * @var bool
	 */
	public $testMode = false;

	/**
	 * Buffer used if in test mode
	 * 
	 * @var mixed
	 */
	public $testModeBuf = null;

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $cid 			The comment id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $cid=null, $user=null)
	{
		parent::__construct($dbh, "discussion", $cid, $user);
	}
	
	/**
	 * Function used for derrived classes to hook load event
	 */
	protected function loaded()
	{
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
	}

	/**
	 * Function used for derrived classes to hook save event
	 */
	protected function saved()
	{
		// Send notifications if set - notify is not a field so it is just a temp buf
		if ($this->getValue("notify"))
		{
			$to = explode(",", $this->getValue("notify"));
			$this->sendNotifications($to);
		}
	}

	/**
	 * Send notifications
	 *
	 * @param array $notify Array of objects or email addresses to send notifications to
	 */
	public function sendNotifications($notify)
	{
		if($this->testMode)
            $this->testModeBuf = array();
                
		// Set the "From" name
		$from = "";
		if ($this->getValue('sent_by'))
		{
			$sentBy = CAntObject::decodeObjRef($this->getValue('sent_by'));
			if ($sentBy)
				$from = objGetName($this->dbh, $sentBy['obj_type'], $sentBy['id']);
                
            if($this->testMode)
                $this->testModeBuf["sent"] = array("by" => $sentBy, "from" => $from);
		}
        
		$referenced_obj = null;
		if ($this->getValue('obj_reference'))
		{
			$parts = CAntObject::decodeObjRef($this->getValue('obj_reference'));
			if ($parts)
				$referenced_obj = CAntObject::factory($this->dbh, $parts['obj_type'], $parts['id'], $this->user);
		}

		// Used to make sure we don't send twice
		$sentTo = array(); 

		foreach ($notify as $sendto)
		{
			$eml = "";
			$full_name = "";
			$link = "";

			if (!$sendto)
				continue;

			$sendto = trim($sendto);
			$notifyEntry = $sendto; // Used to log who was notified


			if (strpos($sendto, ":") === false) // plain text, not an object reference
			{
				if (strpos($sendto, "@") !== false) // this is a valid email
				{
					$eml = $sendto;
					$full_name = $sendto;
				}
			}
			else
			{
				$obj_parts = CAntObject::decodeObjRef($sendto);
				if ($obj_parts)
				{
					switch ($obj_parts['obj_type'])
					{
						case 'user':
							$user = new AntUser($this->dbh, $obj_parts['id']);
							$eml = $user->getEmail();
							$full_name = $user->fullName;
							$link = $this->getAccBaseUrl()."/obj/discussion/".$this->id;

							if ($this->user)
								$desc = $this->user->getValue("full_name") . " invited you to a discussion: " . $this->getName();
							else
								$desc = "New discussion started: " . $this->getName();

							// Add notification for user
							$notification = CAntObject::factory($this->dbh, "notification", null, $this->user);
							$notification->setValue("name", "Invited to Discussion");
							$notification->setValue("description", $desc);
							$notification->setValue("obj_reference", "discussion:" . $this->id);
							$notification->setValue("f_popup", 'f');
							$notification->setValue("f_seen", 'f');
							$notification->setValue("owner_id", $user->id);
							$nid = $notification->save();

							break;

						case 'customer':
							/*
							$cobj = CAntObject::factory($this->dbh, "customer", $obj_parts['id']);
							$emlGet = $cobj->getValue("email_default");
							$full_name = $cobj->getName();

							$eml = $cobj->getValue($emlGet);
							if (!$eml && $cobj->getValue("email"))
								$eml = $cobj->getValue("email");
							if (!$eml && $cobj->getValue("email2"))
								$eml = $cobj->getValue("email2");
							if (!$eml && $cobj->getValue("email3"))
								$eml = $cobj->getValue("email3");

							if ($referenced_obj)
							{
								switch($referenced_obj->object_type)
								{
									case 'case':
										$link = $this->getAccBaseUrl()."/public/support/case/".$referenced_obj->id."/".$cobj->id;
										break;
								}
							}
							 */
							break;

						default:
							// Disallow any other types for now
							break;
					}

					// Add label for quick reloading of comments
					if ($full_name)
						$notifyEntry .= "|" . $full_name;
				}
			}

            if($this->testMode)
            {
                $this->testModeBuf["reference"] = array("object" => $referenced_obj);
                $this->testModeBuf["sendTo"] = array("eml" => $eml, "fullName" => $full_name);
            }

			if ($eml && !in_array($email, $sentTo))
			{
				$notified = $this->getValue("notified");
				$notified .= ($notified) ? ",".$notifyEntry : $notifyEntry;
				$this->setValue("notified", $notified);
                
				// Create new email object
				$headers = array();
				if ($this->user)
					$headers['X-ANT-ACCOUNT-NAME'] = $this->user->accountName;

				/*
				if ($params['obj_reference'])
					$headers["X-ANT-ASSOCIATIONS"] = $params['obj_reference'];
				*/
				$headers['To'] = $eml;
				$headers['Subject'] = "Discussion Invitation";
				$body = "$from invited you discuss ";
				$body .= " to " . $this->getName();
				$body .= "\r\n-----------------------------------------------------\r\n\r\n";
				$body .= $this->getValue("message");

				// Change reply to for intelligent inboxes
				if ($this->user)
				{
					$headers['From'] = "\"" . $this->user->fullName . "\" <";
					$headers['From'] .= $this->user->accountName . "-com-discussion." . $this->id;
					$headers['From'] .= AntConfig::getInstance()->email['dropbox_catchall'] . ">";

					$body .= "\r\n\r\nTIP: You can respond by replying to this email.";
				}
				else
				{
					$headers['From'] = AntConfig::getInstance()->email['noreply'];
				}

				if ($link)
					$body .= "\r\n\r\nClick below for more details:\r\n$link";

                
                if($this->testMode)
                {
                    $this->testModeBuf["sendTo"]["notified"] = $notified;
                    $this->testModeBuf["status"] = "sent";
                }
				else
				{
					$email = new Email();
					$status = $email->send($headers['To'], $headers, $body);
					unset($email);
				}

				// Make sure we don't send duplicate notifications
				$sentTo[] = $eml;
			}
		}

		/*
        if($this->testMode)
            $this->testModeBuf = array();
                
		// Set the "From" name
		$from = "";

		$referenced_obj = null;
		if ($this->getValue('obj_reference'))
		{
			$parts = CAntObject::decodeObjRef($this->getValue('obj_reference'));
			if ($parts)
				$referenced_obj = CAntObject::factory($this->dbh, $parts['obj_type'], $parts['id'], $this->user);
		}
        
		foreach ($notify as $sendto)
		{
			$eml = "";
			$full_name = "";
			$link = "";

			if (!$sendto)
				continue;

			$sendto = trim($sendto);
			$notifyEntry = $sendto; // Used to log who was notified
            
			if (strpos($sendto, ":") === false) // plain text, not an object reference
			{
				if (strpos($sendto, "@") !== false) // this is a valid email
				{
					$eml = $sendto;
					$full_name = $sendto;
				}
			}
			else
			{
				$obj_parts = CAntObject::decodeObjRef($sendto);
				if ($obj_parts)
				{
					switch ($obj_parts['obj_type'])
					{
					case 'user':
						$user = new AntUser($this->dbh, $obj_parts['id']);
						$eml = $user->getEmail();
						$full_name = $user->fullName;
						$link = $this->getAccBaseUrl()."/obj/discussion/".$this->id;

						// Add notification for user
						$notification = CAntObject::factory($this->dbh, "notification", null, $this->user);
						$notification->setValue("name", "Added Comment");
						$notification->setValue("description", $this->user->getValue("full_name") . " started a discussion called " . $referenced_obj->getName());
						$notification->setValue("obj_reference", "discussion:".$this->id);
						$notification->setValue("f_popup", 'f');
						$notification->setValue("f_seen", 'f');
						$notification->setValue("owner_id", $user->id);
						$nid = $notification->save();

						break;

					default:
						// Disallow any other types for now
						break;
					}

					// Add label for quick reloading of comments
					if ($full_name)
						$notifyEntry .= "|" . $full_name;
				}
			}
            
            if($this->testMode)
            {
                $this->testModeBuf["reference"] = array("object" => $referenced_obj);
                $this->testModeBuf["sendTo"] = array("eml" => $eml, "fullName" => $full_name);
            }

			if ($eml)
			{
				// TODO: make sure we have not already sent a notification to this object/email
				
				$notified = $this->getValue("notified");
				$notified .= ($notified) ? ",".$notifyEntry : $notifyEntry;
				$this->setValue("notified", $notify);
				$this->setValue("notify", ""); // will prevent loops
				$this->save(false);
                
				// Create new email object
				$headers = array();
				if ($this->user)
					$headers['X-ANT-ACCOUNT-NAME'] = $this->user->accountName;
				$headers['From'] = AntConfig::getInstance()->email['noreply'];
				$headers['To'] = $eml;
				$headers['Subject'] = "Started Discussion";
				$body = $this->getValue("name") . "\r\n";
				$body .= "-----------------------------------------------\r\n";
				$body .= $this->getValue("message") . "\r\n";
				$body .= "\r\n\r\nClick below for more details:\r\n";
				$body .= $this->getAccBaseUrl() . "/obj/discussion/" . $this->id;

				$email = new Email();
                
                if($this->testMode)
                {
                    $this->testModeBuf["sendTo"]["notified"] = $notified;
                    $this->testModeBuf["status"] = "sent";
                }
				else
				{
					$status = $email->send($headers['To'], $headers, $body);
				}

				unset($email);
			}
		}
		 */
	}
}
