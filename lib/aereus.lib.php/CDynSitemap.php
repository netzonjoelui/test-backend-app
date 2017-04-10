<?php
/*======================================================================================
	
	class:		CDynSitemap

	Purpose:	Create dynamic sitemap-html and sitemap-xml (google) as pages are browsed

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.

	Usage:		

	Variables:	1.	$ALIB_PAGECACHE_DIR (defaults to /tmp)

======================================================================================*/

$siteMap = new CDynSitemap();

class CDynSitemap
{
	var $pages; // array('page'='', 'title'='', subpages=array(...))
	var $sDataFile;
	var $sPage;
	var $pagesNode; // node for this page

	function CDynSitemap()
	{
		global $_SERVER;

		if ($ALIB_PAGECACHE_DIR)
			$this->sDir = $ALIB_PAGECACHE_DIR;
		else
		{
			$this->sDir = "/tmp";

			if ($_SERVER['DOCUMENT_ROOT'])
			{
				$isthere = true;

				if (!file_exists($_SERVER['DOCUMENT_ROOT']."/tmp"))
					$isthere = mkdir($_SERVER['DOCUMENT_ROOT']."/tmp");

				if ($isthere)
					$this->sDir = $_SERVER['DOCUMENT_ROOT']."/tmp";
			}
		}

		$this->sDataFile = $this->sDir."/sitemap.dat";

		// Load adday of pages
		$this->pages = array();

		if (!$this->read())
			$this->pages['/'] = array("title"=>"Home", "link"=>"/", "subpages"=>array());
		
		$this->sPage = $_SERVER['REQUEST_URI'];

		// Exclude sitemap files from sitemap
		if ($this->sPage != "/sitemap.php" && $this->sPage != "sitemap_xml.php" && $this->sPage != "sitemap_ror.php")
			$this->addPage();
	}

	function __destruct() 
	{
		$this->write();
	}

	function addPage()
	{
		$parts = explode("/", $this->sPage);

		$node = &$this->pages['/'];

		// First item (root) will be skipped over
		for ($i = 1; $i < count($parts); $i++)
		{
			if ($parts[$i])
			{
				if (isset($node['subpages'][$parts[$i]]))
				{
					$node = &$node['subpages'][$parts[$i]];
				}
				else
				{
					$node['subpages'][$parts[$i]] = array("title"=>$parts[$i], "link"=>$this->sPage, "subpages"=>array(), "browsable"=>"f");
					$node = &$node['subpages'][$parts[$i]];
				}

				// If this page (last item in array)
				if ($i == count($parts)-1)
				{
					$this->pagesNode = &$node;
					$this->pagesNode["browsable"] = "t";
				}
			}
		}
	}

	function removePage($removePage="")
	{
		$removePage = ($removePage) ? $removePage : $this->sPage;

		$parts = explode("/", $removePage);

		$node = &$this->pages['/'];

		// First item (root) will be skipped over
		for ($i = 1; $i < count($parts); $i++)
		{
			if ($parts[$i])
			{
				if (isset($node['subpages'][$parts[$i]]))
				{
					// This page
					if ($i == count($parts)-1)
					{
						$tmparr = array();
						foreach ($node['subpages'] as $key=>$pagearr)
						{
							if ($key!=$parts[$i])
							{
								$tmparr[$key] = $pagearr;
							}
						}
						$node['subpages'] = $tmparr;
					}

					$node = &$node['subpages'][$parts[$i]];
				}
			}
		}

		if (count($parts)>1)
		{
			$this->clearEmptyTree(&$this->pages['/'], $parts[1]);
		}
	}

	function clearEmptyTree($node, $page)
	{
		$fEmpty = true;
		$subnode = $node['subpages'][$page];

		if (!isset($subnode))
			return true;
		else if ($subnode['browsable'] == 't')
			return false;

		foreach ($subnode['subpages'] as $key=>$pagearr)
		{
			$this->clearEmptyTree(&$subnode, $key);
		}

		foreach ($node['subpages'] as $key=>$pagearr)
		{
			if ($node['subpages'][$key]['browsable'] == 't')
				$fEmpty = false;
		}

		if ($fEmpty)
			$this->removePageNode(&$node, $page);

		return $fEmpty;
	}

	function removePageNode($node, $page)
	{
		$tmparr = array();
		foreach ($node['subpages'] as $key=>$pagearr)
		{
			if ($key!=$page)
			{
				$tmparr[$key] = $pagearr;
			}
		}
		$node['subpages'] = $tmparr;
	}

	function setPageTitle($title)
	{
		if ($this->pagesNode)
			$this->pagesNode['title'] = $title;
	}

	function write()
	{
		file_put_contents($this->sDataFile, serialize($this->pages));
	}

	function read()
	{
		if(file_exists($this->sDataFile))
		{
			$this->pages = unserialize(file_get_contents($this->sDataFile));
			return true;
		}

		return false;
	}

	function getRootPage()
	{
		return new CDynSitemapNode(&$this->pages["/"]);
	}
}

class CDynSitemapNode
{
	var $node;

	function CDynSitemapNode($nodeRef)
	{
		$this->node = $nodeRef;
	}

	function getNumPages()
	{
		return count($this->node['subpages']);
	}

	function getPage($ind)
	{
		$i = 0;
		foreach ($this->node['subpages'] as $key=>$subpage)
		{
			if ($i == $ind)
				return new CDynSitemapNode(&$this->node['subpages'][$key]);
			$i++;
		}
	}

	function isBrowsable()
	{
		return ($this->node['browsable']=="t") ? true : false;
	}

	function getLink($escape=false)
	{
		$link = $this->node['link'];

		if ($escape)
		{
			$link = str_replace("&", "&amp;", $link);
			$link = str_replace("'", "&apos;", $link);
			$link = str_replace("\"", "&quot;", $link);
			$link = str_replace(">", "&gt;", $link);
			$link = str_replace("<", "&lt;", $link);
		}
		return $link;
	}

	function getTitle()
	{
		if ($this->node['title'])
		{
			return $this->node['title'];
		}
		else
		{
			$parts = explode("/", $this->node['link']);
			return $parts[count($parts)-1];
		}
	}
}
?>
