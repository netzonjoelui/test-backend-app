<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/StatusUpdate.php');

class CAntObject_StatusUpdateTest extends PHPUnit_Framework_TestCase
{
	var $ant = null;
	var $user = null;
	var $dbh = null;
	var $dbhSys = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, USER_SYSTEM);
	}

	/**
	 * Test sending notifications
	 */
	public function testSendNotifications()
	{
		// Crate a update and notify this user
		$update = CAntObject::factory($this->dbh, "status_update", null, $this->user);
		$update->setValue("comment", "testSendNotifications");
		$update->setValue("notify", "user:" . $this->user->id . "|Full Name");
		$update->testMode = true;
		$uid = $update->save();

		$this->assertEquals($update->testModeBuf["sendTo"]["eml"], $this->user->getEmail());
		
		// Cleanup
		$update->removeHard();
	}
}

