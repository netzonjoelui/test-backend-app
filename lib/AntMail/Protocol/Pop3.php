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

class AntMail_Protocol_Pop3 extends AntMail_Protocol_Abstract
{
	/**
     * Default timeout in seconds for initiating session
     */
    const TIMEOUT_CONNECTION = 30;

	/**
	 * Flag to determine if we are in transaction state
	 *
	 * @var bool
	 */
	public $authStatus = false;

    /**
     * saves if server supports top
	 *
     * @var null|bool
     */
    public $hasTop = null;

    /**
     * socket to pop3
	 *
     * @var null|resource
     */
    protected $socket;

    /**
     * greeting timestamp for apop
	 *
     * @var null|string
     */
	protected $timestamp;

    /**
    * Plugin setup - this is like the constructor
    */
    public function setup() 
    {
		if (!$this->port)
		{
			$this->port = ($this->ssl) ? 995 : 110;
		}

        $this->connect($this->host, $this->port, $this->ssl);
        $this->login($this->username, $this->password, null);
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        $this->logout();
    }

	/**
	 * Get the last error
	 */
	public function getLastError()
	{
		return "";
	}

	/**
     * Open connection to POP3 server
     *
     * @param  string      $host  hostname or IP address of POP3 server
     * @param  int|null    $port  of POP3 server, default is 110 (995 for ssl)
     * @param  string|bool $ssl   use 'SSL', 'TLS' or false
     * @return string welcome message
     * @throws Exception
     */
    public function connect($host, $port = null, $ssl = false)
    {
		if ($ssl == 'SSL') 
		{
            $host = 'ssl://' . $host;
        }

		if ($port === null) 
		{
            $port = $ssl == 'SSL' ? 995 : 110;
        }

        $errno  =  0;
        $errstr = '';
        $this->socket = @fsockopen($host, $port, $errno, $errstr, self::TIMEOUT_CONNECTION);

		if (!$this->socket) 
		{
            throw new Exception('cannot connect to host ' . $host . ':' . $port . '; error = ' . $errstr .
                                                   ' (errno = ' . $errno . ' )');
        }

        $welcome = $this->readResponse();

        strtok($welcome, '<');
        $this->timestamp = strtok('>');

		if (!strpos($this->timestamp, '@')) 
            $this->timestamp = null;
		else 
            $this->timestamp = '<' . $this->timestamp . '>';

		if ($ssl === 'TLS') 
		{
            $this->request('STLS');
            $result = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
			if (!$result) 
                throw new Exception('cannot enable TLS');
        }

        return $welcome;
	}

	/**
     * Login to POP3 server. Can use APOP
     *
     * @param  string $user      username
     * @param  string $password  password
     * @param  bool   $try_apop  should APOP be tried?
     * @return void
     * @throws Exception 
     */
    public function login($user, $password, $tryApop = true)
    {
        if ($tryApop && $this->timestamp) {
			try 
			{
                $this->request("APOP $user " . md5($this->timestamp . $password));
                return;
			}
			catch (Exception $e) 
			{
                // ignore
            }
        }

		try
		{
			$result = $this->request("USER $user");
			$result = $this->request("PASS $password");
		}
		catch (Exception $e)
		{
			// Login failed
			$this->authStatus = false;
		}

		$this->authStatus = true;

		// Determine if this server supports uid
		$this->hasUniqueId();
    }

	/**
     * End communication with POP3 server (also closes socket)
     *
     * @return null
     */
    public function logout()
    {
        if (!$this->socket) {
            return;
        }

		try 
		{
            $this->request('QUIT');
		} 
		catch (Exception $e) 
		{
            // ignore error - we're closing the socket anyway
        }

        fclose($this->socket);
        $this->socket = null;
    }


	/**
	 * Commit any pending changes
	 *
	 * Often used for purging/expunging deleted messages before the class is closed
     */
    public function commit()
    {
        $this->logout();
        $this->connect($this->host);
        $this->login($this->username, $this->password);
    } 
	/**
     * Send a request
     *
     * @param string $request your request without newline
     * @return null
     * @throws Exception 
     */
    private function sendRequest($request)
    {
        $result = @fputs($this->socket, $request . "\r\n");
        if (!$result)
            throw new Exception('send failed - connection closed?');
    }


