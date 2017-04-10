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

class WorkersTest extends PHPUnit_Framework_TestCase
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
	
	function tearDown() 
	{
	}

	/**
	 * Test process for printing labels
	 */
	function testCustPrintPdfLabels() 
	{
		$dbh = $this->dbh;

		// Make sure this folder exists
		$antfs = new AntFs($dbh, $this->user);
		$fldr = $antfs->openFolder("/System/temp", true);

		// Create test customer
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "WorkerpoolTest");
		$obj->setValue("street", "123 Street");
		$obj->setValue("city", "Springfield");
		$obj->setValue("state", "Oregon");
		$obj->setValue("zip", "97477");
		$oid = $obj->save();
		
		$result = $dbh->Query("select id from printing_papers_labels limit 1");
		if (!$dbh->GetNumberRows($result))
			$this->assertTrue(false);

		$paper = $dbh->GetValue($result, 0, "id");

		// Print newly created customer
		$data = array(
			"paper" => $paper,
			// Set conditions to only print the above created customer
			"conditions" => array(1),
			"condition_blogic_1" => "and",
			"condition_fieldname_1" => "id",
			"condition_operator_1" => "is_equal",
			"condition_condvalue_1" => $oid
		);

		$wp = new WorkerMan($dbh);
		$ret = $wp->run("customers/pdf/mailinglabels", serialize($data));

		$this->assertNotEquals($ret, false);

		// Function will return a file id, delete the file
		if (is_numeric($ret))
		{
			$antfs = new AntFs($dbh, $this->user);
			$antfs->removeFileById($ret, $dbh); // Hard delete (2nd parram) to purge data
		}
		
		// Cleanup
		$obj->removeHard();
	}

	/**
	 * Test object save workers
	 */
	function testLibObjectSave() 
	{
		$dbh = $this->dbh;

		// Create test customer
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "WorkerpoolTest");
		$obj->setValue("street", "123 Street");
		$obj->setValue("city", "Springfield");
		$obj->setValue("state", "Oregon");
		$obj->setValue("zip", "97477");
		$oid = $obj->save();
		unset($obj);

		// Print newly created customer
		$data = array(
			"oid" => $oid, 
			"obj_type" => "customer", 
			"vals" => array("name"=>"WorkerPoolTestModified")
		);

		$wp = new WorkerMan($dbh);
		$ret = $wp->run("lib/object/save", serialize($data));

		$this->assertNotEquals($ret, false);

		// Test to see if data was modified
		$obj = new CAntObject($dbh, "customer", $oid, $this->user);
		$this->assertEquals($obj->getValue("name"), "WorkerPoolTestModified");

		// Cleanup
		$obj->removeHard();
	}

	/**
	 * Test import
	 */
	function testImport() 
	{
		// Print newly created customer
		$data = array(
			"obj_type" => "customer", 
			"data_file" => dirname(__FILE__).'/../data/imp_cust.csv',
			"map_fields"=>array("0"=>"first_name", "1"=>"last_name", "2"=>"nick_name"),
		);

		$wp = new WorkerMan($this->dbh);
		$ret = $wp->run("lib/object/import", serialize($data));

		$this->assertEquals($ret, 2); // should return the number imported

		// Cleanup
		$olist = new CAntObjectList($this->dbh, "customer", $this->user);
		$olist->addCondition("and", "first_name", "begins_with", "UTIMP_");
		$olist->getObjects();
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);
			$obj->removeHard();
		}
	}

	/**
	 * Test email send
	 */
	function testEmailSend() 
	{
		$dbh = $this->dbh;

		// Add a temp file attachment
		$antfs = new AntFs($this->dbh, $this->user);
		$file = $antfs->createTempFile();
		$fid = $file->id;
		$size = $file->write("test contents");
		$this->assertNotEquals($size, -1);

		// $params['uploaded_file']
	
		// Print newly created customer
		$data = array(
			"user_id" => $this->user->id, 
			"cmp_to" => "sky@stebnicki.net", 
			"cmp_frm" => "sky.stebnicki@aereus.com", 
			"cmp_subject" => "My Test Message", 
			"cmpbody" => "This is my test message",
			"uploaded_file" => array($fid),
			"TESTING" => true, 
		);

		$wp = new WorkerMan($dbh);
		$ret = $wp->run("email/send", serialize($data));

		$this->assertNotEquals($ret, false);

		// Cleanup
		$file->removeHard();
	}

	/**
	 * Test file_upload
	 */
	function testFileUploadAns() 
	{
		global $ALIB_ANS_SERVER;

		// Only test if we have an ANS connection to work with
		if (!$ALIB_ANS_SERVER)
			return;

		$dbh = $this->dbh;

		// Create temp file
		$antfs = new AntFs($this->dbh, $this->user);
		$fldr = $antfs->openFolder("/test/mytest", true);
		$this->assertNotNull($fldr);

		$file = $fldr->openFile("test", true);
		$this->assertNotNull($file);

		$size = $file->write("test contents", false); // Second param keeps this from being uploaded
		$this->assertNotEquals($size, -1);

		$fid = $file->id;
		$this->assertTrue($fid > 0);
		
		$data = array(
			"full_local_path" => $file->getFullLocalPath(), 
			"fid" => $file->id, 
			"name" => $file->getValue("name"), 
			"revision" => $file->revision,
		);

		$wp = new WorkerMan($dbh);
		$ret = $wp->run("antfs/file_upload_ans", serialize($data));

		unset($file);

		$file = $antfs->openFileById($fid);
		$this->assertTrue(strlen($file->getValue("dat_ans_key")) > 0);

		// Cleanup
		$file->removeHard();
	}

	/**
	 * Test account creation
	 */
	public function testCreateAccount()
	{
		// Cleanup
		$antsys = new AntSystem();
		$antsys->deleteAccount("testcorp");

		$data = array();
		$data['temp_company'] = "";
		$data['account_id'] = "";
		$data['template_database'] = "ant_template";
		$data['reseller'] = false;
		$data['first_name'] = "Test";
		$data['last_name'] = "Test";
		$data['email'] = "sky.stebnicki@guaranty.com";
		$data['phone'] = "541-541-5411";
		$data['zip'] = "97477";
		$data['company'] = "Test Corp";
		$data['username'] = "test.user";
		$data['password'] = "password";
		$data['account_name'] = "testcorp";
		$data['num_users'] = "100";
		$data['promotion_code'] = "";
		$data['ant_cust_svr'] = AntConfig::getInstance()->localhost;
		$data['testing'] = true; // surpress emails

		$wp = new WorkerMan($this->dbh);
		$ret = $wp->run("antsystem/create_account", serialize($data));
		$this->assertNotEquals($ret, false);

		// Test new account - we don't need to test schema or anything because that is done in antsystem test
		$ant = new Ant($ret['id']);

		$custid = $ant->getAereusCustomerId();
		$this->assertNotNull($custid);

		// Cleanup
		$cust = new CAntObject($this->dbh, "customer", $custid, $this->user);
		$cust->removeHard();
		$antsys->deleteAccount($data['account_name']);
	}
}
