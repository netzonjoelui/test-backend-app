<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../controllers/CustomerController.php');

class CustomerControllerTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $backend = null;

	/**
	 * Handle to the customer controller created on setup
	 *
	 * @var CustomerController
	 */
	public $controller = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
        
        // Initialize controller
        $this->controller = new CustomerController($this->ant, $this->user);
        $this->controller->debug = true;
    }

    function tearDown() 
    {
    }

    /*function getTests()
    {
        return array("testCustGetName");
    }*/

    /**
     * Test ANT Customer - custGetName($params)
     */
    public function testCustGetName()
    {
        $params['first_name'] = "TestUnit CustomerName";

        // create customer data        
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $custid = $obj->save();
        $this->assertTrue($custid > 0);

        // test customer get name
        $params['custid'] = $custid;
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->custGetName($params);
        $this->assertTrue(count($ret) > 0);
        $this->assertEquals($params['first_name'], $ret);

        // clear data
        $obj->removeHard();
    }

    /**
    * Test ANT Customer - custLeadGetName($params)
    */
    function testCustLeadGetName()
    {
        $params['first_name'] = "TestUnit LeadFirstName";
        $params['last_name'] = "TestUnit LeadLastName";

        // add customer lead data
        $obj = new CAntObject($this->dbh, "lead", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $obj->setValue("last_name", $params['last_name']);
        $leadid = $obj->save();
        $this->assertTrue($leadid > 0);

        // test customer lead get name
        $params['lead_id'] = $leadid;
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->custLeadGetName($params);
        $this->assertTrue(count($ret) > 0);
        $this->assertEquals($params['first_name'] . " " . $params['last_name'], $ret);

        // clear data
        $obj->removeHard();
    }

    /**
    * Test ANT Customer - custLeadConvert($params)
    */
    function testCustLeadConvert()
    {
        $params['first_name'] = "TestUnit LeadFirstName";
        $params['last_name'] = "TestUnit LeadLastName";


        // add customer lead data
        $obj = new CAntObject($this->dbh, "lead", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $obj->setValue("last_name", $params['last_name']);
        $leadid = $obj->save();
        $this->assertTrue($leadid > 0);

        // test customer lead convert
        $params['f_createopp'] = "t";
        $params['lead_id'] = $leadid;
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->custLeadConvert($params);
        $this->assertTrue($ret > 0);

        // clear data
        $obj->removeHard();
    }

    /**
    * Test ANT Customer - custOppGetName($params)
    */
    function testCustOppGetName()
    {
        $params['name'] = "UnitTest OpportunityName";

        // create opportunity data
        $obj = new CAntObject($this->dbh, "opportunity", null, $this->user);
        $obj->setValue("name", $params['name']);        
        $oppid = $obj->save();
        $this->assertTrue($oppid > 0);

        // test custOppGetName
        $params['opportunity_id'] = $oppid;
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->custOppGetName($params);
        $this->assertTrue(count($ret) > 0);
        $this->assertEquals($ret, $params['name']);

        // clear data
        $obj->removeHard();
    }

    /**
    * Test ANT Customer - getActivityTypes()
    */
    function testGetActivityTypes()
    {
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        $ret = $customerController->getActivityTypes();
        $this->assertTrue(is_array($ret));
    }
    
    /**
    * Test ANT Customer - custGetZipData($params)
    */
    function testCustGetZipData()
    {
        $params['zipcode'] = 99727;
        
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->custGetZipData($params);
        $this->assertTrue(is_array($ret));
        /*$this->assertEquals($ret[0]["state"], "AK");
        $this->assertEquals($ret[0]["city"], "Buckland");*/
    }
    
    /**
    * Test ANT Customer - savePublish($params)
    */
    function testSavePublish()
    {
        // create customer data
        $params['first_name'] = "TestUnit CustomerName";

        // create customer data        
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $custid = $obj->save();
        $this->assertTrue($custid > 0);
        
        // test save publish (insert mode)
        $params['username'] = "UnitTest PublishUsername";
        $params['password'] = "UnitTest PublishPassword";
        $params['f_files_view'] = "f";
        $params['f_files_upload'] = "f";
        $params['f_files_modify'] = "f";
        $params['customer_id'] = $custid;
        
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->savePublish($params);
        $this->assertTrue($ret > 0);
        
        // Restest save pulish (update mode)
        $ret = $customerController->savePublish($params);
        $this->assertTrue($ret > 0);
        
        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT Customer - getPublish($params)
    */
    function testGetPublish()
    {
        // create customer data
        $params['first_name'] = "TestUnit CustomerName";

        // create customer data        
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $custid = $obj->save();
        $this->assertTrue($custid > 0);
        
        // create publish data
        $params['username'] = "UnitTest PublishUsername";
        $params['password'] = "UnitTest PublishPassword";
        $params['f_files_view'] = "f";
        $params['f_files_upload'] = "f";
        $params['f_files_modify'] = "f";
        $params['customer_id'] = $custid;
        
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->savePublish($params);
        $this->assertTrue($ret > 0);
        
        // test get publish
        $ret = $customerController->getPublish($params);
        $this->assertTrue(is_array($ret));
        $this->assertEquals($ret['username'], rawurlencode($params['username']));
        $this->assertEquals($ret['f_files_view'], false);
        $this->assertEquals($ret['f_files_upload'], false);
        $this->assertEquals($ret['f_files_modify'], false);
        
        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT Customer - saveRelationships($params)
    */
    function testSaveRelationships()
    {
        // create customer data
        $params['first_name'] = "TestUnit CustomerName";
        
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $custid = $obj->save();
        $this->assertTrue($custid > 0);
        
        // create parente data
        $params['first_name'] = "TestUnit ParentName";
        
        $objParent = new CAntObject($this->dbh, "customer", null, $this->user);
        $objParent->setValue("first_name", $params['first_name']);
        $pid = $objParent->save();
        $this->assertTrue($custid > 0);
        
        // save parent in paramenter array
        $params['relationships'][0] = $pid;
        $params['r_type_name_' . $pid] = "Family";
        $params['r_type_id_' . $pid] = 3;
        
        // test the save relationships
        $params['customer_id'] = $custid;
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->saveRelationships($params);
        $this->assertTrue($ret > 0);
        
        // clear data
        $obj->removeHard();
        $objParent->removeHard();
    }
    
    /**
    * Test ANT Customer - getRelationshipTypes($params)
    */
    function testGetRelationshipTypes()
    {
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->getRelationshipTypes();
        $this->assertTrue(is_array($ret));
        $this->assertTrue(sizeof($ret) > 0);
        $this->assertTrue($ret[0]['id'] > 0);
        $this->assertTrue(count($ret[0]['name']) > 0);
    }
    
    /**
    * Test ANT Customer - saveRelationshipType($params)
    */
    function testSaveRelationshipType()
    {
        $params['name'] = "UnitTest RelationTypeName";
        
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $rtid = $customerController->saveRelationshipType($params);        
        $this->assertTrue($rtid > 0);
        
        // retest save relationship type (update mode)        
        $params['id'] = $rtid;
        $result = $customerController->saveRelationshipType($params);
        $this->assertTrue($result > 0);
        $this->assertEquals($result, $rtid);
        
        // Clear Data
        unset($result);
        $result = $customerController->removeRelationshipType($params);        
        $this->assertEquals($result, $rtid);
    }
    
    /**
    * Test ANT Customer - getRelationships($params)
    */
    function testGetRelationships()
    {
        // create customer data
        $params['first_name'] = "TestUnit CustomerName";
        
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $custid = $obj->save();
        $this->assertTrue($custid > 0);
        
        // create parente data
        $params['first_name'] = "TestUnit ParentName";
        
        $objParent = new CAntObject($this->dbh, "customer", null, $this->user);
        $objParent->setValue("first_name", $params['first_name']);
        $pid = $objParent->save();
        $this->assertTrue($custid > 0);
        
        // save parent in paramenter array
        $params['relationships'][0] = $pid;
        $params['r_type_name_' . $pid] = "Family";
        $params['r_type_id_' . $pid] = 3;
        
        // instantiate customer controller
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        // create the relationships data
        $params['customer_id'] = $custid;        
        $ret = $customerController->saveRelationships($params);
        $this->assertTrue($ret > 0);
        
        // test get ralationships        
        $params['customer_id'] = $custid;
        $ret = $customerController->getRelationships($params);
        $this->assertEquals($ret[0]['cid'], $pid);
        $this->assertEquals($ret[0]['rname'], "Family");
        
        // clear data
        $obj->removeHard();
        $objParent->removeHard();
    }
    
    /**
    * Test ANT Customer - createCustomer($params)
    */
    function testCreateCustomer()
    {
        $params['name'] = "UnitTest CustomerName";        
        $params['type_id'] = CUST_TYPE_CONTACT;
        
        $customerController = new CustomerController($this->ant, $this->user);        
        $customerController->debug = true;
        
        $cid = $customerController->createCustomer($params);
        $this->assertTrue($cid > 0);
        
        // clear data
        $obj = new CAntObject($this->dbh, "customer", $cid, $this->user);
        $obj->removeHard();
    }
    
    /**
    * Test ANT Customer - syncCustomers($params)
    */
    function testSyncCustomers()
    {
        // TO DO
    }
    
    /**
    * Test ANT Customer - activitySave($params)
    */
    function testActivitySave()
    {
        // TO DO
    }
    
    /**
    * Test ANT Customer - groupAdd($params)
    */    
    function testGroupAdd()
    {
        // instantiate controllers
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
                        
        // test group add        
        $params['name'] = "UnitTest CustomerGroup";
        $params['color'] = "eeeeee";
        $gid = $customerController->groupAdd($params);
        $this->assertTrue(count($gid ) > 0);
        
        // clean data        
        $params['gid'] = $gid;
        $ret = $customerController->groupDelete($params);
    }
    
    /**
    * Test ANT Customer - groupRename($params)
    */
    function testGroupRename()
    {
        // instantiate controllers
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
                
        $params['name'] = "UnitTest CustomerGroup";
        $params['color'] = "eeeeee";        
        // add group data first        
        $gid = $customerController->groupAdd($params);
        $this->assertTrue(count($gid ) > 0);
        
        $params['gid'] = $gid;        
        // test group rename
        $gname = $customerController->groupRename($params);
        $this->assertEquals($gname, $params['name']);
        
        // clean data                
        $ret = $customerController->groupDelete($params);
    }
    
    /**
    * Test ANT customer - groupDelete($params)
    */
    function testGroupDelete()
    {
        // instantiate controllers
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
                
        // add group data first
        $params['name'] = "UnitTest PersonalGroup";
        $params['color'] = "eeeeee";
        $gid = $customerController->groupAdd($params);
        $this->assertTrue(count($gid ) > 0);
        
        // test group delete
        $params['gid'] = $gid;
        $ret = $customerController->groupDelete($params);
    }
    
    /**
    * Test ANT customer - setLeadConverted($params)
    */
    function testSetLeadConverted()
    {
        // add customer lead data
        $params['first_name'] = "TestUnit LeadFirstName";
        $params['last_name'] = "TestUnit LeadLastName";
        
        $obj = new CAntObject($this->dbh, "lead", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $obj->setValue("last_name", $params['last_name']);
        $leadid = $obj->save();
        $this->assertTrue($leadid > 0);
                
        // test set lead converted
        unset($params);
        $params['id'] = $leadid;
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->setLeadConverted($params);
        $this->assertTrue($ret > 0);
        
        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT customer - setLeadClosed($params)
    */
    function testSetLeadClosed()
    {
         // add customer lead data
        $params['first_name'] = "TestUnit LeadFirstName";
        $params['last_name'] = "TestUnit LeadLastName";
        
        $obj = new CAntObject($this->dbh, "lead", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $obj->setValue("last_name", $params['last_name']);
        $leadid = $obj->save();
        $this->assertTrue($leadid > 0);
                
        // test set lead converted
        unset($params);
        $params['id'] = $leadid;
        $params['f_closed'] = "t";
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->setLeadClosed($params);
        $this->assertTrue($ret > 0);
        
        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT customer - setOppConverted($params)
    */
    function testSetOppConverted()
    {
        $params['name'] = "UnitTest OpportunityName";

        // create opportunity data
        $obj = new CAntObject($this->dbh, "opportunity", null, $this->user);
        $obj->setValue("name", $params['name']);        
        $oppid = $obj->save();
        $this->assertTrue($oppid > 0);

        // test set opportunity Converted
        $params['id'] = $oppid;
        $params['f_won'] = "t";
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->setOppConverted($params);
        $this->assertTrue($ret > 0);

        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT customer - setOppClosed($params)
    */
    function testSetOppClosed()
    {
        $params['name'] = "UnitTest OpportunityName";

        // create opportunity data
        $obj = new CAntObject($this->dbh, "opportunity", null, $this->user);
        $obj->setValue("name", $params['name']);        
        $oppid = $obj->save();
        $this->assertTrue($oppid > 0);

        // test set opportunity Converted
        $params['id'] = $oppid;
        $params['f_closed'] = "t";
        $customerController = new CustomerController($this->ant, $this->user);
        $customerController->debug = true;
        
        $ret = $customerController->setOppClosed($params);
        $this->assertTrue($ret > 0);

        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT customer - billingSaveCcard($params)
    */
    function testBillingSaveCcard()
    {
        $params['first_name'] = "UnitTestCustomer";
        // create customer data
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $cid = $obj->save();
        $this->assertTrue($cid > 0);
        
        // test billing save credit card
        $customerController = new CustomerController($this->ant, $this->user);        
        $customerController->debug = true;
        
        $params['customer_id'] = $cid;
        $params['ccard_name'] = "UnitTest CcName";
        $params['ccard_type'] = "visa";
        $params['ccard_number'] = "1234123412341235";
        $params['ccard_exp_month'] = 01;
        $params['ccard_exp_year'] = 2012;        
        $ccid = $customerController->billingSaveCcard($params);
        $this->assertTrue($ccid > 0);
        
        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT customer - billingGetCcards($params)
    */
    function testBillingGetCcards()
    {
        $params['first_name'] = "UnitTestCustomer";
        
        // create customer data        
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $custid = $obj->save();
        $this->assertTrue($custid > 0);
        
        // create credit card data
        $customerController = new CustomerController($this->ant, $this->user);        
        $customerController->debug = true;
        
        $params['customer_id'] = $custid;
        $params['ccard_name'] = "UnitTest CcName";
        $params['ccard_type'] = "visa";
        $params['ccard_number'] = "1234123412341235";
        $params['ccard_exp_month'] = 01;
        $params['ccard_exp_year'] = 2012;        
        $ccid = $customerController->billingSaveCcard($params);
        $this->assertTrue($ccid > 0);
        
        // test billing tet credit cards
        unset($params);
        $params['customer_id'] = $custid;
        $ret = $customerController->billingGetCcards($params);
        $this->assertTrue(is_array($ret));
        $this->assertEquals($ret[0]['id'], $ccid);
        $this->assertEquals($ret[0]['type'], "visa");
        
        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT customer - accountHistory()
    */
    function testAccountHistory()
    {
        // create credit card data
        $customerController = new CustomerController($this->ant, $this->user);        
        $customerController->debug = true;
        
        $result = $customerController->accountHistory();
        $this->assertTrue(is_array($result));
    }

    /**
     * Test sending email to a customer
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
        
		// Send email test in testmode (no message actually sent)
		$params = array(
			"testmode" => 't',
			"customer_id" => $custid,
			"template_id" => $tid,
			"subject" => "Automated Message",
			"custom_var" => "My Merged Value",
		);
        $ret = $this->controller->sendEmail($params);
		$this->assertEquals($ret['body'], $params['custom_var']);
		$this->assertEquals($ret['headers']['Subject'], $params['subject']);
		$this->assertEquals($ret['recipients']['To'], $cust->getValue("email"));

        // Cleanup
        $cust->removeHard();
        $template->removeHard();
    }
}
