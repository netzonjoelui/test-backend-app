<?php
/**
 * Main abstract class for mail protocols
 * 
 * @category  AntMail
 * @package   IMAP
 * @author	  Marl Tumulak <marl.tumulak@aereus.com>
 * @author	  joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Base protocal definition
 */
abstract class AntMail_Protocol_Abstract
{    
    /**
    * The host name of imap/pop3 server
    *
    * @var string
    */
    public $host = null;
    
    /**
    * The username to be used to authenticate
    *
    * @var string
    */
    public $username = null;

    /**
    * The password to be used to authenticate
    *
    * @var string
    */
    public $password = null;

	/**
	 * The port to use to connect to this server
	 *
	 * @var int
	 */
	public $port = null;

	/**
	 * Connect using a secure socket
	 *
	 * @var bool
	 */
	public $ssl = false;

    /**
     * Flag to determine if backend supports unique id for messages within a mailbox
	 *
     * @var null|bool
     */
    public $hasUniqueId = false;

    /**
     * Flag to determine of sync is two way meaning changes on server should be reflected locally
	 *
     * @var null|bool
     */
    public $syncTwoWay = false;
    
    /**
     * Class constructor
     * 
     * @param string $username The username to be used to authenticate
     * @param string $password The password to be used to authenticate
     * @param string $host The host name of the server
     */
    public function __construct($host, $username, $password, $port=null, $ssl=false)
    {
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->ssl = $ssl;

		if ($port)
			$this->port = $port;

		$this->setup();
    }
    
    /**
     * Class destructor
     * 
     */
    function __destruct()
    {
    }

	/**
	 * Setup function must be implemented
	 */
	abstract public function setup();

	/**
	 * Get the last error
	 */
	abstract public function getLastError();
    
    /**
     * Gets the list of messages in IMAP Server
     *
     * @param string $mailboxPath Path of the mailbox e.g. [Gmail]/Drafts
	 * @param string $updateSince Some protocols support pulling updates after a variable
     */
    abstract public function getMessageList($mailboxPath=null, $updateSince=null);

	/**
	 * Get the number of messages in a mailbox
	 *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
	 * @return int|bool number of messages on success, false on failure
     */
    abstract public function getNumMessages($mailbox="Inbox");
    
    /**
     * Get a full mime message
     *
     * @param string $msgNo The number of the message in this mailbox to retrieve
	 * @return string The full mime message
     */
    abstract public function getFullMessage($msgNo);
    
    /**
     * Deletes a message in IMAP Server
     *
     * @param integer $messageNo Message number to be deleted
	 * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
     */
    abstract public function deleteMessage($messageNo, $mailboxPath=null);
    
    /**
     * Marks a message read in IMAP Server
	 *
	 * This function is not required to be implemented by the protocol
     *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
     * @param integer $messageId    Message Id to be deleted
     */
	public function markMessageRead($mailboxPath, $messageId)
	{
		return false;
	}
    
    /**
     * Marks a message flagged in IMAP Server
	 *
	 * This function is not required to be implemented by the protocol
     *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
     * @param integer $messageId    Message Id to be deleted
     */
    public function markMessageFlagged($mailboxPath, $messageId)
	{
		return false;
	}
    
    /**
     * Adds a new mailbox
	 *
	 * This function is not required to be implemented by the protocol
     *     
     * @param string $mailboxPath Path of the mailbox e.g. [Gmail]/Drafts
     */
	public function addMailbox($mailboxPath)
	{
		return false;
	}
    
    /**
     * Deletes a mailbox
	 *
	 * This function is not required to be implemented by the protocol
     *
     * @param string $mailboxPath Path of the mailbox e.g. [Gmail]/Drafts
     */
	public function deleteMailbox($mailboxId)
	{
		return false;
	}
    
    /**
     * Returns list of available mailboxes
	 *
	 * This function is not required to be implemented by the protocol
     */
	public function getMailboxes()
	{
		return false;
	}

	/**
	 * Commit any pending changes
	 *
	 * Often used for purging/expunging deleted messages before the class is closed
     */
    public function commit()
    {
		return false;
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
		$ret = null;

		$messages = $this->getMessageList($mailboxPath);
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
	 * Convert a mailbox path into netric grouping path
	 *
	 * @param string $mailboxPath
	 * @return string Netric version of path
	 */
	public function translatePathToNetric($path, $delimiter)
	{
		if ($delimiter != '/')
		{
			// Remove unallowed '/' char in names
			$path = str_replace("/", "", $path);

			// Replace delimiter with '/' which is what netric uses for groupings
			$path = str_replace($delimiter, '/', $path);
		}

		// Convert some common mailbox names to inify
		switch ($path)
		{
		case 'Junk E-mail': // Outlook
			$path = "Junk Mail";
			break;
		}

		return $path;
	}
}
