<?php
/**
 * Test the email account sync workers
 */
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/WorkerMan.php');
require_once(dirname(__FILE__).'/../../lib/AntFs.php');

class Workers_Email_AccountSyncTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $ant = null;
	var $user = null;
	var $host = null;
	var $username = null;
	var $password = null;

	/**
	 * Setup class variables
	 */
	public function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
		$this->host = "test.netricos.com";
		$this->username = "administrator@test.netricos.com";
		$this->password = "Password1";
	}
	
	/**
	 * Test object index worker
	 */
	public function testEmailAccountSyncMailbox() 
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
		$cache = CCache::getInstance();

		// Sync will only run every 30 seconds to set timer back$cache = CCache::getInstance();
		$cache->set($this->dbh->dbname . "/email/sync/" . $this->user->id . "/" . $inboxInfo['id'], 0);

		// Call the worker
		$data = array(
			"user_id" => $this->user->id, 
			"mailbox_id" => $inboxInfo['id'],
			"email_account" => $aid,
		);
		$wm = new WorkerMan($this->dbh);
		$ret = $wm->run("email/account_sync_mailbox", serialize($data));
		$this->assertTrue(is_array($ret));
		$this->assertEquals(1, count($ret));
		$this->assertEquals($aid, $ret[0]);

		// Now make sure we skip test because we have not passed the 30 second interval
		$ret = $wm->run("email/account_sync_mailbox", serialize($data));
		$this->assertEquals(true, $ret); // will return true if we are not beyond the interval

		// Cleanup
		$accountObj->remove();
	}
}
