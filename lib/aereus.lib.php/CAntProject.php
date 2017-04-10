<?php
/*======================================================================================
	
	Module:		CAntProject	

	Purpose:	Remote API for ANT Project

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.
	
	Depends:	CAntProject.php

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
				$feedReader = new CFeedReader("http://pathtofeed", "date DESC", "date='1/1/2007'", null, $ALIB_WF_PUSHED);

	Variables:	

======================================================================================*/
class CAntProject
{
	var $m_url;
	var $m_attribs;
	var $m_bugs;
	
	function CAntProject($url, $pid, $username, $password) 
	{
		$this->m_url = $url . "?pid=$pid";
		$this->m_attribs = array();

		$url = $this->m_url;
		$url .= "&function=get_project_details";

		$dom = new DomDocument();

		$dom->load($url); 

		foreach ($dom->documentElement->childNodes as $project) 
		{
			//if node is an element (nodeType == 1) and the name is "item" loop further
			if ($project->nodeType == 1)
			{
				switch ($project->nodeName)
				{
				default:
					$this->m_attribs[$project->nodeName] = rawurldecode($project->textContent);
					break;
				}
			}
		} 
	}

	function __destruct() 
	{
	}

	function getBugs($bid = null) 
	{
		unset($this->m_bugs);
		$this->m_bugs = array();

		$url = $this->m_url;
		$url .= "&function=get_bugs";
		if ($bid)
			$url .= "&bid=$bid";

		$dom = new DomDocument();

		$dom->load($url); 

		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "bug") 
			{
				$b = array();

				foreach ($node->childNodes  as $item) 
				{
					//if node is an element and the name is "title", print it.
					if ($item->nodeType == 1)
					{
						if ($item->nodeName == "comments")
						{
							$b['comments'] = array();
							

							foreach ($item->childNodes as $comment)
							{
								if ($comment->nodeName == "comment")
								{
									$c = array();

									foreach ($comment->childNodes as $attrib)
										$c[$attrib->nodeName] = rawurldecode($attrib->textContent);

									$b['comments'][] = $c;
								}
							}
						}
						else
						{
							$b[$item->nodeName] = rawurldecode($item->textContent);
						}
					}
				}

				$this->m_bugs[] = $b;
			}
		} 

		return $this->m_bugs;
	}

	function addBugComment($bid, $username, $comment)
	{
		$url = $this->m_url;
		$url .= "&bid=$bid&function=add_bug_comment&username=$username&comment=".rawurlencode($comment);

		$dom = new DomDocument();

		$dom->load($url); 
	}

	function addBug($username, $attributes)
	{
		$title = rawurlencode($attributes['title']);
		$description = rawurlencode($attributes['description']);
		$status = rawurlencode($attributes['status']);
		$severity = rawurlencode($attributes['severity']);
		$type = rawurlencode($attributes['type']);

		$url = $this->m_url;
		$url .= "&function=add_bug&username=$username";


		$fields = "";
		foreach( $attributes as $key => $value ) 
			$fields .= "$key=" . urlencode( $value ) . "&";

		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "retval") 
				return rawurldecode($node->textContent);
		}

		return 0;
	}

	function getAttribute($name)
	{
		return $this->m_attribs[$name];
	}
}

// Functions
if ($_GET['function'])
{
	$capi = new CAntProject("http://testserv.aereus.com/customer/wapi.php", "test", "Test");
	///$projects = $capi->getProjects(10003);

	foreach ($projects as $project)
		echo $project['name']."<br>";
}
?>
