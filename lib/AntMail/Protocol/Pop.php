<?php
/**
* Use Pop3 to interact with an Pop3 Mail Server
* 
* @category  AntMail
* @package   IMAP
* @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
*/
require_once('lib/AntMail/Backend/Abstract.php');
require_once(dirname(__FILE__).'/../../CAntObject.php');

class AntMail_Backend_Pop extends AntMail_Backend_Abstract
{
    /**
    * This is used when tokenizing a string
    *
    * @var String
    */
    private $nextToken = null;
    
    /**
    * Result from fgets
    *
    * @var String
    */
    private $buffer = null;
    
    /**
    * Authentication Type
    *
    * @var String
    */
    private $authenticationMechanism = "USER";
    
    /**
    * Connection Object
    *
    * @var Object
    */
    private $connection = null;
    
    /**
    * Determines wheter to execute update upon closing the connection
    *
    * @var Boolean
    */
    private $mustUpdate = null;
    
    /**
    * Contains status info of pop connections
    *
    * @var Array
    */
    public $statusBuffer = array();
    
    /**
    * Pop3 port
    *
    * @var Integer
    */
    public $port = null;
    
    /**
     * If set to true then all 'echo' statements should be ignored for unit tests
     *
     * @var bool
     */
    public $debug = false;
    
