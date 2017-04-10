<?php
/**
 * Aereus securty - discretionary access control list class 
 *
 * The security for every object in ANT is handled through access control lists
 * that allow for inheritance.
 *
 * @category	ANT
 * @package		Dacl
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once(dirname(__FILE__).'/../lib/aereus.lib.php/CCache.php');
require_once(dirname(__FILE__).'/../lib/Dacl/Entry.php');

/**
 * Object for discretionary access controll lists
 */
class Dacl
{
	/**
	 * ID of dacl to inherit permissions from
	 *
	 * @var int
	 */
	private $inheritFrom = null;

	/**
	 * History used to re-enable inheritance
	 *
	 * joe: I'm not sure if this is really needed anymore
	 * but we are leaving it in for now for legacy code
	 *
	 * @var int
	 */
	private $inheritFromOld = null;

	/**
	 * Default permissions
	 *
	 * @var string[]
	 */
	private $defaultPerms  = array("View", "Edit", "Delete");

	/**
	 * Handle to account database
	 *
	 * @var CDatabase
	 */
	private $dbh = null;
	
	/**
	 * Handle to account database (Static Variable)
	 *
	 * @var CDatabase
	 */
	private static $dbh_static = null;

	/**
	 * Saved DACLs will all have a unique id
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * Each DACL may have a unique name to access it by
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * Cache class to make loading entries and permissions faster
	 *
	 * @var CCache
	 */
	private $cache = null;
	
	/**
	 * Cache class to make loading entries and permissions faster (Static Variable)
	 *
	 * @var CCache
	 */
	private static $cache_static = null;

	/**
	 * Used in debugging only
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * Associative array with either group or user assoicated with an permission
	 *
	 * @var array ['group'|'user':unique_id, 'permission':"Name of permission"]
	 */
	private $entries = array();

	/**
	 * List of permissions for this DACL
	 *
	 * @var string[]
	 */
	private $permissions = array();

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh An active handle to a database connection
	 */
	function __construct($dbh, $name=null)
	{
		$this->dbh = $dbh;
		$this->name = $name;
		$this->users = array();
		$this->groups = array();
		$this->cache = CCache::getInstance();
		
		self::$dbh_static = $this->dbh;
		self::$cache_static =  $this->cache;

		if ($name)
			$this->loadByName($name);
	}

	/**
	 * Check if a DACL exists by name. Normally called statically.
	 *
	 * @param string $name The unique name of this DACL
	 * @param CDatabase $dbh If called statically this is required
	 */
	public static function exists($name, $dbh=null)
	{
        $cache = null;
        
		if (!self::$dbh_static)
			$dbh = self::$dbh_static;

		if (isset(self::$cache_static) && self::$cache_static)
			$cache = self::$cache_static;
		
		if (!$dbh) // required if called statically
			return false;

		if (!$cache)
			$cache = CCache::getInstance();

		$cacheRes = $cache->get($dbh->dbname."/security/dacl/$name");
		if (is_array($cacheRes) && $cacheRes!="-1")
		{
			return true;
		}

		// Was not found in cache, look in database
		$query = "select id, inherit_from, inherit_from_old from security_dacl 
							where name='".$dbh->Escape($name)."' ";
		$res2 = $dbh->Query($query);
		if ($res2 && $dbh->GetNumberRows($res2))
		{
			// Since we are here and have the data, go ahead and cache the results for future queries
			$result_arr2 = pg_fetch_all($res2);
			$dbh->FreeResults($res2);
			$cache->set($dbh->dbname."/security/dacl/$name", $result_arr2);

			return true;
		}
		else if ($cacheRes!="-1") // update for future queries
		{
			$cache->set($dbh->dbname."/security/dacl/$name", "-1");
		}

		// Not found
		return false;
	}

	/**
	 * Load definition of this array from data array
	 *
	 * @var array $data Associative array with 'permissions' and 'entries'
	 * @return bool True on success, false on failure
	 */
	public function loadByData($data)
	{
		if (!is_array($data))
			return false;

		$this->id = $data['id'];
		$this->name = $data['name'];
		$this->inheritFrom = $data['inheritFrom'];
		$this->inheritFromOld = $data['inheritFromOld'];

		if (is_array($data['entries']))
			$this->setEntries($data['entries']);

		// Make sure this DACL can be accessed by someone
		if (count($this->entries) == 0)
		{
			$this->grantGroupAccess(GROUP_USERS);
			$this->grantGroupAccess(GROUP_CREATOROWNER);
			$this->grantGroupAccess(GROUP_ADMINISTRATORS);
		}
	}	

