<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');    
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');    
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');        

class AntApi_AuthenticateUserTest extends PHPUnit_Framework_TestCase
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

	/**
	 * Initialize class properties
	 */
	public function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);

		$this->antServer = $this->ant->getAccBaseUrl(false);
		$this->antUser = $this->user->name;
		$this->antPass = "Password1";
	}

	/**
	 * Test authentication
	 */
	public function testAuth()
	{
		// Authenticate the admin user
		$auth = new AntApi_AuthenticateUser($this->antServer, $this->antUser, $this->antPass, false);
		$ret = $auth->authenticate($this->antUser, $this->antPass); // These would normally not be use same user as the api user
		$this->assertNotEquals($ret, false);
	}
}
