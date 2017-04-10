<?php
/**
* Chat Server Class that will handle all database functionalities
*
* This main purpose of this class is to create functions
* that will access database.
*
* @category  AntChat
* @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
*/
class AntChat
{
	var $dbh;        
	var $userId;        
	var $userName;        
	var $accountId;
	var $timeDiff;
	var $cache;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Account database
	 * @param AntUser $user Current user
	 */
	public function __construct($dbh, $user)
	{
		$this->dbh = $dbh;
		$this->userId = $user->id;
		$this->userName = $user->name;
		$this->accountId = $user->accountId;
		$this->timeDiff = 60;
		$this->cache = CCache::getInstance();
	}
	
	/**
	 * Add friend to chat friend list
	 *
	 * @param array $args Array of params for adding a chat user including friendName and Server
	 */
	public function addFriend($args)
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;
		$teamId = null;            
		
		if(isset($args['teamId']))
			$teamId = $args['teamId'];
			
		$friendName = $args['friendName'];

		if (strpos($friendName, "@") !== false)
		{
			$parts = explode("@", $friendName);

			$retVal = $this->saveFriend($userId, $parts[0], $parts[1]);
		}
		else
		{
			$query = "select id as user_id, team_id from users where name='$friendName'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result) > 0)
			{
				$row = $dbh->GetNextRow($result, 0);
				$friendId = $row['user_id'];
				
				if ($friendId == $userId)
				{
					$retError = "Cannot add yourself as a friend! Please enter a new name and try again.";
					$retVal = array("retVal" => -1, "retError" => $retError);
				}
				elseif ($row['team_id'] == $teamId)
				{
					$retError = "Cannot add your team mate as a friend! Please enter a new name and try again.";
					$retVal = array("retVal" => -1, "retError" => $retError);
				}
			}
			
			$dbh->FreeResults($result);
			
			if(empty($retError))
			{
				$retVal = $this->saveFriend($userId, $friendName, "", 1);
				
				// Add pending request
				if(!empty($friendId) && !$this->checkFriendExists($friendId, $userName) && $retVal["id"] > 0) // check first if added successfully, and friend entry does not exists yet
					$this->saveFriend($friendId, $userName, "", 2);
			}
		}
		
