<?php
//require_once 'PHPUnit/Autoload.php';

// ANT Includes 
require_once(dirname(__FILE__).'/../../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../../lib/AntMail/Parser/CssToInline.php');

class AntMail_Parser_CssToInlineTest extends PHPUnit_Framework_TestCase 
{
	var $ant = null;
	var $user = null;
	var $dbh = null;

	/**
	 * Setup each test
	 */
	public function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1, null); // -1 = administrator
	}
	
	/**
	 * Test simple parse
	 */
	public function testConvert()
	{
		$html = "<!DOCTYPE HTML><html>";
		$html .= "<style type='text/css'>.myStyle{font-weight:bold;}</style>\n";
		$html .= "<div class='myStyle'>Hi</div>";
		$html .= "</html>";

		$cssToInline = new AntMail_Parser_CssToInline();
		$cssToInline->setHTML($html);
		$html = $cssToInline->convert();

		// Make sure we not have the class in an inline style
		$this->assertNotEquals(strpos($html, "style=\"font-weight: bold;\""), false);
	}
}
