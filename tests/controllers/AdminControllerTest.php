<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/Email.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/RpcSvr.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/AdminController.php');


class AdminControllerTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $backend = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);

		$this->api = new AntApi(AntConfig::getInstance()->aereus['server'], 
								  AntConfig::getInstance()->aereus['user'],
								  AntConfig::getInstance()->aereus['password']);
    }

    function tearDown() 
    {
    }

    /*function getTests()
    {        
        return array("testRenewTestCcard");
    }*/

    /**
    * Test ANT Admin - domainAdd($params)
    */
    function testDomainAdd()
    {
        $params['name'] = "UnitTest.aereus.com";
        $params['did'] = "UnitTest.aereus.com";
        
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        $ret = $adminController->domainAdd($params);
        $this->assertEquals($ret, $params['name']);
        
        // clear data
        $ret = $adminController->domainDelete($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Admin - domainDelete($params)
    */
    function testDomainDelete()
    {        
        $params['name'] = "UnitTest.aereus.com";
        $params['did'] = "UnitTest.aereus.com";
        
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        // add unit test first        
        $ret = $adminController->domainAdd($params);
        $this->assertEquals($ret, $params['name']);
        
        // test the delete domain
        $ret = $adminController->domainDelete($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Admin - domainSetDefault($params)
    */
    function testDomainSetDefault()
    {                
        $params['domain'] = "UnitTest.aereus.com";
        $params['name'] = "UnitTest.aereus.com";
        $params['did'] = "UnitTest.aereus.com";
        
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        // add unit test first
        $ret = $adminController->domainAdd($params);
        $this->assertEquals($ret, $params['name']);
        
        // test set default
        $ret = $adminController->domainSetDefault($params);
        $this->assertEquals($ret, $params['name']);
        
        // clear data
        $ret = $adminController->domainDelete($params);
        $this->assertTrue($ret > 0);        
    }
    
    /**
    * Test ANT Admin - saveGeneral($params)
    */
    function testSaveGeneral()
    {
        // Instantiate Admin Controller
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        // Get the existing orgName
        $params['get'] = "general/company_name";
        $orgName = $adminController->getSetting($params);
        
        $params['orgName'] = "UnitTest CompanyName";
        
        $ret = $adminController->saveGeneral($params);
        $this->assertTrue($ret > 0);        
        
        // test the saved account settings
        $params['get'] = "general/company_name";
        $this->assertEquals($adminController->getSetting($params), $params['orgName']);
        
        // Revert back the changes
        $params['orgName'] = $orgName;
        $adminController->saveGeneral($params);
    }
    
    /**
    * Test ANT Admin - saveWizardAccount($params)
    */
    function testSaveWizardAccount()
    {
        // Instantiate Admin Controller
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        // Get the existing orgName
        $params['get'] = "general/company_name";
        $orgName = $adminController->getSetting($params);
        
        $params['company_name'] = "UnitTest CompanyName";
        $params['users'] = array("UnitTestUser|UnitTestPassword");
        
        $ret = $adminController->saveWizardAccount($params);
        $this->assertTrue($ret > 0);        
        
        // test the saved account settings
        $params['get'] = "general/company_name";        
        $this->assertEquals($adminController->getSetting($params), $params['company_name']);
        
        // Revert back the changes
        $params['orgName'] = $orgName;
        $adminController->saveGeneral($params);
        
        // clear data
        $query = "delete from users where name = 'UnitTestUser'";
        $this->dbh->Query($query);
    }
    
    /**
    * Test ANT Admin - saveWizardUser($params)
    */
    function testSaveWizardUser()
    {
        // Create new user to test
        $userObj = new CAntObject($this->dbh, "user", null, $this->user);
        $userObj->setValue("name", "Unit Test User");
        $userId = $userObj->save();
        $this->assertTrue($userId > 0);
        
        $unitTestUser = $this->user = new AntUser($this->dbh, $userId);
        
        $params['email_address'] = "UnitTestUser@aereus.com";
        $params['email_display_name'] = "UnitTest WizardUser";
        $params['email_replyto'] = "UnitTest ReplyTo";
        
        $adminController = new AdminController($this->ant, $unitTestUser);
        $adminController->debug = true;
        
        $ret = $adminController->saveWizardUser($params);
        $this->assertTrue($ret > 0);
        $this->assertNull($ret['error']);
        
        // Clean Data
        $userObj->removeHard();
    }
    
    /**
    * Test ANT Admin - renewTestCcard($params)
    * If error occurs, Change the user to a valid customer with acount and customer number
    * If error still occurs, change the $custid to 1
    */
    function testRenewTestCcard()
    {
        // Create new user to test
        $userObj = new CAntObject($this->dbh, "user", null, $this->user);
        $userObj->setValue("name", "Unit Test User");
        $userId = $userObj->save();
        $this->assertTrue($userId > 0);
        
        $unitTestUser = $this->user = new AntUser($this->dbh, $userId);
        
        $params['ccard_name'] = "UnitTestUser CcTest";
        $params['ccard_number'] = "5555555555554444";
        $params['ccard_exp_month'] = "12";
        $params['ccard_exp_year'] = "2019";
        $params['ccard_type'] = "MasterCard";
        
        $adminController = new AdminController($this->ant, $unitTestUser);
        $adminController->debug = true;
        
        $ret = $adminController->renewTestCcard($params);
        $this->assertTrue(is_array($ret));
        $this->assertFalse(isset($ret['error']));
        
        // Clean Data
        $userObj->removeHard();
    }
    
    /**
    * Test ANT Admin - updateBilling($params)    
    */
    function testUpdateBilling()
    {
		// Store old customer id
		$aereusAccountId = $this->ant->getAereusCustomerId();

        $params['address_street'] = "UnitTestUser Street";
        $params['address_street2'] = "UnitTestUser Street2";
        $params['address_city'] = "UnitTestUser City";
        $params['address_state'] = "UnitTestUser State";
        $params['address_zip'] = "UnitTestUser Zip";
        
        $params['ccard_name'] = "UnitTestUser CcTest";
        $params['ccard_number'] = "5555555555554444";
        $params['ccard_exp_month'] = "12";
        $params['ccard_exp_year'] = "2019";
        $params['ccard_type'] = "MasterCard";
        
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        $ret = $adminController->updateBilling($params);
        $this->assertTrue($ret > 0);
        
        // test the saved account settings        
        $params['get'] = "general/suspended_billing";        
        $this->assertEquals($adminController->getSetting($params), "f");

		// Open the customer to see if the credit card info was updated
		$cust = CAntObject::factory($this->ant->dbh, "customer", $aereusAccountId, $this->user);
		$cards = $cust->getCreditCards();
		$this->assertTrue(count($cards) >= 1);

		// Now test address
		$this->assertEquals($cust->getValue("billing_street"), $params['address_street']);
		$this->assertEquals($cust->getValue("billing_street2"), $params['address_street2']);
		$this->assertEquals($cust->getValue("billing_city"), $params['address_city']);
		$this->assertEquals($cust->getValue("billing_state"), $params['address_state']);
		$this->assertEquals($cust->getValue("billing_zip"), $params['address_zip']);
    }
    
    /**
    * Test ANT Admin - getSetting($params)    
    */
    function testGetSetting()
    {
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        $params['set'] = "general/UnitTestSetting";        
        $params['get'] = "general/UnitTestSetting";        
        $params['val'] = "UnitTestSetting Value";
        
        // set account setting first        
        $adminController->setSetting($params);
        
        // test get setting        
        $this->assertEquals($adminController->getSetting($params), $params['val']);
    }
    
    /**
    * Test ANT Admin - setSetting($params)    
    */
    function testSetSetting()
    {
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        $params['set'] = "general/UnitTestSetting";        
        $params['get'] = "general/UnitTestSetting";        
        $params['val'] = "UnitTestSetting Value";
        
        // set account setting
        $adminController->setSetting($params);
        
        // test get setting
        $this->assertEquals($adminController->getSetting($params), $params['val']);
    }
    
    /**
    * Test ANT Admin - getApplications()
    */
    function testGetApplications()
    {
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        $result = $adminController->getApplications();
        $this->assertTrue(is_array($result));
    }
    
    /**
    * Test ANT Admin - createApplication()
    */
    function testCreateApplication()
    {
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        $params['title'] = "PHP Test Application";
        $result = $adminController->createApplication($params);
        $this->assertTrue($result['id'] > 1);
        
        $params['appId'] = $result['id'];
        $result = $adminController->deleteApplication($params);
        $this->assertEquals($result, 1);
    }
    
    /**
    * Test ANT Admin - getGeneralSetting()
    */    
    
    function testGetGeneralSetting()
    {
        /*$adminController = new AdminController($this->ant, $this->user);
        $result = $adminController->getGeneralSetting();
        $this->assertTrue(is_array($result));*/
        
        $ccard_num = "123456789";
        $maskedCc = "";
        for($x=0; $x<=strlen($ccard_num); $x++)
        {
            if($x >= (strlen($ccard_num) - 4))
                $maskedCc .= substr($ccard_num, $x, 1);
            else
                $maskedCc .= "*";
        }
    }
    
    /**
    * Test ANT Admin - renewAccount($params)    
    */
    /*function testRenewAccount()
    {
        $params['address_street'] = "UnitTestUser Street";
        $params['address_street2'] = "UnitTestUser Street2";
        $params['address_city'] = "UnitTestUser City";
        $params['address_state'] = "UnitTestUser State";
        $params['address_zip'] = "UnitTestUser Zip";
        
        $params['ccard_name'] = "UnitTestUser CcTest";
        $params['ccard_number'] = "5555555555554444";
        $params['ccard_exp_month'] = "12";
        $params['ccard_exp_year'] = "2019";
        $params['ccard_type'] = "MasterCard";
        
        $adminController = new AdminController($this->ant, $this->user);
        $adminController->debug = true;
        
        $ret = $adminController->renewAccount($params);
        $this->assertTrue($ret > 0);
        
        // test the saved account settings        
        $params['get'] = "general/trial_expired";        
        $this->assertEquals($adminController->getSetting($params), "f");
    }*/
}
