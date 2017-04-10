<?php
require_once("lib/aereus.lib.php/CCache.php");
require_once("lib/global_functions.php");

$TEAM_ACLS = array("View Team", "Edit Team", "Delete Team");

function UserGetId($db, $user_name)
{
	$query = "select id from users where lower(name)=lower('$user_name')";
	$result = pg_query($db, $query);
	if (pg_num_rows($result))
	{
		$row = pg_fetch_array($result);
		$id = $row["id"];
	}
	pg_free_result($result);
	return $id;
}

/* DEPRICATED
function UserGetIdFromName($dbh, $user_name, $account)
{
	$query = "select id from users where lower(name)=lower('".$dbh->Escape($user_name)."')";
	$result = $dbh->Query($query);
	$id = $dbh->GetValue($result, 0, "id");

	return $id;
}
*/

/* DEPRICATED
function UserGetNameFromId($dbh, $user_id)
{
	$id = null;

	if ($user_id)
	{
		$query = "select name from users where id='".$user_id."'";
		$result = $dbh->Query($query);
		$id = $dbh->GetValue($result, 0, "name");
	}

	return $id;
}
*/

function UserGetAnonymous($dbh, $account)
{
	$id = null;

	if ($account)
	{
		$result = $dbh->Query("select id from users where name='anonymous'");
		if ($dbh->GetNumberRows($result))
		{
			$id = $dbh->GetValue($result, 0, "id");
		}
		else 
		{
			$dbh->Query("insert into users(name, password, full_name) values('anonymous', 'null', 'Anonymous User');");
			$id = UserGetAnonymous($dbh, $account);
		}
	}

	return $id;
}

function UserAccGetNameFromId($dbh, $account_id)
{
	$id = null;

	if ($account_id)
	{
		$query = "select name from accounts where id='".$account_id."'";
		$result = $dbh->Query($query);
		$id = $dbh->GetValue($result, 0, "name");
	}

	return $id;
}


function UserGetImage($dbh, $user_id)
{
	$retval = "";
	
	if (is_numeric($user_id))
	{
		$query = "select image_id from users where id='$user_id'";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$retval = $row['image_id'];
		}
		$dbh->FreeResults($result);
	}
	
	return $retval;
}

function UserGetTimeZone($dbh, $USERID, $toget = 'code')
{
	$user = new AntUser($dbh, $USERID);
	return $user->timezoneName;
}

/**
 * @depricated No longer used anywhere in ANT
function UserGetIdFromCid($dbh, $CID)
{
	$query = "select email_users.user_id from email_users, contacts_personal 
				where contacts_personal.id = '$CID' and
				(lower(contacts_personal.email)=lower(email_users.email_address)
				 or lower(contacts_personal.email2)=lower(email_users.email_address))";
	$result = $dbh->Query($query);
	if ($dbh->GetNumberRows($result))
	{
		$row = $dbh->GetNextRow($result, 0);
		$id = $row["user_id"];
	}
	$dbh->FreeResults($result);
	return $id;
}
 */

$g_usergroupcache = array();
function UserGetGroups($dbh, $UID)
{
	$ret = array();

	if (isset($g_usergroupcache[$UID]))
	{
		$ret = $g_usergroupcache[$UID];
	}
	else
	{
		$g_usergroupcache[$UID] = array();

		$cache = CCache::getInstance();
		$cval = $cache->get($dbh->dbname."/users/$UID/groups");
		if ($cval === false || !is_array($cval))
		{
			$result = $dbh->Query("select group_id from user_group_mem where user_id='$UID'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$gid = $dbh->GetValue($result, $i, "group_id");
				if ($gid)
				{
					$ret[] = intval($gid);
					$g_usergroupcache[$UID][] = intval($gid);
				}
			}
			$dbh->FreeResults($result);

			$cache->set($dbh->dbname."/users/$UID/groups", $ret);
		}
		else
		{
			$g_usergroupcache[$UID] = $cval;
			$ret = $cval;
		}
	}

	// Add "Everyone"=-3
	$ret[] = -3;

	// Make sure workflow and system have full control
	if (($UID == USER_WORKFLOW || $UID == USER_SYSTEM) && in_array(GROUP_ADMINISTRATORS, $ret)==false)
		$ret[] = GROUP_ADMINISTRATORS;

	return $ret;
}

function UserGetName($dbh, $user_id)
{
	if (is_numeric($user_id))
	{
		$cache = CCache::getInstance();
		$name = $cache->get($dbh->dbname."/users/$user_id/name");
		if ($name === false)
		{
			$query = "select name from users where id='$user_id'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$name = $row["name"];
				$cache->set($dbh->dbname."/users/$user_id/name", $name);
			}
			$dbh->FreeResults($result);
		}
	}
	return $name;
}

