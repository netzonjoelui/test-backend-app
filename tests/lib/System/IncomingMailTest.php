<?php
/**
 * Test system incoming mail code
 */
//require_once 'PHPUnit/Autoload.php';

// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once('lib/CDatabase.awp');
require_once('lib/Ant.php');
require_once('lib/AntUser.php');
require_once('lib/System/IncomingMail.php');

class System_IncomingMailTest extends PHPUnit_Framework_TestCase
{
	private $ant = null;
	private $user = null;
	private $dbh = null;
	private $antsys = null;

	function setUp() 
	{
		$this->antsys = new AntSystem();
		$this->ant = new ANT();
		$this->user = new AntUser($this->ant->dbh, -1); // -1 = administrator
		$this->dbh = $this->ant->dbh;
		
		$this->markTestSkipped('Cannot test since imap server is not setup.');
    } 
        
    /**
	 * Test processing the inbox
	 */
	public function testProcessInbox()
	{
		$username = "administrator@test.netricos.com";
		$password = "Password1";
        $port = 465;

        // Put a message on the server for testing -- this will be deleted in the last unit test
        $email = new Email(AntConfig::getInstance()->email['backend_host'], null, null, $port);
		$email->ignoreSupression = true;
        $email->send(
            array(AntConfig::getInstance()->email['dropbox']), 
            array(
                "To"=>$this->ant->name . "-leads" . AntConfig::getInstance()->email['dropbox_catchall'],
                "From"=>"administrator@aereus.com", "Subject"=>"Test Import Lead"
            ), 
            "Test Lead"
        );
		sleep(1); // Give the server time to refresh the email list

		// Create incoming email processor
		$sysim = new System_IncomingMail();

		// Test importing an activity using the email
		$imported = $sysim->processInbox();
		$this->assertTrue(count($imported)>0);

		// Cleanup created objects - leads will return the object rather than the ID
        foreach ($imported as $imp)
        {
            if ($imp instanceof CAntObject)
                $imp->removeHard();
        }
    }
        

