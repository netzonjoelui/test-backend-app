<?php
//require_once 'PHPUnit/Autoload.php';

// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/AntMail/Account.php');

class AntMail_AccountTest extends PHPUnit_Framework_TestCase 
{
	var $ant = null;
	var $user = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1, null); // -1 = administrator
	}
	
	function testMailAccount()
	{
		// Instantiate 
		$accountObj = new AntMail_Account($this->dbh);
		$accountObj->name = "UnitTest ImapEmailAccount";
		$accountObj->emailAddress = "unitTestEmailAccount";
		$accountObj->fDefault = true;
		$accountObj->userId = $this->user->id;
		$accountObj->fSystem = true;
		// Incoming
		$accountObj->type = "imap";
		$accountObj->username = "imapUsername";
		$accountObj->password = "imapPassword";
		$accountObj->host = "imapHost";
		$accountObj->port = 110;
		$accountObj->ssl = true;
		// Outbound
		$accountObj->fOutgoingAuth = true;
		$accountObj->usernameOut = "smtpUsername";
		$accountObj->passwordOut = "smtpPassword";
		$accountObj->hostOut = "smtpHost";
		$accountObj->sslOut = true;
		$accountObj->portOut = 64;
        $accountObj->forward = "test@test.com";

		$accountObj->fOutgoingAuth = true;
		
		// Test Insert Email Account
		$accountId = $accountObj->save();
		$this->assertTrue($accountId > 0);
		
		// Open account in another object and test
		$accountObj2 = new AntMail_Account($this->dbh, $accountId);
		$this->assertEquals($accountObj2->id, $accountId);
		$this->assertEquals($accountObj2->name, $accountObj->name);
		$this->assertEquals($accountObj2->emailAddress, $accountObj->emailAddress);
		$this->assertTrue($accountObj2->fSystem, true);
		$this->assertTrue($accountObj2->fDefault, true);
        $this->assertEquals($accountObj2->forward, $accountObj->forward);
		// Test Incoming
		$this->assertEquals($accountObj2->type, $accountObj->type);
		$this->assertEquals($accountObj2->username, $accountObj->username);
		$this->assertEquals($accountObj2->password, $accountObj->password);
		$this->assertEquals($accountObj2->host, $accountObj->host);
		$this->assertEquals($accountObj2->port, $accountObj->port);
		$this->assertTrue($accountObj2->ssl, true);
		// Test outgoing
		$this->assertTrue($accountObj2->fOutgoingAuth, true);
		$this->assertEquals($accountObj2->usernameOut, $accountObj->usernameOut);
		$this->assertEquals($accountObj2->passwordOut, $accountObj->passwordOut);
		$this->assertEquals($accountObj2->hostOut, $accountObj->hostOut);
		$this->assertEquals($accountObj2->portOut, $accountObj->portOut);
		$this->assertTrue($accountObj2->sslOut, true);
		unset($accountObj2);
		
		// Clean Data
		$accountObj->remove();
	}
}
