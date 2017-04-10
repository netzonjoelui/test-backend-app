<?php
/**
* Use Imap to interact with an imap server
* 
* @category  AntMail
* @package   IMAP
* @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
*/
require_once('lib/AntMail/Protocol/Abstract.php');
require_once('lib/CAntObject.php');

class AntMail_Protocol_Imap extends AntMail_Protocol_Abstract
{
    /**
    * The mailbox to be used for synching
    *
    * @var String
    */
    public $mailbox = "INBOX";

    /**
    * The instance of imap object
    *
    * @var Object
    */
    public $imapObj = null;

    /**
    * Contains Imap Info if authenticated successfully
    *
    * @var Object
    */
    public $imapCheck = null;

    /**
    * Plugin setup - this is like the constructor
    */
    public function setup() 
	{
		if (!$this->port)
			$this->port = 143;
        
        // Check if host already have curly braces
        preg_match_all('/\{([^}]*)\}/', $this->host, $matches);
        
        if(sizeof($matches[1]) == 0) // No curly braces and port
            $this->host = "{" . $this->host . ":" . $this->port . "/novalidate-cert}";

		// Set unique to on
		$this->hasUniqueId = true;

		// Set two way sync to on
		$this->syncTwoWay = true;

		// Authenticate
        $this->authenticateImap();
    }

    /**
     * Class destructor
     * 
     */
    function __destruct()
    {
		if ($this->imapObj)
        	imap_close($this->imapObj);
	}

	/**
	 * Get the last error
	 */
	public function getLastError()
	{
		$errors = imap_errors();
		return $errors[count($errors)-1]; // get last element
	}

    /**
     * Authenticates the Imap Credentials
     *
     * @param boolean $reOpen   Determines whether the Imap object is already opened and need to be re-open
     */
    private function authenticateImap($reOpen=false)
    {
        // Authenticate credentials
        $imapMailBox = $this->host . $this->mailbox;

        if($reOpen)
            imap_reopen($this->imapObj, $imapMailBox);
        else
            $this->imapObj = imap_open($imapMailBox, $this->username, $this->password);

        $this->imapCheck = imap_check($this->imapObj);


        if(empty($this->imapCheck) || !is_object($this->imapCheck))
            return false;
        else
            return true;
    }

    /**
    * Gets the list of messages in IMAP Server
    *
    * @param string $mailboxPath Path of the mailbox e.g. [Gmail]/Drafts
	* @param string $lastUid If set, only get updates since the last uid. This allows for incremental updates.
    */
    public function getMessageList($mailboxPath=null, $lastUid=null)
    {
        if(!empty($mailboxPath)) // check if we need to change the current mailbox
        {
			$mailboxPath = $this->translateMailboxPath($mailboxPath);
			if ($mailboxPath != $this->mailbox)
			{
				$this->mailbox = $mailboxPath;
				if(!$this->authenticateImap(true)) // Try Reopen Imap
					return false; // Authentication failed
			}
        }

        $ret = array();

        $emails = imap_search($this->imapObj, 'ALL');
		
		// Error getting messages
		if (!is_array($emails))
			return $ret;

        foreach($emails as $idx)
        {
            // Get information specific to this email
            $overview = imap_fetch_overview($this->imapObj, $idx, 0);

            /*$message = imap_fetchbody($this->imapObj, $idx, 2, FT_PEEK);
            $structure = imap_fetchstructure($this->imapObj, $idx);
            $attachment = $this->getAttachments($idx, $message, $structure);*/

            // Get the mime message
            /*$header = imap_headerinfo($imap, $msgNo); // only for first line
            $from = "From ".$header->from[0]->mailbox."@".$header->from[0]->host." ".date("D M j H:i:s Y", strtotime($header->date)); // Create From-Line*/

            // Create a mime email
            //$mimeEmail = imap_fetchheader($this->imapObj, $idx) . "\r\n" . imap_body($this->imapObj, $idx, FT_PEEK);
            
            if($lastUid >= $overview[0]->uid)
                continue;
            
            // Email information
            $ret[] = array(
				"uid" => $overview[0]->uid,
				"message_id" => $overview[0]->message_id,
				"msgno" => $overview[0]->msgno,
				"seen" => $overview[0]->seen, 
				"flagged" => $overview[0]->flagged, 
				"answered" => $overview[0]->answered, 
				"deleted" => $overview[0]->deleted, 
				"subject" => $overview[0]->subject, 
				"to" => $overview[0]->to,
				"from" => $overview[0]->from,
				"date" => $overview[0]->date,                            
				/*"body" => $message,
				"attachment" => $attachment,
				"mime_email" => $mimeEmail,*/
			);
        }
        
        return $ret;
	}

