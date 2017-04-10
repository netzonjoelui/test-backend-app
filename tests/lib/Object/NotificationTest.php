<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Notification.php');

class CAntObject_NotificationTest extends PHPUnit_Framework_TestCase
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
	 * Test tsExecute calculation based on a field
	 */
	public function testUnseenDuplicate()
	{
		// Add an object to reference
		$task = CAntObject::factory($this->dbh, "task", null, $this->user);
		$task->setValue("name", "testUnreadDuplicate");
		$tid = $task->save();

		$name = "testUnreadDuplicate";
		
		// Add first notification
		$notification = CAntObject::factory($this->dbh, "notification", null, $this->user);
		$notification->setValue("name", $name);
		$notification->setValue("description", "test");
		$notification->setValue("obj_reference", "task:$tid");
		$notification->setValue("f_popup", 'f');
		$notification->setValue("f_seen", 'f');
		$notification->setValue("owner_id", $this->user->id);
		$nid1 = $notification->save();
		$notification->clearCache();

		// Add an identical notification which should over-write the first because they are both unseen
		$notification2 = CAntObject::factory($this->dbh, "notification", null, $this->user);
		$notification2->setValue("name", $name);
		$notification2->setValue("description", "test");
		$notification2->setValue("obj_reference", "task:$tid");
		$notification2->setValue("f_popup", 'f');
		$notification2->setValue("f_seen", 'f');
		$notification2->setValue("owner_id", $this->user->id);
		$nid2 = $notification2->save();

		// Open and verify that first notification was deleted
		//$testNotif = CAntObject::factory($this->dbh, "notification", $nid1, $this->user);
		//$this->assertEquals($testNotif->getValue("f_deleted"), 't');
		
		$cacheResult = $notification->cache->get($this->dbh->dbname."/objects/gen/notification");
		$this->assertEmpty($cacheResult);
		
		
		// Cleanup
		$notification->removeHard();
		$notification2->removeHard();
		$task->removeHard();
	}
}
