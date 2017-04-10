<?php
/**
 * Aereus Object Email
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * function like "send" for email messages.
 *
 * Example
 * <code>
 * 	$email = new CAntObject_EmailMessage($dbh);
 * 	$email->setHeader("from", "sky.stebnicki@aereus.com");
 * 	$email->setHeader("to", "someone@somewhere.com");
 * 	$email->setBody("Hello there");
 * 	$email->addAttachment("/path/to/my/file.txt");
 * 	$email->send();
 * </code>
 *
 * @category	CAntObject
 * @package		EmailMessage
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

// PHP includes
require_once("PEAR.php");
require_once('Mail.php');
require_once('Mail/mime.php');

// ANT includes
require_once("lib/AntFs.php");
require_once("lib/Object/EmailThread.php");
require_once("lib/AntMail/DeliveryAgent.php");
require_once("lib/AntMail/Account.php");
require_once("lib/AntMail/Backend.php");
require_once("lib/AntMail/Parser/CssToInline.php");

/**
 * Define constants to be used in limiting email messages
 */
define("EMAIL_MAX_ATT_SIZE", 104857600); // 100 MB

/**
 * Object extensions for managing emails in ANT
 */
class CAntObject_EmailMessage extends CAntObject
{
	/**
	 * Array of attachments
	 *
	 * @var array()
	 */
	private $attachments = array();

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
	 * Flag used to skip processing of thread to prevent endless loop
	 *
	 * @var bool
	 */
	public $skipProcessThread = false;
    
    /**
     * Overrides the mailbox "Inbox" when importing email message
     *
     * @var Integer
     */
    public $mailboxId = null;	

	/**
	 * Smtp host to use when sending
	 *
	 * @var string
	 */
	public $smtpHost = null;
	
	/**
	 * Smtp user to use if authentication is required
	 *
	 * @var string
	 */
	public $smtpUser = null;
	
	/**
	 * Smtp password to use if authentication is required
	 *
	 * @var string
	 */
	public $smtpPassword = null;
	
	/**
	 * Optional alternate port to use when sending messages to an SMTP server
	 *
	 * @var int
	 */
	public $smtpPort = null;
	