	/**
	 * Get the number of messages in a mailbox
	 *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
	 * @return int|bool number of messages on success, false on failure
     */
    public function getNumMessages($mailboxPath="Inbox")
    {
        if(!empty($mailboxPath)) // check if we need to change the current mailbox
        {
			$mailboxPath = $this->translateMailboxPath($mailboxPath);
			if ($mailboxPath != $this->mailbox)
			{
				$this->mailbox = $mailboxPath;
				if(!$this->authenticateImap(true)) // Try Reopen Imap
					return false; // Authentication failed
			}
        }

		$num = imap_num_msg($this->imapObj);

		return $num;
    }

	/**
	 * Get full message
	 *
	 * @param int $msgNo The offset of the message in the current list
	 * @return sring Full message with header and body
	 */
	public function getFullMessage($msgNo)
	{
		if (!$this->mailbox)
			return false;

		$fullMsg = imap_fetchheader($this->imapObj, $msgNo);
		$fullMsg .= "\r\n\r\n";
		$fullMsg .= imap_body($this->imapObj, $msgNo, FT_PEEK);

		return $fullMsg;
	}

    /**
    * Deletes a message in IMAP Server
    *
    * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
    * @param integer $messageId    Message Id to be deleted
    */
    public function deleteMessage($messageId, $mailboxPath=null)
    {
        if(empty($messageId))
            return false;

        if(!empty($mailboxPath)) // check if we need to change the current mailbox
        {
			$mailboxPath = $this->translateMailboxPath($mailboxPath);
			if ($mailboxPath != $this->mailbox)
			{
				$this->mailbox = $mailboxPath;
				if(!$this->authenticateImap(true)) // Try Reopen Imap
					return false; // Authentication failed
			}
        }

        //imap_setflag_full($this->imapObj, $messageId, "\\Deleted", ST_UID);
        $ret = imap_delete($this->imapObj, $messageId, FT_UID); // Mark the message for deletion from current mailbox

        $this->commit();

		return $ret;
    }

    /**
    * Marks a message read in IMAP Server
    *
    * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
    * @param integer $messageId    Message Id to be mark as read
    */
    public function markMessageRead($mailboxPath, $messageId, $value=true)
    {
        if(empty($messageId) || !is_numeric($messageId) || $messageId <= 0)
            return false;

        if(!empty($mailboxPath)) // check if we need to change the current mailbox
        {
			$mailboxPath = $this->translateMailboxPath($mailboxPath);
			if ($mailboxPath != $this->mailbox)
			{
				$this->mailbox = $mailboxPath;
				if(!$this->authenticateImap(true)) // Try Reopen Imap
					return false; // Authentication failed
			}
        }

        if($value) // Mark message as read/seen
        {
            imap_setflag_full($this->imapObj, $messageId, "\\Seen", ST_UID);
        }
        else // Mark message as unread/unseen
            imap_clearflag_full($this->imapObj, $messageId, "\\Seen", ST_UID);

        return true;
    }

    /**
    * Marks a message flagged in IMAP Server
    *
    * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
    * @param integer $messageId    Message Id to be flagged
    */
    public function markMessageFlagged($mailboxPath, $messageId, $value=true)
    {
        if(empty($messageId) || !is_numeric($messageId) || $messageId <= 0)
            return false;

        if(!empty($mailboxPath) && $mailboxPath !== $this->mailbox) // reopen Imap using the new mailbox
        {
            $this->mailbox = $mailboxPath;
            if(!$this->authenticateImap(true)) // Try Reopen Imap
                return false; // Authentication failed
        }

        if($value)// Mark message as flagged
        {
            imap_setflag_full($this->imapObj, $messageId, "\\Flagged", ST_UID);
            $flaggedEmails = imap_search($this->imapObj, 'FLAGGED');
        }
        else
            imap_clearflag_full($this->imapObj, $messageId, "\\Flagged", ST_UID);

        return true;
    }

    /**
     * Adds a new mailbox
     *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
	 * @return bool true on succes, false on failure
     */
    public function addMailbox($mailboxPath)
    {
		$path = $this->host;
		$path .= $this->translateMailboxPath($mailboxPath);

		return @imap_createmailbox($this->imapObj, $path);
    }

    /**
     * Deletes a mailbox
     *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
	 * @return bool true on succes, false on failure
     */
    public function deleteMailbox($mailboxPath)
    {
		$path = $this->host;
		$path .= $this->translateMailboxPath($mailboxPath);

		return imap_deletemailbox($this->imapObj, $path);
    }

