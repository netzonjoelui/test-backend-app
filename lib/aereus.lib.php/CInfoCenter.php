<?php
/*======================================================================================
	
	Module:		DEPRICATED: CInfoCenter - use antapi/InfoCenter

	Purpose:	Will read and parse ANT CInfoCenter Documents - ANT WIKI

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2008 Aereus Corporation. All rights reserved.
	
	Depends:	CPageCache.php

	Usage:		

	Variables:	

======================================================================================*/
// Define constants

class CInfoCenterDoc
{
	var $id;
	var $body;
	var $title;
	var $videoFileId;
	var $relatedDocuments;

	function CInfoCenter()
	{
		$this->relatedDocuments = array();
	}
}

class CInfoCenter
{
	var $m_url;
	var $m_documents;

	function CInfoCenter($url, $account) 
	{
		$this->m_url = "http://".$url."/wapi/infocenter";
		$this->m_documents = array();
	}

	function __destruct() 
	{
	}

	function getDocument($docid)
	{
		$docs = array();
		$docs = $this->getDocuments(null, $docid);
		return $docs[0];
	}

	function getDocuments($gid=null, $docid=null, $searchstr="")
	{
		$url = $this->m_url;
		$url .= "?function=get_documents";
		if ($docid)
			$url .= "&docid=$docid";
		if ($gid)
			$url .= "&gid=$gid";
		if ($searchstr)
			$url .= "&search=".rawurlencode($searchstr);

		$dom = new DomDocument();

		$dom->load($url);

		$ret_docs = array();
	
		foreach ($dom->documentElement->childNodes as $docs) 
		{
			$ci_doc = new CInfoCenterDoc();
			foreach ($docs->childNodes as $doc)
			{
				if ($doc->nodeType == 1)
				{
					switch ($doc->nodeName)
					{
					case 'id':
						$ci_doc->id = $doc->textContent;
						break;
					case 'title':
						$ci_doc->title = rawurldecode($doc->textContent);
						break;
					case 'body':
						$ci_doc->body = rawurldecode($doc->textContent);
						break;
					case 'video_file_id':
						$ci_doc->videoFileId = rawurldecode($doc->textContent);
						break;
					case 'related_documents':
						foreach ($doc->childNodes as $rdoc)
						{
							$ci_doc->relatedDocuments[] = array("id"=>$rdoc->getAttribute('id'), "title"=>rawurldecode($rdoc->textContent));
						}	
						break;
					default:
						//$this->m_attribs[$project->nodeName] = rawurldecode($project->textContent);
						break;
					}
				}
			}
			if ($ci_doc->id)
			{
				$ret_docs[] = $ci_doc;
			}
			$ci_doc = null;
		} 

		return $ret_docs;
	}

	function getGroups($gid)
	{
		if (!$gid)
			return false;

		$ret_groups = array();

		$url = $this->m_url;
		$url .= "?function=get_groups&gid=$gid";


		$dom = new DomDocument();

		$dom->load($url);

		foreach ($dom->documentElement->childNodes as $groups) 
		{
			$id = "";
			$name = "";

			foreach ($groups->childNodes as $grp)
			{
				//if node is an element (nodeType == 1) and the name is "item" loop further
				if ($grp->nodeType == 1)
				{
					switch ($grp->nodeName)
					{
					case 'id':
						$id = $grp->textContent;
						break;
					case 'name':
						$name = rawurldecode($grp->textContent);
						break;
					}
				}
			}

			if ($id && $name)
				$ret_groups["$id"] = $name;
		} 

		return $ret_groups;
	}

	function getGroupName($gid)
	{
		if (!$gid)
			return false;

		$ret = "Untitled";

		$url = $this->m_url;
		$url .= "?function=get_group_name&gid=$gid";


		$dom = new DomDocument();

		$dom->load($url);

		foreach ($dom->documentElement->childNodes as $groups) 
		{
			$id = "";
			$name = "";

			foreach ($groups->childNodes as $grp)
			{
				//if node is an element (nodeType == 1) and the name is "item" loop further
				if ($grp->nodeType == 1)
				{
					switch ($grp->nodeName)
					{
					case 'name':
						$ret = rawurldecode($grp->textContent);
						break;
					}
				}
			}
		} 

		return $ret;
	}

	function getGroupInfo($gid)
	{
		if (!$gid)
			return false;

		$ret = array();

		$url = $this->m_url;
		$url .= "?function=get_group_info&gid=$gid";


		$dom = new DomDocument();

		$dom->load($url);

		foreach ($dom->documentElement->childNodes as $groups) 
		{
			$id = "";
			$name = "";
			$parent = "";

			foreach ($groups->childNodes as $grp)
			{
				//if node is an element (nodeType == 1) and the name is "item" loop further
				if ($grp->nodeType == 1)
				{
					switch ($grp->nodeName)
					{
					case 'id':
						$id = $grp->textContent;
						break;
					case 'name':
						$name = rawurldecode($grp->textContent);
						break;
					case 'parent_id':
						$parent = rawurldecode($grp->textContent);
						break;
					}
				}
			}

			if ($id && $name)
			{
				$ret['id'] = $id;
				$ret['name'] = $name;
				$ret['parent_id'] = $parent;
			}

		} 

		return $ret;
	}
}

if ($_GET['test'])
{
	$cic = new CInfoCenter("ant.aereus.com", "aereus");
	//$groups = $cic->getGroups(3);
	//foreach ($groups as $gid=>$gname)
	//	echo "$gid-$gname<br />";
	
	$docs = $cic->getDocuments(3);
	foreach ($docs as $doc)
		echo $doc->title."<br />";
}
?>
