<?php
/**
 * Comment object
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * functions for commenting on objects
 *
 * Example
 * <code>
 * </code>
 *
 * @category	CAntObject
 * @package		Comment
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

// ANT includes
require_once("lib/CAntObject.php");

/**
 * Object extensions for managing comments
 */
class CAntObject_Comment extends CAntObject
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
		parent::__construct($dbh, "comment", $cid, $user);
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
		/*
		 * FIXME: The below is should be deleted since Netric\Entity\ObjType\CommentEntity does the work
		 *
		// Set comments associations to all directly associated objects
		if ($this->getValue('obj_reference'))
		{
			$parts = explode(":", $this->getValue('obj_reference'));
			if (count($parts) > 1)
			{
				$objref = new CAntObject($this->dbh, $parts[0], $parts[1], $this->user);
				$objref->setHasComments();

				// Add object references to the list of associations
				$this->setMValue("associations", $parts[0] . ":" . $parts[1]);

				// Copy associations for everything but status updates
				if ($parts['0'] != "status_update")
				{
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
									$this->setMValue("associations", $field['subtype'].":".$val);
								}
								else if (count(explode(":", $val))>1)
								{
									$this->setMValue("associations", $val);
								}
							}
						}
					}
				}
			}
		}

		if (!$this->getValue('sent_by'))
			$this->setValue("sent_by", "user:" . $this->user->id);
		*/

		// Send notifications if set - notify is not a field so it is just a temp buf
		if ($this->getValue("notify"))
		{
			$to = explode(",", $this->getValue("notify"));

			/*
			 * Once upon a time this would actually send the notifications, but now
			 * we just update followers and let the new Entity notification system
			 * (Netric/Entity/Notifier) handle creating and sending notifications.
			 *
			 * This will need to be in place until we remove the "notify" input box
			 * from comments in the old V1 interface of netric. - joe
			 */
			foreach ($to as $sendTo)
			{
				$sendTo = trim($sendTo);

				if (!$sendTo || ($this->user && ($sendTo == ("user:" . $this->user->id))))
					continue;

				// Make sure this is an object reference to a user
				if (substr($sendTo, 0, strlen("user:")) == "user:")
				{
					$sendToUser = CAntObject::decodeObjRef($sendTo);
					$this->setMValue("followers", $sendToUser['id']);
				}
			}
		}
	}

	/**
	 * Function used for derrived classes to hook save event
	 */
	protected function saved()
	{
		// Send notifications if set
	}

	/**
	 * @deprecated This is now handled in Netric\Entity\ObjType\Comment - joe
	 *
	 * Send notifications
	 *
	 * @param array $notify Array of objects or email addresses to send notifications to
	 *
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

			$sendto = trim($sendto);
			$notifyEntry = $sendto; // Used to log who was notified

			if (!$sendto || ($this->user && ($sendto == ("user:" . $this->user->id))))
				continue;

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
							if ($referenced_obj)
								$link = $this->getAccBaseUrl()."/obj/".$referenced_obj->object_type."/".$referenced_obj->id;

							if ($this->user)
								$desc = $this->user->getValue("full_name") . " commented on " . $referenced_obj->getName();
							else
								$desc = "New comment on " . $referenced_obj->getName();

							// Add notification for user
							$notification = CAntObject::factory($this->dbh, "notification", null, $this->user);
							$notification->setValue("name", "Added Comment");
							$notification->setValue("description", $desc);
							if ($this->getValue('obj_reference'))
								$notification->setValue("obj_reference", $this->getValue('obj_reference'));
							$notification->setValue("f_popup", 'f');
							$notification->setValue("f_seen", 'f');
							$notification->setValue("owner_id", $user->id);

							// Create notification if not the current user
							if ($this->user && $user->id == $this->user->id)
							{
								// Send nothing to the current user because he created the comment
								$eml = "";
							}
							else
							{
								$nid = $notification->save();
							}

							break;

						case 'customer':
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

			if ($eml && !in_array($eml, $sentTo))
			{
				$notified = $this->getValue("notified");
				$notified .= ($notified) ? ",".$notifyEntry : $notifyEntry;
				$this->setValue("notified", $notified);
                
				// Create new email object
				$headers = array();
				if ($this->user)
					$headers['X-ANT-ACCOUNT-NAME'] = $this->user->accountName;

				$headers['To'] = $eml;
				$headers['Subject'] = "Added Comment";
				$body = "$from added a comment";
				if ($referenced_obj)
				$body .= " to " . $referenced_obj->getName();
				$body .= "\r\n-----------------------------------------------------\r\n\r\n";
				$body .= $this->getValue("comment");

				// Change reply to for intelligent inboxes
				if ($referenced_obj && $this->user)
				{
					$headers['From'] = "\"" . $this->user->fullName . "\" <";
					$headers['From'] .= $this->user->accountName . "-com-" . $referenced_obj->object_type . "." . $referenced_obj->id;
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
	}
	*/
}
