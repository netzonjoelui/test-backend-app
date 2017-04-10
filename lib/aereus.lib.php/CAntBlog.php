<?php
/*======================================================================================
	
	Module:		CAntBLog	

	Purpose:	Remote API for ANT blogs using content feeds

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		

	Variables:	

======================================================================================*/
class CAntBLog
{
	var $fid; // feed id
	var $aid; // article id
	var $m_resp; // raw response cache
	var $m_url; // base URL
	var $m_urlGet; // base URL
	var $m_user;
	var $m_pass;
	var $m_server;
	var $lastErrorMessage;
	var $m_arrPosts;
	var $m_arrCategories;
	
	function CAntBLog($server, $username, $password, $fid, $aid=null) 
	{
		$this->m_user = $username;
		$this->m_pass = $password;
		$this->m_server = $server;
		$this->m_url = "http://".$server."/content/wapi.php?auth=".base64_encode($username).":".md5($password);
		$this->m_urlGet = "http://".$server."/objects/xml_get_object.php?auth=".base64_encode($username).":".md5($password)."&obj_type=$obj_type";
		$this->aid = $aid;
		$this->fid = $fid;
		$this->m_arrPosts = array();
		$this->m_arrCategories = array();

		$this->handleFormSubmission();
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
			$obja = new CAntObjectApi($this->m_server, $this->m_user, $this->m_pass, "comment");
			$obja->setValue("obj_reference", "content_feed_post:".$_POST['post_id']);
			$obja->setValue("comment", stripslashes($comment));
			//$obja->setValue("user_name_cache", $_POST['name']." ".$_POST['email']);
			$obja->setMValue("associations", "content_feed:".$_POST['feed_id']);
			$obja->setMValue("associations", "content_feed_post:".$_POST['post_id']);
			$obja->setValue("sent_by", (($_POST['sent_by'])?$_POST['sent_by']:"user:-4")); // Anonymous if not set
			$obja->setValue("owner_id", "-4"); // Anonymous
			$obja->save();
		}
	}

	/*************************************************************************************
	*	Function:	getPosts
	*
	*	Purpose:	Get posts for this blog / feed
	*
	*	Params:		$category = category id to filter
	*
	**************************************************************************************/
	function getPosts($category=null)
	{
		$cond = ($category) ? "category=$category" : null;
		$this->m_arrPosts = array();
		$feedReader = new CFeedReader("http://".$this->m_server."/feeds/?fid=".$this->fid, "time_entered DESC", $cond); //, null, $ALIB_WF_PUSHED
		$num = $feedReader->getNumPosts();
		for ($i = 0; $i < $num; $i++)
		{
			$this->m_arrPosts[$i] = $feedReader->getPostVarValue('id', $i);
		}	
		return count($this->m_arrPosts);
	}

	/*************************************************************************************
	*	Function:	getPost	
	*
	*	Purpose:	Return a CAntBlogPost object
	*
	**************************************************************************************/
	function getPost($ind)
	{
		return new CAntBlogPost($this->m_server, $this->m_user, $this->m_pass, $this->fid, $this->m_arrPosts[$ind]);
	}

	/*************************************************************************************
	*	Function:	getCategories
	*
	*	Purpose:	Get posts for this blog / feed
	*
	**************************************************************************************/
	function getCategories()
	{
		$url = $this->m_url;
		$url .= "&function=feed_get_categories&feed_id=".$this->fid;

		//$ch = curl_init("http://".$this->m_server."/content/wapi.php?function=feed_get_categories&feed_id=".$this->fid); // URL of gateway for cURL to post to
		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		//curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		#curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "category") 
			{
				$cat = array();
				foreach ($node->childNodes as $catnode) 
				{
					if ($catnode->nodeType == 1)
					{
						$cat[$catnode->nodeName] = rawurldecode($catnode->textContent);
					}
				}
				$this->m_arrCategories[] = $cat;
			}
		}
	
		return count($this->m_arrCategories);
	}

	/*************************************************************************************
	*	Function:	getCategory	
	*
	*	Purpose:	Return a CAntBlogPost object
	*
	**************************************************************************************/
	function getCategory($ind)
	{
		return $this->m_arrCategories[$ind];
	}

	/*************************************************************************************
	*	Function:	getPostById
	*
	*	Purpose:	Return a CAntBlogPost object
	*
	**************************************************************************************/
	function getPostById($pid)
	{
		return new CAntBlogPost($this->m_server, $this->m_user, $this->m_pass, $this->fid, $pid);
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
	*	Purpose:	Return a CAntBlogPost object
	*
	**************************************************************************************
	function getPost($ind)
	{
		return new CAntBlogPost($this->m_server, $this->m_user, $this->m_pass, $this->fid, $this->m_arrPosts[$ind]);
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
			echo "CAntBLog::save: ".$e->getMessage()." ------ ".$resp;
		}

		return 0;
	}
}

class CAntBlogPost
{
	var $fid;
	var $pid;
	var $id;
	var $title;
	var $date;
	var $body;
	var $attribs;
	var $feedReader;
	var $m_user;
	var $m_pass;
	var $m_server;

