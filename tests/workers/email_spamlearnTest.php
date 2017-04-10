<?php
/**
 * Test the email account sync workers
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

class Workers_Email_SpamLearnTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $ant = null;
	var $user = null;
	var $host = null;
	var $username = null;
	var $password = null;

	/**
	 * Setup class variables
	 */
	public function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}
	
	/**
	 * Test object index worker
	 */
	public function testSpamLearn() 
	{
        // Import original email
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__)."/../data/mime_emails/attachments-mail.txt");
		$newEmail = new CAntObject_EmailMessage($this->dbh, null, $this->user);
		$mid = $newEmail->import($tmpFile, null, true);
		$this->assertTrue(is_numeric($mid));
		unset($newEmail);
        
        // Open email and mark as spam
        $email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
        $email->setValue("flag_spam", 't');
        $email->save();
        
        // Set paths where we expect the files to be
        $spamPath = AntConfig::getInstance()->data_path . "/email_spam/" . $this->dbh->accountId . "-" . $mid . ".eml";
        $hamPath = AntConfig::getInstance()->data_path . "/email_ham/" . $this->dbh->accountId . "-" . $mid . ".eml";

		// Call the worker
		$data = array(
			"user_id" => $this->user->id, 
			"message_id" => $mid,
		);
		$wm = new WorkerMan($this->dbh);
		$ret = $wm->run("email/spamlearn", serialize($data));
		$this->assertTrue($ret);
        
        // Check to see if the file exists
        $this->assertTrue(file_exists($spamPath), $spamPath . " not found!");
        unlink($spamPath);
        
        // Now set not spam
        $email->setValue("flag_spam", 'f');
        $email->save();
        // Call the worker
		$data = array(
			"user_id" => $this->user->id, 
			"message_id" => $mid,
		);
		$wm = new WorkerMan($this->dbh);
		$ret = $wm->run("email/spamlearn", serialize($data));
		$this->assertTrue($ret);
        
        // Check to see if the file exists
        $this->assertTrue(file_exists($hamPath));
        unlink($hamPath);

		// Cleanup
		$email->removeHard();
        unlink($tmpFile);
	}
    
    /**
	 * Create a temp file to use when importing email
	 *
	 * @group getMessageTempFile
	 * @return string The path to the newly created temp file
	 */
	private function getMessageTempFile($file)
	{
		$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
		if (!file_exists($tmpFolder))
			@mkdir($tmpFolder, 0777);
		$tmpFile = tempnam($tmpFolder, "em");
		file_put_contents($tmpFile, file_get_contents($file)); // copy data

		// Normalize new lines to \r\n
		$handle = @fopen($tmpFile, "r");
		$handleNew = @fopen($tmpFile."-pro", "w");
		$buffer = null;
		if ($handle) 
		{
			while (($buffer = fgets($handle, 4096)) !== false) 
			{

				fwrite($handleNew,  preg_replace('/\r?\n$/', '', $buffer)."\r\n");
			}
			fclose($handle);
			fclose($handleNew);
			unlink($tmpFile);
			$tmpFile = $tmpFile."-pro"; // update name to match processed file
		}

		return $tmpFile;
	}
}