    /**
    * Class constructor
    *
    * @param String $server        Pop3 mail server
    * @param String $username      The username to be used to authenticate
    * @param String $password      The password to be used to authenticate
    * @param Integer $port         Pop3 Port
    *     
    */
    function __construct($host, $username, $password, $port=110)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        
        $this->open();
    }
    
    /**
     * Class destructor
     * 
     */
    function __destruct()
    {
        $result = $this->close();
    }

    private function authenticatePop()
    {
        $ret = array();
        
        $ret = $this->openConnection();
        if($ret['status'] == 1) // Connection successful
        {
            $buffer = $this->getLine();
            if(gettype($buffer) == "string" || $this->tokenize($buffer, " ") == "+OK") // buffer result correct
            {
                $this->tokenize("<");
                $this->buffer = $this->tokenize(">");
                return array("status" => 1, "message" => "Successfully connected");
            }
            else
            {
                $ret = array("status" => -1, "message" => "POP3 server greeting was not found");                
            }
        }
        
        return $ret; // Invalid connection
    }
             
    /**
     * Opens the connection using fsockopen
     *
     * Returns the result of connection; Either success or fail (with error numbers and message)
     */
    private function openConnection()
    {
        $ret = array();
        $this->connection = @fsockopen($this->host, $this->port, &$error, $errstr);
        if($this->connection)
            $ret = array("status" => "1", "message" => "Connection successful");
        else
        {
            switch($error)
            {
			case -3:
				$ret = array("status" => "-3", "message" => "Socket could not be created");
				break;
			case -4:
				$ret = array("status" => "-4", "message" => "Dns lookup on hostname \"" . $this->host . "\" failed");
				break;                    
			case -5:
				$ret = array("status" => "-5", "message" => "Connection refused or timed out");
				break;
			case -6:
				$ret = array("status" => "-6", "message" => "fdopen() call failed");
				break;
			case -7:
				$ret = array("status" => "-7", "message" => "setvbuf() call failed");
				break;
			default:
				$ret = array("status" => $error, "message" => $errstr);
				break;
            }
        }
        return $ret;
    }
    
    /**
     * Closes the current fsockopen connection
     *
     */
    private function closeConnection()
    {
        if($this->connection != 0)
        {
            fclose($this->connection);
            $this->connection = 0;
        }
    }
    
    /**
     * Gets the line/result message using fgets in fsockopen connection
     *
     * Returns the line message
     */
    private function getLine()
    {
        $line = "";
        while(true)
        {
            if(feof($this->connection))
                return(0);
            $line .= fgets($this->connection, 100);
            $length = strlen($line);
            if($length>=2 && substr($line, $length-2, 2)=="\r\n")
            {
                $line = substr($line, 0, $length-2);
                return($line);
                break; // Breaks the loop
            }
        }
    }
    
    /**
     * Executes the command line using fputs
     * 
     * @param String $line      Command line that will be processed
     * 
     * Returns result of fputs
     */
    private function putLine($line)
    {
        $ret = fputs($this->connection,"$line\r\n");
        
        return $ret;
    }
    
    /**
     * Tokenize a string
     * 
     * @param String $string      String that will be tokenized
     * @param String $string      Separator that will be used during the process
     *
     * Returns tokenized string
     */
    private function tokenize($string, $separator="")
    {
        if(!strcmp($separator, ""))
        {
            $separator = $string;
            $string = $this->nextToken;
        }
        for($character=0; $character<strlen($separator); $character++)
        {
            if(gettype($position = strpos($string, $separator[$character])) == "integer")
                $found=(isset($found) ? min($found, $position) : $position);
        }
        if(isset($found))
        {
            $this->nextToken = substr($string,$found+1);
            return(substr($string, 0, $found));
        }
        else
        {
            $this->nextToken = "";
            return($string);
        }
    }
    
    /**
     * Authenticates the user credentials
     *
     */
    private function login()
    {
        if($this->putLine("USER " . $this->username) == 0)
            return array("status" => -1, "message" => "Could not send the USER command");
            
        $response = $this->getLine();
        if(gettype($response) != "string")
            return array("status" => -1, "message" => "Could not get user login entry response");
            
        if($this->tokenize($response," ") != "+OK")
            return array("status" => -1, "message" => "User error: ".$this->tokenize("\r\n"));
            
        if($this->putLine("PASS " . $this->password) == 0)
            return array("status" => -1, "message" => "Could not send the PASS command");
            
        $response=$this->getLine();
        if(gettype($response) != "string")
            return array("status" => -1, "message" => "Could not get login password entry response");
            
        if($this->tokenize($response," ") != "+OK")
            return array("status" => -1, "message" => "Password error: ".$this->tokenize("\r\n"));
            
        return array("status" => "1", "message" => "Login Successful");            
    }
    
    /**
     * Process the header details to get the specific header part
     * 
     * @param Array $headerDetails  Contains the header details
     * @param String $headerPart    Part of header to be processed
     *
     * Returns Value of the header part
     */
    private function processHeader($headerDetails, $headerPart)
    {
        $ret = null;
        foreach($headerDetails as $header)
        {
            $parts = explode(":", $header, 2);
            
            if(strtolower($parts[0]) == strtolower($headerPart))
            {
                $ret = $parts[1];
                break;
            }
        }
        
        return $ret;
    }
    
    /**
     * Gets the message details
     * 
     * @param String $seqId         SequenceId of the message
     * @param Integer $line         Determines what part of message will process
     *
     * Returns Array of message details
     */
    private function getMessageDetails($seqId, $lines=2)
    {
        $ret = array();
        
        // Check what part of message to retrieve
        if($lines < 0)
        {
            $command = "RETR";
            $arguments = "$seqId";
        }
        else
        {
            $command = "TOP";
            $arguments = "$seqId $lines";
        }
        
        if($this->putLine("$command $arguments") == 0)
            return array("status" => -1, "message" => "Could not send the $command command");
            
        $response = $this->getLine();
        if(GetType($response) != "string")
            return array("status" => -1, "message" => "Could not get message retrieval command response");
            
        if($this->tokenize($response," ") != "+OK")
            return array("status" => -1, "message" => "Could not retrieve the message: ".$this->tokenize("\r\n"));

        $mimeEmail = null;
                    
        $line = 0;
        while(true)
        {
            $response = $this->getLine();
            if(gettype($response) != "string")
                return array("status" => -1, "message" => "Could not retrieve the message");
                
            switch($response)
            {
                case ".": // If its already end of the response, return the message details
                    return $ret;
                    break;
                case "":
                    break 2; // Breaks the loop
                default:
                    if(substr($response, 0, 1) == ".")
                        $response = substr($response, 1, strlen($response)-1);
                    break;
            }
            
            if($line > 0 && ($response[0] == "\t" || $response[0] == " ")) // Appends the response to the current header index
                $ret['header'][$line-1] .= $response;
            else
            {
                $ret['header'][$line] = $response;
                $line++;
            }
            
            $mimeEmail .= $response;
        }
        
        // Prepare to append body for mime email
        $mimeEmail .= "\r\n";
        
        $line = 0;
        while(true)
        {
            $line++;
            $response = $this->getLine();
            if(gettype($response) != "string")
                return array("status" => -1, "message" => "Could not retrieve the message");
                
            switch($response)
            {
                case ".": // If its already end of the response, return the message details
                    $ret['mime_email'] = $mimeEmail;
                    return $ret;
                    break;
                default:
                    if(substr($response,0,1) == ".")
                        $response = substr($response, 1, strlen($response)-1);
                    break;
            }
            
            $ret['body'][$line] = $response;
            $mimeEmail .= $response;            
        }
        
        $ret['mimeEmail'] = $mimeEmail;
        return $ret;
    }
     
     /**
      * Builds to connection for Pop3 server
      *
      * Returns Boolean
      */
     private function open()
     {
         $this->statusBuffer = array(); // Reset Status
         
         $result = $this->AuthenticatePop();
         $this->statusBuffer["connection"] = $result;
         
         if($result['status'] == 1) // Connection Succuessful
         {
             $response = $this->login();
             $this->statusBuffer["login"] = $response;
         }
     }
     
      /**
      * Closes the current connection
      * This method must be called at least if there are any messages to be deleted    !Important
      *
      * Returns Boolean
      */
     private function close()
     {
         if($this->mustUpdate)
         {
             if($this->putLine("QUIT") == 0)
                 return array("status" => -1, "message" => "Could not send the QUIT command");
                 
             $response = $this->getLine();
             if(gettype($response) != "string")
                 return array("status" => -1, "message" => "Could not get quit command response");
                 
             if($this->tokenize($response," ") != "+OK")
                 return array("status" => -1, "message" => "Could not quit the connection: ".$this->tokenize("\r\n"));
         }
         
         $this->closeConnection();
         
         $ret = array("status" => "1", "message" => "Connection closed");
         return $ret;
     }
     
    /**
     * Gets the list of messages in IMAP Server
     *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
	 * @param array of messages with the following properties {uid, msgno, message_id, subject, from, date, seen, flagged, answered, deleted}
     */
    public function getMessageList($mailboxPath=null, $queryDetails=true)
    {
        $ret = array();
        
        if($this->putLine("UIDL") == 0)
            return array("status" => -1, "message" => "Could not send the UIDL command");
            
        $response = $this->getLine();
        if(gettype($response) != "string")
            return array("status" => -1, "message" => "Could not get message UIDL command response");
            
        if($this->tokenize($response," ") != "+OK")
            return array("status" => -1, "message" => "Could not get the message listing: ".$this->tokenize("\r\n"));
        
        while(true)
        {
            $response = $this->getLine();
            if(gettype($response) != "string")
                return array("status" => -1, "message" => "Could not get message list response");
                
            if($response == ".") // Breaks the loop
                break;
                
            $seqId = intval($this->tokenize($response, " "));
            $ret[] = array("seq_id" => $seqId, "uid" => $this->tokenize(" "),);
        }
        
        if($queryDetails)
        {
            // Get Message Details
            foreach($ret as $key=>$email)
            {
                $messageDetails = $this->getMessageDetails($email['seqId']); // Get Message Details
                
                $ret[$key]["subject"] = $this->processHeader($messageDetails['header'], "subject");
                $ret[$key]["from"] = $this->processHeader($messageDetails['header'], "from");
                $ret[$key]["date"] = $this->processHeader($messageDetails['header'], "date");
                $ret[$key]["message_id"] = $this->processHeader($messageDetails['header'], "message-id");
                $ret[$key]["body"] = implode("/r/n", $messageDetails['body']);
                
                $ret[$key]["mime_email"] = $messageDetails['mime_email'];
            }
        }
        
        return $ret;
    }

	/**
	 * Get full message
	 *
	 * @param sring Full message with header and body
	 */
	public function getFullMessage($msgNo)
	{
		if (!$this->conn)
			return false;

		$fullMsg = imap_fetchheader($this->conn, $msgNo);
		$fullMsg .= "\r\n\r\n";
		$fullMsg .= imap_body($this->conn, $msgNo);

		return $fullMsg;
	}
    
    /**
     * Deletes a message in IMAP Server
     *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
     * @param integer $messageId    Message Id to be deleted
     */
    public function deleteMessage($mailboxPath, $messageId)
    {
        $seqId = null;
        
        // Lets get message sequence id using message unique id
        $result = $this->getMessageList(null, false);
        foreach($result as $key=>$email)
        {
            if($email['uid'] == $messageId)
            {
                $seqId = $email['seq_id'];
                break;
            }
        }
        
        if(empty($seqId))
            return;
        
        if($this->putLine("DELE $seqId") == 0)
            return array("status" => -1, "message" => "Could not get message DELE command response");
            
        $response = $this->getLine();
        if(gettype($response) != "string")
            return array("status" => -1, "message" => "Could not get message delete command response");
            
        if($this->tokenize($response," ") != "+OK")
            return array("status" => -1, "message" => "Could not delete the message: ".$this->tokenize("\r\n"));
            
        $this->mustUpdate = 1;
        
        $ret = array("status" => "1", "message" => "Message is marked deleted. This will be executed once connection is closed");
        return $ret;
    }
    
    /**
     * Marks a message read in IMAP Server
     *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
     * @param integer $messageId    Message Id to be deleted
     */
    public function markMessageRead($mailboxPath, $messageId, $value=true)
    {        
    }
    
    /**
     * Marks a message flagged in IMAP Server
     *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
     * @param integer $messageId    Message Id to be deleted
     */
    public function markMessageFlagged($mailboxPath, $messageId, $value=true)
    {        
    }
    
    /**
     * Adds a new mailbox
     *     
     */
    public function addMailbox($mailboxPath, $mailboxName)
    {        
    }
    
    /**
     * Deletes a mailbox
     *
     * @param integer $mailboxId  Id of the mailbox to be deleted
     */
    public function deleteMailbox($mailboxId)
    {        
    }
    
    /**
     * Returns list of available mailboxes
     *     
     */
    public function getMailboxes()
    {        
    }
}
