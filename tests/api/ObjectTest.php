<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');    
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');    
require_once(dirname(__FILE__).'/../../controllers/CustomerController.php');
require_once(dirname(__FILE__).'/../../controllers/ObjectController.php');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');        
require_once(dirname(__FILE__).'/../../lib/Olap.php');	

class AntApi_ObjectTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	/**
	 * The url of the ANT sever (usually set to localhost)
	 *
	 * @var string
	 */
	public $antServer = "";

	/**
	 * User to be used when connecting to ant
	 *
	 * @var string
	 */
	public $antUser = "";

	/**
	 * password to be used when connecting to ant
	 *
	 * @var string
	 */
	public $antPass = "";

	function setUp() 
	{
		// Elastic local store
		if (AntConfig::getInstance()->object_index['host'] && AntConfig::getInstance()->object_index['type'] == "elastic")
		{
			global $ANTAPI_STORE_ELASTIC_IDX, $ANTAPI_STORE_ELASTIC_HOST;

			$ANTAPI_STORE_ELASTIC_IDX = "tmp_ant_uni_test";
			$ANTAPI_STORE_ELASTIC_HOST = AntConfig::getInstance()->db['host'];
		}

		// PGSQL local store
		if (AntConfig::getInstance()->db['host'] && AntConfig::getInstance()->db['type'] == "pgsql")
		{
			global $ANTAPI_STORE_PGSQL_HOST, $ANTAPI_STORE_PGSQL_DBNAME, $ANTAPI_STORE_PGSQL_USER, $ANTAPI_STORE_PGSQL_PASSWORD;

			$ANTAPI_STORE_PGSQL_HOST = AntConfig::getInstance()->db['host'];
			$ANTAPI_STORE_PGSQL_DBNAME = "tmp_ant_uni_test";
			$ANTAPI_STORE_PGSQL_USER = AntConfig::getInstance()->db['user'];
			$ANTAPI_STORE_PGSQL_PASSWORD = AntConfig::getInstance()->db['password'];
		}

		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);

		$this->antServer = $this->ant->getAccBaseUrl(false);
		$this->antUser = $this->user->name;
		$this->antPass = "Password1";
	}
	
	/**
	 * Test the Object for save, update, open and delete
	 *
	 * @group general
	 */
	public function testObject()
	{
		$dbh = $this->dbh;
		
		// Setup some data to work with
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$grps = $obj->getGroupingData("groups");
		$cleanGroup = false;
		if (!count($grps))
		{
			// Create a temp group
			$grps = $obj->addGroupingEntry("groups", "UT API GRP");
			$gid = $grps['id'];
			$gname = $grps['title'];
			$cleanGroup = $gid;
		}
		else
		{
			$gid = $grps[0]['id'];
			$gname = $grps[0]['title'];
		}
		
		// First test open
		// -----------------------------------
		
		// Create local
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("first_name", "Test Customer");
		$obj->setMValue("groups", $gid);
		$obj->setMValue("owner_id", $this->user->id);
		$cid = $obj->save();
		
		// Open remote
		$objApi = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "customer");
		$objApi->open($cid);
		
		// No need to include _fval in getting values, since it was not included
		// And also need to set true when setting values
		//$groupsFval = get_object_vars($objApi->getValue("groups_fval"));
		$groupsFval = $objApi->getValue("groups", true); // This will return the string value of groups id
		$groups = $objApi->getValue("groups");
		
		// No need to include _fval in getting values, since it was not included
		// And also need to set true when setting values
		//$ownerFval = get_object_vars($objApi->getValue("owner_id_fval"));
		$ownerFval = $objApi->getValue("owner_id", true); // This will return the string value of owner id
		$owner = $objApi->getValue("owner_id");
		
		$this->assertEquals($obj->getValue("first_name"), $objApi->getValue("first_name"));
		
		$this->assertTrue(in_array($gid, $groups));
		$this->assertEquals($groupsFval, $gname);
		$this->assertEquals($owner, $this->user->id);
		
		// cleanup
		$obj->removeHard();

		// Test remote create
		// -----------------------------------
		$objApi = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "customer");
		$objApi->setValue("name", "joe Test Api");
		$objApi->setValue("owner_id", $this->user->id);
		$objApi->setMValue("groups", $gid);
		$cid = $objApi->save();
		$this->assertNotEquals($cid, 0);

		$obj = new CAntObject($dbh, "customer", $cid, $this->user);
		$this->assertEquals($obj->getValue("name"), "joe Test Api");
		$this->assertTrue($obj->getMValueExists("groups", $gid));
		$this->assertEquals($obj->getValue("owner_id"), $this->user->id);
		$obj->removeHard();

		// Test remote update
		// -----------------------------------
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "Big Company");
		$obj->setMValue("groups", $gid);
		$obj->setMValue("owner_id", $this->user->id);
		$cid = $obj->save();
		unset($obj);

		$objApi = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "customer");
		$objApi->open($cid);
		$objApi->setValue("name", "Big Company 2");
		$objApi->save();

		$obj = new CAntObject($dbh, "customer", $cid, $this->user);
		$this->assertEquals($obj->getValue("name"), $objApi->getValue("name"));
		$this->assertTrue($obj->getMValueExists("groups", $gid));
		$this->assertEquals($obj->getValue("owner_id"), $this->user->id);

		// Cleanup
		if ($cleanGroup)
			$obj->deleteGroupingEntry("groups", $cleanGroup);
		$obj->removeHard();
	}
}
