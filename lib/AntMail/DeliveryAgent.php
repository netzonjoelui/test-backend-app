<?php
/**
 * ANT Mail Delivery Angent
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * function like "send" for email messages.
 *
 * Example
 * <code>
 * </code>
 *
 * @category	CAntObject
 * @package		DeliveryAngent
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntFs.php");
require_once("lib/AntLog.php");
require_once("lib/parsers/MimeMailParser.php");

/**
 * Class that handles delivering email into ANT email
 */
class AntMail_DeliveryAngent
{
	/**
	 * Handle to account database
	 *
	 * @var CDatabase
	 */
	public $dbh = null;

	/**
	 * Handle to current user
	 *
	 * @var AntUser
	 */
	public $user = null;
    
    /**
     * Overrides the mailbox "Inbox" when importing email message
     *
     * @var Integer
     */
    public $mailboxId = null;

	/**
	 * Current parser revision
	 *
	 * This is used to go back and re-process messages if needed
	 *
	 * @var int
	 */
	const PARSE_REV = 15;

	/**
	 * Constructor
	 *
	 * @param CDatabase $dbh An active handle to a database connection
	 * @param AntUser $user Optional current user
	 */
	function __construct($dbh, $user)
	{
		$this->dbh = $dbh;
		$this->user = $user;
	}
	
	/**
	 * Import a raw message into ANT as an email message object
	 *
	 * @param string $filepath The full path to the file to import
	 * @param CAntObject_Email $email The email message to import into
	 * @param string $parser Optionally force parser type
	 * @param bool $saveOriginal If set save a copy of the original raw message
	 */
	public function import($filepath, $email, $parser=null, $saveOriginal=false)
	{
		$ret= false;

		// Determine what parser to use
		if(function_exists('mailparse_msg_parse') && ($parser==null || $parser=="mailparse")) 
		{
			// pecl mailparse is preferred but only available on *nix
			$ret = $this->importMailParse($filepath, $email); // Preferred mailparse extension
		}
		else
		{
			$ret = $this->importMimeDecode($filepath, $email); // failsafe php extension
		}

		if ($ret && $saveOriginal)
			$this->saveOriginal($ret, $filepath);

		return $ret;
	}	

	/**
	 * Use mimeDecode PHP library to decode raw message and import it into ANT
	 *
	 * Inject an email from a file into ANT using mail_mimeDecode to parse
	 * Mail_mimeDecode is ineffecient because it requires you read the entire
	 * message into memory to parse. This is fine for small messages but ANT
	 * will accept up to 2GB emails so memory can be a limitation here. It is preferrable
	 * to use the php mimeParse extension and read is incrementally in to the resource
	 *
	 * @param string $filepath The path to the raw rfc822 message
	 * @param CAntObject_EmailMessage $email The message to parse into
	 * @return bool true on success, false on failure
	 */
	public function importMimeDecode($filepath, $email)
	{
		if (file_exists($filepath))
		{
			$rfc822Msg = file_get_contents($filepath);
		}
		else
		{
			return false; // Fail
		}

		$user = $this->user;
		$dbh = $this->dbh;

		$mobj = new Mail_mimeDecode($rfc822Msg);
		$message = $mobj->decode(array('decode_headers' => true, 'decode_bodies' => true, 
									   'include_bodies' => true, 'charset' => 'utf-8'));
		$plainbody = $this->importMimeDecodeGetBody($message, "plain");
		$htmlbody = $this->importMimeDecodeGetBody($message, "html");
        if (isset($message->headers['x-spam-flag']))
		    $spamFlag = (trim(strtolower($message->headers['x-spam-flag'])) == "yes") ? 't' : 'f';
        else
            $spamFlag = 'f';

		// Create new mail object and save it to ANT
        
		if($this->mailboxId > 0) // If set, save the email in the mailbox id
		{
            $email->setGroupId($this->mailboxId);
		}
		else if (!$email->getValue("mailbox_id"))
		{
			// As default, save the email message in "Inbox"
		    $email->setGroup("Inbox");
		}

		$messageDate = (isset($message->headers['date'])) ? date(DATE_RFC822, strtotime($message->headers['date'])) : date(DATE_RFC822);
            
		$email->setValue("message_date", $messageDate);
		$email->setValue("flag_seen", 'f');
		$email->setValue("parse_rev", self::PARSE_REV);
		$email->setHeader("subject", trim($this->decodeMimeStr($message->headers['subject'])));
		$email->setHeader("from", trim($this->decodeMimeStr($message->headers['from'])));
		$email->setHeader("to", trim($this->decodeMimeStr($message->headers['to'])));
        if (isset($message->headers['cc']))
		    $email->setHeader("cc", trim($this->decodeMimeStr($message->headers['cc'])));
        if (isset($message->headers['bcc']))
		    $email->setHeader("bcc", trim($this->decodeMimeStr($message->headers['bcc'])));
        if (isset($message->headers['in_reply_to']))
		    $email->setHeader("in_reply_to", trim($message->headers['in-reply-to']));
		$email->setHeader("flag_spam", $spamFlag);
        if (isset($message->headers['message_id']))
		    $email->setHeader("message_id", trim($message->headers['message-id']));
		$email->setBody($plainbody, "plain");
		if ($htmlbody)
			$email->setBody($htmlbody, "html");

		$ret = $this->importMimeDecodeAtt($message, $email);

		// Save message to inbox - filters will move if needed
		if ($email->getHeader("from"))
		{
			$mid = $email->save();
		}
		else
		{
			$mid = 0;
		}

		// Process filters/autoresponders before saving
		$this->importProcessFilters($email, $dbh, $user);


		return $mid;
	}

