<?php
/**
 * Parse incoming system email and place in the correct account
 * 
 * @category  AntMail
 * @package   IMAP
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntMail/Backend.php");
require_once("lib/AntMail/MimeParser.php");
require_once("lib/AntFs.php");
 
class System_IncomingMail
{
	/**
     * Temporary Folder
     *
     * @var String
     */
	private $tempFolder = null;

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->tempFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
	}

	/**
	 * Get all messages frm the system incoming mailbox and process each message
	 */
	public function processInbox()
	{
		// If we are not using an email backend then just skip processing
		if (!AntConfig::getInstance()->email['backend_host'])
			return;

        $ret = array();
			
		// Setupmail backend
		$backend = new AntMail_Backend("imap", 
										   AntConfig::getInstance()->email['backend_host'], 
										   AntConfig::getInstance()->email['dropbox'], 
										   AntConfig::getInstance()->db['password']);

        $messages = $backend->getMessageList("Inbox");
		if (is_array($messages))
		{
			foreach($messages as $msg)
			{
				// Get full message text
				$rfc822 = $backend->getFullMessage($msg['msgno']);

				// Write message to temp file
				$tmpFile = $this->saveTempFile($rfc822);

				// Import the message
				$res = $this->importMessage($tmpFile);
                if ($res)
                    $ret[] = $res;

				// Cleanup
				unlink($tmpFile);
                if ($res)
				    $backend->deleteMessage($msg['uid'], "INBOX");
			}
		}

        return $ret;
	}

    /**
	 * Cleanup inbox
	 */
	public function cleanupInbox()
	{
        // TODO: purge invalid emails from the inbox
    }

	/**
	 * Import message
	 *
	 * @param string $filePath The path to the RFC822 file to process
	 * @return 
	 */
	public function importMessage($filePath)
	{
		$parser = new AntMail_MimeParser($filePath);

		$ret = false;

		$to = $parser->getHeader("to");
		$toArr = $parser->parseAddressList($to);
		foreach ($toArr as $recipient)
		{
			// Parse this address to see if we are importing it
			$imporData = $this->parseAddress($recipient['mailbox']);

			// Skip over bad addresses
			if (!$imporData)
				continue;

			// Check for mailer-daemon
			$from = $parser->getHeader("from");
			$fromArr = $parser->parseAddressList($from);
			if ($fromArr[0]['address'])
			{
				if (strstr(strtolower($fromArr[0]['address']), 'mailer-daemon') !== false)
					continue; // Skip this message
			}

			switch ($imporData['action'])
			{
			// Add new lead
			case 'leads':
				if ($imporData['account'])
					$ret = $this->importLead($imporData['account'], $parser);
				break;

			// Add new case
			case 'cases':
				if ($imporData['account'])
					$ret = $this->importCase($imporData['account'], $parser);
				break;

			// Record email activity
			case 'act':
				if ($imporData['account'] && $imporData['ref_obj_type'] && $imporData['ref_id'])
					$ret = $this->importActivity($imporData['account'], $imporData['ref_obj_type'], $imporData['ref_id'], $parser);
				break;

			// Add comment
			case 'com':
				if ($imporData['account'] && $imporData['ref_obj_type'] && $imporData['ref_id'])
					$ret = $this->importComment($imporData['account'], $imporData['ref_obj_type'], $imporData['ref_id'], $parser);
				break;
			}
		}

		return $ret;
	}

	/**
	 * Import comment
	 *
	 * @param string $accountName The name of the account to import data into
	 * @param string $objType The type of object we are commenting on
	 * @param string $oid The unique id of the object we are commenting on
	 * @param AntMail_MimeParser $parser The parser with the email message we are working with
	 * @return CAntObject_Comment
	 */
	public function importComment($accountName, $objType, $oid, $parser)
	{
		$ant = $this->getAccount($accountName);
		if (!$ant)
			return false;

		// Get from address
		$from = $parser->getHeader("from");
		$fromArr = $parser->parseAddressList($from);
		$fromAddress = ($fromArr[0]['address']) ? $fromArr[0]['address'] : "";
		$fromName = ($fromArr[0]['display']) ? $fromArr[0]['display'] : "";
		if (!$fromAddress)
			return false;

		// Try to get the user name from the sender email address
		$user = $ant->getUserByEmail($fromAddress);

		// If not from a user, check to see if sender is a customer
		$customer = null;
		if (!$user)
			$customer = CAntObject_Customer::findCustomerByEmail($ant->dbh, $fromAddress, null, false);

		// Create new customer if not already exists and sender is not a user
		if (!$user && !$customer)
			$customer = $this->createCustomerFromEmail($ant, $fromAddress, $fromName);

		// Get the message body
		$body = $parser->getMessageBody("plain");
		if (!$body)
			$body = $parser->htmlToPlain($parser->getMessageBody("html"));
		
		// Strip out prevous quoted message and signatures
		$body = $parser->parseReply($body);

		// Get referenced object and if it exists then add comment
		$obj = CAntObject::factory($ant->dbh, $objType, $oid, $user);
		if ($obj->id)
		{
            $comment = CAntObject::factory($ant->dbh, "comment", null, $user);
            $comment->setValue("comment", $body);
            $comment->setValue("obj_reference", $objType . ":" . $oid);
            $comment->setValue("sent_by", ($user) ? "user:" . $user->id : "customer:" . $customer->id);

            // Set owners to notify of the new comment
            $followers = $obj->getFollowers();
            if (count($followers))
                $comment->setValue("notify", implode(",", $followers));

			// Get attachments
			$antfs = new AntFs($ant->dbh, $user);
			$attachments = $parser->getAttachments();
			foreach ($attachments as $att)
			{
				$tmpFolder = $antfs->openFolder("%tmp%");
				$file = $tmpFolder->importFile($att->filePath, $att->fileName);

				if ($file->id)
					$comment->setMValue("attachments", $file->id);
			}
        }
		
		$comment->save();
        return $comment;
	}

	/**
	 * Import activity
	 *
	 * @param string $accountName The name of the account to import data into
	 * @param string $objType The type of object we are adding an activity to
	 * @param string $oid The unique id of the object we are referencing
	 * @param AntMail_MimeParser $parser The parser with the email message we are working with
	 * @return CAntObject_Activity
	 */
	public function importActivity($accountName, $objType, $oid, $parser)
	{
		$ant = $this->getAccount($accountName);
		if (!$ant)
			return false;

		// Get from address
		$from = $parser->getHeader("from");
		$fromArr = $parser->parseAddressList($from);
		$fromAddress = ($fromArr[0]['address']) ? $fromArr[0]['address'] : "";
		$fromName = ($fromArr[0]['display']) ? $fromArr[0]['display'] : "";
		if (!$fromAddress)
			return false;

		// Try to get the user name from the sender email address
		$user = $ant->getUserByEmail($fromAddress);

		// If not from a user, check to see if sender is a customer
		$customer = null;
		if (!$user)
			$customer = CAntObject_Customer::findCustomerByEmail($ant->dbh, $fromAddress, null, false);

		// Create new customer if not already exists and sender is not a user
		if (!$user && !$customer)
			$customer = $this->createCustomerFromEmail($ant, $fromAddress, $fromName);

		// Get the message body
		$body = $parser->getMessageBody("plain");
		if (!$body)
			$body = $parser->htmlToPlain($parser->getMessageBody("html"));
		
		// Strip out prevous quoted message and signatures
		$body = $parser->parseReply($body);

		// Get referenced object and if it exists then add activity
		$obj = CAntObject::factory($ant->dbh, $objType, $oid, $user);
		if ($obj->id)
		{
			$act = CAntObject::factory($ant->dbh, "activity", null, $user);
			$act->setValue("verb", "sent");
			$act->setValue("name", "Email");
			$act->setValue("notes", $body);
			//$act->setValue("obj_reference", $objType . ":" . $oid);
			$act->setValue("subject", ($user) ? "user:" . $user->id : "customer:" . $customer->id);
			$act->addAssociation($objType, $oid);
            $act->save();
            return $act;
		}
		
		return false;
	}

	/**
	 * Import lead
	 *
	 * @param string $accountName The name of the account to import data into
	 * @param AntMail_MimeParser $parser The parser with the email message we are working with
	 * @return CAntObject Either a lead or an opportunity if a customer exists with the email address
	 */
	public function importLead($accountName, $parser)
	{
		$ant = $this->getAccount($accountName);
		if (!$ant)
			return false;

		// Get from address
		$from = $parser->getHeader("from");
		$fromArr = $parser->parseAddressList($from);
		$fromAddress = ($fromArr[0]['address']) ? $fromArr[0]['address'] : "";
		$fromName = ($fromArr[0]['display']) ? $fromArr[0]['display'] : "";
		if (!$fromAddress)
			return false;

		// Try to get the user name from the sender email address
		$user = $ant->getUserByEmail($fromAddress);

		// Check if a customer already exists
		$customer = CAntObject_Customer::findCustomerByEmail($ant->dbh, $fromAddress, null, false);

		// Get the message body
		$body = $parser->getMessageBody("plain");
		if (!$body)
			$body = $parser->htmlToPlain($parser->getMessageBody("html"));
		
		// Strip out prevous quoted message and signatures
		$body = $parser->parseReply($body);


        // Get 'Email' lead source - create if missing
		$lead= CAntObject::factory($ant->dbh, "lead", null, $user);
        $sourceGrp = $lead->getGroupingEntryByName("Email");
        if (!$sourceGrp)
            $sourceGrp = $lead->addGroupingEntry("source_id", "Email", "A0A0A0", 7);

        // If email address was found in customers, then create an opportunity rather than a lead
        if ($body && $customer)
        {
			$opp = CAntObject::factory($ant->dbh, "opportunity", null, $user);
			$opp->setValue("customer_id", $customer->id);

            // Get the name from the subject
            $name = $parser->getHeader("subject");
			$opp->setValue("name", ($name) ? $name : "Imported Opportunity");

			$opp->setValue("notes", $body);
            // If being sent from a user email then assign to that user, otherwise assign to customer owner
			$opp->setValue("owner_id", ($user) ? $user->id : $customer->getValue("owner_id"));
            $opp->setValue("lead_source_id", $sourceGrp['id']);
			$opp->save();
            return $opp;
        }
        else if ($body)
        {
            // Create new lead
			$lead= CAntObject::factory($ant->dbh, "lead", null, $user);

            if ($fromName)
            {
                $parts = explode(" ", $fromName);
                $lead->setValue("first_name", trim($parts[0], ","));
                $lead->setValue("last_name", trim($parts[1], ","));
            }
            else
            {
                $lead->setValue("first_name", $fromAddress);
            }

			$lead->setValue("email", $fromAddress);
			$lead->setValue("notes", $body);
            $lead->setValue("source_id", $sourceGrp['id']);
			$lead->save();
            return $lead;
        }
		
		return false;
	}

	/**
	 * Import case
	 *
	 * @param string $accountName The name of the account to import data into
	 * @param AntMail_MimeParser $parser The parser with the email message we are working with
	 * @return string The unique id of the case added
	 */
	public function importCase($accountName, $parser)
	{
		$ant = $this->getAccount($accountName);
		if (!$ant)
			return false;

		// Get from address
		$from = $parser->getHeader("from");
		$fromArr = $parser->parseAddressList($from);
		$fromAddress = ($fromArr[0]['address']) ? $fromArr[0]['address'] : "";
		$fromName = ($fromArr[0]['display']) ? $fromArr[0]['display'] : "";
		if (!$fromAddress)
			return false;

		// Try to get the user name from the sender email address
		$user = $ant->getUserByEmail($fromAddress);

		// If not from a user, check to see if sender is a customer
		$customer = null;
		if (!$user)
			$customer = CAntObject_Customer::findCustomerByEmail($ant->dbh, $fromAddress, null, false);

		// Create new customer if not already exists and sender is not a user
		if (!$user && !$customer)
			$customer = $this->createCustomerFromEmail($ant, $fromAddress, $fromName);

		// Get the message body
		$body = $parser->getMessageBody("plain");
		if (!$body)
			$body = $parser->htmlToPlain($parser->getMessageBody("html"));
		
		// Strip out prevous quoted message and signatures
		$body = $parser->parseReply($body);

        if ($body && $customer)
        {
			$case = CAntObject::factory($ant->dbh, "case", null, $user);
			$case->setValue("customer_id", $customer->id);
			$case->setValue("title", ($parser->getHeader("subject")) ? $parser->getHeader("subject") : "Emailed");
			$case->setValue("description", $body);
			$case->setValue("created_by", ($user) ? $user->name : $customer->getName());
            $case->save();
            return $case;
        }
		
		return false;
	}

	/**
	 * Parse an address to look for account and recipient actions
	 *
	 * @param string $address The mailbox to parse (the address minus the @host)
	 * @return array('account', 'action', 'reference') or false if not an importable address
	 */
	private function parseAddress($address)
	{
		$parts = explode("-", $address);

		if (count($parts) < 2)
			return false;

		// Initailize array and put the first part in as the account which is required in the import address
		// and the second part is the action which is also always required.
		$ret = array(
			"account" => $parts[0],
			"action" => $parts[1],
		);

		// Add optional reference in obj_type:id reference form form
		if (count($parts) == 3)
		{
            $parts[2] = str_replace(".", ":", $parts[2]);
			$objRef = CAntObject::decodeObjRef($parts[2]);
			$ret['ref_obj_type'] = $objRef['obj_type'];
			$ret['ref_id'] = $objRef['id'];
		}

        return $ret;
	}

    /**
     * Saves the mime email into the temp file
     *
     * @param string $mimeEmail Mime Email
     */
    private function saveTempFile($mimeEmail)
    {
        if (!file_exists($this->tempFolder))
            @mkdir($this->tempFolder, 0777, true);        
        $tmpFile = tempnam($this->tempFolder, "incemail");
        
        // Normalize new lines to \r\n
        if ($mimeEmail)
        {
            $handle = @fopen($tmpFile, "w+");
            fwrite($handle, preg_replace('/\r?\n$/', '', $mimeEmail)."\r\n"); // Write the email message content
            
            return $tmpFile;
        }
        else
            return null;
    }

	/**
	 * Get account based on the name
	 *
	 * @param string $name The name of the account to get
	 * @return Ant
	 */
	private function getAccount($name)
	{
		$antSystem = new AntSystem();
		$accInfo = $antSystem->getAccountInfo($name);

		if ($accInfo["id"] == -1 || !$accInfo["id"])
			return false;

		// Create and return account
		// TODO: We may want to cache this in the future to reduce setup load
		$ant = new Ant($accInfo["id"]);

        // Make sure we are working with the same version of code - beta is not processing release
        if (AntConfig::getInstance()->version)
        {
            if ($ant->version != AntConfig::getInstance()->version)
                return false;
        }

		return $ant;
	}

	/**
	 * Create a customer from an email address
	 *
	 * @param Ant $ant The Ant account
	 * @param string $address The actual email address
	 * @param string $display Optional display
	 * @return CAntObject_Customer
	 */
	private function createCustomerFromEmail($ant, $address, $display="")
	{
		$customer = CAntObject::factory($ant->dbh, "customer");
		$customer->setValue("type_id", CUST_TYPE_CONTACT);
		$customer->setValue("email", $address);
		$customer->setValue("notes", "Automatically created from incoming email comment");
		if ($display)
		{
			$parts = explode(" ", $display);
			$customer->setValue("first_name", trim($parts[0], ","));
			$customer->setValue("last_name", trim($parts[1], ","));
		}
		else
		{
			$customer->setValue("first_name", $address);
		}
		$customer->save();

        return $customer;
	}
}