function UserGetFullName($dbh, $user_id)
{
	$name = "";
	if ($user_id)
	{
		$query = "select full_name as name from users where id='$user_id'";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$name = $row["name"];
			$dbh->FreeResults($result);
		}
		else
		{
			if (is_numeric($user_id) && $dbh->TableExists("employee"))
			{
				$query = "select first_name || ' ' || last_name as name from employee where user_id='$user_id'";
				$result = $dbh->Query($query);
				if ($dbh->GetNumberRows($result))
				{
					$row = $dbh->GetNextRow($result, 0);
					$name = $row["name"];
					$dbh->FreeResults($result);
				}
				else
					$name = UserGetName($dbh, $user_id);
			}
			else
			{
				$name = UserGetName($dbh, $user_id);
			}
		}
	}
	
	return $name;
}

function UserGroupGetName($dbh, $group_id)
{
	if (is_numeric($group_id))
	{
		$query = "select name from user_groups where id='$group_id'";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$name = $row["name"];
		}
		$dbh->FreeResults($result);
	}
	return $name;
}

function UserGroupGetIdFromName($dbh, $name, $account)
{
	$id = null;

	$query = "select id from user_groups where lower(name)=lower('".$dbh->Escape($name)."')";
	$result = $dbh->Query($query);
	if ($dbh->GetNumberRows($result))
	{
		$row = $dbh->GetNextRow($result, 0);
		$id = $row["id"];
	}
	$dbh->FreeResults($result);

	return $id;
}

function UserGetPref($dbh, $user_id, $prefname)
{
	if (is_numeric($user_id) && $prefname)
	{
		$cache = CCache::getInstance();
		$cval = $cache->get($dbh->dbname."/users/preferences/$user_id/$prefname");
		if ($cval === false)
		{
			$query = "select key_val from system_registry where user_id='$user_id' and key_name='$prefname'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$key_val = $row["key_val"];
				$cache->set($dbh->dbname."/users/preferences/$user_id/$prefname", $key_val);
			}
			$dbh->FreeResults($result);
		}
		else
		{
			$key_val = $cval;
		}
	}
	return $key_val;
}

function UserSetPref($dbh, $user_id, $prefname, $prefval)
{
	if (is_numeric($user_id) && $prefname)
	{
		$cache = CCache::getInstance();
		$cache->remove($dbh->dbname."/users/preferences/$user_id/$prefname");

		// Look to see if preference value is already set
		$result = $dbh->Query("select id from system_registry where key_name='$prefname' and user_id='$user_id' and key_val='$prefval'");
		if ($dbh->GetNumberRows($result))
		{
			$dbh->FreeResults($result);
			return;
		}
		
		// Look to see if this preference has already been saved
		$result = $dbh->Query("select id from system_registry where key_name='$prefname' and user_id='$user_id'");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$dbh->FreeResults($result);
			$dbh->Query("update system_registry set key_val='$prefval' where id='".$row['id']."'");
		}
		else
		{
			$dbh->Query("insert into system_registry(user_id, key_val, key_name) values('$user_id', '$prefval', '$prefname')");
		}
	}
}

function UserDeletePref($dbh, $user_id, $prefname)
{
	if (is_numeric($user_id) && $prefname)
	{
		$cache = CCache::getInstance();
		$cache->remove($dbh->dbname."/users/preferences/$user_id/$prefname");

		$dbh->Query("delete from system_registry where key_name='$prefname' and user_id='$user_id'");
	}
}

// UserPrefArr functions are used for simple arrays like field1:field2:filed3 preferences
// -------------------------------------------------------------------------------------------
function UserPrefArrExists($dbh, $user_id, $prefname, $key)
{
	$ret = false;

	$vals = UserGetPref($dbh, $user_id, $prefname);
	if ($vals)
	{
		$parts = explode(":", $vals);
		foreach ($parts as $val)
			if ($val == $key)
				return true;
	}

	return $ret;
}

function UserPrefArrGet($dbh, $user_id, $prefname)
{
	$vals = UserGetPref($dbh, $user_id, $prefname);
	if ($vals)
	{
		$parts = explode(":", $vals);
	}
	else
		$parts = array();

	return $parts;
}