	/**
	 * Contains the instance of the service ANT
	 *
	 * @var Ant Service
	 */
	private $ant = null;

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $mid 			The message id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $mid=null, $user=null)
	{
		parent::__construct($dbh, "email_message", $mid, $user);
		
		// Get the ANT service
		$this->ant = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator()->getAnt();
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
		// This has been moved to Netric\Entity\ObjType\EmailMessageEntity

		/*
		// Check message_id
		$this->getMessageId(); // will set message_id property

		// Get number of attachments and save
		$numatt = $this->getValue("num_attachments");
		if (!is_numeric($numatt))
			$numatt = 0;

		if (is_array($this->attachments))
		{
			// Do not count inline attachments
			foreach ($this->attachments as $att)
			{
				if (!$att->contentId)
					$numatt++;
			}
			//$numatt += count($this->attachments);
		}
		
		$this->setValue("num_attachments", $numatt);

		// Update thread
		// -------------------------------------------------
		$thread = null;
		if (!$this->getValue("thread"))
		{
			$thread = $this->createNewThread();

			// Update fields in thread if this is a new one, otherwise data was previously set
			$mcnt = $thread->getValue("num_messages");
			if (!is_numeric($mcnt)) $mcnt = 0;
			$thread->setValue("num_messages", ++$mcnt);

			if ($this->getValue("num_attachments"))
			{
				$acnt = $thread->getValue("num_attachments");
				if (!is_numeric($acnt)) $acnt = 0;
				$thread->setValue("num_attachments", $acnt + $this->getValue("num_attachments"));
			}
			else
			{
				$thread->setValue("num_attachments", 0);
			}

			$thread->setValue("subject", $this->getValue("subject"));
			$existingBody = $thread->getValue("body");
			$thread->setValue("body", $this->getValue("body") . "\n\n" . $existingBody); // mostly for searching & snippets
            
            $senders = $thread->updateSenders($this->getValue("sent_from"), $thread->getValue("senders"));
			$thread->setValue("senders", $senders);
            
            $receivers = $thread->updateSenders($this->getValue("send_to"), $thread->getValue("receivers"));    
			$thread->setValue("receivers", $receivers);
            
			$thread->setValue("ts_delivered", $this->getValue("message_date"));
		}
		else // already part of a thread so no need to make too many modifications to the thread
		{
			$thread = CAntObject::factory($this->dbh, "email_thread", $this->getValue("thread"), $this->user);
		}

		if ($this->getValue("mailbox_id") && !$thread->getMValueExists("mailbox_id", $this->getValue("mailbox_id")))
		{
			$thread->setMValue("mailbox_id", $this->getValue("mailbox_id")); // Add to same mailbox as the message
		}

		$thread->setValue("f_seen", $this->getValue("flag_seen"));

		$tid = $thread->save();
		if (!$this->getValue("thread") && $tid) 
			$this->setValue("thread", $tid);
		*/
	}

	/**
	 * Function used for derrived classes to hook save event
	 */
	protected function saved()
	{
		// This has been moved to Netric\Entity\ObjType\EmailMessageEntity

		/*
		// Add local file attachments
		if (is_array($this->attachments) && count($this->attachments))
		{
			foreach ($this->attachments as $att)
			{
				$aid = $this->saveAttachment($att);
			}

			// Clear queue
			$this->attachments = array();
		}
		*/
	}

	/**
	 * Function used for derived classes to hook deletion event
	 *
	 * If this is the only message in a thread, then the thread will
	 * also be removed. Otherwise, simply remove the thread from this folder_id/group.
	 * That was if a user deletes a message in the inbox that was sent as a reply
	 * to a message sent by the user, then the 'sent' thread will stay in tact but the
	 * thread will not be in the inbox any longer.
	 *
	 * @param bool $hard Set to true if this is a hard delete rather than just a soft or flagged deletion
	 */
	protected function removed($hard=false)
	{
		// This has been moved to Netric\Entity\ObjType\EmailMessageEntity

		/*
		// Remove all attachments
		$olist = new CAntObjectList($this->dbh, "email_message_attachment", $this->user);
		$olist->addCondition('and', "message_id", "is_equal", $this->id);
		$olist->getObjects();
		$a_num = $olist->getNumObjects();
		for ($m = 0; $m < $a_num; $m++)
		{
			$att = $olist->getObject($m);
			if ($hard)
				$att->removeHard();
			else
				$att->remove();
		}

		// Remove original (raw) message
		if ($hard)
		{
			$this->dbh->Query("SELECT lo_unlink(lo_message) FROM email_message_original WHERE message_id='".$this->id."'");
			$this->dbh->Query("DELETE FROM email_message_original WHERE message_id='".$this->id."'");
		}

		// Remove all other messages and the tread
		if ($this->getValue("thread") && !$this->skipProcessThread)
		{
			$thread = CAntObject::factory($this->dbh, "email_thread", $this->getValue("thread"), $this->user);
			if ($hard)
				$thread->removeHard();
			else
				$thread->remove();
		}
		*/
	}

	/**
	 * This function is called once the message has been unremoved
	 */
	protected function unremoved()
	{
		// This has been moved to Netric\Entity\ObjType\EmailMessageEntity

		/*
		// unremove all attachments
		$olist = new CAntObjectList($this->dbh, "email_message_attachment", $this->user);
		$olist->addCondition('and', "message_id", "is_equal", $this->id);
		$olist->getObjects();
		$a_num = $olist->getNumObjects();
		for ($m = 0; $m < $a_num; $m++)
		{
			$att = $olist->getObject($m);
			$att->unremove();
		}
		*/
	}

	/**
	 * Send this email to recipients
	 *
	 * @param bool $saveSent If set to true then a copy of this message is saved to the sent folder
	 * @return bool true on success, false on failure. $this->lastErrorMessage will be set if failed
	 */
	public function send($saveSent=true)
	{
		// Get SMTP settings if they are not already set
		$this->setupSMTP();

		// Create mime mail for building parts
		$mimeMsg = new Mail_mime();

		// AntFs is used to handle attachments
		$antfs = new AntFs($this->dbh, $this->user);

		$tmpHeaders = array();

		// Check message_id
		$this->getMessageId(); // This will set 'message_id' if it is not already set

		$this->setHeader("Message-ID", $this->getValue("message_id"));
		$tmpHeaders["Message-ID"] = $this->getValue("message_id");
		//$this->setHeader("Date",  date('D, j M Y H:i:s ', mktime()) . EmailTimeZone());
		$this->setValue("message_date", date("Y-m-d H:i:s"));
		if ($this->getValue("in_reply_to"))
			$tmpHeaders["In-Reply-To"] = $this->getValue("in_reply_to");

		$mimeMsg->setFrom($this->processEmailAddresses($this->getHeader("from")));
		$mimeMsg->setSubject($this->getHeader("subject"));

		// Set recipients
		// ------------------------------------------------------------
		$recipients = array();
		$recipients['To'] = $this->processEmailAddresses($this->getValue("send_to"));
		if ($this->getValue("cc"))
			$recipients['Cc'] = $this->processEmailAddresses($this->getValue("cc"));
		if ($this->getValue("bcc"))
			$recipients['Bcc'] = $this->processEmailAddresses($this->getValue("bcc"));

		// Set the body
		// ------------------------------------------------------------
		$tmpbody = $this->getValue("body");

		// Handle enbedded images - this will add to the $this->attachments array if needed and replace url with cid in body
		$tmpbody = $this->encodeEmbeddedAttachments($tmpbody);

		// Set attachments
		// ------------------------------------------------------------
		if (is_array($this->attachments) && count($this->attachments))
		{
			foreach ($this->attachments as $att)
			{
				switch ($att->type)
				{
				// TODO: Add attachments from local file
				case 'local_file':
					if ($att->filePath)
					{
					}
					break;

				// Add attachments from AntFs
				case 'antfs_file':
					if ($att->fileId)
					{
						$file = $antfs->openFileById($att->fileId);
						if ($file)
						{
							if ($file->getValue("file_size") <= EMAIL_MAX_ATT_SIZE)
							{
								$contents = $file->read();
								if ($contents)
								{
									AntLog::getInstance()->info("Addign AntFs file[" . $att->fileId . "] size=".strlen($contents));
									if ($att->contentId) // put inline if contentId was set
										$mimeMsg->addHTMLImage($contents, $file->getContentType(), $att->contentId, false);
									else
										$mimeMsg->addAttachment($contents, $file->getContentType(), $file->getValue("name"), false);
								}
							}
						}
						else
						{
							AntLog::getInstance()->error("Tried to attach an AntFs file[" . $att->fileId . "] but could not open by id");
						}
					}
					break;

				// Add attachment from existing attachmetn (copy)
				case 'attachment':
					if ($att->attachmentId)
					{
						$att = CAntObject::factory($this->dbh, "email_message_attachment", $att->attachmentId, $this->user);
						if ($att->getValue("file_id"))
						{
							$file = $antfs->openFileById($att->getValue("file_id"));
							if ($file->getValue("file_size") <= EMAIL_MAX_ATT_SIZE)
							{
								$contents = $file->read();

								if ($att->getValue("content_id")) // Check if we are working with an embedded image
								{
									$mimeMsg->addHTMLImage($contents, $att->getValue("content_type"), $att->getValue("content_id"), false);
								}
								else
								{
									$mimeMsg->addAttachment($contents, $att->getValue("content_type"), $att->getValue("name"), false);
								}
							}
						}
					}
					break;
				}
			}
		}

		// Render raw mime message and send
		// ------------------------------------------------------------

		// Render the raw body and headers for this message
		if ($this->getValue("body_type") == "html")
		{
			$html = $this->encloseHtmlBody($tmpbody);

			// Convert css classes to inline styles
			$html = $this->cssToInline($html);

			// add the multipart/alternative body
			$mimeMsg->setHTMLBody($html);
			$mimeMsg->setTXTBody(strip_tags(str_replace("<br>", "\n", $tmpbody)));
		}
		else
		{
			//$mimeMsg->setHTMLBody(str_replace("\n", "<br />", $tmpbody));
			$mimeMsg->setTXTBody($tmpbody);
		}

		// The notes in the pear document say to NEVER call these in reverse order!
		// No idea why and have not tested the results of doing so.
		$body = $mimeMsg->get(array("text_charset"=>"UTF-8", "html_charset"=>"UTF-8"));
		$tmpHeaders = array_merge($tmpHeaders, $recipients);
		$headers = $mimeMsg->headers($tmpHeaders);

		// Create new email object
		if ($this->testMode)
		{
			$this->testModeBuf = array("recipients"=>$recipients, "headers"=>$headers, "body"=>$body);
			$status = true;
		}
		else
		{
			$email = new Email($this->smtpHost, $this->smtpUser, $this->smtpPassword, $this->smtpPort);
			$status = $email->send($recipients, $headers, $body);
		}

		if ($saveSent)
		{
			// Remove draft flag if set
			$this->setValue("flag_draft", 'f');
			$this->setValue("flag_seen", 't');
			$this->setValue("parse_rev", AntMail_DeliveryAngent::PARSE_REV);
			
			// Move to sent
			$this->setGroup("Sent");

			// Save
			$this->save();

			// TODO: Add activity to all customers with a matching email address
			// if setting: email/log_allmail is set to 1
		}

		return $status;
	}

	/**
	 * Move this message to another mailbox/group by id
	 *
	 * If this message is the only message in a thread, then the whole
	 * thread will be moved. Otherwise, the $gid will be added to
	 * the thread and this message will be removed.
	 *
	 * @param int $gid The group id to move this message to
	 * @return true on success
	 */
	public function move($gid)
	{
		if ($this->getValue("thread"))
		{
			$thread = CAntObject::factory($this->dbh, "email_thread", $this->getValue("thread"), $this->user);
			return $thread->move($gid, $this->getValue("mailbox_id"));
		}
		else // should never happen that a message exists without a thread, but just in case...
		{
			$this->setValue("mailbox_id", $gid);
			return $this->save();
		}
	}

	/**
	 * Set header field
	 *
	 * This is really just an alias to CAntObject::setValue but
	 * the name of the field is translated from ANT field names to RFC names.
	 *
	 * @param string $name The header name
	 * @param string $value The value to set the header to
	 */
	public function setHeader($name, $value)
	{
		$name = $this->transFromRfcToAnt($name);
		if ($name)
		{
			$this->setValue($name, $value);
		}
	}

	/**
	 * Get header field
	 *
	 * This is really just an alias to CAntObject::getValue but
	 * the name of the field is translated from ANT field names to RFC names.
	 *
	 * @param string $name The header name
	 * @return string The value of the header field
	 */
	public function getHeader($name)
	{
		$name = $this->transFromRfcToAnt($name);
		return $this->getValue($name);
	}

	/**
	 * Translate RFC field to ANT field name
	 *
	 * @param string $name The RFC name of the field
	 * @return string The name of the ANT field
	 */
	private function transFromRfcToAnt($name)
	{
		// All properties in ANT should be lower case
		$name = strtolower($name);

		// Replace dash with underscore
		$name = str_replace("-", "_", $name);

		$trans = array(
			"from" => "sent_from",
			"to" => "send_to",
		);

		foreach ($trans as $rfcname=>$antname)
		{
			if ($rfcname == strtolower($name))
				return $antname;
		}

		// Check to make sure the field exists
		if ($this->def->getField($name))
		{
			return $name;
		}
		else
		{
			return null;

		}
	}

	/**
	 * Set the body of this message
	 *
	 * For the most part this is an alias to CAntObject::setValue but does some
	 * processing based on the type of the body for mime messages
	 */
	public function setBody($body, $type="plain")
	{
		$this->setValue("body", $body);
		$this->setValue("body_type", $type);
		$this->body_type = $type;
	}

	/**
	 * Add an attachment from a local file
	 *
	 * This function returns an object accepting a number of properties including:
	 *
	 * name = the name of the attachment
	 * contentType = set the mime content type like image/jpeg
	 * contentTransferEncoding = set encoding type for encoded attachments
	 * contentId = The content id for inline attachemnts
	 * contentDisposition = 'attachment' or 'inline'
	 * cleanFileOnSave = if true the file is deleted once this email is saved. Default = false
	 * 
	 * @param string $path The local path of the attachment to add
	 * @param string $fileName The name of the file, if null then path will be used
	 * @return Object class to define properties for the attachment.
	 */
	public function addAttachment($path, $fileName=null)
	{
		$att = new stdClass();
		$att->id = null;
		$att->type = "local_file";
		$att->filePath = $path;
		$att->name = null;
		$att->fileName = $fileName; // filename
		$att->conentType = null; // content_type
		$att->contentTransferEncoding = null; // encoding
		$att->contentId = null; // content_id
		$att->contentDisposition = null; // disposition
		$att->size = null; // size
		$att->cleanFileOnSave = false; // cleanup temp file ($path) once messages is saved
        $att->obj = $this;

		$this->attachments[] = $att;
		return $att;
	}

	/**
	 * Add an attachment from a temp AntFs file
	 *
	 * This function is needed because when composing new messages there might not even be an id
	 * for this message crated yet, so uploaded attachments will go into a temp directory
	 * that will get purged regularly. Upon processing the temp file this class needs to move 
	 * the file so it will not get purged.
	 *
	 * @param int $fid The id of the temp file
	 */
	public function addAttachmentAntFsTmp($fid)
	{
		$att = new stdClass();
		$att->id = null;
		$att->type = "antfs_file";
		$att->fileId = $fid;
		$att->filePath = null;
		$att->name = null;
		$att->fileName = null; // filename
		$att->conentType = null; // content_type
		$att->contentTransferEncoding = null; // encoding
		$att->contentId = null; // content_id
		$att->contentDisposition = null; // disposition
		$att->size = null; // size
		$att->cleanFileOnSave = true; // move the temp file to attachments on save

		$this->attachments[] = $att;
		//$this->attachmentsTmp[] = $fid;
	}

	/**
	 * Add an attachment that is forwarded from another message
	 *
	 * @param int $aid The attachment id of the 'email_message_attachment' object
	 */
	public function addAttachmentFwd($aid)
	{
		$att = new stdClass();
		$att->id = null;
		$att->type = "attachment";
		$att->attachmentId = $aid;
		$att->filePath = null;
		$att->name = null;
		$att->fileName = null; // filename
		$att->conentType = null; // content_type
		$att->contentTransferEncoding = null; // encoding
		$att->contentId = null; // content_id
		$att->contentDisposition = null; // disposition
		$att->size = null; // size
		$att->cleanFileOnSave = true; // move the temp file to attachments on save

		$this->attachments[] = $att;
		//$this->attachmentsFwd[] = $aid;
	}

	/**
	 * Save an added attachment to the appropriate subsystem
	 *
	 * @param object $att Attachment stdObject to add. See $this->addAttachment notes.
	 */
	private function saveAttachment($att)
	{
		$ret = null; // assume failure

		switch ($att->type)
		{
		case 'local_file':
			if ($att->filePath)
				$ret = $this->saveAttachmentLocalFile($att);
			break;
		case 'antfs_file':
			if ($att->fileId)
				$ret = $this->saveAttachmentAntFs($att->fileId);
			break;
		case 'attachment':
			if ($att->attachmentId)
				$ret = $this->saveAttachmentFwd($att->attachmentId);
			break;
		}

		return $ret;
	}

	/**
	 * Save an attachment added from a local file to ANT
	 *
	 * @param object $att Attachment stdObject to add. See $this->addAttachment notes.
	 */
	private function saveAttachmentLocalFile(&$att)
	{
		$ret = null; // assume failure

		// Populate name & file name
		if ($att->fileName && !$att->name)
			$att->name = $att->fileName;
		else if ($att->name && !$att->fileName)
			$att->fileName = $att->name;

		if ($this->id)
		{
			// upload file via AntFs
			$antfs = new AntFs($this->dbh, $this->user);
			$fldr = $antfs->openFolder("%emailattachments%", true);
			$dataWrtn = false; // Check if we have created a file with any data
			if ($att->filePath)
			{
				$file = $fldr->importFile($att->filePath, $att->fileName);
			}
			else if ($att->data) // Create file
			{
				$file = $fldr->openFile($att->fileName, true);
				$dataWrtn = $file->write($att->data);
			}

		 	// cleanup temp files
			if ($file->id && $att->cleanFileOnSave)
				@unlink($att->filePath);

			// Save attachment object
			if ($file->id)
			{
				$obj = CAntObject::factory($this->dbh, "email_message_attachment", null, $this->user);
				$obj->setValue("message_id", $this->id);
				$obj->setValue("file_id", $file->id);
				$obj->setValue("owner_id", $this->user->id);
				$obj->setValue("filename", $att->fileName);
				$obj->setValue("name", $att->name);
				$obj->setValue("content_type", $att->conentType);
				$obj->setValue("encoding", $att->contentTransferEncoding);
				$obj->setValue("content_id", $att->contentId);
				$obj->setValue("disposition", $att->contentDisposition);
				$obj->setValue("size", $file->getValue('file_size'));
				$aid = $obj->save(false);
				$ret = $aid;
				$att->id = $aid;
				$att->obj = $obj;
			}
		}

		return $ret;
	}

	/**
	 * Save a copy of an attachment from AntFs into the email attachments
	 *
	 * @param object $att Attachment stdObject to add. See $this->addAttachment notes.
	 * @param bool $istemp If file is a temp file then move it, otherwise copy. Default = false
	 */
	private function saveAttachmentAntFs($fid, $istemp=false)
	{
		$ret = null; // assume failure

		if (!$this->id)
			return $ret;

		$antfs = new AntFs($this->dbh, $this->user);

		$file = $antfs->openFileById($fid);

		if ($istemp)
		{
			$attfldr = $antfs->openFolder("%emailattachments%", true);
			$file->move($attfldr);
		}

		// Save attachment object
		if ($file->id)
		{
			$obj = CAntObject::factory($this->dbh, "email_message_attachment", null, $this->user);
			$obj->setValue("message_id", $this->id);
			$obj->setValue("file_id", $file->id);
			$obj->setValue("owner_id", $this->user->id);
			$obj->setValue("filename", $file->getValue("name"));
			$obj->setValue("name", $file->getValue("name"));
			$obj->setValue("content_type", $file->getContentType());
			$obj->setValue("encoding", "");
			$obj->setValue("content_id", "");
			$obj->setValue("disposition", "attachment");
			$obj->setValue("size", $file->getValue("file_size"));
			$ret = $obj->save(false);
		}

		return $ret;
	}

	/**
	 * Save a copy of an attachment from another message
	 *
	 * @param integer $attid Unique id of attachment to copy to this message
	 */
	private function saveAttachmentFwd($attid)
	{
		$ret = null; // assume failure

		if (!$this->id)
			return $ret;

		/** TODO: we need to copy the file
		$antfs = new AntFs($this->dbh, $this->user);
		$file = $antfs->openFileById($fid);
		 */

		$source = CAntObject::factory($this->dbh, "email_message_attachment", $attid, $this->user);

		// Save attachment object
		if ($source->id)
		{
			$obj = CAntObject::factory($this->dbh, "email_message_attachment", null, $this->user);
			$obj->setValue("message_id", $this->id);
			$obj->setValue("owner_id", $this->user->id);
			// Copy remaining values
			$obj->setValue("file_id", $source->getValue("file_id"));
			$obj->setValue("filename", $source->getValue("filename"));
			$obj->setValue("name", $source->getValue("name"));
			$obj->setValue("content_type", $source->getValue("content_type"));
			$obj->setValue("encoding", $source->getValue("encoding"));
			$obj->setValue("content_id", $source->getValue("content_id"));
			$obj->setValue("disposition", $source->getValue("disposition"));
			$obj->setValue("size", $source->getValue("size"));
			$ret = $obj->save(false);
		}

		return $ret;
	}

	/**
	 * Set group (folder) for this message by path
	 *
	 * @param string $path The path of the group to use like /Inbox/MySubFolder
	 */
	public function setGroup($path)
	{
		$grp = $this->getGroup($path);
		$this->setGroupId($grp['id']);
	}

	/**
	 * Set group (folder) for this message by id
	 *
	 * @param string $gid The id of the group to set for this message
	 */
	public function setGroupId($gid)
	{
		$this->setValue("mailbox_id", $gid);
	}

	/**
	 * Get group (folder) for this message by path
	 *
	 * @param string $path The path of the group to use like /Inbox/MySubFolder
	 * @return array Object grouping array consisting of {id, name, color} params
	 */
	public function getGroup($path)
	{
		$grp = $this->getGroupingEntryByPath("mailbox_id", $path);
		return $grp;
	}

	/**
	 * Get group (folder) id for this message by path
	 *
	 * @param string $path The path of the group to use like /Inbox/MySubFolder
	 * @return array Object grouping array consisting of {id, name, color} params
	 */
	public function getGroupId($path)
	{
		$grp = $this->getGroup($path);
		return $grp['id'];
	}

	/** 
	 * Get message attachments with disposition='attachment'
	 *
	 * This function is to be used on a saved message, not a new one or
	 * to get the queue of "to-be-added" attachments from $this->attachments,
	 * $this->attachmentsTmp or $this->attachmentsFwd
     */
	public function getAttachments() 
	{
		if (!$this->id)
			return array();

		$dbh = $this->dbh;
		$attachments = array();

		$olist = new CAntObjectList($this->dbh, "email_message_attachment", $this->user);
		$olist->addCondition('and', "message_id", "is_equal", $this->id);
		$olist->getObjects();
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$attachments[] = $olist->getObject($i);
		}

		return $attachments;
    }

	/**
	 * Import a raw message into ANT as an email message object
	 *
	 * @param string $filepath The full path to the file to import
	 * @param string $parser Optional name of the parser to use, otherwise it will be automatic
	 * @param bool $saveOriginal If true a copy of the original message will be saved in its raw form
	 */
	public function import($filepath, $parser=null, $saveOriginal=false)
	{
		$ret= false;

		$mda = new AntMail_DeliveryAngent($this->dbh, $this->user);
		$mda->debug = $this->debug;
        $mda->mailboxId = $this->getValue("mailbox_id");
		$ret = $mda->import($filepath, $this, $parser, $saveOriginal);

		return $ret;
	}

	/**
	 * Reparse a message with the latest parse engine
	 *
	 * @param string $parseer Optionally force the parser to be used, otherwise automatic
	 */
	public function reparse($parser=null)
	{
		$ret= false;

		$mda = new AntMail_DeliveryAngent($this->dbh, $this->user);
		$ret = $mda->reparse($this, $parser);

		return $ret;
	}

	/**
	 * Set embedded images
	 *
	 * This function takes a body, finds references to the antfs system files
	 * and then replaces that reference with an cid:uniqueid that is then
	 * added to the local attachments to be included in this email.
	 *
	 * @return string The body with urls to ANT files embeeded with cid links
	 */
	private function encodeEmbeddedAttachments($body)
	{
		$arr_files = array();

		// The new links will all be: $fid
			
		$break = false;
		$arr_check = array("src=\"/antfs/images/"=>'"',
						   "src='/antfs/images/"=>"'",
							// Legacy links
						   "src=\"/userfiles/file_download.awp?view=1&amp;fid="=>'"',
						   "src='/userfiles/file_download.awp?view=1&amp;fid="=>"'",
						   "src=\"/files/images/"=>'"',
						   "src='/files/images/"=>"'",
						   "src=\"http://".$this->getAccBaseUrl(false)."/userfiles/file_download.awp?view=1&amp;fid="=>'"',
						   "src='http://".$this->getAccBaseUrl(false)."/userfiles/file_download.awp?view=1&amp;fid="=>"'");
		foreach ($arr_check as $chec_beg=>$check_end)
		{
			$cur_pos = 0;
			$attBuf = array();
			while (1) 
			{
				$cur_pos = strpos($body, $chec_beg, $cur_pos);
					
				if ($cur_pos !== false)
				{
					$arr_file_attribs = array();
					$cur_pos = $cur_pos  + strlen($chec_beg);
					$cur_pos_end = strpos($body, $check_end, $cur_pos);
					if ($cur_pos_end !== false)
						$attBuf[] = substr($body, $cur_pos, $cur_pos_end - $cur_pos); // the fileid
					else
						break;
					
					$cur_pos = $cur_pos_end;
				}
				else
					break;
			}

			// Now process files
			foreach ($attBuf as $fid)
			{
				$contentId = $fid; // try this for now, later we may want to acutally open the file and get the name

				$body = str_replace($chec_beg.$fid.$check_end, "src=\"$contentId\"", $body);

				// Add attachment to attachments queue to be sent
				$att = new stdClass();
				$att->id = null;
				$att->type = "antfs_file";
				$att->fileId = $fid;
				$att->contentId = $contentId; // content_id
				$att->cleanFileOnSave = false; // leave file in place

				$this->attachments[] = $att;
			}
		}
		
		return $body;
	}

	/**
	 * Put the HTML part inside a full HTML document 
	 *
	 * @param string $tmpbody The body to enclose in the full HTML document
	 */
	private function encloseHtmlBody($tmpbody)
	{
		global $def_font_family, $def_font_size, $def_font_color;

		// First check to see if the document is alrady built
		if (strpos($tmpbody, "body")!==false || strpos($tmpbody, "html")!==false)
			return $tmpbody;

		$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"      "http://www.w3.org/TR/html4/loose.dtd">';
		$body .= "<html>\n";
		$body .= "<head>
					<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">
				  </head>";
		$body .= "<body class='ant_bdiv'>";
		$body .= "<div style='";
		if ($def_font_family)
			$body .= "font-family: $def_font_family;";
		if ($def_font_size)
			$body .= "font-size: $def_font_size;";
		if ($def_font_color)
			$body .= "color: $def_font_color;";
		$body .= "'>";
		$body .= $tmpbody;
		$body .= "</div>";
		$body .= "</body>\n</html>\n\n";

		return $body;
	}

	/**
	 * Convert embedded images with 'cid:' src to AntFs file references
	 *
	 * This function takes a body, finds references to embedded images/files
	 * and then replaces that reference with the path to the uploaded AntFs file.
	 *
	 * @return string The body with 'cid:*' links pointing to AntFs file IDs.
	 */
	private function decodeEmbeddedAttachments($body)
	{
		return $body;
	}

	/**
	 * Flag a message
	 *
	 * @param bool $on If true the flag is on, otherwise remove flag
	 */
	public function markFlag($on=true)
	{
		$this->setValue("flag_flagged", ($on) ? 't' : 'f');
        //$this->initUpsync(array("action" => "flagged", "value" => $on)); // Now using AntObjectSync
		return $this->save();
	}

	/**
	 * Flag a message as spam
	 *
	 * @param bool $isspam If true then this message is spam
	 */
	public function markSpam($isspam=true)
	{
		$this->setValue("flag_spam", ($isspam) ? 't' : 'f');
        
        // Start background process to learn from this message
        if ($this->id)
        {
            $data = array("message_id"=>$this->id, "user_id"=>$this->user->id);
            $wman = new WorkerMan($this->dbh);
            $jobid = $wman->runBackground("email/spamlearn", serialize($data));
        }
        
		return $this->save();
	}

	/**
	 * Mark the message as read
	 *
	 * @param bool $read If true then this message has been seen
	 */
	public function markRead($read=true)
	{
		$this->setValue("flag_seen", ($read) ? 't' : 'f');
		$res = $this->save();
		
        // Check if type is imap then sync action to the imap server
        
		// Check if the thread should be marked too
		if ($this->getValue("thread") && !$this->skipProcessThread)
		{
			// If there are no more unviewed messages in the thread then mark
			$olist = new CAntObjectList($this->dbh, "email_message", $this->user);
			$olist->addCondition('and', "thread", "is_equal", $this->getValue("thread"));
			$olist->addCondition('and', "flag_seen", "is_equal", ($read) ? 'f' : 't'); // Reverse of set
			$olist->getObjects();
			if ($olist->getNumObjects()==0) // if we marked the last message as read
			{
				$thread = CAntObject::factory($this->dbh, "email_thread", $this->getValue("thread"), $this->user);
				if ($thread->getValue("f_seen") != (($read) ? 't' : 'f'))
				{
					$thread->setValue("f_seen", ($read) ? 't' : 'f');
					$thread->save();
				}
			}
		}

		return $res;
	}

	/**
	 * Create a new thread, or try to find an existing thread for this message
	 */
	private function createNewThread()
	{
		$tid = null;

		$owner = ($this->getValue("owner_id")) ? $this->getValue("owner_id") : $this->user->id;

		// Check if this message should be joined to an already existing thread
		if (trim($this->getValue("in_reply_to")))
		{
			$olist = new CAntObjectList($this->dbh, "email_message", $this->user);
			$olist->addCondition('and', "message_id", "is_equal", $this->getValue("in_reply_to"));
			$olist->addCondition('and', "owner_id", "is_equal", $owner); // Make sure to join only this users threads
			$olist->addMinField("thread");
			$olist->getObjects();
			if ($olist->getNumObjects())
			{
				$data = $olist->getObjectMin(0);
				$tid = $data['thread'];
			}
		}

		$thread = CAntObject::factory($this->dbh, "email_thread", $tid, $this->user);
		if (!$thread->getValue("owner_id"))
			$thread->setValue("owner_id", $owner);

		return $thread;
	}

	/**
	 * Get unique message id for this email message
	 */
	public function getMessageId()
	{
		if (!$this->getValue("message_id"))
		{
			$this->setValue("message_id", '<' . sha1(microtime()) . '.antmail@' . AntConfig::getInstance()->localhost .'>');
		}

		return $this->getValue("message_id");
	}

	/**
	 * Get the message body
	 *
	 * @param bool $forAntReader If true then modify body to be used in the ANT message body reader
	 */
	public function getBody($forAntReader=false)
	{
		// First check to see if we need to reparse this message
		if ($this->getValue("parse_rev") < AntMail_DeliveryAngent::PARSE_REV)
			$this->reparse();

		$body = $this->getValue("body");

		if ($forAntReader)
		{
			if ($this->getValue("body_type") == "plain" || $this->getValue("body_type") == "text/plain")
				$body = str_replace("\n", "<br />", $body);

			$body = EmailActivateLinks($body);

			if ($body)
				$body = $this->cssToInline($body);

			// Remove inline style block
			/*
			$body = preg_replace('@<style[^>]*?>.*?</style>@siu',"", $body);
			 */
		}

		return $body;
	}

	/**
	 * Get a plain text version of this body
	 *
	 */
	public function getPlainTextBody()
	{
		$body = $this->getValue("body");

		if ($this->getValue("body_type") == "html" || $this->getValue("body_type") == "text/html")
		{
			$tags = array (
				0 => '~<h[123][^>]+>~si',
				1 => '~<h[456][^>]+>~si',
				2 => '~<table[^>]+>~si',
				3 => '~<tr[^>]+>~si',
				4 => '~<li[^>]+>~si',
				5 => '~<br[^>]+>~si',
				6 => '~<p[^>]+>~si',
				7 => '~<div[^>]+>~si',
			);

			$body = preg_replace($tags,"\n",$body);
			$body = preg_replace('~</t(d|h)>\s*<t(d|h)[^>]+>~si',' - ',$body);
			$body = preg_replace('~<[^>]+>~s','',$body);
			// reducing spaces
			$body = preg_replace('~ +~s',' ',$body);
			$body = preg_replace('~^\s+~m','',$body);
			$body = preg_replace('~\s+$~m','',$body);
			// reducing newlines
			$body = preg_replace('~\n+~s',"\n",$body);
			
		}

		// Remove inline styles
		$body = preg_replace('@<style[^>]*?>.*?</style>@siu',"", $body);

		return $body;
	}

	/**
	 * Set template fields for an email campaign
	 *
	 * @param CAntObject_EmailCampaign $ecamp The email campaign
	 * @param CAntObject_Customer $customer Optional customer object
	 * @param string $email Optional string email if no customer is defined
	 */
	public function processCampaignTemplate($ecamp, $cust=null, $email=null)
	{
        $body = $this->getBody();

		// Add open in new window
		$newWindowLink =  $this->getAccBaseUrl() . "/public/email/campaign/" . $ecamp->id;
		$newWindowLink .= ($cust) ? "/" . $cust->id : "&eml=" . base64_encode($email);

		// Add unsubscribe
		$unsubLink =  $this->getAccBaseUrl() . "/public/email/unsubscribe.php?eid=" . $ecamp->id;
		$unsubLink .= ($cust) ? "&cid=" . $cust->id : "&eml=" . base64_encode($email);

		// Variables and values
		$vars = array(
			"SUBJECT"=>$this->getValue("subject"),
			"UNSUBSCRIBE"=>$unsubLink,
			"LIVE_VERSION"=>$newWindowLink,
		);

		// Set variables
		foreach ($vars as $name=>$val)
		{
			$body = str_replace("<%$name%>", $val, $body);
			$body = str_replace("%3C%$name%%3E", $val, $body);
			$body = str_replace("&lt;%$name%&gt;", $val, $body);
		}

		// Make sure unsubscribe exists
		if (strpos($body, $unsubLink)===false)
		{
			if ("html" == $this->body_type)
			{
				$body .= "<br /><div style='text-align:center;'><a href=\"$unsubLink\">";
				$body .= "Click here to unsubscribe from this mailing list</a>.</div>";
			}
			else
				$body .= "\r\n\r\nClick this link to unsubscribe from this list: $unsubLink";
		}

		$this->setBody($body, $this->body_type);

		if ($cust)
			$this->setMergeFields($cust);
	}
    
	/**
	 * Do a replace in the body, subject, and recipients if merge fields are defined
	 *
	 * @param CAntObject $object The object we are getting values from
	 * @param array $params Optional associative array of params that can be used for custom merge vars
	 */
    public function setMergeFields($object, $params=array())
    {
		$cmp_to = $this->getHeader("To");
		$cmp_cc = $this->getHeader("Cc");
		$cmp_bcc = $this->getHeader("Bcc");
		$cmp_subject = $this->getHeader("Subject");
		$m_body = $this->getBody();

		// Set object variables
        if ($object)
        {

            $fields = $object->fields->getFields();
            foreach ($fields as $field=>$fdef)
            {
                if ($fdef['type'] != 'fkey_multi' && $fdef['type'] != 'object_multi')
                {
                    $val = $object->getValue($field, true);

                    $cmp_to = str_replace("<%".$field."%>", $val, $cmp_to);
                    $cmp_cc = str_replace("<%".$field."%>", $val, $cmp_cc);
                    $cmp_bcc = str_replace("<%".$field."%>", $val, $cmp_bcc);
                    $cmp_subject = str_replace("<%".$field."%>", $val, $cmp_subject);
                    $m_body = str_replace("<%".$field."%>", $val, $m_body);
                    $m_body = str_replace("&lt;%".$field."%&gt;", $val, $m_body);
                }
            }

            $m_body = str_replace("<%object_link%>", $this->getAccBaseUrl()."/obj/" . $object->object_type . '/' . $object->id, $m_body);
            $m_body = str_replace("<%id%>", $object->id, $m_body);
            $m_body = str_replace("%3C%id%%3E", $object->id, $m_body);
            $m_body = str_replace("&lt;%id%&gt;", $object->id, $m_body);

        }

		// Set custom merge params
		if (is_array($params) && count($params))
		{
            foreach ($params as $pname=>$pval)
            {
				$cmp_to = str_replace("<%".$pname."%>", $pval, $cmp_to);
				$cmp_cc = str_replace("<%".$pname."%>", $pval, $cmp_cc);
				$cmp_bcc = str_replace("<%".$pname."%>", $pval, $cmp_bcc);
				$cmp_subject = str_replace("<%".$pname."%>", $pval, $cmp_subject);
				$m_body = str_replace("<%".$pname."%>", $pval, $m_body);
				$m_body = str_replace("&lt;%".$pname."%&gt;", $pval, $m_body);
            }
		}

		// Add some static merge fields
		$vars = array("CURRENT_YEAR"=>date("Y"));

		// NOTE: UNSUBSCRIBE may be handled first by processCampaignTemplate if we are working with a campaign 
		if ($object->object_type == "customer")
			$vars["UNSUBSCRIBE"] =  $this->getAccBaseUrl() . "/public/email/unsubscribe.php?cid=" . $object->id;

		// Set variables
		foreach ($vars as $name=>$val)
		{
			$m_body = str_replace("<%$name%>", $val, $m_body);
			$m_body = str_replace("%3C%$name%%3E", $val, $m_body);
			$m_body = str_replace("&lt;%$name%&gt;", $val, $m_body);
		}

		// Now apply changes to this message
		$this->setHeader("To", $cmp_to);
		$this->setHeader("Cc", $cmp_cc);
		$this->setHeader("Bcc", $cmp_bcc);
		$this->setHeader("Subject", $cmp_subject);
		$this->setBody($m_body, $this->body_type);
    }

	/**
	 * Reconstruct the original message
     */
	public function getOriginal() 
	{
		if (!$this->id)
			return "";

		$dbh = $this->dbh;

		// See if original message text is stored
		$result = $dbh->Query("select lo_message from email_message_original where message_id='".$this->id."'");
		if ($dbh->GetNumberRows($result))
		{
			$loid = $dbh->GetValue($result, 0, "lo_message");
			if ($loid)
			{
				$href = $dbh->loOpen($loid);
				if ($href)
				{
					$buf = "";
					$tmp = "";
					$size = $dbh->loSize($href);
					while (($tmp = $dbh->loRead($href)) == true)
					{
						$buf .= $tmp;
					}

					return $buf;
				}
			}
		}

		// No original message - reconstruct from parts
		//return $this->getRecurParts();
		return "";
	}
    
    /**
     * Gets the object id of the sync-ed email message
     *
     * @param integer $messageUid   Unique id of the email message
     * @param integer $accountId    Email Account Id used when sync-ing
     * @param integer $mailboxId    Mailbox Id used when sync-ing     
     */
    public function getSyncEmailId($messageUid, $accountId, $mailboxId=null)
    {
        $ret = null;
        
        $olist = new CAntObjectList($this->dbh, "email_message", $this->user);
        $olist->fromSync = true;
        
        if($mailboxId > 0)
            $olist->addCondition('and', "mailbox_id", "is_equal", $mailboxId);
            
        if($accountId > 0)
            $olist->addCondition('and', "email_account", "is_equal", $accountId);
        
        $olist->addCondition('and', "message_uid", "is_equal", $messageUid);
        $olist->getObjects(0, 1);
        $num = $olist->getNumObjects();
        for ($x = 0; $x < $num; $x++)
        {
            $obj = $olist->getObject($x);
            $ret = $obj->id;
        }
        
        return $ret;
    }
    
    /**
    * Syncs the action to the imap server
    *
	* @depricated AntObjectSync has replaced this function
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function initUpsync($params)
    {
		// NOTE: we are now using AntObjectSync to send updates from Netric to email backend
		/**
        $data = array(
                    "action" => $params["action"],
                    "value" => $params["value"],
                    "object_id" => $this->id,
                    "user_id" => $this->user->id,
                    "test_mode" => $this->testMode,
                    );
		//$this->processUpsync($data); // Used to be called from the worker, now is immediate
		 */
                
        return true;
    }
    
    /**
     * Syncs the action to the imap server
     *
     * @params array $data An assocaitive array of parameters passed to this function.
     */
    public function processUpsync($data)
    {
        $ret = null;

        if(!$this->id || !$this->getValue("email_account"))
            return false;
        
        // Get Account Info
        $accountObj = new AntMail_Account($this->dbh, $this->getValue("email_account"));

		if (!$accountObj->type)
			return false; // no upsync to be done if this is a system email - no backend server associated
        
        // Get Mailbox Entry
        $mailboxObj = new CAntObject($this->dbh, "email_thread", $this->id, $this->user);
        $mailboxInfo = $mailboxObj->getGroupingById("mailbox_id", $this->getValue("mailbox_id"));
        
        $host = $accountObj->host;
        $mailBox = $mailboxInfo['mailbox'];
        $username = $accountObj->username;
        $password = $accountObj->password;
        $type = $accountObj->type;
        $port = $accountObj->port;
        $ssl = $accountObj->ssl;

        // Instantiate Ant Mail backend
        $mailObj = new AntMail_Backend($type, $host, $username, $password, $port, $ssl);
        $mailObj->processUpsync($mailBox, $this->getValue("message_uid"), $data["action"], $data["value"]);
        unset($mailObj); // destruct backend class class
        
        return $ret;
    }
    
    /**
    * Adds a new mailbox
    *
    * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
    * @param string $mailboxTitle  Title of the mailbox  
    */
    public function addMailbox($mailboxPath, $mailboxTitle)
    {
        $color = null;
        $sort = 1;
        $parentId = null;
        $args = array("type" => "imap", "mailbox" => $mailboxPath);
        
        $data = $this->addGroupingEntry("mailbox_id", $mailboxTitle, $color, $sort, $parentId, false, $args);

        return $data;
    }

    /**
    * Deletes a mailbox
    *
    * @param integer $mailboxId  Id of the mailbox to be deleted
    */
    public function deleteMailbox($mailboxId)
    {
        $result = $this->deleteGroupingEntry("mailbox_id", $mailboxId);

        return $result;
    }

    /**
    * Returns list of saved mailboxes
    *     
    * @param String $type       Type of the mailbox
    */
    public function getMailboxes($type)
    {
        $data = array();
        
        if(!empty($type))
        {
            $conditions = array();
            $conditions[] = array("blogic" => "and", "field" => "type", "operator" => "=", "condValue" => "'$type'");
            $data = $this->getGroupingData("mailbox_id", $conditions);
        }

        return $data;
    }

	/**
	 * Process email addresses - trim, clean, translate
	 *
	 * @param string $list List of addresses separated with a comma
	 * @return string Processed email addresses
	 */
	public function processEmailAddresses($list)
	{
		if ($list)
		{
			$parts = explode(",", $list);
			$list = "";

			foreach ($parts as $part)
			{
				$part = trim($part);
				$part = stripslashes($part);

				if (strpos($part, "@")===false)
				{
					// TODO: Check for groups
					/*
					if ($USERID)
					{
						$possible_name = trim($part, "\"");
						$result = $dbh->Query("select id from contacts_personal_labels where 
												lower(name)=lower('".$dbh->Escape($possible_name)."') and user_id='$USERID'");
						if ($dbh->GetNumberRows($result))
						{
							$gid = $dbh->GetValue($result, 0, "id");
							$part = EmailExplodeGroup($dbh, $gid);
						}
					}
					 */
				}

				$list .= ($list) ? ", $part" : $part;
			}

			$list = $this->cleanEmailAddresses($list);
		}

		return $list;
	}

	/**
	 * Cleanup and trim email addresses for delivery
	 *
	 * @param string $list List of addresses separated with a comma
	 * @return string Cleaned email addresses
	 */
	public function cleanEmailAddresses($list)
	{
		if ($list && strpos($list, ",")!==false)
		{
			$parts = explode(",", $list);
			$list = "";

			foreach ($parts as $part)
			{
				$part = trim($part);
				if ($part != "" && $part != " " && strpos($part, "@")!==false)
					$list .= ($list) ? ", $part" : $part;
			}
		}

		return $list;
	}

	/**
	 * Get SMTP settings for this type of message
	 *
	 * @param AntMail_Account $account Used for account specific SMTP settings
	 * @param bool $bulk Used to indicate this is a bulk message
	 */
	public function setupSMTP($account=null, $bulk=false)
	{
		// If already set then skip
		if (null == $this->smtpHost)
		{
			// Check for bulk system settings
			if ($bulk)
			{
				$host = $this->ant->settingsGet("email/smtp_bulk_host", $this->dbh);
				if ($host)
				{
					$this->smtpHost = $host;
					$this->smtpUser = $this->ant->settingsGet("email/smtp_bulk_user", $this->dbh);
					$this->smtpPassword = $this->ant->settingsGet("email/smtp_bulk_password", $this->dbh);
					$this->smtpPort = $this->ant->settingsGet("email/smtp_bulk_port", $this->dbh);
				}
				else
				{
					$emailConf = AntConfig::getInstance()->email;
					
					// Use config settings if they exist
					$this->smtpHost = isset($emailConf['bulk_server']) ? $emailConf['bulk_server'] : null;
					$this->smtpUser = isset($emailConf['bulk_user']) ? $emailConf['bulk_user'] : null;
					$this->smtpPassword = isset($emailConf['smtpPassword']) ? $emailConf['smtpPassword'] : null;
					$this->smtpPort = isset($emailConf['bulk_port']) ? $emailConf['bulk_port'] : null;
				}
			}
			else if ($account)
			{
				$this->smtpHost = $account->smtpHost;
				$this->smtpUser = isset($account->smtpUser) ? $account->smtpUser : null;
				$this->smtpPassword = isset($account->smtpPassword) ? $account->smtpPassword : null;
				$this->smtpPort = isset($account->smtpPort) ? $account->smtpPort : null;
			}

			// Check for global system settings
			if (!$this->smtpHost)
			{	
				$host = $this->ant->settingsGet("email/smtp_host", $this->dbh);
				if ($host)
				{
					$this->smtpHost = $host;
					$this->smtpUser = $this->ant->settingsGet("email/smtp_user", $this->dbh);
					$this->smtpPassword = $this->ant->settingsGet("email/smtp_password", $this->dbh);
					$this->smtpPort = $this->ant->settingsGet("email/smtp_port", $this->dbh);
				}
			}

			// Use system defaults
			if (!$this->smtpHost)
			{
				$this->smtpHost = AntConfig::getInstance()->email['server'];
			}
		}
	}

	/**
	 * Convert css blocks into inline styles
	 *
	 * @param string $html Html to process
	 * @return string The edited html
	 */
	public function cssToInline($html)
	{
		try
		{
			$cssToInline = new AntMail_Parser_CssToInline();
			$cssToInline->setHTML($html);
			$buf = $cssToInline->convert();

			// Seems to be clearing it on failure for some reason
			if (!empty($buf))
				$html = $buf;
		}
		catch (Exception $e)
		{
			AntLog::getInstance()->error("AntMail_Parser_CssToInline Failed with: " . $e->getMessage());
		}

		return $html;
	}

	/**
	 * Return default list of mailboxes which is called by verifyDefaultGroupings in base class.
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @return array
	 */
	public function getVerifyDefaultGroupingsData($fieldName)
	{
		$checkfor = array();

		if ($fieldName == "mailbox_id")
			$checkfor = array("Inbox" => "-100", "Trash" => "-90", "Junk Mail" => "-80", "Sent" => "-70");

		return $checkfor;
	}	
}