	/**
	 * Load a DACL by a unique id rather than a name
	 *
	 * @param int $id The unique id of the dacl to load
	 */
	public function loadById($id)
	{
		$dbh = $this->dbh;

		return $this->loadFromDb("id", $id);
	}

	/**
	 * Load definition of this array from data array
	 *
	 * @param array $data Associative array with 'permissions' and 'entries'
	 * @param bool $loadEntries If true load the entries from the perm store
	 * @return bool True on success, false on failure
	 */
	public function loadByName($key, $loadEntries=true)
	{
		if (!$key)
			return false;

		return $this->loadFromDb("name", $key, $loadEntries);
	}	

	/**
	 * Load DACL from local database
	 *
	 * @param string $field Can either be 'id' or 'key'
	 * @param string $ident Can either be the unique id or the kkey name
	 * @param bool $loadEntries If true load the entries from the perm store
	 */
	private function loadFromDb($field, $ident, $loadEntries=true)
	{
		if (!$field || !$ident)
			return false;

		if ($field == "id" && !is_numeric($ident))
			return false;

		$result_arr2 = $this->cache->get($this->dbh->dbname."/security/dacl/$ident"); // can be stored as both id and name
		if (!$result_arr2 || $result_arr2=="-1")
		{
			$query = "select id, inherit_from, inherit_from_old from security_dacl 
								where $field='".$this->dbh->Escape($ident)."' ";
			$res2 = $this->dbh->Query($query);
			if ($res2 && $this->dbh->GetNumberRows($res2))
			{
				$result_arr2 = pg_fetch_all($res2);
				$this->dbh->FreeResults($res2);
				$this->cache->set($this->dbh->dbname."/security/dacl/$ident", $result_arr2);
			}
			else
			{
				$this->cache->set($this->dbh->dbname."/security/dacl/$ident", "-1");
			}
		}

		if (is_array($result_arr2) && count($result_arr2))
		{
			$this->id = $result_arr2[0]["id"];
			$inhfrom = $result_arr2[0]["inherit_from"];
			$this->inheritFromOld = $result_arr2[0]["inherit_from_old"];
			if ($inhfrom)
			{
				// Run up the tree to find a non-inherited DACL
				$this->inheritFrom = $this->getInheritFromRoot($inhfrom);
			}
		}
		
		if ($loadEntries)
		{
			$this->loadEntriesFromDb();

			// Make sure this DACL can be accessed by someone
			if (count($this->entries) == 0)
			{
				$this->grantGroupAccess(GROUP_USERS);
				$this->grantGroupAccess(GROUP_CREATOROWNER);
				$this->grantGroupAccess(GROUP_ADMINISTRATORS);
			}
		}
	}

	/**
	 * Create a JSON encoded string representing this dacl
	 *
	 * @return string The json encoded string for this dacl
	 */
	public function stringifyJson()
	{
		$data = array();

		$data['id'] = $this->id;
		$data['name'] = $this->name;
		$data['inheritFrom'] = $this->inheritFrom;
		$data['inheritFromOld'] = $this->inheritFromOld;
		$data['entries'] = $this->getEntries();
		
		return json_encode($data);
	}

	/**
	 * Clear all caches that hold this DACL
	 */
	public function clearCache()
	{
		$dbh = $this->dbh;

		if ($this->name)
		{
			$this->cache->remove($this->dbh->dbname."/security/dacl/".$this->name);
			$this->cache->remove($this->dbh->dbname."/security/dacl/".$this->name."/entries");
		}

		if ($this->id)
		{
			$this->cache->remove($this->dbh->dbname."/security/dacl/".$this->id);
			$this->cache->remove($this->dbh->dbname."/security/dacl/".$this->id."/entries");
		}
	}

