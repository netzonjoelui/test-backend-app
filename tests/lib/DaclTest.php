<?php
/**
 * This has been depricated in favor of the new Dacl class
 */
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Dacl.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');

class DaclTest extends PHPUnit_Framework_TestCase 
{
	var $user = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM); // -1 = administrator
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}

	public function preTestLoad()
	{
		$dacl = array(
			'inheritFrom' => null,
			'permissions' => array(
				"View", "Edit", "Delete"
			),
			'entries' => array(
				array('user'=>USER_SYSTEM, 'permission'=>"View"),
				array('group'=>GROUP_ADMINISTRATORS, 'permission'=>"delete"),
			),
			'children' => array(
				// put subtypes here lick 'case' with it's own dacl
			),
		);

		$dacl = DaclLoader::getInstance($this->dbh)->fromData($dacl);
		$dacl = DaclLoader::getInstance($this->dbh)->fromName("/objects/customer");
	}

	/**
	 * Test basic access controll
	 */
	public function testCheckAccess() 
	{
		$dbh = $this->dbh;
		$anonymous  = new AntUser($dbh, USER_ANONYMOUS); // should only be a member of the everyone group
		//$this->dacl->setInheritFrom($parent_folder->dacl->id);

		// Make sure user can be denied access
		$dacl = new Dacl($dbh); // Create new DACL with no permissions set
		$this->assertFalse($dacl->checkAccess($this->user, "View", false, true)); // last param will ignore admin group

		// Now test based on group
		$dacl->grantGroupAccess(GROUP_ADMINISTRATORS, "View");
		$this->assertTrue($dacl->checkAccess($this->user, "View")); // should pass with administrators group memebership

		// Grant user access
		$dacl->grantUserAccess($this->user->id, "View");
		$this->assertTrue($dacl->checkAccess($this->user, "View", false, true)); // last param will ignore admin group

		// Revoke user access ant test for just user
		$dacl->revokeGroupAccess(GROUP_ADMINISTRATORS, "View");
		$this->assertFalse($dacl->checkGroupPermission(GROUP_ADMINISTRATORS, "View"));

		// Revoke user access ant test for just user
		$dacl->revokeUserAccess($this->user->id, "View");
		$this->assertFalse($dacl->checkUserPermission($this->user->id, "View"));
	}

	/**
	 * Test saving and opening a DACL
	 *
	 * @group testSaveOpen
	 */
	public function testSaveOpen() 
	{
		$dbh = $this->dbh;
		$name = "/tests/saveopen";
		$permission = "testSaveOpen";
		
		$dacl = new Dacl($dbh);
		$dacl->grantGroupAccess(GROUP_ADMINISTRATORS, $permission);
		$dacl->grantUserAccess($this->user->id, $permission);
		$id = $dacl->save($name);
		$this->assertNotEquals($dacl->id, NULL);
		unset($dacl);

		// Open again by name and test if entries were preserved
		$dacl = new Dacl($dbh);
		$dacl->debug = true;
		$dacl->loadByName($name);
		$this->assertTrue($dacl->checkUserPermission($this->user->id, $permission));
		$this->assertTrue($dacl->checkGroupPermission(GROUP_ADMINISTRATORS, $permission));
		$this->assertTrue($dacl->checkAccess($this->user, $permission, false, true));
		$id = $dacl->save(); // Now save updates

		// Open one last time to make sure updates preserved
		$dacl = new Dacl($dbh, $name);
		$this->assertTrue($dacl->checkUserPermission($this->user->id, $permission));
		$this->assertTrue($dacl->checkGroupPermission(GROUP_ADMINISTRATORS, $permission));
		$this->assertTrue($dacl->checkAccess($this->user, $permission, false, true));

		// Clean up
		$dacl->remove();
	}

	/**
	 * Test loading dacl from array
	 */
	public function testDataLoad()
	{
		$name ="/tests/testDataLoad";

		$dacl = new Dacl($this->dbh);
		$dacl->grantGroupAccess(GROUP_ADMINISTRATORS, "View");
		$dacl->grantUserAccess($this->user->id, "View");
		$id = $dacl->save($name);
		$this->assertNotEquals($dacl->id, NULL);
		$daclData = $dacl->stringifyJson();
		unset($dacl);

		// Load the dacl from the json data
		$dacl = new Dacl($this->dbh);
		$dacl->loadByData(json_decode($daclData, true));
		$this->assertTrue($dacl->checkUserPermission($this->user->id, "View"));
		$this->assertTrue($dacl->checkGroupPermission(GROUP_ADMINISTRATORS, "View"));
		$this->assertTrue($dacl->checkAccess($this->user, "View", false, true));

		// Cleanup
		$dacl->remove();
	}

	/**
	 * Test the 'exists' dacl function
	 */
	public function testExists() 
	{
		$dbh = $this->dbh;
		$anonymous  = new AntUser($dbh, USER_ANONYMOUS); // should only be a member of the everyone group
		$name = "long_unit_test_name_that_no_exist";

		// Should not exist
		$this->assertFalse(Dacl::exists($name, $dbh));

		// Check if the results were cached
		$cache = CCache::getInstance();
		$cacheResult = $cache->get($dbh->dbname."/security/dacl/$name");
		
		if(is_numeric($cacheResult))
			$this->assertEquals($cacheResult, -1);
		$cache->remove($dbh->dbname."/security/dacl/$name"); // cleanup

		// Now check for an existing DACL
		$dacl = new Dacl($dbh, $name, true, array("View", "Edit")); // Create new Dacl with View and Edit permissions
		$dacl->save();
		$this->assertTrue(Dacl::exists($name, $dbh));

		// Cleanup
		$dacl->remove();
	}

	/**
	 * Test deleting DACL
	 *
	 * Dacls are hierarchical so if the root is deleted (denoted by path) then the children should also be deleted 
	 */
	public function testDelete() 
	{
		$dbh = $this->dbh;

		// Make sure user can be denied access
		$dacl = new Dacl($dbh, "/tests/accesstest", true, array("View", "Edit")); // check if exists but do not create
		$id = $dacl->save();
		$dacl1 = new Dacl($dbh, "/tests/accesstest/1", true, array("View", "Edit")); // check if exists but do not create
		$id1 = $dacl1->save();
		$dacl2 = new Dacl($dbh, "/tests/accesstest/2", true, array("View", "Edit")); // check if exists but do not create
		$id2 = $dacl2->save();
		$dacl->remove();

		// Check for the deletion of the root
		if (!$dbh->GetNumberRows($dbh->Query("select id from security_dacl where id='$id'")))
			$this->assertFalse(false);
		else
			$this->assertFalse(true);

		// Check for the deletion of child 1
		if (!$dbh->GetNumberRows($dbh->Query("select id from security_dacl where id='$id1'")))
			$this->assertFalse(false);
		else
			$this->assertFalse(true);

		// Check for the deletion of child 2
		if (!$dbh->GetNumberRows($dbh->Query("select id from security_dacl where id='$id2'")))
			$this->assertFalse(false);
		else
			$this->assertFalse(true);
	}

	/**
	 * Test inheritance model for objects
	 *
	 * Test security inheritance from associated objects. Will
	 * test using cases and projects because case is set to inherit
	 * dacl from project_id:object field
	 */
	public function testDaclInheritance()
	{
		$dbh = $this->dbh;

		// Setup parent
		$parentDacl = new Dacl($dbh);
		$parentDacl->grantGroupAccess(GROUP_ADMINISTRATORS, "View");
		$parentDacl->grantUserAccess($this->user->id, "View");
		$parentId = $parentDacl->save("/tests/parent");
		$this->assertTrue(is_numeric($parentId));

		// Setup child
		$childDacl = new Dacl($dbh);
		$childDacl->setInheritFrom($parentId);
		$childId = $childDacl->save("/tests/child");
		$this->assertTrue(is_numeric($childId));

		// See if parent permissions were inherited by child immediately
		$this->assertTrue($childDacl->checkUserPermission($this->user->id, "View"));
		$this->assertTrue($childDacl->checkGroupPermission(GROUP_ADMINISTRATORS, "View"));
		$this->assertTrue($childDacl->checkAccess($this->user, "View", false, true));

		// Now close, repoen and see if they were inherited
		unset($childDacl);
		$childDacl = new Dacl($dbh, "/tests/child");
		$this->assertTrue($childDacl->checkUserPermission($this->user->id, "View"));
		$this->assertTrue($childDacl->checkGroupPermission(GROUP_ADMINISTRATORS, "View"));
		$this->assertTrue($childDacl->checkAccess($this->user, "View", false, true));

		// Now close both, update the parent, and test the child
		unset($parentDacl);
		$parentDacl = new Dacl($dbh, "/tests/parent");
		$parentDacl->revokeGroupAccess(GROUP_ADMINISTRATORS, "View");
		$parentDacl->save();

		unset($childDacl);
		$childDacl = new Dacl($dbh, "/tests/child");
		$this->assertTrue($childDacl->checkUserPermission($this->user->id, "View"));
		$this->assertFalse($childDacl->checkGroupPermission(GROUP_ADMINISTRATORS, "View"));

		// Cleanup
		$parentDacl->remove();
		$childDacl->remove();
	}

	/**
	 * Test saving a dacl with a duplicate name
	 *
	 * @group testSaveDuplicate
	 */
	public function testSaveDuplicate() 
	{
		$dbh = $this->dbh;
		$name = "/tests/testSaveDuplicate";
		
		// First Save
		$dacl = new Dacl($dbh);
		$dacl->grantGroupAccess(GROUP_ADMINISTRATORS);
		$id = $dacl->save($name);
		$this->assertNotEquals($dacl->id, NULL);
		unset($dacl);      

		// Save again with the sanme name
		$dacl = new Dacl($dbh);
		$dacl->grantGroupAccess(GROUP_ADMINISTRATORS);
		$secondid = $dacl->save($name);
		$this->assertEquals($id, $secondid);

		// Cleanup
		$dacl->remove();
	}

	/*
	function testDaclInheritance()
	{
		global $OBJECT_FIELD_ACLS;

		$dbh = $this->dbh;

		$daclProject = new Dacl($dbh, "/objects/project", true, $OBJECT_FIELD_ACLS);
		$daclProject->save();
		$daclCase = new Dacl($dbh, "/objects/case", true, $OBJECT_FIELD_ACLS);
		$daclCase->save();

		// Create test project
		$proj = new CAntObject($dbh, "project", null, $this->user);
		$proj->setValue("name", "Test");
		$pid = $proj->save(false);
		$daclProjectCase = new Dacl($dbh, "/objects/project/$pid/case", true, $OBJECT_FIELD_ACLS);
		$daclProjectCase->save();

		// Test case dacl
		$case = new CAntObject($dbh, "case", null, $this->user);
		$this->assertEquals($case->dacl->id, $daclCase->id); // Default
		$case->setValue("project_id", $pid);
		// Now dacl should be $daclProjectCase 
		$this->assertEquals($case->dacl->id, $daclProjectCase->id);
		$case->save(false);
		// After saving dacl should be inheriting but unique $daclProjectCase 
		$this->assertNotEquals($case->dacl->id, $daclProjectCase->id);
		$this->assertEquals($case->dacl->inherit_from, $daclProjectCase->id);

		// Clean-up
		$proj->remove();
		$proj->remove();
		$case->remove();
		$case->remove();
	}
	 */
}