	/** 
	 * Process attachments for a message being parsed by mimeDecode
	 *
	 * @param Mail_mimeDecode_Part $mimePart The part we are working on right now
	 * @param CAntObject_EmailMessage $email Current email we are saving to
	 */
	private function importMimeDecodeAtt(&$mimePart, &$email)
	{
		if((isset($mimePart->disposition) && strcasecmp($mimePart->disposition,"attachment")==0) ||
            (isset($mimePart->ctype_primary) && $mimePart->ctype_primary=="image"))
		{
			/*
			 * 	1. Write attachment to temp file
			 *	-------------------------------------------------------
			 * 	It is important to use streams here to try and keep the attachment out of memory if possible
			 * 	The parser should alrady have decoded the bodies for us so no need to use base64_decode or
			 * 	anything like that
			 */
			$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
			if (!file_exists($tmpFolder))
				@mkdir($tmpFolder, 0777, true);
			$tmpFile = tempnam($tmpFolder, "ematt");
			$handle = fopen($tmpFile, "w");
			fwrite($handle, trim($mimePart->body));
			fclose($handle);

			if (!file_exists($tmpFile))
				return false;

			// 2. Add the attachment to the EmailMessage object
			$att = $email->addAttachment($tmpFile, $mimePart->d_parameters['filename']);
			$att->name = $mimePart->ctype_parameters['name'];
			$att->fileName = $mimePart->d_parameters['filename'];
			$att->conentType = $mimePart->ctype_primary."/".$mimePart->ctype_secondary; 
			$att->contentId	= $mimePart->ctype_parameters['content-id']; // content_id
			$att->contentDisposition = $mimePart->disposition; // disposition
			$att->contentTransferEncoding = $mimePart->headers['content-transfer-encoding']; // encoding
			$att->cleanFileOnSave = true; // cleanup once the attachment has been saved
		}
		else if(isset($mimePart->ctype_primary) && strcasecmp($mimePart->ctype_primary,"multipart")==0) // call recurrsively to get all attachments
		{
			foreach($mimePart->parts as $subPart) 
			{
				$this->importMimeDecodeAtt($subPart, $email);
			}
		}
	}

	/**
	 * Get text body of a message based on subtype
	 *
	 * @param Mail_mimeDecode::part $mimePart Mime part to process
	 * @param string $subtype Sub content type to get - usually plain or html
	 * @param string The body of the message
	 */
	private function importMimeDecodeGetBody($mimePart, $subtype) 
	{
		$body = "";

		if(strcasecmp($mimePart->ctype_primary,"text")==0 && strcasecmp($mimePart->ctype_secondary,$subtype)==0 && isset($mimePart->body))
		{
			$body = $mimePart->body;
		}
		else if(strcasecmp($mimePart->ctype_primary,"multipart")==0) 
		{
			foreach($mimePart->parts as $part) 
			{
				if(!isset($part->disposition) || strcasecmp($part->disposition,"attachment"))  
				{
					$body = $this->importMimeDecodeGetBody($part, $subtype, $body);
				}
			}
		}

		return $body;
	}