	/**
	 * Recurrsively get the root DACL from tree of 'inheritFrom' links
	 *
	 * DACL's can inherit permissions from a parent object. This allows
	 * for simplified management where parent objects control the securty
	 * for all child objects. For instnace, with folders, the root folder
	 * could have the DACL set and all child folders would simply inherit
	 * settings from the root folder.
	 *
	 * @param int $daclid The id of the parent DACL
	 * @return int $ret The id of the root DACL
	 */
	private function getInheritFromRoot($daclid)
	{
		if (!$daclid)
			return null;

		$ret = $daclid;

		$dbh = $this->dbh;
		$result = $dbh->Query("select inherit_from from security_dacl where id='$daclid'");
		if ($dbh->GetNumberRows($result))
		{
			$inh = $dbh->GetValue($result, 0, "inherit_from");
			if ($inh)
				$ret = $this->getInheritFromRoot($inh);
		}

		return $ret;
	}
	
	/**
	 * Get array of list entries for this DACL
	 *
	 * Entries associate a user or group with a permission
	 *
	 * @return array Array of entries
	 */
	public function getEntries()
	{
		$entries = array();

		foreach ($this->entries as $pname=>$ent)
		{
			foreach ($ent->groups as $grp)
				$entries[] = array("permission"=>$pname, "group_id"=>$grp);

			foreach ($ent->users as $uid)
				$entries[] = array("permission"=>$pname, "user_id"=>$uid);
		}

		return $entries;
	}

	/**
	 * Clear entries
	 */
	public function clearEntries()
	{
		$this->entries = array();
	}

	/**
	 * Load permissions from either cache or the database
	 */
	private function loadEntriesFromDb()
	{
		unset($this->entries);
		$this->entries = array();

		$id = ($this->inheritFrom) ? $this->inheritFrom: $this->id;

		if ($id)
		{
			$dbh = $this->dbh;

			$entries = $this->cache->get($this->dbh->dbname."/security/dacl/".$id."/entries");
			if (!$entries || $entries=="-1")
			{

				$query = "SELECT security_acle.user_id, security_acle.group_id, security_acle.pname as permission FROM  security_acle
							WHERE security_acle.dacl_id='".$id."';";

				$result = $dbh->Query($query);
				if ($result && $dbh->GetNumberRows($result))
				{
					$entries = $dbh->fetchAll($result);
					$dbh->FreeResults($result);
					$this->cache->set($this->dbh->dbname."/security/dacl/".$id."/entries", $entries);
				}
				else
				{
					$this->cache->set($this->dbh->dbname."/security/dacl/".$id."/entries", "-1");
				}
			}

			if (is_array($entries) && count($entries))
			{
				$this->setEntries($entries);
			}
		}
	}

	/**
	 * Set local entries from array
	 */
	private function setEntries($entries)
	{
		for ($i = 0; $i < count($entries); $i++)
		{
			$ent = $entries[$i];

			if (!isset($this->entries[$ent['permission']]))
				$this->entries[$ent['permission']] = new Dacl_Entry();

			if (isset($ent['user_id']) && is_numeric($ent['user_id']) && !in_array($ent['user_id'], $this->entries[$ent['permission']]->users))
				$this->entries[$ent['permission']]->users[] = $ent['user_id'];

			if (isset($ent['group_id']) && is_numeric($ent['group_id']) && !in_array($ent['group_id'], $this->entries[$ent['permission']]->groups))
				$this->entries[$ent['permission']]->groups[] = $ent['group_id'];
		}
	}

	/**
	 * Get array of users mentioned in the entries
	 *
	 * @return array(array('id','name')) of users
	 */
	public function getUsers()
	{
		$dbh = $this->dbh;

		$uids = array();

		// Get distinct list of users
		foreach ($this->entries as $ent)
		{
			foreach ($ent->users as $userId)
			{
				if (!in_array($userId, $uids))
					$uids[] = $userId;
			}
		}

		$users = array();
		$uobj = CAntObject::factory($dbh, "user");

		foreach ($uids as $uid)
		{
			$users[] = array("id"=>$uid, "name"=>$uobj->getName($uid));
		}

		return $users;
	}

