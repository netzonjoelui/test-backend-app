<?php
/**
 * DB implementation of DACL datamppaer
 * 
 * TODO: this class is in progress being converted from the old \Dacl class
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\Permissions\Dacl;

use Netric\DataMapperAbstract;

/**
 * DataMapper for persistant storage of DACLs
 */
class DataMapperDb extends DataMapperAbstract
{
    // The below was all copied from the old \DACL class so it needs
    // lots of work!  - joe
    
    /**
	 * Check if a DACL exists by name. Normally called statically.
	 *
	 * @param string $name The unique name of this DACL
	 * @param CDatabase $dbh If called statically this is required
	 */
	public function exists($name, $dbh=null)
	{
        $cache = null;
        
		if (isset($this))
		{
			if (!$dbh)
				$dbh = $this->dbh;

			if (isset($this->cache) && $this->cache)
				$cache = $this->cache;
		}

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
}
