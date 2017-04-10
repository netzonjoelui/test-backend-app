<?php
/**
 * Test entity definition loader class that is responsible for creating and initializing exisiting definitions
 */
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Help.php');

class HelpTest extends PHPUnit_Framework_TestCase 
{
	var $dbh = null;
	var $user = null;

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
	 * Test dynamic factory of entity
	 */
	public function testGetTourItem()
	{
		$help = $this->ant->getServiceLocator()->get("Help");
		$content = $help->getTourItem("tests/1-first");
		$this->assertEquals("First Test Content", trim($content));
	}
}