    /**
	 * Test importing a raw rfc email message
	 */
	public function testImportMessage()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__) . "/../../data/mime_emails/system_incoming.txt", "lead");
		$parser = new AntMail_MimeParser($tmpFile);

		// First make sure we cleanup any existing customers with the import email
        $olist = new CAntObjectList($this->dbh, "customer");
        $olist->addCondition("and", "email", "is_equal", "test-from@aereus.com");
        $olist->getObjects();
        for ($i = 0; $i < $olist->getNumObjects(); $i++)
        {
            $obj = $olist->getObject($i);
            $obj->removeHard();
        }

		// Create incoming email processor
		$sysim = new System_IncomingMail();

		// Test importing an activity using the email
		$lead = $sysim->importMessage($tmpFile);
		$this->assertNotEquals(false, $lead);

        // Test lead values
		$this->assertEquals("lead", $lead->object_type);
		$this->assertEquals("test-from@aereus.com", $lead->getValue("email"));
		$this->assertEquals("plain body content", $lead->getValue("notes"));
        $this->assertNotNull($lead->getValue("source_id"));

		// Cleanup
		$lead->removeHard();
		unlink($tmpFile);
	}
	
	/**
	 * Test importing comment
	 */
	public function testImportComment()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__) . "/../../data/mime_emails/system_incoming_att.txt");
		$parser = new AntMail_MimeParser($tmpFile);

		// Create test customer to comment on
		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testImportComment");
		$cust->save();

		// Create incoming email processor
		$sysim = new System_IncomingMail();

		// Test importing a comment using the email
		$comment = $sysim->importComment($this->ant->name, "customer", $cust->id, $parser);
		$this->assertNotEquals(false, $comment);

		$this->assertEquals("customer:" . $cust->id, $comment->getValue("obj_reference"));
		$this->assertEquals("plain body content", $comment->getValue("comment"));

		$newCustObjRef = $comment->getValue("sent_by");
		$refParts = CAntObject::decodeObjRef($newCustObjRef);
		$this->assertEquals("customer", $refParts['obj_type']);
		$newCust = CAntObject::factory($this->dbh, "customer", $refParts['id'], $this->user);

		// Check attachments
		$attachments = $comment->getValue("attachments");
		$this->assertEquals(3, count($attachments));

		// Cleanup
		$newCust->removeHard(); // sender customer created in the import
		$cust->removeHard();
        $comment->removeHard();
		unlink($tmpFile);
	}

    /**
	 * Test importing activity
	 */
	public function testImportActivity()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__) . "/../../data/mime_emails/system_incoming.txt");
		$parser = new AntMail_MimeParser($tmpFile);

		// Create test customer to add activity to
		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testImportActivity");
        $cust->setValue("email", "test-from@aereus.com"); // send from self - this is the from in the test email
		$cust->save();

		// Create incoming email processor
		$sysim = new System_IncomingMail();

		// Test importing an activity using the email
		$activity = $sysim->importActivity($this->ant->name, "customer", $cust->id, $parser);
		$this->assertNotEquals(false, $activity);

        // Test activity values
		$this->assertEquals("customer:" . $cust->id, $activity->getValue("subject"));
		$this->assertEquals("plain body content", $activity->getValue("notes"));

		// Cleanup
		$activity->removeHard();
		$cust->removeHard();
		unlink($tmpFile);
	}

    /**
	 * Test importing case
	 */
	public function testImportCase()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__) . "/../../data/mime_emails/system_incoming.txt");
		$parser = new AntMail_MimeParser($tmpFile);

		// Create test customer to add activity to
		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testImportCase");
        $cust->setValue("email", "test-from@aereus.com"); // send from self - this is the from in the test email
		$cust->save();

		// Create incoming email processor
		$sysim = new System_IncomingMail();

		// Test importing an activity using the email
		$case = $sysim->importCase($this->ant->name, $parser);
		$this->assertNotEquals(false, $case);

        // Test activity values
		$this->assertEquals($cust->id, $case->getValue("customer_id"));
		$this->assertEquals("plain body content", $case->getValue("description"));

		// Cleanup
		$case->removeHard();
		$cust->removeHard();
		unlink($tmpFile);
	}

    /**
	 * Test importing lead - with no exsiting customer
	 */
	public function testImportLead()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__) . "/../../data/mime_emails/system_incoming.txt");
		$parser = new AntMail_MimeParser($tmpFile);

        // First make sure we cleanup any existing customers with the import email
        $olist = new CAntObjectList($this->dbh, "customer");
        $olist->addCondition("and", "email", "is_equal", "test-from@aereus.com");
        $olist->getObjects();
        for ($i = 0; $i < $olist->getNumObjects(); $i++)
        {
            $obj = $olist->getObject($i);
            $obj->removeHard();
        }

		// Create incoming email processor
		$sysim = new System_IncomingMail();

		// Test importing an activity using the email
		$lead = $sysim->importLead($this->ant->name, $parser);
		$this->assertNotEquals(false, $lead);

        // Test lead values
		$this->assertEquals("lead", $lead->object_type);
		$this->assertEquals("test-from@aereus.com", $lead->getValue("email"));
		$this->assertEquals("plain body content", $lead->getValue("notes"));
        $this->assertNotNull($lead->getValue("source_id"));

		// Cleanup
		$lead->removeHard();
		unlink($tmpFile);
	}

    /**
	 * Test importing lead with existing customer - convert to opportunity
	 */
	public function testImportLeadExistCust()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__) . "/../../data/mime_emails/system_incoming.txt");
		$parser = new AntMail_MimeParser($tmpFile);

        // First make sure we cleanup any existing customers with the import email
        $olist = new CAntObjectList($this->dbh, "customer");
        $olist->addCondition("and", "email", "is_equal", "test-from@aereus.com");
        $olist->getObjects();
        for ($i = 0; $i < $olist->getNumObjects(); $i++)
        {
            $obj = $olist->getObject($i);
            $obj->removeHard();
        }

		// Create test customer to add opportunity to
		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testImportLeadExistCust");
        $cust->setValue("email", "test-from@aereus.com"); // send from self - this is the from in the test email
		$cust->save();

		// Create incoming email processor
		$sysim = new System_IncomingMail();

		// Test importing an activity using the email
		$opp = $sysim->importLead($this->ant->name, $parser);
		$this->assertNotEquals(false, $opp);

        // Test lead values
		$this->assertEquals("opportunity", $opp->object_type);
		$this->assertEquals($cust->id, $opp->getValue("customer_id"));
		$this->assertEquals("plain body content", $opp->getValue("notes"));
        $this->assertNotNull($opp->getValue("lead_source_id"));
        $this->assertNotNull($opp->getValue("name"));

		// Cleanup
		$opp->removeHard();
		$cust->removeHard();
		unlink($tmpFile);
	}

    /**
     * Test parsing address
     */
    public function testParseAddress()
    {
		// Create incoming email processor
		$sysim = new System_IncomingMail();

		// Test private parseAddress with leads inbox
		$refIm = new \ReflectionObject($sysim);
        $parseAddress = $refIm->getMethod("parseAddress");
		$parseAddress->setAccessible(true);
        $parsed = $parseAddress->invoke($sysim, "aereus-leads");
		$this->assertEquals($parsed['account'], "aereus");
		$this->assertEquals($parsed['action'], "leads");

		// Test private parseAddress with comment type address
		$refIm = new \ReflectionObject($sysim);
        $parseAddress = $refIm->getMethod("parseAddress");
		$parseAddress->setAccessible(true);
        $parsed = $parseAddress->invoke($sysim, "aereus-com-customer.123");
		$this->assertEquals($parsed['account'], "aereus");
		$this->assertEquals($parsed['action'], "com");
		$this->assertEquals($parsed['ref_obj_type'], "customer");
		$this->assertEquals($parsed['ref_id'], "123");
    }

	/**
	 * Create a temp file to use when importing email
	 *
	 * @group getMessageTempFile
	 * @return string The path to the newly created temp file
	 */
	private function getMessageTempFile($file, $type="")
	{
		$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
		if (!file_exists($tmpFolder))
			@mkdir($tmpFolder, 0777);
		$tmpFile = tempnam($tmpFolder, "em");
		file_put_contents($tmpFile, file_get_contents($file)); // copy data

		// Normalize new lines to \r\n
		$handle = @fopen($tmpFile, "r");
		$handleNew = @fopen($tmpFile."-pro", "w");
		$buffer = null;
		if ($handle) 
		{
			while (($buffer = fgets($handle, 4096)) !== false) 
			{
                // Re-write to to include include the account
                if (trim($buffer) == "To: TestTo <test-to@aereus.com>")
                {
                    switch ($type)
                    {
                    case 'lead':
                        $buffer = "To: TestTo <" . $this->ant->name . "-leads@aereus.com>";
                        break;
                    }
                }

				fwrite($handleNew,  preg_replace('/\r?\n$/', '', $buffer)."\r\n");
			}
			fclose($handle);
			fclose($handleNew);
			unlink($tmpFile);
			$tmpFile = $tmpFile."-pro"; // update name to match processed file
		}

		return $tmpFile;
	}
}
