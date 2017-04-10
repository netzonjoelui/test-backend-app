<?php
/**
* Security actions
*/
require_once(dirname(__FILE__).'/../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../lib/Dacl.php');

/**
* Actions for interacting with Ant Sales
*/
class SecurityController extends Controller
{
    /**
     * Get list of users and groups in a Dacl
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function loadDaclUsersAndGroups($params)
	{
		$dacl = DaclLoader::getInstance($this->ant->dbh)->byName($params['name']);

		$ret = array();

		$users = $dacl->getUsers();
		foreach ($users as $user)
			$ret[] = array("user_id"=>$user['id'], "name"=>$user['name']);

		$groups = $dacl->getGroups();
		foreach ($groups as $grp)
			$ret[] = array("group_id"=>$grp['id'], "name"=>$grp['name']);

		return $this->sendOutputJson($ret);
	}

    /**
     * Get full list of permission options from DACL
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function loadDaclPermissions($params)
    {
		$ret = array();

		$dacl = DaclLoader::getInstance($this->ant->dbh)->byName($params['name']);

		// Set all permissions available
		$ret['entries'] = array();
		$entries = $dacl->getEntries();
		foreach ($entries as $ent)
		{
			$ret['entries'][] = array(
				"pname"=>$ent['permission'],
				"user_id"=>$ent['user_id'],
				"group_id"=>$ent['group_id'],
			);
		}

		// Set all permissions available
		$ret['permissions'] = array(
			array("name"=>"Full Control", "children"=>array(
				array("name"=>"View"),
				array("name"=>"Edit"),
				array("name"=>"Delete"),
				),
			),
		);

		return $this->sendOutputJson($ret);
	}

	/**
     * Save all entries for a user or group
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function saveDaclEntries($params)
	{
		$dacl = DaclLoader::getInstance($this->ant->dbh)->byName($params['name']);

		// Purge existing entries
		$dacl->clearEntries();

		// Now grant access to each entry
		foreach ($params['entries'] as $entStr)
		{
			$ent = json_decode($entStr, true);
			if ($ent)
			{
				if ($ent['group_id'])
					$dacl->grantGroupAccess($ent['group_id'], $ent['pname']);

				if ($ent['user_id'])
					$dacl->grantUserAccess($ent['user_id'], $ent['pname']);
			}
		}

		// TODO: Check if this DACL is unique to an object
		//$entity->setValue("dacl", $this->dacl->stringifyJson());
		
		return $this->sendOutputJson($dacl->save());
	}
}