	function CAntBlogPost($server, $username, $password, $fid, $pid)
	{
		$this->fid = $fid;
		$this->pid = $pid;
		$this->m_user = $username;
		$this->m_pass = $password;
		$this->m_server = $server;
		$this->attribs = array();

		$feedReader = new CFeedReader("http://$server/feeds/?fid=$fid", "time_entered DESC", "id=".$pid); //, null, $ALIB_WF_PUSHED
		if ($feedReader->getNumPosts())
		{
			$this->id = $feedReader->getPostVarValue('id', 0);
			$this->title = $feedReader->getPostVarValue('title', 0);
			$this->body = $feedReader->getPostVarValue('body', 0);
			$this->date = $feedReader->getPostVarValue('time_entered', 0);
			$video = $feedReader->getPostVarValue('video', 0);
			// Format date
			if ($this->date)
				$this->date = date("M jS Y \\a\\t h:i a", strtotime($this->date));
			$this->feedReader = $feedReader;
		}
	}

	function getValue($name)
	{
		switch ($name)
		{
		case 'id':
			return $this->id;
			break;
		case 'title':
			return $this->title;
			break;
		case 'date':
			return $this->date;
			break;
		case 'body':
			return $this->body;
			break;
		default:
			return $this->feedReader->getPostVarValue($name, 0);
			break;
		}

		return "";
	}

	function printSocialPromos()
	{
		global $_SERVER;

		// Print digg inline javascript
		echo '<script type="text/javascript">
				(function() {
				var s = document.createElement("SCRIPT"), s1 = document.getElementsByTagName("SCRIPT")[0];
				s.type = "text/javascript";
				s.async = true;
				s.src = "http://widgets.digg.com/buttons.js";
				s1.parentNode.insertBefore(s, s1);
				})();
				</script>';
		// Print twitter inline javascript
		echo '<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>';

		echo "<table><tr>";
		echo '<td>
					<a class="DiggThisButton DiggCompact"></a> 
					<a href="http://twitter.com/share" class="twitter-share-button" data-count="horizontal">Tweet</a>
				 </td>';
		echo "<tr><td><iframe src=\"http://www.facebook.com/widgets/like.php?href=".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']."\"
					scrolling=\"no\" frameborder=\"0\"
					style=\"border:none; width:400px; height:25px\"></iframe></td></tr>";
		echo "</tr></table>";
	}

	function printComments()
	{
		$list = new CAntObjectListApi($this->m_server, $this->m_user, $this->m_pass, "comment");
		$list->addCondition("and", "associations", "is_equal", "content_feed_post:".$this->pid);
		$list->addSortOrder("ts_entered");
		$list->getObjects();
		for ($i = 0; $i < $list->getNumObjects(); $i++)
		{
			$obj = $list->getObject($i);
			if ($obj)
			{
				if ($obj->getValue("ts_entered"))
					$time = "on ".date("M jS Y \\a\\t h:i a", strtotime($obj->getValue("ts_entered")));
				else
					$time = "";

				$sent_by = $obj->getForeignValue("sent_by");
				if (strpos($sent_by, ":")!==false)
				{
					$parts = explode(":", $sent_by);
					if (count($parts) > 1)
						$sent_by = $parts[1];
				}

				echo "<div><strong>".$sent_by."</strong> $time said:</div>";
				echo "<p>";
				echo $obj->getValue("comment");
				echo "</p>";
			}
		}
	}

	function printCommentForm($allowanonymous=false)
	{
		global $_SERVER, $_SESSION;

		if ($_SESSION['LOGIN_NAME'] && $_SESSION['LOGIN_CUSTOMER_ID'])
		{
			echo "<form name='frm_comment' method='post' action='".$_SERVER['REQUEST_URI']."'>";
			echo "<input type='hidden' name='feed_id' value='".$this->fid."'>";
			echo "<input type='hidden' name='post_id' value='".$this->pid."'>";
			echo "<input type='hidden' name='sent_by' value='customer:".$_SESSION['LOGIN_CUSTOMER_ID']."'>";
			echo "<div><textarea name='comment' style='width:98%;height:50px;'></textarea></div>";
			//echo "<input type='hidden' name='inform[]' value='".$row['id']."'>";
			echo "<input type='submit' name='add_comment' value='Post Comment'>";
			echo "</form>";
		}
		else if ($allowanonymous)
		{
			echo "<form name='frm_comment' method='post' action='".$_SERVER['REQUEST_URI']."'>";
			echo "<input type='hidden' name='feed_id' value='".$this->fid."'>";
			echo "<input type='hidden' name='post_id' value='".$this->pid."'>";
			echo "<input type='hidden' name='sent_by' value='"."'>";
			echo "<div class='g4 inside' style='margin-bottom:10px;'>Your Name: <input type='text' style='width:210px;' name='name'></div>";
			echo "<div class='g5' style='margin-bottom:10px;'>Your Email:  <input type='text' style='width:210px;' name='email'> (requred)</div>";
			echo "<div><textarea name='comment' style='width:98%;height:50px;'></textarea></div>";
			//echo "<input type='hidden' name='inform[]' value='".$row['id']."'>";
			echo "<input type='submit' name='add_comment' value='Post Comment'>";
			echo "</form>";
		}
		else
		{
			echo "<p class='notice'><a href='/login?goto=".base64_encode($_SERVER['REQUEST_URI'])."'>Sign in to comment on this article</a>.</p>";
		}
	}
}
?>
