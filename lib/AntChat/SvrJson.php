<?php
/**
* Json Chat Server Class for AntChat
*
* This main purpose of this class is to create functions for AntChat Module
* that will return json encoded data
*
* @category  AntChat
* @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
*/

require_once("lib/AntChat/Svr.php");
require_once("lib/AntChat.php");    

class AntChat_SvrJson extends AntChat_Svr
{           
	var $antchat;

	function __construct($ant, $user)
	{
		$dbh = $ant->dbh;
		$this->antchat = new AntChat($dbh, $user);
	}

	/**
	* Add friend to chat friend list
	*/
	public function addFriend($args)
	{
		$retVal = $this->antchat->addFriend($args);            
		if ($retVal)
		{                
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);                
			if(!$this->testMode)
				echo $retValJson;
			
			return $retVal;
		}            
	}

	/**
	* Delete friend to chat friend list
	*/
	public function deleteFriend($args)
	{            
		$retVal = $this->antchat->deleteFriend($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}

	/**
	* Get chat friend list
	*/
	public function getFriendList($args)
	{            
		$retVal = $this->antchat->getFriendList($args);            
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
		}
		return $retVal;
	}
	
	/**
	* Get user details
	*/
	public function getUserDetails()
	{            
		$retVal = $this->antchat->getUserDetails();
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	/**
	* Save user message
	*/
	public function saveMessage($args)
	{            
		$retVal = $this->antchat->saveMessage($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}

	/**
	* Get user message
	*/
	public function getMessage($args)
	{            
		$retVal = $this->antchat->getMessage($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	/**
	* save isTyping state
	*/
	public function saveChatSession($args)
	{            
		$retVal = $this->antchat->saveChatSession($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	/**
	* clears the chat session
	*/
	public function clearChatSession($args)
	{            
		$retVal = $this->antchat->clearChatSession($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	/**
	* get isTyping state
	*/
	public function getChatSession($args)
	{            
		$retVal = $this->antchat->getChatSession($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	/**
	* Retrieve the all new chat messages from other users
	* This function can only be called at ANT Chat Messenger
	*/
	public function getNewMessages($args)
	{            
		$retVal = $this->antchat->getNewMessages($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	/**
	* Removes the messages that are 2 days old or more
	*/
	public function removeOldMessage($args)
	{
		$retVal = $this->antchat->removeOldMessage($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	/**
	* Count friends online
	*/
	public function countFriendOnline($args)
	{            
		$retVal = $this->antchat->countFriendOnline($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	/**
	* Get prev chat
	*/
	public function getPrevChat($args)
	{            
		$retVal = $this->antchat->getPrevChat($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
	
	public function processStatus($args)
	{
		$retVal = $this->antchat->processStatus($args);
		if ($retVal)
		{
			if(!is_array($retVal))
				$retVal = array("retVal"=>$retVal);
				
			$retValJson = json_encode($retVal);
			if(!$this->testMode)
				echo $retValJson;
				
			return $retVal;
		}
	}
}
