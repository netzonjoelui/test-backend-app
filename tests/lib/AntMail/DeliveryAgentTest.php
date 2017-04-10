<?php
//require_once 'PHPUnit/Autoload.php';

// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once('lib/CDatabase.awp');
require_once('lib/Ant.php');
require_once('lib/AntUser.php');
require_once('lib/Object/EmailMessage.php');

class AntMail_DeliveryAgentTest extends PHPUnit_Framework_TestCase
{
	var $ant = null;
	var $user = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new ANT();
		$this->user = new AntUser($this->ant->dbh, -1); // -1 = administrator
		$this->dbh = $this->ant->dbh;
		
		$this->markTestSkipped('Cannot test since imap server is not setup.');
	}
	
	/**
	 * Test save original
	 */
	public function testSaveOriginal()
	{
		$newEmail = new CAntObject_EmailMessage($this->dbh, null, $this->user);
		$newEmail->setValue("subject", "manual");
		$newEmail->setGroup("Inbox");
		$mid = $newEmail->save();
		$this->assertTrue(is_numeric($mid));

		$mda = new AntMail_DeliveryAngent($this->dbh, $this->user);
		$mda->saveOriginal($mid, dirname(__FILE__)."/../../data/mime_emails/attachments-mail.txt");

		$oid = null;
		$message_id = null;
		$result = $this->dbh->Query("select message_id, lo_message from email_message_original where message_id='$mid';");
		if ($this->dbh->GetNumberRows($result))
		{
			$message_id = $this->dbh->GetValue($result, 0, "message_id");
			$oid = $this->dbh->GetValue($result, 0, "lo_message");
		}

		$this->assertNotEquals($message_id, null);
		$this->assertNotEquals($oid, null);

		// Cleanup
		$newEmail->removeHard();
	}

	/**
	 * Test delivery date
	 *
	 * The imported file header date is: "Thu, 26 Mar 2009 20:24:11 -0700"
	 */
	public function testImportDeliveryDate()
	{
		$newEmail = new CAntObject_EmailMessage($this->dbh, null, $this->user);
		$newEmail->setValue("subject", "manual");
		$newEmail->setGroup("Inbox");
		$mid = $newEmail->save();
		$this->assertTrue(is_numeric($mid));

		$mda = new AntMail_DeliveryAngent($this->dbh, $this->user);
		$mda->import(dirname(__FILE__)."/../../data/mime_emails/attachments-mail.txt", $newEmail);

		$this->assertNotEquals($newEmail->id, null);
		$this->assertEquals(strtotime($newEmail->getValue("message_date")), strtotime("03/26/2009 08:24 pm PDT"));

		// Cleanup
		$newEmail->removeHard();
	}
	
}
