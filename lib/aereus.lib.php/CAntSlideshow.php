<?php
/*======================================================================================
	
	Module:		CAntSlideshow

	Purpose:	Reads all images in a directory and outputs them to slideshow xml

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		$ss = new CAntSlideshow("http://ant.aereus.com/userfiles/wapi.php", 186); // The last param is the id of the folder
				echo $ss->getSlideshowXml();

				// This xml can either be processed through ajax, or pointed to
				// but the SSP flah application /lib/flash/SlideShowPro

	Variables:	

======================================================================================*/

class CAntSlideshow
{
	var $m_url;
	var $m_files;
	
	function CAntSlideshow($url, $username, $password, $cid) 
	{
		$this->m_url = $url;
		$this->m_url .= "?function=slideshow&cid=$cid&auth=".base64_encode($username).":".md5($password);

		$this->m_files = array();

		$dom = new DomDocument();

		$dom->load($this->m_url); 

		foreach ($dom->documentElement->childNodes as $files) 
		{
			//if node is an element (nodeType == 1) and the name is "item" loop further
			if ($files->nodeType == 1)
			{
				if ($files->nodeName == "file")
				{
					$file_obj = array();

					foreach ($files->childNodes as $file)
					{
						$file_obj[$file->nodeName] = rawurldecode($file->textContent);
					}

					$this->m_files[] = $file_obj;
				}
			}
		} 
	}

	function __destruct() 
	{
	}

	function getSlideshowXml($inline=false)
	{
		$ret = "";
		if (!$inline)
		{
			$ret .= "<gallery>";
			$ret .= "<album title=\"\" description=\"\">";
		}

		$num = count($this->m_files);
		for ($i = 0; $i < $num; $i++)
		{
			$file = $this->m_files[$i];
			$link = ($file['keywords']) ? $file['keywords'] : $file['url'];

			$ret .= "<img src=\"".$file['url']."\" caption=\"".$file['title']."\" link=\"".$link."\" target=\"_blank\" />";
		}

		if (!$inline)
		{
			$ret .= "</album>";
			$ret .= "</gallery>";
		}

		return $ret;
	}

	function getNumFiles()
	{
		return count($this->m_files);
	}

	function getFileVarValue($varname, $ind)
	{
		return $this->m_posts[$ind]->m_vars[$varname][1];
	}

	function getFile($i)
	{
		return $this->m_files[$i];
	}
}

if ($_GET['function'])
{
	switch($_GET['function'])
	{
	case 'print':
		header("Content-type: text/xml");			// Returns XML document
		echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

		$ss = new CAntSlideshow("http://ant.aereus.com/userfiles/wapi.php", 186);
		echo $ss->getSlideshowXml();
		break;
	}
}
?>