    /**
     * Returns list of saved mailboxes
     *     
     */
    public function getMailboxes()
    {
        $mailboxes = array();
        $list = imap_getmailboxes($this->imapObj, $this->host, "*");

        if (is_array($list)) 
        {
            foreach ($list as $key=>$val)
            {
				$mname = imap_utf7_decode($val->name);

				// Remove host form beginning
				$mname = substr($mname, strlen($this->host));

				// Convert delimiter to netric path
				$mname = $this->translatePathToNetric($mname, $val->delimiter);
				$mailboxes[] = $mname;

                //$mailboxes[] = array("name" => imap_utf7_decode($val->name), "delimiter" => $val->delimiter, "attributes" => $val->attributes);
            }
        }
        
        return $mailboxes;
    }

    /**
     * Gets the encoding value and decodes the message
     *
     * @param string @message        Message Data
     * @param integer $coding        Encoding Type
     * @param string $encodingStr    Encoding Type Definition
     */
    private function getDecodeValue($message, $coding, &$encodingStr)
    {
        switch($coding)
        {
            case 0:
            case 1:
                $encodingStr = "8bit";
                $message = imap_8bit($message);
                break;
            case 2:
                $encodingStr = "binary";
                $message = imap_binary($message);
                break;
            case 3:
            case 5:
                $encodingStr = "base64";
                $message=imap_base64($message);
                break;
            case 4:
                $encodingStr = "quoted-printable";
                $message = imap_qprint($message);
                break;
        }
        return $message;
    }
    
    /**
     * Saves the email attachment in the temp folder
     *
     * @param integer $emailIdx      Index of the current email
     * @param string $body           Message Body
     */
    private function getAttachments($emailIdx, &$body, $structure)
    {
        $hasAttachment = false;
        $attachment = array();
        $message = array();
        $message["attachment"]["type"][0] = "text";
        $message["attachment"]["type"][1] = "multipart";
        $message["attachment"]["type"][2] = "message";
        $message["attachment"]["type"][3] = "application";
        $message["attachment"]["type"][4] = "audio";
        $message["attachment"]["type"][5] = "image";
        $message["attachment"]["type"][6] = "video";
        $message["attachment"]["type"][7] = "other";
        
        $tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
        
        $parts = $structure->parts;
        $fpos = 2;
        for($x = 1; $x < count($parts); $x++)
        {
            $message["pid"][$x] = ($x);
            $part = $parts[$x];
            if($part->disposition == "ATTACHMENT")
            {
                $hasAttachment = true; // Will overwrite the message body later
                
                $ext = $part->subtype;
                $params = $part->dparameters;
                $filename = $part->dparameters[0]->value;
                
                $emailAttachment = imap_fetchbody($this->imapObj, $emailIdx, $fpos, FT_PEEK); // Get the encoded attachment
                $tmpFile = "$tmpFolder/$filename";
                $handle = fopen($tmpFile, "w");
                $data = $this->getDecodeValue($emailAttachment, $part->type, $encodingStr);
                fputs($handle, $data);                    
                fclose($handle);
                $fpos+=1;
                
                $attachment[] = array("filename" => $filename,
                                        "tmpFile" => $tmpFile,
                                        "conentType" => $message["attachment"]["type"][$part->type] . "/" . strtolower($part->subtype),
                                        "subType" => strtolower($part->subtype),
                                        "content-transfer-encoding" => $encodingStr,
                                        "disposition" => strtolower($part->disposition),
                                        );
            }
        }
        
        if($hasAttachment)
            $body = imap_fetchbody($this->imapObj, $emailIdx, 1.1, FT_PEEK); // Get the message body
        
        return $attachment;
    }

	/**
	 * Translate Netric mailbox path to imap
	 *
	 * @param string $mailboxPath
	 * @return string Imap version of mailbox path
	 */
	public function translateMailboxPath($mailboxPath)
	{
		$newPath = "";
		$parts = explode("/", $mailboxPath);

		for ($i = 0; $i < count($parts); $i++)
		{
			if (0 == $i && $parts[$i] == "Inbox")
				$parts[$i] = "INBOX";

			if ($i) // add separator
				$newPath .= ".";

			$newPath .= imap_utf7_encode($parts[$i]);
		}

		return $newPath;
	}
    
    /**
     * Commit any pending changes
     *
     * Often used for purging/expunging deleted messages before the class is closed
     */
    public function commit()
    {
        return imap_expunge($this->imapObj); // Delete the message that is marked for deletion
    }
}
