<?php
/**
 * Test facebook social interface
 */
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Social.php');

class AntSocial_TwitterTest extends PHPUnit_Framework_TestCase 
{
	var $ant = null;
	var $user = null;
	var $dbh = null;
	var $dbhSys = null;

	public function testDummy()
	{
		$this->assertTrue(true);
	}

	/**
	 * Set the unit test
	public function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, USER_SYSTEM);
		$this->fbUserId = "740162866"; // sky.stebnicki

		// Override session and cookie
		global $_SESSION, $_COOKIE;
		$_SESSION = array();
		$_COOKIE = array();
	}

	/**
	 * Test is authenticated
	 *
	 * @group testIsAuthenticated
	public function testIsAuthenticated()
	{
		// cache & clear existing user id
		$oldId = $this->user->getSetting("accounts/facebook/id");
		$this->user->setSetting("accounts/facebook/id", "");

		// Try to authenticate with no access token
		$fb = new AntSocial_Twitter($this->user);
		$this->assertFalse($fb->isAuthenticated());

		// Now set authentication
		$this->user->setSetting("accounts/facebook/id", $this->fbUserId);
		$fb = new AntSocial_Twitter($this->user);
		$this->assertTrue($fb->isAuthenticated());

		// Cleanup
		$this->user->setSetting("accounts/facebook/id", $oldId);
	}

	/**
	 * Test is authenticated
	 *
	 * @group testGetProfile
	public function testGetProfile()
	{
		// cache existing user id
		$oldId = $this->user->getSetting("accounts/facebook/id");

		// Now set authentication
		$this->user->setSetting("accounts/facebook/id", $this->fbUserId);
		$fb = new AntSocial_Twitter($this->user);
		$prof = $fb->getProfile();
		$this->assertEquals($prof->id, $this->fbUserId);

		// Cleanup
		$this->user->setSetting("accounts/facebook/id", $oldId);
	}

	/**
	 * Test is authenticated
	 *
	 * @group testGetFriends
	public function testGetFriends()
	{
		// cache existing user id
		$oldId = $this->user->getSetting("accounts/facebook/id");

		// Now set authentication
		$this->user->setSetting("accounts/facebook/id", $this->fbUserId);
		$fb = new AntSocial_Twitter($this->user);
		$friends = $fb->getFriends();
		$this->assertTrue(is_array($friends));

		// Cleanup
		$this->user->setSetting("accounts/facebook/id", $oldId);
	}
	 */
}
