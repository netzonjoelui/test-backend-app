<?php	
/*======================================================================================
	
	Class:		CEmail

	Purpose:	Encapsulate PHP email functionality

	Author:		Matt Anderson, matt.anderson@aereus.com
				Copyright (c) 2006 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		
				// Send plain-text email
				$headers['From']  = 'no-reply@aereus.com';
				$headers['To']  = 'some@domain.com';
				$headers['Subject']  = "New Website Lead";
				$message_body = "Date & Time: ".date('l dS \of F Y h:i:s A')."\r\n";
				$message_body .= "Name: ".$_POST["firstName"]." ".$_POST["lastName"]."\r\n";
				$message_body .= "Customer Comments:\r\n".$_POST['Comments'];
				$message_body .= "\r\n\r\n";
				$message_body .= "Follow these instructions to manage this lead:\r\n";
				$message_body .= "1. Go to https://[company].ant.aereus.com\r\n";
				$message_body .= "2. Log in with your user name and password. If you are prompted for company name, enter \"ross\"\r\n";
				$message_body .= "3. Click the Customers tab on the top\r\n";
				$message_body .= "4. Click \"Leads\" on the left\r\n";
				$message_body .= "5. Under \"Queues\" select \"All Leads\" and click \"Update\" on the right\r\n";
				$message_body .= "6. You will see a lead titled \" ".$_POST["firstName"]." ".$_POST["lastName"]."\" click the name to manage the lead.\r\n";
				$message_body .= "7. Remember to set the status to \"In-Progress\" once you have talked to the customer.\r\n";
					
				// Create email obj and send
				$email = new CEmail();
				$status = $email->Send($headers['To'], $headers, $message_body);
				unset($email);	

	Globals:	
				1. $ALIB_EMAIL_USEPEAR	= true;
				2. $ALIB_EMAIL_SERVER	= "localhost"; 
				3. $ALIB_EMAIL_PORT		= 25;
======================================================================================*/

if ($ALIB_EMAIL_USEPEAR !== false)
{
	require_once("PEAR.php");
	require_once("Mail.php");
}

class CEmail
{
	var $params;
	var $headers;
	var $staus;
	var $err_msg;
	
	function CEmail()
	{
		global $settings_email_server, $ALIB_EMAIL_SERVER;

		if ($ALIB_EMAIL_SERVER)
			$this->params["host"] = $settings_email_server;
		else if ($settings_email_server)
			$this->params["host"] = $settings_email_server;
		else
			$this->params["host"] = "localhost";

		if ($ALIB_EMAIL_PORT)
			$this->params["port"] = $ALIB_EMAIL_PORT;
		else
			$this->params["port"] = "25";
	}
	
	function Send($recipients, $headers, $body)
	{
		global $settings_no_pear;
		
		if ($settings_no_pear)
		{
			if (is_array($recipients))
			{
				foreach ($recipients as $key=>$to)
				{
					if ("To" == $key)
						$sendto = $to;
					else
						$headers[$key] = $to;
				}
			}
			else
				$sendto = $recipients;
			
			if (is_array($headers))
			{
				foreach ($headers as $key=>$val)
				{
					$strhead .= "$key: $val\r\n";
				}
			}
			$this->headers = $headers;	
			
			$this->status = mail($sendto, $headers['Subject'], $body, $strhead);

		}
		else
		{
			$this->headers = $headers;
			$mail_object =& Mail::factory("smtp", $this->params);
			$this->status = $mail_object->send($recipients, $this->headers, $body);

		}
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
?>
