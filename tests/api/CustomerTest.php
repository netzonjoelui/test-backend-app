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

class AntApi_CustomerTest extends PHPUnit_Framework_TestCase
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
	 * Test to see if query customer by id works
	 */
	public function testAuthGetCustEmail()
	{
		$dbh = $this->dbh;
		$username = "UnitTest@CustomerName.com";            
		
		// create customer data
		$objCustomer = new CAntObject($dbh, "customer", null, $this->user);
		$objCustomer->setValue("name", "UnitTest CustomerName");
		$objCustomer->setValue("username", "UnitTestUserName");
		$objCustomer->setValue("email2", $username);
		$custid = $objCustomer->save();
		
		$custapi = new AntApi_Customer($this->antServer, $this->antUser, $this->antPass);
		$ret = $custapi->getIdByEmail($username);
		$this->assertEquals($custid, $ret);

		// clear data
		$objCustomer->removeHard();
	}

	/**
	 * Test the automated send email api
	 *
	 * @group testSendEmail
	 */
	public function testSendEmail()
	{
		// create a test customer for merging
        $cust = new CAntObject($this->dbh, "customer", null, $this->user);
        $cust->setValue("first_name", "customer");
        $cust->setValue("last_name", "controller");
		$cust->setValue("email", "sky.stebnicki@aereus.com");
        $custid = $cust->save(false);

		// Now create a test template
		$template= CAntObject::factory($this->dbh, "html_template", null, $this->user);
		$template->setValue("body_plain", "<%custom_var%>"); // Set body to a custom var for merge testing
		$template->setValue("name", "UTest Template");
		$tid = $template->save(false);

		// Get object API
		$custapi = new AntApi_Customer($this->antServer, $this->antUser, $this->antPass);
		$custapi->open($custid);
		$this->assertEquals($custid, $custapi->id);

		// Send email test in testmode (no message actually sent)
		$vars = array(
			"testmode" => 't',
			"custom_var" => "My Merged Value",
		);                      
		$ret = $custapi->sendEmail("Automated Message", null, $tid, $vars);
		$this->assertEquals($ret->body, $vars['custom_var']);
		$this->assertEquals($ret->headers->Subject, "Automated Message");
		$this->assertEquals($ret->recipients->To, $cust->getValue("email"));

		// clear data
		$cust->removeHard();
		$template->removeHard();
	}
}
