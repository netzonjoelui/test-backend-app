<?php
/**
 * This class handles an individual blog posting which is really just a content feed post
 */

/**
 * Blog post class
 */
class AntApi_BlogPost
{
	var $fid;
	var $pid;
	var $id;
	var $title;
	var $date;
	var $body;
	var $attribs;
	var $obj;
	var $m_user;
	var $m_pass;
	var $m_server;

	/**
	 * Class constructor
	 *
	 * @param string $server The url of the antserver
	 * @param string $username The name of a user with API access
	 * @param string $password The password of the user in question
	 * @param int $fid The content feed ID in netric
	 * @param string $pid The post id in netric. Can be a unique id or the uname
	 */
	public function __construct($server, $username, $password, $fid, $pid)
	{
		$this->fid = $fid;
		$this->pid = $pid;
		$this->m_user = $username;
		$this->m_pass = $password;
		$this->m_server = $server;
		$this->attribs = array();

		$this->obj = new AntApi_Object($server, $username, $password, "content_feed_post");
		$this->obj->open($pid);
		$this->id = $this->obj->getValue("id");
		$this->title = $this->obj->getValue("title");
		$this->body = $this->obj->getValue("data");
		$this->date = $this->obj->getValue("time_entered");

		if ($this->date)
			$this->date = date("M jS Y \\a\\t h:i a", strtotime($this->date));
	}

	/**
	 * Get the author of this blog post
	 *
	 * The author can be manually entered or will default to the owner name of the posting
	 *
	 * @return string the name of the author
	 */
	public function getAuthor()
	{
		if ($this->obj->getValue("author"))
			return $this->obj->getValue("author");

		if ($this->obj->getValue("user_id"))
			return $this->obj->getValue("user_id", true); // Get foreign value

		// Failover
		return "Unknown";
	}
	
	/**
	 * Get a short text only description of the post
	 *
	 * @return string
	 */
	public function getShortDescription()
	{
		return substr(strip_tags($this->obj->getValue("data")), 0, 512);
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
			return $this->obj->getValue($name);
			break;
		}

		return "";
	}

	function printSocialPromos()
	{
		global $_SERVER;

		echo $this->getSocialPromos();
	}

	/**
	 * Create social links for this blog post / page
	 */
	public function getSocialPromos()
	{
		global $_SERVER;

		$buf = "";

		// Print digg inline javascript
		$buf .= '<script type="text/javascript">
				(function() {
				var s = document.createElement("SCRIPT"), s1 = document.getElementsByTagName("SCRIPT")[0];
				s.type = "text/javascript";
				s.async = true;
				s.src = "http://widgets.digg.com/buttons.js";
				s1.parentNode.insertBefore(s, s1);
				})();
				</script>';
		// Print twitter inline javascript
		$buf .= '<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>';

		$buf .= "<table><tr>";
		$buf .= '<td>
					<a class="DiggThisButton DiggCompact"></a> 
					<a href="http://twitter.com/share" class="twitter-share-button" data-count="horizontal">Tweet</a>
				 </td>';
		$buf .= "<tr><td><iframe src=\"http://www.facebook.com/widgets/like.php?href=".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']."\"
					scrolling=\"no\" frameborder=\"0\"
					style=\"border:none; width:400px; height:25px\"></iframe></td></tr>";
		$buf .= "</tr></table>";

		return $buf;
	}

	function getComments()
	{
		$comments = array();

		$list = new AntApi_ObjectList($this->m_server, $this->m_user, $this->m_pass, "comment");
		$list->addCondition("and", "associations", "is_equal", "content_feed_post:".$this->id);
		$list->addSortOrder("ts_entered");
		$list->getObjects(0, 1000);
		for ($i = 0; $i < $list->getNumObjects(); $i++)
		{
			$obj = $list->getObject($i);
			if ($obj)
			{
				$comment = new stdClass();
				$comment->timeEntered = ($obj->getValue("ts_entered")) ? date("M jS Y \\a\\t h:i a", strtotime($obj->getValue("ts_entered"))) : "";
				$comment->sentBy = $obj->getForeignValue("sent_by");
				if (strpos($comment->sentBy, ":")!==false) // Take away object type qualifier
				{
					$parts = explode(":", $comment->sentBy);
					if (count($parts) > 1)
						$comment->sentBy = $parts[1];
				}

				if ($comment->sentBy == "Untitled")
					$comment->sentBy = "Anonymous";

				$comment->body = $obj->getValue("comment");
				$comments[] = $comment;
			}
		}

		return $comments;
	}

	function printComments()
	{
		$comments = $this->getComments();
		foreach ($comments as $comment)
		{
			echo "<div><strong>".$comment->sentBy."</strong> ".$comment->timeEntered." said:</div>";
			echo "<p>";
			echo $comment->body;
			echo "</p>";
			}
	}

	function saveComment($comment, $sent_by)
	{
		if ($sent_by && $comment && $this->id && $this->fid)
		{
			$obja = new AntApi_Object($this->m_server, $this->m_user, $this->m_pass, "comment");
			$obja->setValue("obj_reference", "content_feed_post:".$this->id);
			$obja->setValue("comment", $comment);
			$obja->setMValue("associations", "content_feed:".$this->fid);
			$obja->setMValue("associations", "content_feed_post:".$this->id);
			$obja->setValue("sent_by", $sent_by); // Anonymous if not set
			$obja->setValue("owner_id", "-4"); // Anonymous
			$obja->save();
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
			echo "<input type='hidden' name='sent_by' value='".$this->pid."'>";
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
