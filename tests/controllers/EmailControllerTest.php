<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntFs.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/EmailController.php');


class EmailControllerTest extends PHPUnit_Framework_TestCase
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
    }

    function tearDown() 
    {
    }

    /*function getTests()
    {        
        return array("testSend");        
    }*/

    /**
    * Test ANT Email - saveDefaultEmail()
    */
    function testSaveDefaultEmail()
    {
        // instantiate email controller
        $emailController = new EmailController($this->ant, $this->user);
        $emailController->debug = true;
        
        $params['email_address'] = "UnitTest@unittest.com";
        $params['email_display_name'] = "UnitTest DisplayName";
        $params['email_replyto'] = "UnitTest ReplyTo";
        $result = $emailController->saveDefaultEmail($params);
        $this->assertTrue($result > 0);
    }
    
    /**
    * Test ANT Email - getVmailTemplates()
    */
    function testGetVmailTemplates()
    {
        // instantiate email controller
        $emailController = new EmailController($this->ant, $this->user);
        $emailController->debug = true;
        
        $result = $emailController->getVmailTemplates();
        $this->assertTrue(is_array($result));
    }
    
    /**
    * Test ANT Email - getEmails()
    */
    function testGetEmails()
    {
        // instantiate email controller
        $emailController = new EmailController($this->ant, $this->user);
        $emailController->debug = true;
        
        $result = $emailController->getEmails();
        $this->assertTrue(is_array($result));
    }

    /**
    * Test sending emails
    */
    function testSend()
    {
        // instantiate email controller
        $emailController = new EmailController($this->ant, $this->user);
        $emailController->debug = true;

		// Add a temp file attachment
		$antfs = new AntFs($this->dbh, $this->user);
		$file = $antfs->createTempFile();
		$fid = $file->id;
		$size = $file->write("test contents");
		$this->assertNotEquals($size, -1);
        $use_account = null;
        
        if(isset($params['use_account']))
            $use_account = $params['use_account'];
        
        $params = array(
                "user_id" => $this->user->id,
                "use_account" => $use_account,
                "cmp_to" => "sky.stebnicki@aereus.com",
                "cmp_cc" => "",
                "cmp_bcc" => "",
                "cmp_subject" => "A test message",
                "cmpbody" => "This is the body",
                "uploaded_file" => $fid,
                "objects" => "",
                "obj_type" => "",
                "using" => "",
                "in_reply_to" => "",
                "message_id" => "",
                "testing" => "1",
			);

        $result = $emailController->sendEmail($params);
        $this->assertTrue($result);

		// Cleanup
		$file->removeHard();
    }
}