function UserPrefArrAdd($dbh, $user_id, $prefname, $item)
{
	$vals = UserGetPref($dbh, $user_id, $prefname);
	if ($vals)
		$parts = explode(":", $vals);
	else 
		$parts = array();

	$f_found = false;
	foreach ($parts as $val)
	{
		if ($val == $item)
			$f_found =  true;
	}

	if (!$f_found)
		$parts[] = $item;

	UserPrefArrSet($dbh, $user_id, $prefname, $parts);
}

function UserPrefArrDelete($dbh, $user_id, $prefname, $item)
{
	$vals = UserGetPref($dbh, $user_id, $prefname);
	if ($vals)
	{
		$parts = explode(":", $vals);
		$new_arr = array();
		foreach ($parts as $val)
		{
			if ($val != $item)
				$new_arr[] = $val;
		}

		UserPrefArrSet($dbh, $user_id, $prefname, $new_arr);
	}
}

// Reset all values
function UserPrefArrSet($dbh, $user_id, $prefname, $arr)
{
	if (is_numeric($user_id) && $prefname && is_array($arr))
	{
		$buf = "";
		foreach ($arr as $val)
		{
			$buf .= ($buf) ? ":$val" : $val;
		}

		UserSetPref($dbh, $user_id, $prefname, $buf);
	}
}

// Set all values only if value is empty
function UserPrefArrDefault($dbh, $user_id, $prefname, $arr)
{
	if (is_numeric($user_id) && $prefname && is_array($arr))
	{
		$vals = UserGetPref($dbh, $user_id, $prefname);
		if (!$vals)
			UserPrefArrSet($dbh, $user_id, $prefname, $arr);
	}
}

function UserGetEmail($dbh, $user_id, $system=false)
{
	if (is_numeric($user_id))
	{
		$sVal = EmailGetUserName($dbh, $user_id, 'address', null, true);
		
		if (!$sVal || $system)
		{
            //$query = "select email_address from email_users where user_id='$user_id'";
			$query = "select id, name, address, reply_to from email_accounts where user_id='$user_id' and f_default='t'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$sVal = $row["address"];
			}
			$dbh->FreeResults($result);
		}
	}
	return $sVal;
}

function UserGetAccount($dbh, $user_id)
{
	$retval = "";
	
	if (is_numeric($user_id))
	{
		$cache = CCache::getInstance();
		$retval = $cache->get($dbh->dbname."/users/$user_id/account_id");
		if ($retval === false)
		{
			$query = "select account_id from users where id='$user_id'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$retval = $row['account_id'];
			}
			$dbh->FreeResults($result);

			$cache->set($dbh->dbname."/users/$user_id/account_id", $retval);
		}
	}
	
	return $retval;
}

function UserGetEmployeeInfo($dbh, $user_id, $toget)
{
    $sVal = null;
    
	if (is_numeric($user_id) && $dbh->TableExists("employee"))
	{
		$query = "select $toget from employee where user_id='$user_id'";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$sVal = $row[$toget];
		}
		$dbh->FreeResults($result);
	}
    
	return $sVal;
}

function UserGetTheme($dbh, $userid, $get='css')
{
	global $USERTHEMECACHE;

	if ($USERTHEMECACHE[$get])
	{
		return $USERTHEMECACHE[$get];
	}
	else if ($userid)
	{
		$cache = CCache::getInstance();

		$theme = $cache->get($dbh->dbname."/users/$userid/theme");

		if ($theme === false || is_array($theme) || $theme == "")
		{

			$result = $dbh->Query("select theme from users where id='$userid'");
			if ($dbh->GetNumberRows($result))
			{
				$theme = $dbh->GetValue($result, 0, "theme");
				$dbh->FreeResults($result);
			}

			// Set default if none selected
			$themes = Ant::getThemes();
			if (!$theme)
				$theme = $themes[0]['name'];

			// Save to cache
			$cache->set($dbh->dbname."/users/$userid/theme", $theme);
		}


		$USERTHEMECACHE['css'] = "ant_".$theme.".css";
		$USERTHEMECACHE['name'] = $theme;
		$USERTHEMECACHE['title'] = $theme;

		return $USERTHEMECACHE[$get];
	}
}

function UserGetQuota($dbh, $userid)
{
	$quota_size = "0";
	
	if (is_numeric($userid))
	{
		$query = "select quota_size from users where id='$userid'";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$quota_size = $row['quota_size'] * 1000000;
		}
		$dbh->FreeResults($result);
	}
	
	return $quota_size;
}

function UserGetQuotaFree($dbh, $userid)
{
	$quota_size = UserGetQuota($dbh, $userid);
	$usage_size = UserGetQuotaUsed($dbh, $userid);

	return $quota_size - $usage_size;
}

