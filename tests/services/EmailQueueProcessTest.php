<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/AntService.php');
require_once(dirname(__FILE__).'/../../services/EmailQueueProcess.php');

class EmailQueueProcessTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}
	
	function tearDown() 
	{
	}

	function testEmailQueueProcess() 
	{
		$dbh = $this->dbh;
		
		// First import test message
		$oid = $dbh->loImport(dirname(__FILE__)."/data/mime_emails/mail.txt");
		$dbh->Query("insert into email_message_queue(user_id, lo_message, ts_delivered) values('".$this->user->id."', '$oid', 'now')");

		// Process Queue
		$svc = new EmailQueueProcess();
		$svc->main($dbh); // Pass only this database to main - no need to loop through all accounts

		// Test to make sure email is no longer in queue
		$this->assertEquals($dbh->GetNumberRows($dbh->Query("select id from email_message_queue where lo_message='$oid'")), 0);

		// Test to make sure email is now in email_message_original and that message_id is set to a new email message
		$result = $dbh->Query("select message_id from email_message_original where lo_message='$oid'");
		$mid = $dbh->GetValue($result, 0, "message_id");
		$this->assertEquals($dbh->GetNumberRows($result), 1);

		// Test for valid email message
		$email = new CAntObject($dbh, "email_message", $mid);
		$this->assertTrue($email->id>0);

		// Cleanup
		$dbh->loUnlink($oid);
		$dbh->Query("delete from email_message_original where message_id='$mid'");
		$email->removeHard();
	}
}
