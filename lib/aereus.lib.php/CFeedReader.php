<?php
/*======================================================================================
	
	Module:		CFeedReader

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
				// but make sure that /siteroot/tmp exists and the apache process (www) has read/write access
				$feedReader = new CFeedReader("http://pathtofeed", "time_entered DESC", "date='1/1/2007'", null, $ALIB_WF_PUSHED);

	Variables:	

======================================================================================*/
// Define constants
$ALIB_WF_PUSHED = -1;
$ALIB_WF_ALWAYS = 0;

class CFeedReaderPost
{
	// 2d array(name=>array(title, value))
	var $m_vars;
	var $m_curname;
	var $m_curtitle;

	function CFeedReaderPost()
	{
		$this->m_vars = array();
	}
}

class CFeedReader
{
	var $m_parcer;
	var $m_url;
	var $m_pax_posts;
	var $m_path;
	var $m_posts;
	var $m_curind;
	var $m_fInPost;
	var $m_encurl;
	var $m_cacheint;
	var $m_cachepage = null;
	var $m_fixHtml = true;
	
	function CFeedReader($url, $order=null, $condition=null, $max=null, $cacheinterval=0, $archived=false) 
	{
		$this->m_url = $url;
		$this->m_curind = 0;
		$this->m_fInPost = false;
		$this->m_pax_posts = $max;
		$this->m_cacheint = $cacheinterval;

		$this->m_parcer = xml_parser_create();
		xml_set_object($this->m_parcer, $this);
		xml_set_element_handler($this->m_parcer, "startElement", "endElement");
		xml_set_character_data_handler($this->m_parcer, "characterData");
		
		// Add condition and order by
		if ($order)
			$url .= "&order=".rawurlencode($order);
		if ($condition)
			$url .= "&condition=".rawurlencode($condition);
		if ($max)
			$url .= "&max_num=$max";
		if ($archived)
			$url .= "&archived=1";

		$this->m_url = $url;
		$this->m_encurl = rawurlencode($url);

		if ($cacheinterval == -1 || $cacheinterval)
		{
			// Change to local cached file
			$this->m_cachepage = new CPageCache($this->m_cacheint, $this->m_encurl);;
			if ($this->m_cachepage->IsExpired())
			{
				if ($cacheinterval == -1)
					$this->registerCache(); // Register push url with server

				$this->updateCache($this->m_cachepage);
			}
			
			$this->executeCache($this->m_cachepage);
		}
		else
		{
			$this->execute($url);
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
		//$url .= "&register_push=".base64_encode($_SERVER['HTTP_HOST'].'/'.substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT'])+1));
		$url .= "&register_push=".base64_encode($_SERVER['HTTP_HOST'].'/lib/Aereus/CFeedReader.php');
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

	function getPostVarValue($varname, $ind, $type="html", $enc="utf8")
	{
		$val = $this->m_posts[$ind]->m_vars[$varname][1];

		if ($type == "html" && !is_array($val))
		{
			$val = rawurldecode($val);
			$val = $this->htmlButTags($val);
			$val = preg_replace("/<(img|hr|br|base|frame|input)([^>]*)>/mi", "<$1$2 />", $val); 
			$val = str_replace("<br>", "<br />", $val);
		}
		
		return $val;
	}	

	function getPostVarTitle($varname, $ind)
	{
		return $this->m_posts[$ind]->m_vars[$varname][0];
	}

	function startElement($parser, $name, $attrs)
	{
		$this->m_path .= ($this->m_path) ?  ":".$name : $name;

		switch ($this->m_path)
		{
		case 'ANT_FEED:POST':
			$this->m_posts[$this->m_curind] = new CFeedReaderPost();
			$this->m_fInPost = true;
			break;
		case 'ANT_FEED:POST:CATEGORIES:CATEGORY':
			if ($this->m_fInPost)
			{
				if (!isset($this->m_posts[$this->m_curind]->m_vars['categories'][1]))
					$this->m_posts[$this->m_curind]->m_vars['categories'][1] = array();

				$this->m_posts[$this->m_curind]->m_vars['categories'][1][$attrs['ID']] = rawurldecode($attrs['NAME']);
				$this->m_posts[$this->m_curind]->m_vars['categories'][0] = "Categories";
			}
			break;
		case 'ANT_FEED:POST:CATEGORIES':
			break;
		default:
			// If inside post then populate variable
			if ($this->m_fInPost)
			{
				$this->m_posts[$this->m_curind]->m_curname = strtolower($name);
				$this->m_posts[$this->m_curind]->m_vars[strtolower($name)][0] = rawurldecode($attrs['TITLE']);
			}
			break;
		}
	}

	function endElement($parser, $name)
	{
		switch ($this->m_path)
		{
		case 'ANT_FEED:POST':
			$this->m_curind++;
			$this->m_fInPost = false;
			break;
		}
		
		$this->m_path = substr($this->m_path, 0, strrpos($this->m_path, ":"));
	}

	function characterData($parser, $data)
	{
		if ($this->m_fInPost && $data)
		{
			$name = $this->m_posts[$this->m_curind]->m_curname;
			$this->m_posts[$this->m_curind]->m_vars[strtolower($name)][1] .= $data;
		}
	}

	// Convert all text (non tags) into standard entities for complianace
	function htmlButTags($str) 
	{
        // Take all the html entities
        $caracteres = get_html_translation_table(HTML_ENTITIES);
        // Find out the "tags" entities
        $remover = get_html_translation_table(HTML_SPECIALCHARS);
        // Spit out the tags entities from the original table
        $caracteres = array_diff($caracteres, $remover);
        // Translate the string....
        $str = strtr($str, $caracteres);
        // And that's it!
        // oo now amps
        $str = preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&amp;" , $str);
       
        return $str;
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
		exit;
		break;
	case 'testpath':
		echo $_SERVER['HTTP_HOST'].'/'.substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT'])+1).":".$_SERVER['PATH_INFO'];
		break;
	}
}
?>