/*
function UserGetQuotaUsed($dbh, $userid)
{
	$quota_size = "0";
	
	if (is_numeric($userid))
	{
		$query = "select sum(message_size) as cnt from email_messages, email_mailboxes, email_users
						where email_messages.mailbox_id=email_mailboxes.id
						and email_mailboxes.email_user=email_users.id
						and email_users.user_id='$userid'";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result);
			$usage_size = $row['cnt'];
			$dbh->FreeResults($result);
		}

		// Get size of file storage for "My Files"
		$result = $dbh->Query("select sum(file_size) as cnt from user_files where user_id='$userid'");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$usage_size += $row['cnt']*10;
			$dbh->FreeResults($result);
		}
	}
	
	return $usage_size;
}
 */
function UserGetUserIdFromAddress($dbh, $address, $src="address")
{
	$ret = null;

    if($src == "email_address")
        $src = "address";
    
	$query = "select user_id from email_accounts where $src='".$dbh->Escape($address)."'";
	$result = $dbh->Query($query);
	if ($dbh->GetNumberRows($result))
	{
		$row = $dbh->GetNextRow($result, 0);
		$ret = $row["user_id"];
	}
	else if ($src != "reply_to") // keep this from looping
	{
		// Try reply to address
		$ret = UserGetUserIdFromAddress($dbh, $address, "reply_to");
	}
	$dbh->FreeResults($result);
	
	return $ret;
}

function UserLogAction($dbh, $USERID)
{
	global $_SERVER;

	/*
	$dbh->Query("update users set active_timestamp='now' where id='$USERID'");

	if ($_SERVER['REMOTE_ADDR'])
	{
		$dbh->Query("update users set last_login_from='".$_SERVER['REMOTE_ADDR']."' where id='$USERID'");
	}
	 */
}

function UserTeamsGetChildrenArray($dbh, $tid)
{
	$children_arr = array($tid);

	if ($tid)
	{
		$query = "select id from user_teams where parent_id='$tid' order by name";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);

			$subchildren = UserTeamsGetChildrenArray($dbh, $row['id']);

			if (count($subchildren))
				$children_arr = array_merge($children_arr, $subchildren);
		}
		$dbh->FreeResults($result);
	}

	return $children_arr;
}

function UserGetTeam($dbh, $USERID)
{
	$ret = null;

	if ($USERID)
	{
		$result = $dbh->Query("select team_id from users where id='$USERID'");
		if ($dbh->GetNumberRows($result))
			$ret = $dbh->GetValue($result, 0, "team_id");
	}

	return $ret;
}

function UserGetTeamName($dbh, $TID)
{
	$ret = null;

	if ($TID)
	{
		$result = $dbh->Query("select name from user_teams where id='$TID'");
		if ($dbh->GetNumberRows($result))
			$ret = $dbh->GetValue($result, 0, "name");
	}

	return $ret;
}

function UserAccGetTeam($dbh, $ACCOUNT)
{
	$ret = null;

	if ($USERID)
	{
		$result = $dbh->Query("select id from user_teams where parent_id is null and account_id='$ACCOUNT'");
		if ($dbh->GetNumberRows($result))
			$ret = $dbh->GetValue($result, 0, "id");
	}

	return $ret;
}

function UserGetTeamOptionStr($dbh, $user, $selected_tid, $parent=null, $path='')
{
	global $TEAM_ACLS;

	$ret = "";

	if ($parent)
		$team_cond = "parent_id='$parent'";
	else
		$team_cond = " parent_id is null";

	$result = $dbh->Query("select id, name from user_teams where account_id='".$user->account."' and $team_cond");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetRow($result, $i);

		$DACL_TEAM = new Dacl($dbh, "teams/".$row['id']);
		if (!$DACL_TEAM->id)
			$DACL_TEAM->save(); // save with default perms

		if ($DACL_TEAM->checkAccess($user, "View Team"))
			$ret .= "<option value='".$row['id']."' ".(($row['id']==$selected_tid)?'selected':'').">$path".stripslashes($row['name'])."</option>";

		$ret .= UserGetTeamOptionStr($dbh, $user, $selected_tid, $row['id'], $path.stripslashes($row['name'])."/");
	}

	return $ret;
}

function UserPublicAuth($dbh, $auth)
{
	global $_SERVER;

	$dbh->Query("update users set active_timestamp='now' where id='$USERID'");

	if ($_SERVER['REMOTE_ADDR'])
	{
		$dbh->Query("update users set last_login_from='".$_SERVER['REMOTE_ADDR']."' where id='$USERID'");
	}
}
