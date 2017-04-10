<?php
//require_once 'PHPUnit/Autoload.php';

// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once('lib/CDatabase.awp');
require_once('lib/Ant.php');
require_once('lib/AntUser.php');
require_once('lib/AntMail/MimeParser.php');

class AntMail_MimeParserTest extends PHPUnit_Framework_TestCase
{
	var $ant = null;
	var $user = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new ANT();
		$this->user = new AntUser($this->ant->dbh, -1); // -1 = administrator
		$this->dbh = $this->ant->dbh;
		
		$this->markTestSkipped('Cannot test since imap server is not setup.');
	}

	/**
	 * Test getting a header
	 */
	public function testGetHeader()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__)."/../../data/mime_emails/parse-attachment.txt");
		$this->assertTrue(file_exists($tmpFile));

		// Test mailparse parser
		// ----------------------------------------------------------
		if(function_exists('mailparse_msg_parse'))
		{
			$mimeParser = new AntMail_MimeParser($tmpFile, "MimeMailParser");
			$this->assertEquals("TestTo <test-to@aereus.com>", $mimeParser->getHeader('to'));
			$this->assertEquals("TestFrom <test-from@aereus.com>", $mimeParser->getHeader('from'));
			$this->assertEquals("Test Parse Subject", $mimeParser->getHeader('subject'));
		}

		// Test mimeDecode parser
		// ----------------------------------------------------------
		$mimeParser = new AntMail_MimeParser($tmpFile, "Mail_mimeDecode");
		$this->assertEquals("TestTo <test-to@aereus.com>", $mimeParser->getHeader('to'));
		$this->assertEquals("TestFrom <test-from@aereus.com>", $mimeParser->getHeader('from'));
		$this->assertEquals("Test Parse Subject", $mimeParser->getHeader('subject'));

		// Cleanup
		unlink($tmpFile);
	}

	/**
	 * Test getting the message body
	 */
	public function testgetMessageBody()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__)."/../../data/mime_emails/parse-attachment.txt");
		$this->assertTrue(file_exists($tmpFile));

		// Test mailparse parser
		// ----------------------------------------------------------
		if(function_exists('mailparse_msg_parse'))
		{
			$mimeParser = new AntMail_MimeParser($tmpFile, "MimeMailParser");
			$this->assertEquals("plain body content", trim($mimeParser->getMessageBody('plain')));
			$this->assertEquals("<html><body>html body content</body></html>", trim($mimeParser->getMessageBody('html')));
		}

		// Test mimeDecode parser
		// ----------------------------------------------------------
		$mimeParser = new AntMail_MimeParser($tmpFile, "Mail_mimeDecode");
		$this->assertEquals("plain body content", trim($mimeParser->getMessageBody('plain')));
		$this->assertEquals("<html><body>html body content</body></html>", trim($mimeParser->getMessageBody('html')));

		// Cleanup
		unlink($tmpFile);
	}

	/**
	 * Test parsing an address list
	 */
	public function testParseAddressList()
	{
		$ret = AntMail_MimeParser::parseAddressList('"joe" <sky.stebnicki@aereus.com>, sky@stebnicki.net');

		$this->assertEquals("sky.stebnicki@aereus.com", $ret[0]['address']);
		$this->assertEquals("sky.stebnicki", $ret[0]['mailbox']);
		$this->assertEquals("aereus.com", $ret[0]['host']);
		$this->assertEquals("joe", $ret[0]['display']);
		$this->assertEquals("sky@stebnicki.net", $ret[1]['address']);
	}

	/**
	 * Test replyparser
	 */
	public function testParseReply()
	{
		$body = file_get_contents(dirname(__FILE__)."/../../data/mime_emails/reply_parser_body.txt");
		$this->assertEquals("actual body", AntMail_MimeParser::parseReply($body));
	}

	/**
	 * Test getting the message attachments
	 */
	public function testGetAttachments()
	{
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__)."/../../data/mime_emails/parse-attachment.txt");
		$this->assertTrue(file_exists($tmpFile));

		// Test mailparse parser
		// ----------------------------------------------------------
		if(function_exists('mailparse_msg_parse'))
		{
			$mimeParser = new AntMail_MimeParser($tmpFile, "MimeMailParser");
			$attachments = $mimeParser->getAttachments();
			$this->assertEquals(3, count($attachments)); // parse-attachment.txt as three attachments
		}

		// Test mimeDecode parser
		// ----------------------------------------------------------
		$mimeParser = new AntMail_MimeParser($tmpFile, "Mail_mimeDecode");
		$attachments = $mimeParser->getAttachments();
		$this->assertEquals(3, count($attachments)); // parse-attachment.txt as three attachments

		// Cleanup
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
