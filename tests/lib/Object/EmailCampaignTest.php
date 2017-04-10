<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Invoice.php');
require_once(dirname(__FILE__).'/../../../lib/PaymentGateway.php');
require_once(dirname(__FILE__).'/../../../lib/PaymentGatewayManager.php');
require_once(dirname(__FILE__).'/../../../lib/aereus.lib.php/antapi.php');
require_once(dirname(__FILE__).'/../../../lib/AntMail/DeliveryAgent.php');
require_once(dirname(__FILE__).'/../../../lib/WorkerMan.php');

class CAntObject_EmailCampaignTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;

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
	 * Test saving email campaign
	 *
	 * @group testSave
	 */
    public function testSave()
    {
        $dbh = $this->dbh;
        $user = $this->user;
        
        // Create Test Email Campaign
        $campaignObj = CAntObject::factory($dbh, "email_campaign", null, $user);
        $campaignObj->setValue("body_html", "test email campaign - body");
        $campaignObj->setValue("body_plain", "test email campaign - body");
        $campaignObj->setValue("confirmation_email", "marl.tumulak@aereus.com");
        $campaignObj->setValue("design_type", "blank");
        $campaignObj->setValue("status", "3"); // Pending
        $campaignObj->setValue("f_confirmation", "t");
        $campaignObj->setValue("f_trackcamp", "t");
        $campaignObj->setValue("from_email", "marl.tumulak@aereus.com");
        $campaignObj->setValue("from_name", "Marl Tumulak");
        $campaignObj->setValue("name", "Email Campaign Unit Test");
        $campaignObj->setValue("to_type", "manual");
        $campaignObj->setValue("to_manual", "administrator@test.netricos.com");
		$campaignObj->setValue("ts_start", date("m/d/Y", strtotime("tomorrow"))); // Delayed run
		//$campaignObj->skipBackgroundJob = true;
        $emailCampaignId = $campaignObj->save();
        $this->assertTrue($emailCampaignId > 0);
        unset($campaignObj);
        
        // Instantiate Campaign Object and test values
        $campaignObj = CAntObject::factory($dbh, "email_campaign", $emailCampaignId, $user);
		$this->assertTrue(is_numeric($campaignObj->getValue("job_id")));
		$this->assertTrue(is_numeric($campaignObj->getValue("campaign_id")));
        
        // Clean Entry
		$mcamp = CAntObject::factory($dbh, "marketing_campaign", $campaignObj->getValue("campaign_id"), $user);
		$mcamp->removeHard();
        $campaignObj->removeHard();
    }

	/**
	 * Test get recipients
	 *
	 * @group testGetRecipients
	 */
	public function testGetRecipients()
	{
		$dbh = $this->dbh;
        $user = $this->user;
        $campaign = CAntObject::factory($dbh, "email_campaign", null, $user);
        
        // Test 'manual' to_type
		// --------------------------------------------
        $campaign->setValue("to_type", "manual");
        $campaign->setValue("to_manual", "administrator@test.netricos.com, sky@stebnicki.net");
		$recipients = $campaign->getRecipients();
		$this->assertTrue(in_array("administrator@test.netricos.com", $recipients));
		$this->assertTrue(in_array("sky@stebnicki.net", $recipients));

		// Test 'condition' to_type
		// --------------------------------------------
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "testGetRecipients");
		$obj->setValue("email", "testGetRecipients@testGetRecipients.com");
		$cid = $obj->save(false);

        $campaign->setValue("to_type", "condition");
		$conditions = array(
			array("blogic"=>"and", "fieldName"=>"id", "operator"=>"is_equal", "condValue"=>$cid),
		);
        $campaign->setValue("to_conditions", json_encode($conditions));
		$recipients = $campaign->getRecipients();
		$this->assertEquals("testGetRecipients@testGetRecipients.com", $recipients[0]);

		// Retry with f_noemailspam set to 't'
		$obj->setValue("f_noemailspam", "t");
		$cid = $obj->save(false);

		$conditions = array(
			array("blogic"=>"and", "fieldName"=>"id", "operator"=>"is_equal", "condValue"=>$cid),
		);
        $campaign->setValue("to_conditions", json_encode($conditions));
		$recipients = $campaign->getRecipients();
		$this->assertEquals(0, count($recipients));

		// TODO: test 'view' to_type
		// --------------------------------------------

		// Cleanup
		$obj->removeHard();
	}

	/**
	 * Test processing/sending email campaign
	 *
	 * @group testProcessEmailCampaign
	 */
    public function testProcessEmailCampaign()
    {
        $dbh = $this->dbh;
        $user = $this->user;

		$cust = new CAntObject($dbh, "customer", null, $this->user);
		$cust->setValue("first_name", "testProcessEmailCampaign");
		$cust->setValue("email", "testProcessEmailCampaign@test.netricos.com");
		$cid = $cust->save(false);
        
        // Create Test Email Campaign
        $emailCamp = CAntObject::factory($dbh, "email_campaign", null, $user);
        $emailCamp->setValue("body_html", "<%first_name%>");
        $emailCamp->setValue("confirmation_email", "marl.tumulak@aereus.com");
        $emailCamp->setValue("design_type", "blank");
        $emailCamp->setValue("f_confirmation", "f");
        $emailCamp->setValue("f_trackcamp", "t");
        $emailCamp->setValue("from_email", "marl.tumulak@aereus.com");
        $emailCamp->setValue("from_name", "Marl Tumulak");
        $emailCamp->setValue("name", "Email Campaign Unit Test");
        $emailCamp->setValue("to_type", "condition");
        $emailCamp->setValue("status", "3");
		$conditions = array(
			array("blogic"=>"and", "fieldName"=>"id", "operator"=>"is_equal", "condValue"=>$cid),
		);
        $emailCamp->setValue("to_conditions", json_encode($conditions));
        $emailCampaignId = $emailCamp->save();
        $this->assertTrue($emailCampaignId > 0);
        unset($emailCamp);
        
        // Instantiate Campaign Object and test values
        $emailCamp = CAntObject::factory($dbh, "email_campaign", $emailCampaignId, $user);
        $emailCamp->testMode = true;
        $result = $emailCamp->processEmailCampaign();
        $this->assertTrue($result);

		// Test marketing campaing
		$marketingCamp = $emailCamp->getMarketingCamp();
		$this->assertEquals(1, $marketingCamp->getValue("num_sent"));
        
        // Cleanup
		$marketingCamp->removeHard();
        $emailCamp->removeHard();
		$cust->removeHard();
    }
}