<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/RpcSvr.php');

// Creat a test server class
class TestMockRpcSvr
{
	function __construct($ant, $user)
	{
	}

	public function testOne()
	{
		return "one";
	}
}

class RpcSvrTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}
	
	function tearDown() 
	{
	}

	/**
	 * Test if properties are appropriately called
	 */
	function testMethodCall()
	{
		global $_REQUEST;

		$_REQUEST['function'] = 'testOne';
		$svr = new RpcSvr($this->ant, $this->user);
		$svr->setClass("TestMockRpcSvr");
		$ret = $svr->run();

		$this->assertEquals($ret, "one");
	}
}