<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Template.php');

class AntObject_FileTest extends PHPUnit_Framework_TestCase
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
		$this->antfs = new AntFs($this->dbh, $this->user); // -1 = administrator
	}

	/**
	 * Test if a file is temp
	 *
	 * @group testIsTemp
	 */
	public function testIsTemp()
	{
		return; // This test is skipped since we do not have a test domain for ANS right now
		
		$fldr = $this->antfs->openFolder("%tmp%", true);
		$this->assertNotNull($fldr->id);

		$file = $fldr->openFile("test", true);
		$size = $file->write("test contents");

		// Test
		$this->assertTrue($file->isTemp());

		// Move then test again
		$fldr = $this->antfs->openFolder("/test/mytest", true);
		$file->move($fldr);
		$this->assertFalse($file->isTemp());

		// Cleanup
		$ret = $file->removeHard();
	}
}
