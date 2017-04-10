<?php
// SimpleTest Framework
//require_once 'PHPUnit/Autoload.php';
//require_once(dirname(__FILE__).'/../simpletest/autorun.php');
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/AntFs.php');

class AntConfigTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;
	var $antfs = null;

	function setUp() 
	{
		$this->path = dirname(__FILE__).'/../data/config';
	}

	/**
	 * Test loading data
	 */
	public function testLoadSetting()
	{
		$config = new AntConfig(null, $this->path);

		$this->assertEquals($config->testvar, "test");
		$this->assertEquals($config->section['val'], "sectest");
	}

	/**
	 * Test subvals
	 */
	public function testOverrides()
	{
		$config = new AntConfig("sub", $this->path); // load ant.sub.ini

		// Check a base var that is not overidden
		$this->assertEquals($config->section['stay'], "unchanged");

		// Inherited overrides
		$this->assertEquals($config->testvar, "testsub");
		$this->assertEquals($config->section['val'], "sectestsub");
	}

	/**
	 * Test local
	 */
	public function testLocal()
	{
		$config = new AntConfig("sublcl", $this->path); // load ant.sublcl.local.ini

		// Stay is not set in the ant.sub.ini but it is set in the ant.sub.local.ini file
		$this->assertEquals($config->section['stay'], "local");
	}
}