	/**
	 * Get array of groups mentioned in the entries
	 *
	 * @return array(array('id','name')) of users
	 */
	public function getGroups()
	{
		$dbh = $this->dbh;

		$gids = array();

		// Get distinct list of users
		foreach ($this->entries as $ent)
		{
			foreach ($ent->groups as $groupId)
			{
				if (!in_array($groupId, $gids))
					$gids[] = $groupId;
			}
		}

		$groups = array();
		$uobj = CAntObject::factory($dbh, "user");
		$grpsData = $uobj->getGroupingData("groups"); // load all groups

		foreach ($gids as $gid)
		{
			$title = "";

			foreach ($grpsData as $gdata)
				if ($gdata['id'] == $gid)
					$title = $gdata['title'];
					
			$groups[] = array("id"=>$gid, "name"=>$title);
		}

		return $groups;
	}

	/**
	 * Get url for editing this DACL
	 */
	public function getEditLink()
	{
		$params = 'width=800,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';

		return "window.open('/security/dacl.php?id=".$this->id."', 'dacl_'+".$this->id.", '$params');";
	}

	/**
	 * Create a new DACL with a set of permissions
	 *
	 * @param string $name An optional unique name for this object if one is not already set
	 * @param string[] $permissions The permissions (by name) that are available for this DACL
	 */                                           
	public function save($name="")
	{
		if (!$name && $this->name)
			$name = $this->name;

		// Check for duplicates if saving by name
		if (!$this->id && $name)
			$ret = $this->loadByName($name, false); // second param will leave entries alone to avoid over-write

		$id = $this->id;
		$dbh = $this->dbh;


		// Save data for acl
		// ------------------------------------------
		if (!$id)
		{
			$result = $dbh->Query("insert into security_dacl(name, inherit_from, inherit_from_old) 
									values('".$dbh->Escape($name)."', ".$dbh->EscapeNumber($this->inheritFrom).", 
										    ".$dbh->EscapeNumber($this->inheritFromOld).");
								   select currval('security_dacl_id_seq') as id;");
			if ($dbh->GetNumberRows($result))
			{
				$id = $dbh->GetValue($result, 0, "id");
				$this->id = $id;
			}
			$dbh->FreeResults($result);
		}
		else
		{
			$result = $dbh->Query("UPDATE security_dacl SET
									inherit_from=".$dbh->EscapeNumber($this->inheritFrom).",
									inherit_from_old=".$dbh->EscapeNumber($this->inheritFromOld)."
									WHERE id='$id';");

			$id = $this->id;
		}

		if (!is_numeric($id))
			return false;

		// Purge old entries
		$dbh->Query("DELETE FROM security_acle WHERE dacl_id='$id'");

		// Make sure we don't have a blank DACL that nobody can access
		// Default is administrators and owner has full control
		if (count($this->entries) == 0)
		{
			$this->grantGroupAccess(GROUP_USERS);
			$this->grantGroupAccess(GROUP_CREATOROWNER);
			$this->grantGroupAccess(GROUP_ADMINISTRATORS);
		}

		// Now save entries
		foreach ($this->entries as $pname=>$ent)
		{
			foreach ($ent->users as $userid)
			{
				$dbh->Query("insert into security_acle(pname, user_id, dacl_id) values('".$dbh->Escape($pname)."', '".$userid."', '$id');");
			}

			foreach ($ent->groups as $gid)
			{
				$dbh->Query("insert into security_acle(pname, group_id, dacl_id) values('".$dbh->Escape($pname)."', '".$gid."', '$id');");
			}
		}

		$this->saveObjectDacl($name);

		$this->clearCache();

		return $id;
	}

	/**
	 * This function will take dalcs with /objects/[otype]/[oid] and update the object cache
	 *
	 * It is used to update unique DACLs for specific objects
	 *
	 * @param string $name The key or name of this dacl
	 */
	private function saveObjectDacl($name)
	{
		$parts = explode("/", $name);

		if (count($parts) > 3) 
		{
			if ($parts[0] == "" && $parts[1] == "objects" && $parts[2] && is_numeric($parts[3]))
			{
				$obj = CAntObject::factory($this->dbh, $parts[2], $parts[3]);
				$obj->setValue("dacl", $this->stringifyJson());
				$obj->save(false);
			}
		}
	}

	/**
	 * Remove this DACL from the database and cache
	 */
	public function remove()
	{
		$dbh = $this->dbh;
		$this->clearCache();

		// Get child dacls
		if ($this->name)
		{
			$query = "select id, name from security_dacl 
								where name like '".$dbh->Escape($this->name)."/%' ";
			$res2 = $dbh->Query($query);
			for ($i = 0; $i < $dbh->GetNumberRows($res2); $i++)
			{
				$tmpd = new Dacl($dbh, $dbh->GetValue($res2, $i, "name"));
				if ($tmpd)
					$tmpd->remove();
			}
		}

		// purge this dacl
		if ($this->id)
			$dbh->Query("delete from security_dacl where id='".$this->id."';");
	}

	/**
	 * Remove this DACL from the database and cache
	 */
	public function deleteDacl()
	{
		$this->remove();
	}

	/**
	 * Grant access to a user to a specific permission
	 *
	 * @param int $USERID The user id to grant access to
	 * @param string $permission The permssion to grant access to
	 */
	public function grantUserAccess($USERID, $permission="Full Control")
	{
		$dbh = $this->dbh;

		if ("Full Control" == $permission)
		{
			foreach ($this->entries as $ent)
			{
				if (!in_array($USERID, $ent->users))
					$ent->users[] = $USERID;
			}
		}

		// Add specific permission
		if (!isset($this->entries[$permission]))
			$this->entries[$permission] = new Dacl_Entry();

		$ent = $this->entries[$permission];
		if ($ent && !in_array($USERID, $ent->users))
			$ent->users[] = $USERID;

		//$this->clearCache();
		$this->removeInheritFrom();
	}

	/**
	 * Grant access to a group to a specific permission
	 *
	 * @param int $gid The group id to grant access to
	 * @param string $permission The permssion to grant access to
	 */
	public function grantGroupAccess($gid, $permission="Full Control")
	{
		$dbh = $this->dbh;

		// Add specific permission
		if (!isset($this->entries[$permission]))
			$this->entries[$permission] = new Dacl_Entry();

		// Grant group access
		$ent = $this->entries[$permission];
		if (!in_array($gid, $ent->groups))
			$ent->groups[] = $gid;

		if ("Full Control" == $permission)
		{
			foreach ($this->entries as $ent)
			{
				if (!in_array($gid, $ent->groups))
					$ent->groups[] = $gid;
			}
		}

		//$this->removeInheritFrom();
	}

	/**
	 * Deny access to a user to a specific permission
	 *
	 * @param int $USERID The user id to grant access to
	 * @param string $permission The permssion to grant access to
	 */
	public function revokeUserAccess($uid, $permission="Full Control")
	{
		$dbh = $this->dbh;

		if ($this->entries[$permission])
		{
			for ($i = 0; $i < count($this->entries[$permission]->users); $i++)
			{
				if ($this->entries[$permission]->users[$i] == $uid)
					array_splice($this->entries[$permission]->users, $i, 1);
			}
		}
	}

	/**
	 * Deny access to a group to a specific permission
	 *
	 * @param int $USERID The user id to grant access to
	 * @param string $permission The permssion to grant access to
	 */
	public function revokeGroupAccess($gid, $permission="Full Control")
	{
		$dbh = $this->dbh;

		if ($this->entries[$permission])
		{
			for ($i = 0; $i < count($this->entries[$permission]->groups); $i++)
			{
				if ($this->entries[$permission]->groups[$i] == $gid)
				{
					array_splice($this->entries[$permission]->groups, $i, 1);
				}
			}
		}
	}

	/**
	 * Check if a user has access to a permission either directly or through group membership
	 *
	 * @param AntUser $user The user to check for access
	 * @param string $permission The permission to check against. Defaults to 'Full Control'
	 * @param bool isowner Set to true if the $USERID is the owner of the object being secured by this DACL
	 * @param bool ignoreadmin If set to true then the 'god' access of the administrator is ignored
	 */
	public function checkAccess($user, $permission="Full Control", $isowner=false, $ignoreadmin=false)
	{
		$granted = false;
		$groups = $user->getGroups();
		if ($isowner)
			$groups[] = GROUP_CREATOROWNER; // Add to Creator/Owner group

		// Sometimes used for user-specific objects like calendars
		if ($ignoreadmin)
		{
			$tmp_groups = array();
			foreach ($groups as $gid)
			{
				if ($gid != GROUP_ADMINISTRATORS) // Admin
					$tmp_groups[] = $gid;
			}
			unset($groups);
			$groups = $tmp_groups;
		}

		// First check to see if the user has full control
		if ($permission!="Full Control")
		{
			$granted = $this->checkAccess($user, "Full Control", $isowner, $ignoreadmin);
			if ($granted)
				return $granted;
		}

        $per = null;
        if(isset($this->entries[$permission]))
		    $per = $this->entries[$permission];

		if ($per)
		{
			// Test users
			if (count($per->users))
			{
				foreach ($per->users as $uid)
				{
					if ($uid == $user->id)
						$granted = true;
				}
			}

			// Test groups
			if (count($per->groups))
			{
				foreach ($per->groups as $gid)
				{
					if (in_array($gid, $groups))
					{
						$granted = true;
					}
				}
			}
		}

		return $granted;
	}

	/**
	 * Check if a specific user has access to a specific permission
	 *
	 * @param int $USERID The user id to check
	 * @param string $permission The permission to check against
	 * @return bool true if user has permission, false if access is denied
	 */
	public function checkUserPermission($USERID, $permission="Full Control")
	{
		$granted = false;

		$per = $this->entries[$permission];

		if ($per)
			$granted = in_array($USERID, $per->users);

		return $granted;
	}

	/**
	 * Check if a specific group has access to a specific permission
	 *
	 * @param int $GROUPID The group to check
	 * @param string $permission The permission to check against
	 * @return bool true if group has permission, false if access is denied
	 */
	public function checkGroupPermission($GROUPID, $permission="Full Control")
	{
		$granted = false;

		$per = $this->entries[$permission];

		if ($per)
			$granted = in_array($GROUPID, $per->groups);

		return $granted;
	}

	/**
	 * Set this DACl to inherit permissions from a parent DACL
	 */
	public function setInheritFrom($daclid)
	{
		if (!$daclid)
			return false;

		$dbh = $this->dbh;

		if ($this->inheritFrom != $daclid) // No need to reload if already inheriting
		{
			$this->inheritFrom = $daclid;
			$this->inheritFromOld = null;
			$this->loadEntriesFromDb();
		}

		return true;
	}

	/**
	 * Unlink this DACL to a parent
	 *
	 * This will create a unique instance rather than inheriting from a parent DACL
	 *
	 * @return bool true on succes, false on failure
	 */
	public function removeInheritFrom()
	{
		$dbh = $this->dbh;

		if ($this->inheritFrom)
		{
			$this->inheritFromOld = $this->inheritFrom;
			$this->inheritFrom= null;
		}

		return true;
	}

	/**
	 * DEPRICATED: Make sure all permissions are present
	 *
	 * This is no longer used because we have removed the need for there to be all permissions to check access
	 */
	private function verifyPermissions($permissions)
	{
		$clear_cache = false;
		if ($this->id && is_array($permissions))
		{
			$dbh = $this->dbh;
			$id = ($this->inheritFrom) ? $this->inheritFrom : $this->id;
			$pid = $this->acl_permissions["Full Control"];
			
			foreach ($permissions as $per)
			{
				if (!$this->acl_permissions[$per])
				{
					$eres = $dbh->Query("insert into security_aclp(dacl_id, name, parent_id) 
											values('$id', '".$dbh->Escape($per)."', ".$dbh->EscapeNumber($pid).");
											select currval('security_aclp_id_seq') as id;");
					if ($dbh->GetNumberRows($eres))
					{
						$dbh->Query("insert into security_acle(aclp_id, group_id) 
										values('".$dbh->GetValue($eres, 0, "id")."', '".GROUP_ADMINISTRATORS."');");
						$dbh->Query("insert into security_acle(aclp_id, group_id) 
										values('".$dbh->GetValue($eres, 0, "id")."', '".GROUP_CREATOROWNER."');");
						$dbh->Query("insert into security_acle(aclp_id, group_id) 
										values('".$dbh->GetValue($eres, 0, "id")."', '".GROUP_USERS."');");
						$clear_cache = true;
					}
				}
			}
		}

		if ($clear_cache)
			$this->clearCache();
	}
}
