<?php
/*======================================================================================
	
	Module:		CAntCalendar

	Purpose:	Will read and parse ANT Feeds

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.
	
	Depends:	CPageCache.php

	Usage:		// Create a new instance of parser - The last two params are optional
				$feedReader = new CFeedReader("http://pathtofeed", "date DESC", "date='1/1/2007'");

				// Get number of posts
				$num = $feedReader->getNumPosts();

				// Read the title of field 'testfield' in post 0
				// Array numbering starts at 0 so if you want to access first item use 0
				$title = $feedReader->getPostVarTitle('testfield', 0);

				// Read the value of field 'testfield' in post 0
				$value = $feedReader->getPostVarValue('testfield', 0);

				// Get cached or pushed data
				// The 5th param can be $ALIB_WF_PUSHED, $ANTLIB_WF_ALWAYS (defualt), or number of seconds to cache
				$feedReader = new CFeedReader("http://pathtofeed", "time_entered DESC", "date='1/1/2007'", null, $ALIB_WF_PUSHED);

	Variables:	

======================================================================================*/
// Define constants

class CAntCalendarEvent
{
	var $eid = "";
	var $rid = "";
	var $name = "";
	var $date = "";
	var $time_start = "";
	var $time_end = "";
	var $weekday = "";
	var $location= "";
	var $notes = "";
	var $labelclr = "";
}

class CAntCalendar
{
	var $m_url;
	var $m_max_events;
	var $m_path;
	var $m_events;
	var $m_curind;
	var $m_encurl;
	var $m_cacheint;
	var $m_cachepage = null;
	
	function CAntCalendar($url, $cid) 
	{
		// Get Calendar
		$url = "http://ant.aereus.com/calendar/xml_gwc_events.awp?month_start=".date("m");
		$url .= "&month_end=".date("m", strtotime("+1 month"))."&year_end=".date("Y", strtotime("+1 month"))."&toget=general";
		$dom = new DomDocument();
		$dom->load($url);
		foreach ($dom->documentElement->childNodes as $section) 
		{
			if ($section->nodeType == 1)
			{
				foreach ($section->childNodes as $events) 
				{
					if ($events->nodeType == 1)
					{
						if ($events->nodeName == "section_weekday")
							echo "<h4>".rawurldecode($events->textContent)."</h4>";
						if ($events->nodeName == "ection_date")
							echo "<h5>".rawurldecode($events->textContent)."</h5>";

						if ($events->nodeName == "event")
						{
							$time_start = "";
							$time_end = "";
							$name = "";
							$location = "";

							foreach ($events->childNodes as $eparts)
							{
								switch ($eparts->nodeName)
								{
								case 'time_start':
									$time_start = rawurldecode($eparts->textContent);
									break;
								case 'time_end':
									$time_end = rawurldecode($eparts->textContent);
									break;
								case 'name':
									$name = rawurldecode($eparts->textContent);
									break;
								case 'location':
									$name = rawurldecode($eparts->textContent);
									break;
								}
							}

							echo "<div>$time_start - $time_end $name: $location</div>";
						}
					}
				}
			}
		} 
		
	}

	function updateCache($page=null)
	{
		if (!$page)
		{
			if ($this->m_cachepage)
				$page = $this->m_cachepage;
			else
				$page = new CPageCache($this->m_cacheint, $this->m_encurl);
		}

		$page->purge();

		if (($fp = fopen($this->m_url, "r"))) 
		{
			while ($data = fread($fp, 4096)) 
				$page->put($data);
		}
	}

	function getCache($page)
	{
		return $page->getCache();
	}

	// Register a page with direct-push on the server
	function registerCache()
	{
		global $_SERVER;

		$url = $this->m_url;
		$url .= "&register_push=".base64_encode($_SERVER['HTTP_HOST'].'/'.substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT'])+1));
		$url .= "&push_url=".base64_encode($this->m_url);
		$fp = fopen($url, "r");
	}

	function execute($url)
	{
		if (($fp = fopen($url,"r"))) 
		{
			while ($data = fread($fp, 4096)) 
			{
				if (!xml_parse($this->m_parcer, $data, feof($fp)))
				{
					//die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), 
					//		xml_get_current_line_number($xml_parser)));
				}
			}
			xml_parser_free($this->m_parcer);
		}
	}

	function executeCache($page)
	{
		if (!xml_parse($this->m_parcer, $page->getCache(), true))
		{
			//die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), 
			//		xml_get_current_line_number($xml_parser)));
		}
		xml_parser_free($this->m_parcer);
	}
	
	function __destruct() 
	{
	}

	function getNumPosts()
	{
		return count($this->m_posts);
	}

	function getPostVarValue($varname, $ind)
	{
		return $this->m_posts[$ind]->m_vars[$varname][1];
	}

	function getPostVarTitle($varname, $ind)
	{
		return $this->m_posts[$ind]->m_vars[$varname][0];
	}
}

// Functions
if ($_GET['function'])
{
	if (file_exists($_SERVER['DOCUMENT_ROOT']."/lib/AntConfig.php"))
		require_once($_SERVER['DOCUMENT_ROOT']."/lib/AntConfig.php");
	
	require_once("CPageCache.php");

	switch ($_GET['function'])
	{
	case 'update':
		if ($_GET['furl'])
		{
			$feed_url = base64_decode($_GET['furl']);
			$feedReader = new CFeedReader($feed_url, null, null, null, $ALIB_WF_PUSHED);
			$feedReader->updateCache();
		}
		break;
	case 'test':
		$cid = 1;
	}
}
?>