    /**
     * read a response
     *
     * @param  boolean $multiline response has multiple lines and should be read until "<nl>.<nl>"
     * @return string response
     * @throws Exception 
     */
    private function readResponse($multiline = false)
    {
        $result = @fgets($this->socket);
		if (!is_string($result)) 
		{
            throw new Exception('read failed - connection closed?');
        }

        $result = trim($result);
		if (strpos($result, ' ')) 
		{
            list($status, $message) = explode(' ', $result, 2);
		} 
		else 
		{
            $status = $result;
            $message = '';
        }

		if ($status != '+OK') 
            throw new Exception('last request failed: ' . $status . "=" . $message);

		if ($multiline) 
		{
            $message = '';
            $line = fgets($this->socket);
			while ($line && rtrim($line, "\r\n") != '.') 
			{
				if ($line[0] == '.') 
				{
                    $line = substr($line, 1);
                }

                $message .= $line;
                $line = fgets($this->socket);
            };
        }

        return $message;
    }

    /**
     * Send request and get resposne
     *
     * @see sendRequest(), readResponse()
     *
     * @param  string $request    request
     * @param  bool   $multiline  multiline response?
     * @return string             result from readResponse()
     * @throws Exception 
     */
    private function request($request, $multiline = false)
    {
        $this->sendRequest($request);
        return $this->readResponse($multiline);
    }

	/**
     * Get capabilities from POP3 server
     *
     * @return array list of capabilities
     * @throws Exception 
     */
    public function capa()
    {
        $result = $this->request('CAPA', true);
        return explode("\n", $result);
    }

    /**
     * Make STAT call for message count and size sum
     *
     * @param  int $messages  out parameter with count of messages
     * @param  int $octets    out parameter with size in octects of messages
     * @return void
     * @throws Exception  
     */
    public function status(&$messages, &$octets)
    {
		if (!$this->authStatus) 
            throw new Exception('Not Authenticated! Cannot call status until authenticated');

        $messages = 0;
        $octets = 0;
        $result = $this->request('STAT');

        list($messages, $octets) = explode(' ', $result);
    }

	/**
	 * Get the number of messages in a mailbox
	 *
     * @param string $mailboxPath   Path of the mailbox e.g. [Gmail]/Drafts
     */
    public function getNumMessages($mailbox="Inbox")
    {
		$count = 0;
		$size = 0;
		$this->status($count, $size);

		return $count;
	} 

    /**
    * Gets the list of messages on the server
    *
    * @param string $mailboxPath Not supported by pop3
	* @param string $updateSince Not supported by pop3
    */
    public function getMessageList($mailboxPath=null, $updateSince=null)
    {
		$ret = array();

		// POP3 only handles inbox
		if ($mailboxPath != null && strtolower($mailboxPath)!="inbox")
			return $ret;

		// Get number of messages
		$count = $this->getNumMessages($mailboxPath);

		for ($i = 1; $i <= $count; $i++)
		{
			$bodyLines = 0;
			$rawHeader = $this->top($i, $bodyLines, true);
			$header = imap_rfc822_parse_headers($rawHeader);
			$uid = $this->uniqueid($i);
			if (!$uid)
				$uid = $i;

            // Email information
            $ret[] = array(
				"uid" => $uid,
				"msgno" => $i,
				"message_id" => $header->message_id,
				"subject" => $header->subject, 
				"from" => $header->fromaddress,
				"to" => $header->toaddress,
				"date" => $header->date,
				"seen" => ($header->unseen) ? false : true, 
				"size" => $header->Size, 
				"recent" => $header->Recent, 
				"flagged" => $header->flagged, 
				"answered" => $header->answered, 
				"deleted" => $header->deleted, 
         	);
		}

		return $ret;
    } 

