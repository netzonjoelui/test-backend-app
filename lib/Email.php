<?php	
/**
 * Email class used to interface with SMTP server
 *
 * @category	Email
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 * @author		joe <sky.stebnicki@aereus.com>
 */
require_once("PEAR.php");
require_once("Mail.php");

/**
 * Email class definition
 */
class Email
{
	var $params;
	var $headers;
	var $staus;
	var $err_msg;

	/**
	 * If true messages will be sent even if supress email config var is true
	 *
	 * @var bool
	 */
	public $ignoreSupression = false;
	
	/**
	 * Class constructor
	 *
	 * @param string $host Optional mail host to use
	 * @param string $username Optional user name if needed for the smtp server
	 * @param string $password Optional password to be used if needed for the smtp auth
	 * @param int $port Optional alternate port to use
	 */
	public function __construct($host=null, $username=null, $password=null, $port=null)
	{
		$this->params["host"] = ($host) ? $host : AntConfig::getInstance()->email['server'];
		if ($username)
			$this->params["username"] = $username;
		if ($password)
			$this->params["password"] = $password;
		$this->params["port"] = ($port) ? $port : "25";
		$this->status = 0;

		if ($username || $password)
			$this->params['auth'] = true;
	}
	
	/**
	 * Send email message
	 *
	 * @param array $recipients Associatiave array of recipients for "To", "Cc" and "Bcc" entries
	 * @param array $headers Associative array representing headers for email message
	 * @param string $body The raw body of the message to send
	 */
	public function send($recipients, $headers, $body)
	{
		global $settings_no_pear;

		// Sometimes we do not want to actually send emails
		if (AntConfig::getInstance()->email['supress'] && !$this->ignoreSupression)
			return true;
		
		try
		{
			$this->headers = $headers;
			$mail_object = Mail::factory("smtp", $this->params);
			$this->status = $mail_object->send($recipients, $this->headers, $body);
		}
		catch (Exception $e)
		{
		}

		return $this->status;
	}
	
	// 0 = no error, 1 = bad address
	function ErrorStatus()
	{
		$msg = $this->status->getMessage();
		$strlen = strlen("unable to add recipient [");
		if (substr($msg, 0, $strlen) == "unable to add recipient [")
		{
			$this->err_msg = "Bad Address: ".substr($msg, $strlen, strpos($msg, "]", 0)-$strlen);
			return 1;
		}
		
		/*
		if (!$this->status)
			return 1;
		*/
	}
	
	function GetErrorMessage()
	{
		return $this->err_msg;
	}
}
