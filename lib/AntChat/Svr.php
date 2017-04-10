<?php
abstract class AntChat_Svr
{
	/**
	 * Determines if the class is run by unit test
	 *
	 * @var Boolean
	 */
	public $testMode = false;

	abstract public function addFriend($args);
	
	abstract public function deleteFriend($args);
	
	abstract public function getFriendList($args);
	
	abstract public function getUserDetails();
	
	abstract public function saveMessage($args);
	
	abstract public function getMessage($args);
	
	abstract public function saveChatSession($args);
	
	abstract public function clearChatSession($args);
	
	abstract public function getChatSession($args);
	
	abstract public function getNewMessages($args);
	
	abstract public function removeOldMessage($args);
	
	abstract public function countFriendOnline($args);
	
	abstract public function getPrevChat($args);
	
	abstract public function processStatus($args);
}
