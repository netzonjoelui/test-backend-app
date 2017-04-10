<?php
//require_once 'PHPUnit/Autoload.php';
//require_once(dirname(__FILE__).'/../simpletest/autorun.php');
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/AntFs.php');
require_once(dirname(__FILE__).'/../../lib/AntFsStreamWrapper.php');

class AntFsStreamWrapperTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;
	var $antfs = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator
		$this->antfs = new AntFs($this->dbh, $this->user); // -1 = administrator
		
		$this->markTestSkipped('ANS Test server is not yet ready.');
	}
	
	/**
	 * Test reading from a file
	 */
	public function testRead()
	{
		$fldr = $this->antfs->openFolder("/test/mytest", true);
		$this->assertNotNull($fldr);

		$file = $fldr->openFile("test", true);
		$this->assertNotNull($file);

		$size = $file->write("test contents");
		$this->assertNotEquals($size, -1);

		// Open stream and read one byte at a time
		$buf = "";
		$stream = AntFsStreamWrapper::OpenFile($file);
		while (!feof($stream))
		{
			$ch = fread($stream, 1);
			$buf .= $ch;
		}
		$this->assertEquals($buf, "test contents");

		// Cleanup
		$ret = $file->removeHard();
	}
}