	/**
	 * Use mailParse to decode raw message and import it into ANT
	 *
	 * Inject an email from a file into ANT using mailParse to parse which is preferred because
	 * Mail_mimeDecode is ineffecient because it requires you read the entire
	 * message into memory to parse. This is fine for small messages but ANT
	 * will accept up to 2GB emails so memory can be a limitation here. It is preferrable
	 * to use the php mimeParse extension and read is incrementally in to the resource.
	 *
	 * @param string $filepath The path to the raw rfc822 message
	 * @param CAntObject_EmailMessage $email The message to parse into
	 * @return bool true on success, false on failure
	 */
	public function importMailParse($filepath, $email)
	{
		if (!file_exists($filepath))
			return false; // Fail

		$user = $this->user;
		$dbh = $this->dbh;

		$parser = new MimeMailParser();
		$parser->setPath($filepath);

		$plainbody = $parser->getMessageBody('text');
		$htmlbody = $parser->getMessageBody('html');

		// Get char types
		$htmlCharType = $this->getCharTypeFromHeaders($parser->getMessageBodyHeaders("html"));
		$plainCharType = $this->getCharTypeFromHeaders($parser->getMessageBodyHeaders("text"));

		$spamFlag = (trim(strtolower($parser->getHeader('x-spam-flag'))) == "yes") ? 't' : 'f';

		// Make sure messages are unicode
		ini_set('mbstring.substitute_character', "none"); 
  		$plainbody= mb_convert_encoding($plainbody, 'UTF-8', $plainCharType); 
  		$htmlbody= mb_convert_encoding($htmlbody, 'UTF-8', $htmlCharType); 
		/*
		$plainbody = iconv($plainCharType,  "UTF-8//IGNORE", $plainbody);
		$htmlbody = iconv($htmlCharType,  "UTF-8//IGNORE", $htmlbody);
		 */

		if($this->mailboxId > 0) // If set, save the email in the mailbox id
		{
            $email->setGroupId($this->mailboxId);
		}
		else if (!$email->getValue("mailbox_id"))
		{
			// As default, save the email message in "Inbox"
		    $email->setGroup("Inbox");
		}

        $origDate = $parser->getHeader('date');
        if (is_array($origDate))
            $origDate = $origDate[count($origDate) - 1];
        if (!strtotime($origDate) && $origDate)
            $origDate = substr($origDate, 0, strrpos($origDate, " "));
		$messageDate = ($origDate) ? date(DATE_RFC822, strtotime($origDate)) : date(DATE_RFC822);
            
		// Create new mail object and save it to ANT
		$email->setValue("message_date", $messageDate);
		$email->setValue("flag_seen", 'f');
		$email->setValue("parse_rev", self::PARSE_REV);
		$email->setHeader("Subject", trim($this->decodeMimeStr($parser->getHeader('subject'))));
		$email->setHeader("From", trim($this->decodeMimeStr($parser->getHeader('from'))));
		$email->setHeader("To", trim($this->decodeMimeStr($parser->getHeader('to'))));
		$email->setHeader("Cc", trim($this->decodeMimeStr($parser->getHeader('cc'))));
		$email->setHeader("Bcc", trim($this->decodeMimeStr($parser->getHeader('bcc'))));
		$email->setHeader("in_reply_to", trim($parser->getHeader('in-reply-to')));
		$email->setHeader("flag_spam", $spamFlag);
		$email->setHeader("message_id", trim($parser->getHeader('message-id')));
		$email->setBody($plainbody, "plain");
		if ($htmlbody)
			$email->setBody($htmlbody, "html");

		$attachments = $parser->getAttachments();
		foreach ($attachments as $att)
			$this->importMailParseAtt($att, $email);

		if ($email->getHeader("from"))
		{
			$mid = $email->save();
			// Process filters/autoresponders before saving
			$this->importProcessFilters($email, $dbh, $user);
		}
		else
		{
			$mid = 0;
		}

		// Cleanup resources
		$parser = null;

		return $mid;
	}

