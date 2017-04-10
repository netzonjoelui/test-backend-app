<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once('lib/CDatabase.awp');
require_once('lib/Ant.php');
require_once('lib/AntUser.php');
require_once('lib/CAntObject.php');
require_once('lib/CAntObjectList.php');

class AntObjectList_PluginTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator
	}

	/**
	 * Test to make sure the plugin is called
	 *
	 * The object list dynamically loads plugins based on the object type.
	 * The purpose of this test is to make sure it loads the correct plugin.
	 */
	public function testPluginLoad()
	{
		// Call email message  because there is a message plugin
		$objList = new CAntObjectList($this->dbh, "email_message", $this->user);
		$objList->debug = true;
		$objList->getObjects(0, 1);	

		// Check to make sure the plugin has been dynamically loaded from the plugin directory
		$this->assertTrue(class_exists("AntObjectList_Plugin_EmailMessage", false));
		$this->assertEquals("AntObjectList_Plugin_EmailMessage", get_class($objList->plugin));
	}
}
