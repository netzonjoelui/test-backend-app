<?php
/**
 * This needs to be split out into diferent unit test classes for each worker.
 *
 * DO NOT ADD TO THIS CLASS!
 * All new tests should be written in new unit test class files. 
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

class Workers_Lib_ObjectsTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $ant = null;
	var $user = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}
	
	/**
	 * Test object index worker
	 */
	function testObjectIndex() 
	{
		$dbh = $this->dbh;

		// Create test customer
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "WorkerpoolTest - testObjectIndex");
		$oid = $obj->save();
		unset($obj);

		// Print newly created customer
		$data = array(
			"oid" => $oid, 
			"obj_type" => "customer", 
		);

		$wp = new WorkerMan($dbh);
		$ret = $wp->run("lib/object/index", serialize($data));

		$this->assertEquals($ret, true);

		// Cleanup
		$obj = new CAntObject($dbh, "customer", $oid, $this->user);
		$obj->removeHard();
	}
}