	/** 
	 * Process attachments for a message being parsed by mimeparse
	 *
	 * @param mimeParsePart $mimePart The part we are working on right now
	 * @param CAntObject_EmailMessage $email Current email we are saving to
	 */
	private function importMailParseAtt(&$parserAttach, &$email)
	{
		/*
		 * 	1. Write attachment to temp file
		 *	-------------------------------------------------------
		 * 	It is important to use streams here to try and keep the attachment out of memory if possible
		 * 	The parser should alrady have decoded the bodies for us so no need to use base64_decode or
		 * 	anything like that
		 */
		$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
		if (!file_exists($tmpFolder))
			@mkdir($tmpFolder, 0777, true);
		$tmpFile = tempnam($tmpFolder, "ematt");
		$handle = fopen($tmpFile, "w");
		$buf = null;
		while (($buf = $parserAttach->read()) != false)
		{
			fwrite($handle, $buf);
		}
		fclose($handle);

		if (!file_exists($tmpFile))
			return false;

		// 2. Add the attachment to the EmailMessage object
		$att = $email->addAttachment($tmpFile, $parserAttach->getFilename());
		$att->name = $parserAttach->getFilename();
		$att->fileName = $parserAttach->getFilename();
		$att->conentType = $parserAttach->getContentType(); 
		$att->contentId	= $parserAttach->content_id; // content_id
		$att->contentDisposition = $parserAttach->getContentDisposition(); // disposition
		$att->contentTransferEncoding = $parserAttach->transfer_encoding; // encoding
		$att->cleanFileOnSave = true; // cleanup once the attachment has been saved

		//echo "<pre>".var_export($att, true)."</pre>";
	}

	/**
	 * Process filters and actions for an email message
	 *
	 * @param CAntObject_Email $email Handle to email to check, if null and not called statically then use $this
	 * @param CDatabase $dbh Handle to database quired if called statically
	 * @param AntUser $user Handle to current user required if called statically
	 */
	private function importProcessFilters($email=null, $dbh=null, $user=null)
	{
		// Check for spam status
		// ------------------------------------------------
		$fromEmail = EmailAdressGetDisplay($email->getHeader("from"), 'address');
		if ("t" == $email->getValue("flag_spam"))
		{
			// First make sure this user is not in the whitelist
			$query = "select id from email_settings_spam where preference='whitelist_from' 
						and '".strtolower($fromEmail)."' like lower(replace(value, '*', '%'))
						and user_id='".$user->id."'";
			if (!$dbh->GetNumberRows($dbh->Query($query)))
			{
				$email->move($email->getGroupId("Junk Mail"));
				return; // No futher filters should be processed if this is junk
			}
		}
		else
		{
			// Now make sure this user is not in the blacklist
			$query = "select id from email_settings_spam where preference='blacklist_from' 
						and '".strtolower($fromEmail)."' like lower(replace(value, '*', '%'))
						and user_id='".$user->id."'";
			if ($dbh->GetNumberRows($dbh->Query($query)))
			{
				$email->move($email->getGroupId("Junk Mail"));
				//$email->setGroup("Junk Mail");
				return;
			}
		}

		// Check for filters
		// ------------------------------------------------
		$query = "select kw_subject, kw_to, kw_from, kw_body, act_mark_read, act_move_to 
					from email_filters where user_id='".$user->id."'";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$fSkipFilter = false;

			if ($row['kw_subject'] && $email->getHeader("subject"))
			{
				if (stristr(strtolower($email->getHeader("subject")), strtolower($row['kw_subject']))!==false)
				{
					$fSkipFilter = false;
				}
				else
				{
					$fSkipFilter = true;
				}
			}

			if ($row['kw_to'] && $email->getHeader("to"))
			{
				if (stristr(strtolower($email->getHeader("to")), strtolower($row['kw_to']))!==false)
				{
					$fSkipFilter = false;
				}
				else
				{
					$fSkipFilter = true;
				}
			}

			if ($row['kw_from'] && $email->getHeader("from"))
			{
				if (stristr(strtolower($email->getHeader("from")), strtolower($row['kw_from']))!==false)
				{
					$fSkipFilter = false;
				}
				else
				{
					$fSkipFilter = true;
				}
			}

			if ($row['kw_body'] && $email->getBody())
			{
				$body = strtolower(strip_tags($email->getBody()));
				if (stristr($body, strtolower($row['kw_body']))!==false)
				{
					$fSkipFilter = false;
				}
				else
				{
					$fSkipFilter = true;
				}
			}

			if (!$fSkipFilter)
			{
				if ($row['act_move_to'])
					$email->move($row['act_move_to']);
					//$email->setGroupId($row['act_move_to']);

				if ($rpw['act_mark_read'] == 't')
					$email->markRead();
					//$email->f_seen = 'f';
			}
		}
		$dbh->FreeResults($result);

