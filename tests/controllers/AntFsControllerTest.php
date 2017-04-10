<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../controllers/AntFsController.php');

class AntFsControllerTest extends PHPUnit_Framework_TestCase
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
        $this->antfs = new AntFs($this->dbh, $this->user);
    }
    
    function tearDown() 
    {
    }
    
	/**
	 * Use the below function to limit the current test
	 */
	/*
    function getTests()
    {        
        return array("testGetForms");        
    }    
	 */

	/**
     * Make sure the readfolder returns the right data
     */
    function testNewFolder()
    {        
		$fldr = $this->antfs->openFolder("/test/nftest", true);
		$fldr->remove();

		$params = array();
		$params['path'] = "/test";
		$params['name'] = "nftest";

		$objController = new AntFsController($this->ant, $this->user);
		$objController->debug = true;
		$ret = $objController->newFolder($params);
		$this->assertTrue($ret['folder_id']>0);

		// Make sure the folder exists
		$fldr = $this->antfs->openFolder("/test/nftest", false);
		$this->assertNotNull($fldr);

		// Cleanup
		$fldr->remove();
    }
    
    /**
     * Test ObjectList::move
     */
    function testMove()
    {
		// Test moving to root
		$moveto = $this->antfs->openFolder("/", true);
		$fldr = $this->antfs->openFolder("/test/movetest", true);
		$this->assertNotNull($fldr->id);

		$params['obj_type'] = "file";
		$params['browsebyfield'] = "folder_id";
		$params['objects'] = array("browse:".$fldr->id);
		$params['move_to_id'] = $moveto->id;
        
        $objController = new AntFsController($this->ant, $this->user);
		$objController->debug = true;
        $ret = $objController->move($params);
        $this->assertEquals("browse:".$fldr->id, $ret[0]);
		unset($fldr);

		$fldr = $this->antfs->openFolder("/test/movetest");
		$this->assertNull($fldr);

		$fldr = $this->antfs->openFolder("/movetest");
		$this->assertNotNull($fldr);

		$moveto = $this->antfs->openFolder("/test", true);
		$params['obj_type'] = "file";
		$params['browsebyfield'] = "folder_id";
		$params['objects'] = array("browse:".$fldr->id);
		$params['move_to_id'] = $moveto->id;
        $objController = new AntFsController($this->ant, $this->user);
		$objController->debug = true;
        $ret = $objController->move($params);
        $this->assertEquals("browse:".$fldr->id, $ret[0]);
		unset($fldr);

		$fldr = $this->antfs->openFolder("/test/movetest");
		$this->assertNotNull($fldr);
	}

	/**
     * Test uploading files to ANT
     */
    function testUpload()
    {
		$folder = $this->antfs->openFolder("/test", true);
		$this->assertNotNull($folder);

		$auth = $this->user->getAuthString();

		$url = "http://".AntConfig::getInstance()->localhost."/controller/AntFs/upload?auth=" . $this->user->getAuthString();
		$url .= "&folderid=" . $folder->id;

		// Send the file to the controller with a POST request
		$response = $this->sendFileToAntFs($url, dirname(__FILE__)."/../data/mime_emails/testatt.txt");
		$this->assertNotEquals($response, ""); // Need to test in test site
		$ret = json_decode($response);
		$file = $this->antfs->openFileById($ret[0]->id);
		$fid = $ret[0]->id;
		$this->assertEquals($file->getValue("folder_id"), $folder->id);
		
		$buf = $file->read();
		$this->assertEquals($buf, "My Test Attachment\n");
		$file->close();
		unset($file);

		// Now try re-uploading to the existing file
		$response = $this->sendFileToAntFs($url."&fileid=$fid", dirname(__FILE__)."/../data/mime_emails/testatt2.txt");
		$this->assertNotEquals($response, "");

		$ret = json_decode($response);
		$this->assertEquals($fid, $ret[0]->id);
		$file = $this->antfs->openFileById($ret[0]->id);

		// Make sure the name changed back to the imported file name, the content has been updated, and the owner is preserved
		$this->assertEquals($file->getValue("name"), "testatt2.txt");
		$this->assertEquals($file->getValue("owner_id"), $this->user->id);
		$this->assertEquals($file->getForeignValue("owner_id"), $this->user->fullName);
		$buf = $file->read();
		$this->assertEquals($buf, "My Test Attachment 2\n");
		$file->close();

		$file->removeHard();
	}

	/**
	 * Helper function to send uploaded file to AntFs
	 *
	 * @param string $url The url to send this file to
	 */
	private function sendFileToAntFs($url, $file)
	{
		$ch = curl_init();
		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_INFILESIZE, $size);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("file"=>"@".$file));
		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}
}
