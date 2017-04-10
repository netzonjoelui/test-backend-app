<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Template.php');

class AntObject_TaskTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}

	/**
	 * Test deafult user-level groupings for category
	 */
	public function testVerifyDefaultGroupings()
	{
		$dbh = $this->dbh;
		$obj = CAntObject::factory($dbh, "task", null, $this->user);            
		
		$groups = $obj->getGroupingData("category", null, array("user_id"=>$this->user->id));
		foreach ($groups as $group)
		{
			$obj->deleteGroupingEntry("category", $group['id']);
		}
		
		// Now verify groupings which should create defaults
		$result = $obj->verifyDefaultGroupings("category");
		
		// Make sure we have more than one default grouping added
		$this->assertTrue(count($result) >= 1);
	}

}