		return $retVal;
	}
	
	function saveFriend($userId, $friendName, $friendServer, $status=null)
	{
		$dbh = $this->dbh;
		
		$result = $dbh->Query("insert into chat_friends(user_id, friend_name, friend_server, local_name, status) 
								values('$userId', '$friendName', '$friendServer', '$friendName', '$status');
								select currval('chat_friends_id_seq') as id;");
				
		if ($dbh->GetNumberRows($result))
		{
			$id = $dbh->GetValue($result, 0, "id");
			$retVal = array("retVal" => 1, "id" => $id);                        
		}
		else
			$retVal = array("retVal" => -1, "retError" => "Insert Error.");
			
		return $retVal;
	}
	
	/**
	 * Update the team id for a fiend
	 *
	 * @param array $args Array of params including friend_name and team_id
	 * @return - on no update, the id of the team added on success
	 */
	private function updateFriendTeam($args)
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		
		$friendName = $args['friend_name'];
		$teamId = $args['team_id'];
		
		if($teamId > 0)
		{
			$result = $dbh->Query("update chat_friends set team_id = '$teamId' where friend_name='$friendName' and user_id='$userId' RETURNING team_id");            
			$retVal = $dbh->GetValue($result, 0, "team_id");
			$dbh->FreeResults($result);
		}
		else
			$retVal = 0;
		
		return $retVal;
	}

	/**
	 * Delete friend to chat friend list
	 */
	function deleteFriend($args)
	{            
		$dbh = $this->dbh;
		$friendId = $args['friendId'];
		if (is_numeric($friendId))
		{
			$dbh->Query("delete from chat_friends where id='$friendId'");
			$retVal = array("retVal" => 1);
		}
		else
			$retVal = array("retVal" => -1);

		return $retVal;
	}

	/**
	* Get chat friend list
	*/
	function getFriendList($args)
	{            
		$dbh = $this->dbh;
		$userId = $this->userId;
		
		$teamId = null;
		$friendFilter = null;
		$arr_online = array();
		$arr_offline = array();
		
		if(isset($args['teamId']))
			$teamId = $args['teamId'];
		
		if(isset($args['filterId']))
			$friendFilter = " and id = " . $args['filterId'];
		
		$sql = "select id, friend_name, friend_server, team_id, null as image_id, 
			0 as friend_team, NULL as active_timestamp, NULL as checkin_timestamp,
			status from chat_friends where user_id='$userId' $friendFilter";
		
		if($teamId > 0)
		{
			$sql .= " union ";
			$sql .= "select id, name as friend_name, 'localhost' as friend_server, 
					 team_id, image_id, 1 as friend_team, 
					 active_timestamp, 
					 checkin_timestamp,
					 NULL as status 
					 from users where team_id='$teamId' and id != '$userId'
					 order by team_id asc, friend_name";
		}

		$result = $dbh->Query($sql);

		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			
			// joe: Commented this out because it was pegging the db with 220k queries in a 24
			// hour period and it does not appear to be necessary as users are never added to the friends list
			// -------------------------------------------------------------------------------------------
			// update friend team id in chat_friend table
			//$ret = $this->updateFriendTeam($row);
			
			// Only list team members at this point
			if($teamId > 0 && $row['friend_team']==0)
				continue;
			
			// get friendDetails
			//$friendDetails = $this->chatFriendGetStatus($row);

			$checkDiff = (time() - strtotime($row['checkin_timestamp']));
			$actDiff = (time() - strtotime($row['active_timestamp']));
			if($checkDiff > 120) // no checkin for 120 seconds or 2 minutes
			{
				$status = 0;
				$statusText = "Offline";
			}
			else
			{
				if ($actDiff > $this->timeDiff)
					$statusText = "Inactive";
				else
					$statusText = "Available";

				$status = 1;                                
			}

			if($row['image_id'] > 0)
				$image = "/files/images/" . $row['image_id'] . "/40/40";
			else
				$image = "/images/icons/objects/user_48.png";
			
			$row['inviteStatus'] = $row["status"];;
			$row['statusText'] = $statusText;
			$row['fullName'] = $row['friend_name'];
			$row['online'] = $status;
			$row['image'] = $image;
			$row['friendServer'] = $row['friend_server'];
			$row['teamId'] = $row['team_id'];
			
			if ($row['online'] == 1)
				$arr_online[] = $row;
			else
				$arr_offline[] = $row;
		}
		$dbh->FreeResults($result);
		
		if($num>0)
			$retVal = array_merge($arr_online, $arr_offline);
		else
			$retVal = array("retVal" => 1, "teamId" => $teamId);

		return $retVal;
	}

	/**
	* Get chat friend list status and other info
	*/
	function chatFriendGetStatus($friendDetails)
	{
		$dbh = $this->dbh;
		
		$id = $friendDetails['id'];
		$friendName = $friendDetails['friend_name'];
		$teamId = $friendDetails['team_id'];
		
		/*if(empty($friendDetails['active_timestamp']))
			$friendDetails = $this->getUserDetails($friendName);*/
		
		if($friendDetails['image_id'] > 0)
			$image = "/files/images/" . $friendDetails['image_id'] . "/40/40";
		else
			$image = "/images/icons/objects/user_48.png";
		
		$lastTimestamp = $this->cache->get($this->dbh->dbname."/chat/$friendName/lastTimestamp");
		$checkDiff = (time() - $lastTimestamp);
		
		if($checkDiff > $this->timeDiff)
		{
			$status = 0;
			$statusText = "Offline";
		}
		else
		{
			$status = 1;                                
			$statusText = "Available";
		}
		
		if(!empty($friendDetails['full_name']))
			$friendName = $friendDetails['full_name'];
		
		$retVal = array("fullName" => $friendName,
		"online" => $status,
		"statusText" => $statusText,
		"image" => $image,
		"teamId" => $friendDetails['team_id'],
		"active_timestamp" => $friendDetails['active_timestamp']);
		
		return $retVal;
	}
	
	/**
	* Get user details to be used
	*/
	function getUserDetails($userName=null)
	{
		// remove old messages
		$this->removeOldMessage();
		
		$dbh = $this->dbh;
		$userId = $this->userId;
		
		if(!empty($userName))
			$whereClause = "where users.name = '$userName'";
		else
			$whereClause = "where users.id = '$userId'";
		
		$query = "select users.id as user_id, users.team_id, image_id, active_timestamp, user_teams.name as team_name from users left outer join user_teams on users.team_id = user_teams.id $whereClause";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		
		if($num>0)
		{
			$row = $dbh->GetNextRow($result, 0);
			$row["userImage"] = ($row['image_id']) ? "/antfs/images/".$row['image_id']."/40/40" : "";
			$row["chatSound"] = "/media/audio/chat.mp3";
			$row["retVal"] = 1;
			if(empty($row["team_id"]) || $row["team_id"] == null)
			{
				$row["team_id"] = 0;
				$row["team_name"] = "";
			}
			
			$retVal = $row;
		}
		else
			$retVal = -1;
			
		$dbh->FreeResults($result);
		return $retVal;
	}
	
	function checkFriendExists($userId, $friendName)
	{
		$dbh = $this->dbh;
		$query = "select id from chat_friends where user_id = '$userId' and friend_name = '$friendName'";
		$result = $dbh->Query($query);
		
		return $dbh->GetNumberRows($result);
	}
	
	/**
	 * Save / send  a new chat message
	 *
	 * @param array $args Params for the chat message including 'message', 'friendName', 'friendServer'
	 */
	public function saveMessage($args)
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;
		
		$message = $args['message'];            
		$friendName = $args['friendName'];
		$friendServer = isset($args['friendServer']) ? $args['friendServer'] : null;
		$messageTimestamp = time();
		
		$result = $dbh->Query("insert into chat_server(user_id, user_name, friend_name, friend_server, message, ts_last_message, message_timestamp) 
								values('$userId', '" . $dbh->Escape($userName) . "', '" . $dbh->Escape($friendName) . "', 
								'" . $dbh->Escape($friendServer) . "', '" . $dbh->Escape($message) . "', 
										'now', '$messageTimestamp');
								select currval('chat_server_id_seq') as id;");
		
		if ($dbh->GetNumberRows($result))
		{
			$id = $dbh->GetValue($result, 0, "id");                
			
			// set new message notification
			$args = array(
				"friendName" => $friendName, 
				"friendServer" => $friendServer, 
				"type" => "isNewMessage", 
				"value" => 1, 
				"calledFrom" => "antChatClient"
			);
			$this->saveChatSession($args);

			// Cache the data
			$cacheData = array(
				"id" => $message, 
				"timestamp" => "Sent on " . date("m/d/Y") . " at " . date("h:ia"), 
				"messageTimestamp" => $messageTimestamp, 
				'message_timestamp' => $messageTimestamp,
				"day" => date("d"), 
				"date" => date("l, F d, Y"), 
				"currentTimestamp" => time(),
				'user_name' => $userName,
				'friend_name' => $friendName,
				'friend_server' => $friendServer,
				'message' => $message,
			);

			// Cache the new message for the user for 2 hours
			$cacheData['friend_message'] = 'f';
			$cacheData['f_read'] = 't'; // read because user sent it
			$threadData = $this->cache->get($this->dbh->dbname."/chat/$userName/threads/$friendName");
			if (is_array($threadData)) 
				array_unshift($threadData, $cacheData); // add to beginning
			else
				$threadData = array($cacheData);
			
			$this->cache->set($this->dbh->dbname."/chat/$userName/threads/$friendName", $threadData, 60*60*2);

			// Cache the new message for the friend 2 hours
			$cacheData['friend_message'] = 't';
			$cacheData['f_read'] = 'f';
			$threadData = $this->cache->get($this->dbh->dbname."/chat/$friendName/threads/$userName");
			if (is_array($threadData)) 
				array_unshift($threadData, $cacheData); // add to beginning
			else
				$threadData = array($cacheData);
			$this->cache->set($this->dbh->dbname."/chat/$friendName/threads/$userName", $threadData, 60*60*2);
		}
					
		// Set return values
		$date = date("m/d/Y");
		$day = date("d");
		$time = date("h:ia");
		$retVal = array(
			"retVal" => 1, 
			"message" => $message, 
			"timestamp" => "Sent on $date at $time", 
			"messageTimestamp" => $messageTimestamp, 
			"messageId" => $id, 
			"day" => $day, 
			"date" => date("l, F d, Y"), 
			"currentTimestamp" => time(),
		);
		
		return $retVal;
	}
	
	/**
	 * Retrieve the chat message
	 */
	public function getMessage($args)
	{
		// First try to get from cache
		$messages = $this->cache->get($this->dbh->dbname."/chat/" . $this->userName . "/threads/" . $args['friendName']);
		if (!$messages)
		{
			// Not loaded in cache. Let's pull from the database
			$messages = $this->getMessageFromDb($args);

			// Cache for two hours
			$this->cache->set($this->dbh->dbname."/chat/" . $this->userName . "/threads/" . $args['friendName'], $messages, 60*60*2);
		}
		else
		{
			// Filter out only new messages
			if (isset($args['lastMessageTs']))
			{
				$tmpBuf = array();
				foreach ($messages as $msg)
				{
					if ($msg['message_timestamp'] > $args['lastMessageTs'])
						$tmpBuf[] = $msg;
				}
				$messages = $tmpBuf;
			}
		}
		
		return $messages;
	}

	/**
	 * Pull chat messages from the db
	 */
	public function getMessageFromDb($args)
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;            
		
		$friendName = $args['friendName'];            
		$lastMessageTs = $args['lastMessageTs'];
		
		if($lastMessageTs>0)
			$whereClause = "and message_timestamp>='$lastMessageTs'";

		$queryLimit = "";
		if(isset($args['limit']) && $args['limit'] > 0)
			$queryLimit = "limit {$args['limit']}";
		
		$sql =  "select *, true as friend_message from chat_server where friend_name='$userName' and user_name='$friendName' $whereClause ";
		
		$sql .=  " union select *, false as friend_message from chat_server where friend_name='$friendName' and user_name='$userName' $whereClause order by message_timestamp desc $queryLimit";
		
		
		$result = $dbh->Query($sql);
		$num = $dbh->GetNumberRows($result);
		
		if($num>0)
			$retVal = array();
		else
			$retVal = 1;
		
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$id = $row['id'];                
			$date = date("m/d/Y", $row['message_timestamp']);
			$day = date("d", $row['message_timestamp']);
			$time = date("h:i a", $row['message_timestamp']);
			$row["timestamp"] = "Sent on $date at $time";
			$row["messageTimestamp"] = $row['message_timestamp'];
			$row["day"] = $day;
			$row["date"] = date("l, F d, Y", $row['message_timestamp']);;
			$row["currentTimestamp"] = time();
			$retVal[] = $row;
		}
		$dbh->FreeResults($result);
		
		return $retVal;
	}
	
	/**
	 * Go back further to retrieve all messages
	 */
	public function getPrevChat($args)
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;
		
		$friendName = $args['friendName'];            
		$chatFirstMessageTs = $args['chatFirstMessageTs'];
		
		if($lastMessageTs>0)
			$whereClause = "and message_timestamp<'$chatFirstMessageTs'";
		
		$sql =  "select *, true as friend_message from chat_server where friend_name='$userName' and user_name='$friendName' $whereClause ";
		$sql .=  " union select *, false as friend_message from chat_server where friend_name='$friendName' and user_name='$userName' $whereClause order by message_timestamp desc limit 1 offset 0";
		
		$result = $dbh->Query($sql);
		$num = $dbh->GetNumberRows($result);
		
		$retVal = array("prevChatNum" => $num);
		
		$dbh->FreeResults($result);
		
		return $retVal;
	}
	
	/**
	 * Retrieve the all new chat messages from other users
	 * This function can only be called at ANT Chat Messenger
	 */
	public function getNewMessagesFromDb()
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;            
		$result = $dbh->Query("select distinct user_name from chat_server where friend_name='$userName' and f_read=false");
		$num = $dbh->GetNumberRows($result);
		
		if($num>0)
			$retVal = array();
		else
			$retVal = 1;
			
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$retVal[] = $row;
		}
			
		$dbh->FreeResults($result);
		return $retVal;
	}
	
	/**
	* Save chat session
	*/
	function saveChatSession($args)
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;
		$lastTimestamp = time();
		$friendName = $args['friendName'];
		$type = $args['type'];
		$value = $args['value'];
		$friendServer = null;
		
		if(isset($args['friendServer']))
			$friendServer = $args['friendServer'];
		
		switch($type)
		{
			case "isTyping":
			case "isNewMessage":
				// Get the status first
				$cached = $this->cache->get($this->dbh->dbname."/chat/$friendName/$type");
				
				$status = array();
				if(is_array($cached))
				{
					foreach($cached as $cKey=>$cValue)
						$status[$cKey] = $cValue;
				}
				
				$status[$userName] = $value;
				
				$this->cache->set($this->dbh->dbname."/chat/$friendName/$type", $status);
				break;
			case "isOnline": // Do not do anything yet since timestamp already stored
				break;
			default:
				$this->cache->set($this->dbh->dbname."/chat/$userName/$type", $value);
		}
		
		$this->cache->set($this->dbh->dbname."/chat/$userName/lastTimestamp", $lastTimestamp);
		
		//$retVal = array("retVal" => 1, "$type" => $value, "id" => $id);
		$retVal = array("retVal" => 1, "$type" => $value);
		
		return $retVal;
	}
	
	/**
	* Clears the chat session
	*/
	function clearChatSession($args)
	{
		$userName = $this->userName;
		$friendName = $args['friendName'];
		$friendServer = $args['friendServer'];
		
		$type = $args['type'];
		$value = $args['value'];
		
		$cached = $this->cache->get($this->dbh->dbname."/chat/$userName/$type");
		
		$status = array();
		if(is_array($cached))
		{
			foreach($cached as $cKey=>$cValue)
				$status[$cKey] = $cValue;
		}
		
		$status[$friendName] = $value;
		
		$this->cache->set($this->dbh->dbname."/chat/$userName/$type", $status);
		
		$retVal = array("retVal" => 1);
		
		return $retVal;
	}
	
	/**
	* Get chat session
	*/
	function getChatSession($args, $fromParent=false)
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;
		$calledFrom = null;
		$friendName = $args['friendName'];
		
		if(isset($args['calledFrom']))
			$calledFrom = $args['calledFrom'];
		
		// update server chat session last timestamp
		if(!$fromParent)
		{
			$params['friendName'] = "[all]";
			$params['type'] = "isOnline";
			$params['value'] = true;
			$this->saveChatSession($params);
		}
		
		$retVal = array();
		
		switch($calledFrom)
		{
			case "antChatMessenger": // Just get new messages
				$cNewMessage = $this->cache->get($this->dbh->dbname."/chat/$userName/isNewMessage");
				
				if(is_array($cNewMessage))
				{
					foreach($cNewMessage as $cKey=>$cValue)
						$retVal[] = array("retVal" => 1, "friendName" => $cKey, "isNewMessage" => $cValue);
				}
				else
					$retVal[] = array("retVal" => 1, "friendName" => null, "isNewMessage" => 0);
				break;
			case "antChatClient": // Just get is Typing
				$cIsTyping = $this->cache->get($this->dbh->dbname."/chat/$userName/isTyping");
				
				if(is_array($cIsTyping))
				{
					foreach($cIsTyping as $cKey=>$cValue)
					{
						if($cKey == $friendName)
						{
							$retVal = array("retVal" => 1, "friendName" => $cKey, "isTyping" => $cValue);
							break;
						}
					}
				}
				else
					$retVal[] = array("retVal" => 1, "friendName" => null, "isTyping" => 0);
										
				break;
			default:
				$retVal[] = array("retVal" => 1, "isTyping" => false, "isPopup" => false, "isNewMessage" => false, "id" => -1);
				break;
		}
		
		return $retVal;
	}

	/**
	 * Get list of friends with new messages
	 */
	public function getNewMessages()
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;
		$retval = array();

		$cNewMessage = $this->cache->get($this->dbh->dbname."/chat/$userName/isNewMessage");

		// If not cached then pull from db
		if(!is_array($cNewMessage))
			$cNewMessage = $this->getNewMessagesFromDb();

		if(is_array($cNewMessage))
		{
			foreach($cNewMessage as $friendName=>$isNew)
			{
				if ($isNew)
					$retval[] = $friendName;
			}
		}

		return $retval;
	}
	
	/**
	 * Remove messages that are more than x days old or purge all past from a friend if friend_name is an arg
	 */
	public function removeOldMessage($args=null)
	{
		$userName = $this->userName;            
		$dbh = $this->dbh;            
		$message_timestamp = time() - 864000; // 10 days
		$messageId = $args['messageId'];
		
		if($messageId>0)
		{
			$whereClause = "id = $messageId";
		}
		elseif($args['friend_name'])
		{
			$whereClause = "((user_id='" . $this->userId . "' AND friend_name='" . $dbh->Escape($args['friend_name']). "')
							or (friend_name='" . $dbh->Escape($userName). "' and user_name='" . $dbh->Escape($args['friend_name']). "'))";

			$this->cache->remove($this->dbh->dbname."/chat/" . $this->userName . "/threads/" . $args['friend_name']);
		}
		elseif($message_timestamp>0)
		{
			$whereClause = "message_timestamp <= $message_timestamp";
		}
		
		if(!empty($whereClause))
		{
			$dbh->Query("delete from chat_server where $whereClause");
			$retVal = array("retVal" => 1);
		}
		else
			$retVal = array("retVal" => -1);
					
		return $retVal;
	}
	
	/**
	 * Count friends online
	 */
	public function countFriendOnline($args)
	{
		$dbh = $this->dbh;
		$teamId = null;
		$userId = $this->userId;
		
		if(isset($args['teamId']))
			$teamId = $args['teamId'];
		
		if(empty($teamId))
		{
			$userDetails = $this->getUserDetails();
			$teamId = $userDetails['team_id'];
		}
		
		$sql = "select users.active_timestamp from chat_friends left outer join users on chat_friends.friend_name = users.name
				where chat_friends.user_id='$userId' and (chat_friends.team_id != $teamId or chat_friends.team_id IS NULL)";
				
		if($teamId>0)
		{
			$sql .= " union ";
			$sql .= "select users.active_timestamp from users left outer join chat_server_session on users.id = chat_server_session.user_id 
					where f_online=true and team_id='$teamId' and users.id != '$userId'";
		}
		
		$result = $dbh->Query($sql);
		$num = $dbh->GetNumberRows($result);
		
		$retVal = array();            
		if($num>0)
		{
			$friendOnline = 0;
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$activeTs = $row['active_timestamp'];
				if((time() - strtotime($activeTs)) < $this->timeDiff)
					$friendOnline++;
			}

			$retVal = array(
				"retVal" => 1, 
				"onlineCount" => $friendOnline, 
				"time" => time(), 
				"timestamp" => strtotime($activeTs), 
				"actualTime"=> $activeTs
			);
		}
		else
			$retVal = array("retVal" => 1, "onlineCount" => 0, "notice" => "No friends Available");

		$dbh->FreeResults($result);
		return $retVal;
	}
	
	function processStatus($args)
	{
		$dbh = $this->dbh;
		$userId = $this->userId;
		$userName = $this->userName;
		
		$friendName = $args['friendName'];
		$status = $args['status'];
		
		// Get friend Details
		$result = $this->getUserDetails($friendName);
		$friendId = $result["user_id"];
		
		switch($status)
		{                
			case 1: // Approve
				// Delete user friend entry
				$dbh->Query("update chat_friends set status = NULL where user_id = '$userId' and friend_name = '$friendName'");
				
				// Delete friend invite
				$dbh->Query("update chat_friends set status = NULL where user_id = '$friendId' and friend_name = '$userName'");
				break;
			case 0: // Cancel                    
			default: // As default, execute cancel
				// Delete user friend entry
				$dbh->Query("delete from chat_friends where user_id = '$userId' and friend_name = '$friendName'");
				
				// Delete friend invite
				$dbh->Query("delete from chat_friends where user_id = '$friendId' and friend_name = '$userName'");
				
				$retVal = array("retVal" => 1);
				break;
		}
		
		return $retVal;
	}
}
