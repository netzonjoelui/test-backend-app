<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/AntLog.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/AntFs.php');

class AntLogTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;
	var $antfs = null;

	function setUp() 
	{
		$this->path = dirname(__FILE__).'/../data/config';
	}

	/**
	 * Test logging errors
	 */
	public function testLogError()
	{
		// By default the logging is set to LOG_ERR
		$ret = AntLog::getInstance()->error("My Test");
		$this->assertNotEquals($ret, false);

		// Try logging an info message which should not be logged at all
		$ret = AntLog::getInstance()->info("My Test");
		$this->assertFalse($ret);
	}
}
