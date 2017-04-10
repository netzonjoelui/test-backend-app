<?php
/*======================================================================================
	
	Module:		AntApi_ContentFeed

	Purpose:	Will read and parse ANT Feeds

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.
	
	Depends:	CPageCache.php

	Usage:		// Create a new instance of parser - The last two params are optional
				$feedReader = new AntApi_ContentFeed("http://pathtofeed", "date DESC", "date='1/1/2007'");

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
				$feedReader = new AntApi_ContentFeed("http://pathtofeed", "time_entered DESC", "date='1/1/2007'", null, $ALIB_WF_PUSHED);

	Variables:	

======================================================================================*/
// Define constants
$ALIB_WF_PUSHED = -1;
$ALIB_WF_ALWAYS = 0;

class AntApi_ContentFeedPost
{
	var $obj = null;
	var $m_fixHtml = true;

	function AntApi_ContentFeedPost($postobj)
	{
		$this->obj = $postobj;
	}

	function getValue($fname)
	{
		$val = $this->obj->getValue($fname);
		$val = $this->htmlEncodeStrict($val);
		return $val;
	}

	function htmlEncodeStrict($val, $type="html", $enc="utf8")
	{
		if ($type == "html" && !is_array($val))
		{
			$val = $val;
			//$val = $this->htmlButTags($val);
			$val = preg_replace("/<(img|hr|br|base|frame|input)([^>]*)>/mi", "<$1$2 />", $val); 
			$val = str_replace("<br>", "<br />", $val);
		}
		
		return $val;
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

class AntApi_ContentFeed
{
	var $id = null; // Feed ID
	var $username = "";
	var $password = "";
	var $server = "";
	var $objListPosts = null;
	
	function __construct($server, $username, $password, $feed_id)
	{
		$this->id = $feed_id;
		$this->username = $username;
		$this->password = $password;
		$this->server = $server;
		$this->objListPosts = new CAntApiObjectList($this->server, $this->username, $this->password, "content_feed_post");
		$this->objListPosts->addCondition("and", "feed_id", "is_equal", $feed_id);
		$this->objListPosts->addCondition("and", "f_publish", "is_equal", 't');
	}

	function __destruct() 
	{
	}

	/*************************************************************************************
	*	Function:	addCondition	
	*
	*	Purpose:	Add a condition to this query
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:string	 - value to set attribute to
	**************************************************************************************/
	function addCondition($blogic, $fieldname, $operator, $condvalue)
	{
		$this->objListPosts->addCondition($blogic, $fieldname, $operator, $condvalue);
	}

	/*************************************************************************************
	*	Function:	addSortOrder	
	*
	*	Purpose:	Add a condition to this query
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:string	 - value to set attribute to
	**************************************************************************************/
	function addSortOrder($field, $direction="ASC")
	{
		$this->objListPosts->addSortOrder($field, $direction);
	}

	/*************************************************************************************
	*	Function:	getPosts
	*
	*	Purpose:	Execute query and get post results
	*
	*	Arguments:	offset:int	- Start offset
	*				limit:int	- maximum number of objects to pull
	**************************************************************************************/
	function getPosts($offset=0, $limit=null)
	{
		return $this->objListPosts->getObjects($offset, $limit);
	}

	/*************************************************************************************
	*	Function:	getPost	
	*
	*	Purpose:	Get the post at the specificied index
	*
	*	Arguments:	idx:int	- Index of post to retrieve
	*
	**************************************************************************************/
	function getPost($idx)
	{
		$obj = $this->objListPosts->getObject($idx);
		$post = new AntApi_ContentFeedPost($obj);
		return  $post;
	}

	/*************************************************************************************
	*	Function:	getPostById
	*
	*	Purpose:	Get a post by id
	*
	*	Arguments:	idx:int	- Index of post to retrieve
	*
	**************************************************************************************/
	function getPostById($id)
	{
		$obj = new AntApi_Object($this->server, $this->username, $this->password, "content_feed_post");
		$obj->open($id);
		$post = new AntApi_ContentFeedPost($obj);
		return  $post;
	}

	/*************************************************************************************
	*	Function:	getNumPosts	
	*
	*	Purpose:	Get number of posts that have been pulled in this set - not the total
	**************************************************************************************/
	function getNumPosts()
	{
		return $this->objListPosts->getNumObjects();
	}

	/**
	 * Get RSS rendered version of this feed
	 *
	 * @param string $urlBase If set then link will be $rulBase + uname of the posting
	 */
	public function renderRss($urlBase="")
	{
		// Just grab the rss from netric
	}
}

/* The below is used only for backwards compatibility */
class CContentFeed extends AntApi_ContentFeed
{
}
/* The below is used only for backwards compatibility */
class CContentFeedPost extends AntApi_ContentFeedPost
{
}
