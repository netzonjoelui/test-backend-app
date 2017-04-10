<?php
/**
 * Backend class for IMAP
 * 
 * This is the base class used by all the IMAP implementation classes.  
 *
 * @category  AntMail
 * @package   IMAP
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Base object IMAP class
 */
 
require_once('lib/AntMail/Protocol/Imap.php');
require_once('lib/AntMail/Protocol/Pop3.php');
require_once('lib/AntMail/Protocol/Test.php');
 
class AntMail_Backend
{
    /**
     * Instance of the Ant Mail Backend Class
     *
     * @var Object
     */
    public $mailProtocol = null;
    
    /**
     * Class constructor
     * 
     * @param string $type Determines which mail type to use (e.g. imap)
     * @param string $username The username to be used to authenticate
     * @param string $password The password to be used to authenticate
     * @param string $host The host name of imap server
     * @param int $port Default port to connect; Only used for Pop3 server
	 * @param bool $ssl If true use secure socket
     */
    function __construct($type, $host, $username, $password, $port=null, $ssl=false)
    {
        switch($type)
        {
		case "pop3":
		case "pop":
			$this->mailProtocol = new AntMail_Protocol_Pop3($host, $username, $password, $port, $ssl);
			break;
		case "imap":
			$this->mailProtocol = new AntMail_Protocol_Imap($host, $username, $password, $port, $ssl);
			break;
        case "test":
            $this->mailProtocol = new AntMail_Protocol_Test($host, $username, $password, $port, $ssl);
            break;
		default:
			break;
        }
    }
    
    /**
     * Class destructor
     */
    function __destruct()
    {
        unset($this->mailProtocol);
    }
    
    /**
     * Gets the list of messages in IMAP Server
     *
     * @param string $mailboxPath Path of the mailbox e.g. INBOX
	 * @param string $lastUpdate Variable used to store last update variable if protocol supports it
     */
    public function getMessageList($mailboxPath=null, $lastUpdate=null)
    {
        return $this->mailProtocol->getMessageList($mailboxPath, $lastUpdate);
    } 

    
    /**
     * Get the last error if any
     */
    public function getLastError()
    {
        return $this->mailProtocol->getLastError();
    } 

	/**
	 * Get full message
	 *
	 * @param sring Full message with header and body
	 */
	public function getFullMessage($msgNo)
	{
        return $this->mailProtocol->getFullMessage($msgNo);
    } 
    
    /**
     * Deletes a message in IMAP Server
     *
     * @param string $mailboxPath   Path of the mailbox e.g. INBOX
     * @param integer $messageId    Message Id to be deleted
     */
    public function deleteMessage($messageId, $mailboxPath="Inbox")
    {
        return $this->mailProtocol->deleteMessage($messageId, $mailboxPath);
    }
    
    /**
     * Marks a message read in IMAP Server
     *
     * @param string $mailboxPath   Path of the mailbox e.g. INBOX
     * @param integer $messageId    Message Id to be deleted
     */
    public function markMessageRead($mailboxPath, $messageId)
    {
        return $this->mailProtocol->markMessageRead($mailboxPath, $messageId);
    }
    
    /**
     * Marks a message flagged in IMAP Server
     *
     * @param string $mailboxPath   Path of the mailbox e.g. INBOX
     * @param integer $messageId    Message Id to be deleted
     */
    public function markMessageFlagged($mailboxPath, $messageId)
    {
        return $this->mailProtocol->markMessageFlagged($mailboxPath, $messageId);
    }
    
    /**
     * Adds a new mailbox
     *     
     */
    public function addMailbox($mailboxPath)
    {
        return $this->mailProtocol->addMailbox($mailboxPath);
    }
    
    /**
     * Deletes a mailbox
     *
     * @param integer $mailboxId  Id of the mailbox to be deleted
     */
    public function deleteMailbox($mailboxId)
    {
        return $this->mailProtocol->deleteMailbox($mailboxId);
    }

    /**
     * Returns list of available mailboxes
     *     
     */
    public function getMailboxes()
    {
        return $this->mailProtocol->getMailboxes();
    }
    
    /**
     * Returns the current mail protocol
     *     
     */
    public function getMailProtocol()
    {
        return $this->mailProtocol;
    }

	/**
     * Get a message number from a unique id
     *
     * I.e. if you have a webmailer that supports deleting messages you should use unique ids
     * as parameter and use this method to translate it to message number right before calling removeMessage()
     *
     * @param string $id unique id
     * @return int message number
     */
    public function getNumberByUniqueId($id)
    {
		// Use uid for deletion
        if ($this->mailProtocol->hasUniqueId)
            return $id;

		$ret = null;

		$messages = $this->mailProtocol->getMessageList($mailboxPath);
		foreach ($messages as $msg)
		{
			if ($msg['uid'] == $id)
			{
				$ret = $msg['msgno'];
				break;
			}
		}

		return $ret;
    }
    
    /**
     * Deletes a mailbox
     *
     * @param string $mailboxPath   Path of the mailbox e.g. INBOX
	 * @return mixed The result from the action attempt
     */
    public function processUpsync($mailboxPath, $messageId, $action, $value)
    {
		/**
		 * Now handled in protocol class
		$msgNo = $this->getNumberByUniqueId($messageId);

		if (!$msgNo)
			return; // message is no longer in the message store
		 */

        switch($action)
        {
		case "seen":
		case "read":
			$result = $this->mailProtocol->markMessageRead($mailboxPath, $messageId, $value);
			break;
		case "flag":
		case "flagged":
			$result = $this->mailProtocol->markMessageFlagged($mailboxPath, $messageId, $value);
			break;
		case "delete":
		case "deleted":
			$result = $this->mailProtocol->deleteMessage($messageId, $mailboxPath);
			break;
        }

		return $result;
    }

	/**
	 * Commit any pending changes
	 *
	 * Often used for purging/expunging deleted messages before the class is closed
     */
    public function commit()
    {
        return $this->mailProtocol->commit();
    } 

	/**
     * Get number of messages for a mailbox
     *
     * @param string $mailboxPath Path of the mailbox e.g. INBOX
     */
    public function getNumMessages($mailboxPath="Inbox")
	{
        return $this->mailProtocol->getNumMessages($mailboxPath);
	}

	/**
	 * Check if protocol backend supports two way sync
	 *
	 * This basically means if a message is moved or deleted on the server then changes should be
	 * imported into Netric. If not, then only new messages are added.
	 *
	 * @return bool True if backend supports two-way sync, otherwise false
	 */
	public function isTwoWaySync()
	{
		return $this->mailProtocol->syncTwoWay;
	}
}