        // Check for a future date which is almost always junk mail
        // ------------------------------------------------
        if (strtotime("+30 days") < $email->getValue("message_date"))
        {
            $email->move($email->getGroupId("Junk Mail"));
        }
	}

	/**
	 * Process this message checking for auto responders
	 */
	private function importProcessAutoResp()
	{
		// TODO: add auto-responder
	}

	/**
	 * Decode mime encoded values
	 *
	 * @param string $string The strign to decode
	 * @param string $charset Defaults to unicode, the character set we are working with
	 * @return string If the string was encoded, then return the decoded string, otherwise return the original string
	 */
	public function decodeMimeStr($string, $charset="UTF-8")
	{
		if (is_array($string)) // some parsers pass empty array if value not found
			return "";

		if (!$string)
			return "";

		$newString = '';
		$elements=imap_mime_header_decode($string);

		for($i=0;$i<count($elements);$i++)
		{
			if ($elements[$i]->charset == 'default')
			  $elements[$i]->charset = $charset; //'iso-8859-1';

			if (mb_check_encoding($elements[$i]->text, $charset))
				$newString .=  mb_convert_encoding($elements[$i]->text, $charset, $elements[$i]->charset);
			else
				$newString .= utf8_encode($elements[$i]->text);
		}

		// Make sure UTF is proper
		$newString = iconv('utf-8',"utf-8//IGNORE", $newString);

		return $newString;
	} 
	
	/**
	 * Reparse an exisitng message
	 *
	 * @param CAntObject_EmailMessage $email The email message to reporcess
	 * @param string $parser Optionally force a mime parser engine, othewise automatic
	 */
	public function reparse($email, $parser=null)
	{
		$ret= false;
		$this->verbose = true;

		// First find out if original message exists and then reparse
		// --------------------------------------------------
		$filepath = $this->reparseGetOriginal($email->id);
		if ($filepath)
		{
			// Purge existing attachments
			$this->reparseRemoveAtt($email);
			
			// Determine what parser to use & reinject message
			if(function_exists('mailparse_msg_parse') && ($parser==null || $parser=="mailparse")) 
			{
				// pecl mailparse is preferred but only available on *nix
				$this->reparseMailParse($filepath, $email); // Preferred mailparse extension
			}
			else
			{
				$this->reparseMimeDecode($filepath, $email); // failsafe php extension
			}

			$ret = $email->save(false);

            // Update date for thread
            if ($email->getValue('thread'))
            {
                $thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue('thread'));
                $thread->setValue("ts_delivered", $email->getValue("message_date"));
                $thread->save(false);
            }
		}


		// If it does not simply try to reconstruct the body
		// --------------------------------------------------
		if (!$filepath)
			$ret = $this->reparseBodyFromAtt($email);


		return $ret;
	}	

	/**
	 * Try to just rebuild the body from attached data (send used to do this and really old emails).
	 * 
	 * This is really legacy code and will eventually go away
	 *
	 * @param CAntObject_EmailMessage $email The email message to reporcess
	 */
	private function reparseBodyFromAtt($email)
	{
		if (!$email->id)
			return false;

		// This is from legacy messages, new sent messages will not have this at all
		$query = "select attached_data, encoding from email_message_attachments where message_id='".$email->id."' and content_type='text/html'";
		$result = $this->dbh->Query($query);
		if ($this->dbh->GetNumberRows($result))
		{
			$ret = $this->dbh->GetValue($result, 0, "attached_data");

			if ($ret)
			{
				if ($this->dbh->GetValue($result, 0, "encoding") == "base64")
					$ret = base64_decode($ret);
				if ($this->dbh->GetValue($result, 0, "encoding") == "quoted-printable")
					$ret = quoted_printable_decode($ret);

				$email->setBody($ret, "html");
				$email->setValue("parse_rev", self::PARSE_REV);
				$email->save(false);
			}
		}
			
		$this->dbh->FreeResults($result);
		
		return true;
	}

	/**
	 * Get full original message by saving it to a local path
	 * 
	 * @param integer $mid The message id to pull
	 * @return string Full path to temp file, or null if original was not found
	 */
	private function reparseGetOriginal($mid)
	{
		$oid = false;

		if (!$mid)
			return null;

		$result = $this->dbh->Query("select lo_message from email_message_original where message_id='".$mid."'");
		if ($this->dbh->GetNumberRows($result))
			$oid = $this->dbh->GetValue($result, 0, "lo_message");

		if (!$oid)
			return null;

		$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
		if (!file_exists($tmpFolder))
			@mkdir($tmpFolder, 0777, true);
		$tmpFile = tempnam($tmpFolder, "em");

		$ret = $this->dbh->loExport($oid, $tmpFile);

		// Normalize new lines to \r\n
		if ($ret)
		{
			$handle = @fopen($tmpFile, "r");
			$handleNew = @fopen($tmpFile."-pro", "w");
			$buffer = null;
			if ($handle) 
			{
				while (($buffer = fgets($handle, 4096)) !== false) 
				{

					fwrite($handleNew,  preg_replace('/\r?\n$/', '', $buffer)."\r\n");
				}
				fclose($handle);
				fclose($handleNew);
				unlink($tmpFile);
				$tmpFile = $tmpFile."-pro"; // update name to match processed file
			}
		}

		//echo file_get_contents($tmpFile);

		if ($ret)
			return $tmpFile;
		else
			return null;
	}

	/**
	 * Remove attachments for a given message
	 *
	 * @param CAntObject_EmailMessage $email The email message to reporcess
	 */
	private function reparseRemoveAtt($email)
	{
		$attachments = $email->getAttachments();
		if (count($attachments))
		{
			foreach ($attachments as $att)
			{
				// Open the base object because we are going to delete the file associations manually
				$obj = new CAntObject($this->dbh, "email_message_attachment", $att->id, $this->user);
				$obj->removeHard();
			}
		}
	}
	
	/**
	 * Use mimeDecode PHP library to decode raw message and import it into ANT
	 *
	 * Inject an email from a file into ANT using mail_mimeDecode to parse
	 * Mail_mimeDecode is ineffecient because it requires you read the entire
	 * message into memory to parse. This is fine for small messages but ANT
	 * will accept up to 2GB emails so memory can be a limitation here. It is preferrable
	 * to use the php mimeParse extension and read is incrementally in to the resource
	 *
	 * @param string $filepath The path to the raw rfc822 message
	 * @param CAntObject_EmailMessage $email The message to parse into
	 * @return bool true on success, false on failure
	 */
	public function reparseMimeDecode($filepath, $email)
	{
		if (file_exists($filepath))
		{
			$rfc822Msg = file_get_contents($filepath);
		}
		else
		{
			return false; // Fail
		}

		$user = $this->user;
		$dbh = $this->dbh;

		$mobj = new Mail_mimeDecode($rfc822Msg);
		$message = $mobj->decode(array('decode_headers' => true, 'decode_bodies' => true, 
									   'include_bodies' => true, 'charset' => 'utf-8'));
		$plainbody = $this->importMimeDecodeGetBody($message, "plain");
		$htmlbody = $this->importMimeDecodeGetBody($message, "html");
		$spamFlag = (trim(strtolower($message->headers['x-spam-flag'])) == "yes") ? 't' : 'f';

		// Create new mail object and save it to ANT
		$email->setValue("parse_rev", self::PARSE_REV);
		$email->setHeader("subject", trim($this->decodeMimeStr($message->headers['subject'])));
		$email->setHeader("from", trim($this->decodeMimeStr($message->headers['from'])));
		$email->setHeader("to", trim($this->decodeMimeStr($message->headers['to'])));
		$email->setHeader("cc", trim($this->decodeMimeStr($message->headers['cc'])));
		$email->setHeader("in_reply_to", trim($message->headers['in-reply-to']));
		$email->setHeader("message_id", trim($message->headers['message-id']));
		if ($htmlbody)
			$email->setBody($htmlbody, "html");
		else
			$email->setBody($plainbody, "plain");

		// The same function works for inport and reparse
		$ret = $this->importMimeDecodeAtt($message, $email);
	}

	/**
	 * Use mailParse to decode raw message and import it into ANT
	 *
	 * Inject an email from a file into ANT using mailParse to parse which is preferred because
	 * Mail_mimeDecode is ineffecient because it requires you read the entire
	 * message into memory to parse. This is fine for small messages but ANT
	 * will accept up to 2GB emails so memory can be a limitation here. It is preferrable
	 * to use the php mimeParse extension and read is incrementally in to the resource.
	 *
	 * @param string $filepath The path to the raw rfc822 message
	 * @param CAntObject_EmailMessage $email The message to parse into
	 * @return bool true on success, false on failure
	 */
	public function reparseMailParse($filepath, $email)
	{
		if (!file_exists($filepath))
			return false; // Fail

		$user = $this->user;
		$dbh = $this->dbh;

		$parser = new MimeMailParser();
		$parser->setPath($filepath);

		$plainbody = $parser->getMessageBody('text');
		$htmlbody = $parser->getMessageBody('html');

		// Get char types
		$htmlCharType = $this->getCharTypeFromHeaders($parser->getMessageBodyHeaders("html"));
		$plainCharType = $this->getCharTypeFromHeaders($parser->getMessageBodyHeaders("text"));

		$spamFlag = (trim(strtolower($parser->getHeader('x-spam-flag'))) == "yes") ? 't' : 'f';

		// Make sure messages are unicode
		ini_set('mbstring.substitute_character', "none"); 
  		$plainbody= mb_convert_encoding($plainbody, 'UTF-8', $plainCharType); 
  		$htmlbody= mb_convert_encoding($htmlbody, 'UTF-8', $htmlCharType); 
		//$plainbody = iconv($plainCharType,  "UTF-8//IGNORE", $plainbody);
		//$htmlbody = iconv($htmlCharType,  "UTF-8//IGNORE", $htmlbody);

        $origDate = $parser->getHeader('date');
        if (is_array($origDate))
            $origDate = $origDate[count($origDate) - 1];
        if (!strtotime($origDate) && $origDate)
            $origDate = substr($origDate, 0, strrpos($origDate, " "));
        $messageDate = ($origDate) ? date(DATE_RFC822, strtotime($origDate)) : date(DATE_RFC822);

        // Create new mail object and save it to ANT
        $email->setValue("message_date", $messageDate);
		$email->setValue("parse_rev", self::PARSE_REV);
		$email->setHeader("Subject", trim($this->decodeMimeStr($parser->getHeader('subject'))));
		$email->setHeader("From", trim($this->decodeMimeStr($parser->getHeader('from'))));
		$email->setHeader("To", trim($this->decodeMimeStr($parser->getHeader('to'))));
		$email->setHeader("Cc", trim($this->decodeMimeStr($parser->getHeader('cc'))));
		$email->setHeader("in_reply_to", trim($parser->getHeader('in-reply-to')));
		$email->setHeader("message_id", trim($parser->getHeader('message-id')));
		if ($htmlbody)
			$email->setBody($htmlbody, "html");
		else
			$email->setBody($plainbody, "plain");

		$attachments = $parser->getAttachments();
		foreach ($attachments as $att)
			$this->importMailParseAtt($att, $email); // The same function works for import and reprocess

		// Cleanup resources
		$parser = null;
	}

	/**
	 * Save original message in raw format
	 *
	 * @param int $mid The message id to save
	 * @param string $filePath The path of the file to upload
	 */
	public function saveOriginal($mid, $filePath)
	{
		if (!is_numeric($mid) || !file_exists($filePath))
			return false;

		// First make sure it does not already exist
		if (!$this->dbh->GetNumberRows($this->dbh->Query("select message_id from email_message_original where message_id='$mid';")))
		{
			$oid = $this->dbh->loImport($filePath);
			$this->dbh->Query("insert into email_message_original(message_id, lo_message) values('$mid', '$oid');");
		}
	}

	/**
	 * Try to get char-type from headers
	 *
	 * @param array Array of mime headers
	 */
	private function getCharTypeFromHeaders($headers)
	{
		$ret = "UTF-8";

		if (isset($headers['content-type']))
		{
			preg_match('/charset="([^"]+)"/', $headers['content-type'], $matches);
			if ($matches[1])
				$ret = $matches[1];
		}

		return $ret;
	}
}
