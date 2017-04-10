<?php
/*======================================================================================
	
	Module:		AntApi_Blog	

	Purpose:	Remote API for ANT blogs using content feeds

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		

	Variables:	

======================================================================================*/
class AntApi_Blog
{
	var $fid; // feed id
	var $username;
	var $password;
	var $server;
	var $lastErrorMessage;
	var $objListPosts;
	var $categories = null; // Once categories are pulled, this will become an array
	
	function __construct($server, $username, $password, $feed_id) 
	{
		$this->username = $username;
		$this->password = $password;
		$this->server = $server;
		$this->fid = $feed_id;

		$this->objListPosts = new AntApi_ObjectList($this->server, $this->username, $this->password, "content_feed_post");
		$this->objListPosts->addCondition("and", "feed_id", "is_equal", $feed_id);
		$this->objListPosts->addCondition("and", "f_publish", "is_equal", 't');
		$this->objListPosts->addCondition("and", "time_publish", "is_equal", '');
		$this->objListPosts->addCondition("or", "time_publish", "is_greater_or_equal", date("m/d/Y"));
	}

	function __destruct() 
	{
	}

	/*************************************************************************************
	*	Function:	handleFormSubmission	
	*
	*	Purpose:	Check for added comments
	*
	**************************************************************************************/
	function handleFormSubmission()
	{
		global $_POST;

		if ($_POST['comment'] && ($_POST['sent_by'] || ($_POST['name'] && $_POST['email'])) && $_POST['post_id'] && $_POST['feed_id'])
		{
			$comment = ($_POST['sent_by']) ? $_POST['comment'] : $_POST['name']. " wrote:\n".$_POST['comment'];
			$obja = new AntApi_Object($this->server, $this->username, $this->password, "comment");
			$obja->setValue("obj_reference", "content_feed_post:".$_POST['post_id']);
			$obja->setValue("comment", $comment);
			//$obja->setValue("user_name_cache", $_POST['name']." ".$_POST['email']);
			$obja->setMValue("associations", "content_feed:".$_POST['feed_id']);
			$obja->setMValue("associations", "content_feed_post:".$_POST['post_id']);
			$obja->setValue("sent_by", (($_POST['sent_by'])?$_POST['sent_by']:"user:-4")); // Anonymous if not set
			$obja->setValue("owner_id", "-4"); // Anonymous
			$obja->save();
		}
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
	*	Purpose:	Add sort order to this query
	*
	*	Arguments:	field:string		- name of field to sort by
	*				direction:string	- ascending or descending
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
	function getPosts($offset=0, $limit=1000)
	{
		$ret = $this->objListPosts->getObjects($offset, $limit);
		return $ret;
	}

	/*************************************************************************************
	*	Function:	getPost	
	*
	*	Purpose:	Return a AntApi_BlogPost object
	*
	**************************************************************************************/
	function getPost($ind)
	{
		$prow = $this->objListPosts->getObjectMin($ind);
		return new AntApi_BlogPost($this->server, $this->username, $this->password, $this->fid, $prow['id']);
	}

	/*************************************************************************************
	*	Function:	getCategories
	*
	*	Purpose:	Get posts for this blog / feed
	*
	**************************************************************************************/
	function getCategories()
	{
		if (!is_array($this->categories))
		{
			$this->categories = array(); // Initialize

            //$url = "http://".$this->server."/objects/xml_get_groupings.php";
			$url = "http://".$this->server."/api/php/Object/getGroupings";
            $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
			$url .= "&obj_type=content_feed_post";
			$url .= "&field=categories";
			// Add filer
			$url .= "&feed_id=".$this->fid;

			$ch = curl_init($url); // URL of gateway for cURL to post to
			curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			$resp = curl_exec($ch); //execute post and get results
			curl_close ($ch);
            
            $ret = json_decode($resp, true);
            foreach($ret as $key=>$category)
            {
                $cat = new stdClass();
                $cat->id = $category["id"];
                $cat->name = $category["viewname"];
                $cat->title = $category["title"];

                $this->categories[] = $cat;
            }
		}

		return $this->categories;
	}

	/*************************************************************************************
	*	Function:	getCategory	
	*
	*	Purpose:	Return a AntApi_BlogPost object
	*
	**************************************************************************************/
	function getCategory($ind)
	{
		return $this->m_arrCategories[$ind];
	}

	/*************************************************************************************
	*	Function:	getPostById
	*
	*	Purpose:	Return a AntApi_BlogPost object
	*
	**************************************************************************************/
	function getPostById($pid)
	{
		return new AntApi_BlogPost($this->server, $this->username, $this->password, $this->fid, $pid);
	}

	/*************************************************************************************
	*	Function:	getCategories
	*
	*	Purpose:	Get categories for this blog
	*
	**************************************************************************************
	function getCategories()
	{
		$this->m_arrPosts = array();
		$feedReader = new CFeedReader("http://".$this->m_server."/feeds/?fid=".$this->fid, "time_entered DESC"); //, $cnd, null, $ALIB_WF_PUSHED
		$num = $feedReader->getNumPosts();
		for ($i = 0; $i < $num; $i++)
		{
			$this->m_arrCategories[$i] = $feedReader->getPostVarValue('id', $i);
		}	
		return count($this->m_arrPosts);
	}
	*/

	/*************************************************************************************
	*	Function:	getPost	
	*
	*	Purpose:	Return a AntApi_BlogPost object
	*
	**************************************************************************************
	function getPost($ind)
	{
		return new AntApi_BlogPost($this->m_server, $this->m_user, $this->m_pass, $this->fid, $this->m_arrPosts[$ind]);
	}
	*/

	/*************************************************************************************
	*	Function:	getValue	
	*
	*	Purpose:	Get the value for an attributed
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	**************************************************************************************/
	function getValue($name)
	{
		return $this->m_attribs[$name];
	}

	/*************************************************************************************
	*	Function:	saveChanges	
	*
	*	Purpose:	Save only changed attributes to ANT CRM. On success a valid customer id is returned.
	*				On failure -1 is returned with a message explaining the error.
	**************************************************************************************/
	function saveChanges()
	{
		return $this->save(true);
	}

	/*************************************************************************************
	*	Function:	save	
	*
	*	Purpose:	Saves all attributes to ANT by default. This can be dangerous if customer
	*				is already existing and has not been opened yet. onlychanged=true
	*				allows for updating only of changed attributes.
	*
	*	Arguments:	onlychange:bool - defaults to saving/overwriting all attributes even if blank
	**************************************************************************************/
	function save($onlychange=false)
	{
		$url = $this->m_url;
		$url .= "&function=object_save";
		if ($onlychange)
			$url .= "&onlychange=1";
		if ($this->m_id)
			$this->m_url .= "&id=".$this->m_id;

		$fields = "";
		foreach( $this->m_values as $key => $value ) 
			$fields .= "$key=" . urlencode( $value ) . "&";

		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		$this->m_resp = $resp;
		curl_close ($ch);

		try
		{
			$dom = new DomDocument();
			$dom->loadXml($resp); 
			foreach ($dom->documentElement->childNodes as $node) 
			{
				if ($node->nodeType == 1 && $node->nodeName == "retval") 
				{
					$this->m_id = $node->textContent;
					return rawurldecode($node->textContent);
				}
			}
		}
		catch (Exception $e) 
		{
			echo "AntApi_Blog::save: ".$e->getMessage()." ------ ".$resp;
		}

		return 0;
	}
}