	/**
     * Make UIDL call for getting a uniqueid
     *
     * @param  int|null $msgno number of message, null for all
     * @return string|array uniqueid of message or list with array(num => uniqueid)
     * @throws Exception 
     */
    private function uniqueid($msgno = null)
    {
        if ($msgno !== null) {
            $result = $this->request("UIDL $msgno");

            list(, $result) = explode(' ', $result);
            return $result;
        }

        $result = $this->request('UIDL', true);

        $result = explode("\n", $result);
        $messages = array();
        foreach ($result as $line) {
            if (!$line) {
                continue;
            }
            list($no, $id) = explode(' ', trim($line), 2);
            $messages[(int)$no] = $id;
        }

        return $messages;

	}

	/**
     * Make TOP call for getting headers and maybe some body lines
     * This method also sets hasTop - before it it's not known if top is supported
     *
     * The fallback makes normale RETR call, which retrieves the whole message. Additional
     * lines are not removed.
     *
     * @param  int  $msgno    number of message
     * @param  int  $lines    number of wanted body lines (empty line is inserted after header lines)
     * @param  bool $fallback fallback with full retrieve if top is not supported
     * @return string message headers with wanted body lines
     * @throws Exception 
     */
    private function top($msgno, $lines = 0, $fallback = false)
    {
		if ($this->hasTop === false) 
		{
			if ($fallback) 
			{
                return $this->getFullMessage($msgno);
			} 
			else 
			{
                throw new Exception('top not supported and no fallback wanted');
            }
        }
        $this->hasTop = true;

        $lines = (!$lines || $lines < 1) ? 0 : (int)$lines;

		try 
		{
            $result = $this->request("TOP $msgno $lines", true);
		} 
		catch (Exception $e) 
		{
            $this->hasTop = false;
			if ($fallback) 
			{
                $result = $this->getFullMessage($msgno);
			} 
			else 
			{
                throw $e;
            }
        }

        return $result;
	}

    /**
     * Make a NOOP call, maybe needed for keeping the server happy
     *
     * @return null
     * @throws Exception 
     */
    public function noop()
    {
        $this->request('NOOP');
    }
    
	/**
	 * Get full message
	 *
	 * @param int $msgNo The offset of the message in the current list
	 * @return sring Full message with header and body
	 */
	public function getFullMessage($msgno)
	{
		$result = $this->request("RETR $msgno", true);
        return $result;
	}

    /**
    * Deletes a message in IMAP Server
    *
    * @param integer $uid Message Id to be deleted
    * @param string $mailboxPath Path of the mailbox e.g. [Gmail]/Drafts
    */
    public function deleteMessage($uid, $mailboxPath=null)
    {
		// POP3 only handles inbox
		if ($mailboxPath != null && strtolower($mailboxPath)!="inbox")
			return false;

		$msgno = $this->getNumberByUniqueId($uid);

		try
		{
        	$ret = $this->request("DELE $msgno");
		}
		catch (Exception $e)
		{
			// TODO: for some reason this is returning "-err" on the unit test but the message
			// is being deleted?
		}

		return $ret;
	}

	/**
     * Make RSET call, which rollbacks delete requests
     *
     * @return null
     * @throws Exception 
     */
    public function undeleteMessage()
    {
        $this->request('RSET');
    }

    /**
    * Returns list of saved mailboxes
    */
    public function getMailboxes()
    {
       return array("Inbox");
    }

	/**
     * Determin if the server supports top
     *
     * @param  string $var
     * @return string
     * @throws Exception 
     */
    private function hasTop()
    {
		if ($this->hasTop)
			return true;

		if ($this->hasTop === null) 
		{
			// need to make a real call, because not all server are honest in their capas
			try 
			{
				$this->top(1, 0, false);
			} 
			catch(Exception $e) 
			{
				// top will set $this->hasTop to false if it fails
			}
		}
		return $this->hasTop;
    }

	/**
     * Determin if the server supports UIDL
     *
     * @param  string $var
     * @return string
     * @throws Exception 
     */
    private function hasUniqueId()
    {
		if ($this->hasUniqueId)
			return true;

		$id = null;
		try 
		{
			$id = $this->uniqueid(1);
		} 
		catch(Exception $e) 
		{
			// ignoring error
		}
		$this->hasUniqueId = $id ? true : false;
		return $this->hasUniqueId;
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

		$messages = $this->getMessageList("INBOX");
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
}
