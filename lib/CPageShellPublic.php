<?php
class CPageShellPublic
{
	/**
	 * Array of additional javascript sources to load
	 *
	 * @var array
	 */
	public $scripts = array();

	var $title;
	var $navigation;
	var $metaDescription;
	var $metaKeywords;
	var $opts;

	function CPageShellPublic($pTitle, $navigation, $mdescription="", $mkeywords="", $opts=null)
	{
		global $siteMap;

		$this->title = $pTitle;
		$this->navigation = $navigation;
		$this->metaDescription = $mdescription;
		$this->metaKeywords = $mkeywords;
		$this->opts = ($opts) ? $opts :  array("print_subnav"=>true);
	}

	function PrintHeader()
	{
		global $ANT;
		
		$pTitle = $this->title;

		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo "	<head>";
		echo "		<title>$pTitle</title>";
		echo "		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
		echo "		<meta name=\"description\" content=\"".$this->metaDescription."\" />";	
		echo "		<meta name=\"keywords\" content=\"".$this->metaKeywords."\" />";
		echo "		<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"/css/public.css\" />";
		echo "		<script src=\"/lib/js/global.js\" type=\"text/javascript\"></script>";
		foreach ($this->scripts as $script)
			echo "		<script src=\"$script\" type=\"text/javascript\"></script>";
					
		// Print any external javascript and favicons here
		// -------------------------------------------------
		//echo "<link rel=\"SHORTCUT ICON\" href=\"../favicon.ico\">";
		$ALIBPATH = "/lib/aereus.lib.js/";
		include("lib/aereus.lib.js/js_lib.php");
		
		echo "</head>";

		echo "<body>";

		// Open body containers
		echo "<div id='main'>";
		// create header div
		// -----------------------------------------------
		echo "<div id='pageHeader'>";
		echo "<div id='pageTitle'>".$this->title."</div>";
		echo "<div id='mainNav'>".$this->GetMainNav()."</div>";
		$logo = "/images/logo_public.png";
		if ($ANT)
		{
			$header_image = $ANT->settingsGet("general/header_image_public");
			if (is_numeric($header_image))
				$logo = "/files/images/$header_image";
		}
		//echo "<a href='http://www.aereus.com/ant' target='_blank' border='0'><img src=\"$logo\" style='margin-top:5px;'/></a>";
		echo "<div id='pageLogo'><img src=\"$logo\" style='margin-top:5px;'/></div>";
		echo "</div>";
		
		// create nav (can be left, right, top, or even bottom - use css)
		// -----------------------------------------------
		if ($this->opts['print_subnav'])
		{
			echo "<div id='subNav'>";
			$this->PrintNavigation();
			echo "</div>";

			// open body
			echo "<div id='pageContent'>";
		}
		else
		{
			echo "<div>";
		}
	}							

	function PrintShell()
	{
		$this->PrintHeader();
	}
	
	function PrintFooter()
	{
		echo "<div class='clear'></div>";
		echo "</div>
			  <div id='pageFooter'>";
		echo "Powered by <a href='http://www.netric.com' target='_blank'>Netric</a>.&nbsp;";
		echo "Copyright  &copy; ".date("Y").", Aereus Corporation";
		echo "</div>			  
			  </div>
			  </body>
			  </html>";
	}

	// This prints to bottom of the page -> printFooter							
	function __destruct()
	{
		$this->PrintFooter();
	}

	function GetMainNav()
	{
		global $g_links, $ANT;
		$ret = "";

		// Check to see if links were defined by this page
		if ($g_links)
		{
			$links = $g_links;
		}
		else
		{
			$links = array();
			// Home
			if ($ANT && $ANT->dbh)
				$website = $ANT->settingsGet("general/company_website");
			if (!$website)
				$website = "www.aereus.com";

			$links[] = array('http://'.$website, 'website', $website, null);
		}

		foreach ($links as $node)
		{
			if ($this->skip_nav_home && $node[1] == "home")
				continue;

			$off_class = ($this->navigation == $node[1]) ? "mainNavButtonOn" : "mainNavButtonOff";
			$over_class = ($this->navigation == $node[1]) ? "mainNavButtonOn" : "mainNavButtonOver";

			$ret .=  "<a href=\"".$node[0]."\">".$node[2]."</a>";
		}

		return $ret;
	}

	function PrintNavigation()
	{
		global $g_links;

		// Check to see if links were defined by this page
		if ($g_links)
		{
			$links = $g_links;
		}
		else
		{
			$links = array();
			// Home
			$links[] = array('/', '', 'Home', null);
			
			// Content
			$sublinks = array();
			$sublinks[] = array("content.php", "projects", "Project Details");
			$links[] = array('content.php', 'content', 'Page Content', $sublinks);

			// Contact
			$links[] = array('/contact', 'contact', 'Contact', null);
		}

		foreach ($links as $node)
		{
			$href = $node[0];
			echo "<a href='$href'>".$node[2]."</a><br />";

			// The following can be used for sub-navigation		
			if ($node[1] == $this->navigation)
			{
				if (is_array($node[3]))
				{
					foreach ($node[3] as $sublink)
					{
						echo "&nbsp;<a href='".$sublink[0]."'>".$sublink[2]."</a><br/>";
					}
				}
			}
		}
	}
}
?>
