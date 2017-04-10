<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("users/user_functions.php");
	require_once("chat_functions.php");


	$FUNCTION = $_REQUEST['function'];
	$USER = $_REQUEST['username'];
	$PASS = $_REQUEST['password'];
	$dbh = $ANT->dbh;
	$account_name = $ANT->accountName;
	$account = $ANT->accountId;

	// Return XML
	header("Content-type: text/xml");

	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
	switch ($FUNCTION)
	{
	// Create a session with a remote friend
	case 'create_session':
		$username = $_GET['user_name'];
		$remote_name = $_GET['remote_name'];
		$remote_server = $_GET['remote_server'];
		$remote_session = $_GET['remote_session'];

		if ($username)
		{
			$sid = -1;
			$message = "Friend not found";

			// Get local user id from name
			$uid = UserGetIdFromName($dbh, $username, $account);
			if ($uid)
			{
				// Get friend id from name (if exists)
				$fid = chatGetFriendId($dbh, $uid, $remote_name, $remote_server);
				if ($fid)
				{
					$sid = chatCreateSession($dbh, $uid, $fid);
					if ($sid)
					{
						$tid = chatGetLocalToken($dbh, $sid, $remote_session, $fid);
					}
				}
				else
				{
					$sid = chatCreateSession($dbh, $uid, null);
					if ($sid)
					{
						$tid = chatGetLocalToken($dbh, $sid, $remote_session, null, $remote_name);
					}
				}
			}
			
			print("<response>\n");
			print("<session_id>".rawurlencode($sid)."</session_id>");
			print("<token_id>".rawurlencode($tid)."</token_id>");
			print("<message>".rawurlencode($message)."</message>");
			print("</response>\n");
		}
		break;
	case 'get_token':
		$username = $_GET['user_name'];
		$remote_name = $_GET['remote_name'];
		$remote_server = $_GET['remote_server'];
		$remote_session = $_GET['remote_session'];
		$sid = $_GET['sid'];

		// Only need to check for username
		if ($username)
		{
			$tid = 0;
			$message = "Friend not found";
				
			// Get local user id from name
			$uid = UserGetIdFromName($dbh, $username, $account);
			if ($uid)
			{
				// Get friend id from name (if exists)
				$fid = chatGetFriendId($dbh, $uid, $remote_name, $remote_server);
				if ($fid)
				{
					$sid = chatCreateSession($dbh, $uid, $fid);
					if ($sid)
					{
						$tid = chatGetLocalToken($dbh, $sid, $remote_session, $fid);
					}
				}
			}

			print("<response>\n");
			print("<token_id>".rawurlencode($tid)."</token_id>");
			print("<message>".rawurlencode($message)."</message>");
			print("</response>\n");
		}
		break;
	case 'change_status':
		break;
	// Put message
	case 'put_message':
		$tid = $_GET['tid'];
		$sid = $_GET['sid'];
		$message = stripslashes(rawurldecode($_GET['message']));

		if ($tid && $sid)
		{
			if ($dbh->GetNumberRows($dbh->Query("select id from chat_session_remotes where id='$tid'")))
			{
				$result = $dbh->Query("insert into chat_session_content(session_id, ts_entered, body, remote_id)
										   values('$sid', 'now', '".$dbh->Escape($message)."', '$tid');
										   select currval('chat_session_content_id_seq') as id;");

				$dbh->Query("update chat_sessions set f_read='f' where id='$sid'");

				$retval = 1; // Success
			}
			else
			{
				$retval = 2; // New session needs to be created
			}
		}
		else
		{
			$retval = -1; // Invalid call (missing token/remote)
		}
		break;
	// Put message to everyone in the session
	case 'put_session_message':
		$sid = $_GET['sid'];
		$message = stripslashes(rawurldecode($_GET['message']));

		if ($sid)
		{
			// Get all remote ids
			$result = $dbh->Query("select id from chat_session_remotes where session_id='".$sid."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$ret = chatPutRemoteMessage($dbh, $row['id'], $message);
			}
			$dbh->FreeResults($result);

			$result = $dbh->Query("insert into chat_session_content(session_id, ts_entered, body)
								   values('$sid', 'now', '".$dbh->Escape($message)."');
								   select currval('chat_session_content_id_seq') as id;");
			// return the last entry id
			$retval = $dbh->GetValue($result, 0, "id");
		}
		else
		{
			$retval = -1; // Invalid call (missing token/remote)
		}
		break;
	// Allow remote clients that are local friends to check your status
	case 'check_status':
		$username = $_GET['user_name'];
		$remote_name = $_GET['remote_name'];
		$remote_server = $_GET['remote_server'];

		if ($username && $account)
		{
			$uid = UserGetIdFromName($dbh, $username, $account);

			if ($uid)
			{
				$result = $dbh->Query("select users.name, users.full_name, users.status_text, users.image_id,
										extract('epoch' from checkin_timestamp) as checkin_timestamp,
										extract('epoch' from now()-checkin_timestamp) as online_seconds_ago,
										extract('epoch' from now()-active_timestamp) as active_seconds_ago
										from users where users.id='$uid' and account_id='$account'");
				$num = $dbh->GetNumberRows($result);
				for ($i = 0; $i < $num; $i++)
				{
					$row = $dbh->GetNextRow($result, $i);

					// 1 min ago
					$status =  ($row['online_seconds_ago'] < 60) ? 1 : 0;
					if ($row['status_text'] == "Invisible")
					{
						$status = 0;
					}
					else
					{
						if ($row['active_seconds_ago'] > 600)
							$status_text = "Inactive";
						else
							$status_text = rawurlencode($row['status_text']);
					}

					$image = ($row['image_id']) ? rawurlencode("http://" . AntConfig::getInstance()->localhost . "/files/images/".$row['image_id']) : "";

					print("<response>\n");
					print("<full_name>".rawurlencode($row['full_name'])."</full_name>");
					print("<online>".$status."</online>");
					print("<status_text>".rawurlencode($status_text)."</status_text>");
					print("<image>".$image."</image>");
					print("</response>\n");
				}
				$dbh->FreeResults($result);
			}
			else
			{
				$retval = "-1";
			}
		}
		else
		{
			$retval = "-1";
		}

		break;
	// Check the status of a chat queue
	case 'queue_entry_get_status':
		$eid = $_GET['queue_eid']; // Queue entry id

		if ($eid)
		{
			$result = $dbh->Query("select session_id, token_id from chat_queue_entries where id='$eid'");
			$sid = $dbh->GetValue($result, 0, "session_id");
			$tid = $dbh->GetValue($result, 0, "token_id");

			if ($sid && $tid)
			{
				print("<response>\n");
				print("<retval>1</retval>");
				print("<session_id>".rawurlencode($sid)."</session_id>");
				print("<token_id>".rawurlencode($tid)."</token_id>");
				print("</response>\n");
			}
			else
			{
				$retval = "-1"; // still waiting on agent to answer
			}
		}
		break;
	// Check if there are any agents monitoring a queue
	case 'queue_is_active':
		$qid = $_GET['qid']; // Queue entry id

		if ($qid)
		{
			$result = $dbh->Query("select id from chat_queue_agents where queue_id='$qid'");
			if ($dbh->GetNumberRows($result))
			{
				$retval = "1";
			}
			else
			{
				$retval = "-1"; // still waiting on agent to answer
			}
		}
		break;
	// Check the status of a chat queue
	case 'queue_entry_create':
		$qid = $_GET['qid'];
		$name = rawurldecode($_GET['name']);
		$notes = rawurldecode($_GET['notes']);

		if ($qid && $name)
		{
			$ent = chatQueueAddEntry($dbh, $qid, $name, $notes);
			$retval = $ent;
		}
		break;
	default:
		$retval = "-1";
		break;
	}

	if ($retval)
	{
		echo "<response>";
		echo "<retval>" . rawurlencode($retval) . "</retval>";
		echo "<cb_function>".$_GET['cb_function']."</cb_function>";
		echo "</response>";
	}
?>
