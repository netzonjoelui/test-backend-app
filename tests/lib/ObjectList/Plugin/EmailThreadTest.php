<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../../lib/AntConfig.php');
require_once('lib/CDatabase.awp');
require_once('lib/Ant.php');
require_once('lib/AntUser.php');
require_once('lib/CAntObject.php');
require_once('lib/CAntObjectList.php');

class AntObjectList_Plugin_EmailThreadTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator
		$this->host = "test.netricos.com";
		$this->username = "administrator@test.netricos.com";
		$this->password = "Password1";
	}

	/**
	 * Make sure accounts are not synchronized if no mailbox is defined
	 */
	public function testNoMailboxQuery()
	{
		// Query without mailbox and make sure accounts are not processed
		$objList = new CAntObjectList($this->dbh, "email_thread", $this->user);
		$objList->debug = true;
		$objList->getObjects(0, 1);	

		// Nothing should have been processed
		$this->assertEquals(null, $objList->plugin->jobId);
	}

	/**
	 * Now test sync with email account set
	 */
	public function testOnQueryObjectsBefore()
	{
		$accountObj = new AntMail_Account($this->ant->dbh);
		$accountObj->name = "UnitTest Pop EmailAccount";
		$accountObj->type = "pop3";
		$accountObj->username = $this->username;
		$accountObj->password = $this->password;
		$accountObj->host = $this->host;
        $accountObj->userId = $this->user->id;
		$aid = $accountObj->save();
		$this->assertTrue($aid > 0);

		$mailObj = CAntObject::factory($this->dbh, "email_thread", null, $this->user);
		$inboxInfo = $mailObj->getGroupingEntryByPath("mailbox_id", "Inbox");

		// Clear the cached to force the job to run
		$cache = CCache::getInstance();
		$cache->remove($this->dbh->dbname . "/email/sync/" . $this->user->id . "/" . $inboxInfo['id']);

		// Query without mailbox and make sure accounts are not processed
		$objList = new CAntObjectList($this->dbh, "email_thread", $this->user);
		$objList->debug = true;
		$objList->addCondition("and", "mailbox_id", "is_equal", $inboxInfo['id']);
		$objList->addCondition("and", "email_account", "is_equal", $aid);
		$objList->getObjects(0, 1);	

		$this->assertNotEquals($objList->plugin->jobId, null);
		$this->assertTrue(is_numeric($objList->plugin->jobId));
		/*
		$this->assertEquals(1, count($objList->plugin->accountsProcessed));
		$this->assertEquals($aid, $objList->plugin->accountsProcessed[0]);
		*/

		// Cleanup
		$accountObj->remove();
	}
}
