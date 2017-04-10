<?php
// ALIB
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("users/user_functions.php");
require_once("email/email_functions.awp");
require_once("lib/Stats.php");
require_once("lib/CAntObject.php");
require_once("lib/AntMail/Account.php");
require_once("lib/aereus.lib.php/CCache.php");
//require_once("lib/aereus.lib.php/CSessions.php");
require_once("lib/aereus.lib.php/antapi.php");
require_once('security/security_functions.php');
require_once('lib/ServiceLocatorLoader.php');

// Define reserved group ids
define("GROUP_USERS", -4);
define("GROUP_EVERYONE", -3);
define("GROUP_CREATOROWNER", -2);
define("GROUP_ADMINISTRATORS", -1);
// Define reserved users
define("USER_ADMINISTRATOR", -1);
define("USER_CURRENT", -3);
define("USER_ANONYMOUS", -4);
define("USER_SYSTEM", -5);
define("USER_WORKFLOW", -6);
// Define reserved team variables
define("TEAM_CURRENTUSER", -3);

/**
 * AntUser class
 */
class AntUser
{
	var $id;
	var $name;
	var $fullName;
	var $account; // Depricated, use accountId now
	var $accountId;
	var $accountName;
	var $dbh;
	var $timezoneName;
	var $themeName;
	var $userObj; // CAntObject representation of user data
	var $emailUserId = null;
	var $emailUserName = null;
	var $email = null;
	var $groups = null; // Array of groups this user belongs to. Initializes to null but set to array once getGroups is called
    var $emailUserNames = null;
    
    private static $dbh_static; // This is used for static function e.g. public static authenticate();

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param int $id The id of the user to load, if null then current user is loaded
	 * @param Ant $ant Instance of Ant account object. If null the try to get from global $ANT variable.
	 */
	public function __construct($dbh, $id=null, $ant=null)
	{
		global $ANT, $_GET, $_SERVER;

		$antObj = ($ant) ? $ant : $ANT;

		$this->dbh = $dbh;
		self::$dbh_static = $dbh;

		// Eventually Ant will be required before loading AntUser, for now we can still pull from session 'aid'
		// variable if needed.
		if ($antObj)
		{
			$this->accountId = $antObj->accountId;
			$this->accountName = $antObj->accountName;
		}
		else if (Ant::getSessionVar("aid") && $_REQUEST["Authentication"])
		{
			// $_REQUEST["Authentication"] was added above to check to make
			// sure the aid cookie was set with the new authentication service
			// - joe, 2015-07-15
			$antsys = new AntSystem();
			$this->accountId = Ant::getSessionVar("aid");
			$this->accountName = $antsys->getAccountInfoByUId($this->accountId);
		}

		$this->account = $this->accountId;

		if(isset($_GET['auth']))
		{
			$this->id = $this->authenticateEnc($_GET['auth']);
			if ($this->id)
				$this->name = UserGetName($dbh, $this->id);
		}
		else
		{
			$this->id = ($id) ? $id : Ant::getSessionVar("uid");	
			$this->name = Ant::getSessionVar("uname");	
		}

		if ($this->id!=null)
		{
			$this->userObj = new CAntObject($dbh, "user", $this->id);

			// Automatically create system accounts
			if (!$this->userObj->isLoaded() && ($this->id == USER_SYSTEM || $this->id == USER_WORKFLOW || $this->id == USER_ADMINISTRATOR))
			{
				if ($this->id == USER_ADMINISTRATOR)
				{
					$this->initAdminAccount();
				}

				if ($this->id == USER_WORKFLOW)
				{
					$dbh->Query("insert into users(id, name, password, full_name) 
									values('".USER_WORKFLOW."', 'workflow', '', 'Workflow');");
				}

				if ($this->id == USER_SYSTEM)
				{
					$dbh->Query("insert into users(id, name, password, full_name) 
									values('".USER_SYSTEM."', 'system', '', 'System');");
				}

				$this->userObj = new CAntObject($dbh, "user", $this->id);
			}

			$this->fullName = $this->userObj->getValue("full_name");
			$this->name = $this->userObj->getValue("name");
			$this->teamId = $this->userObj->getValue("team_id");
			$this->themeName = UserGetTheme($this->dbh, $this->id, 'name');
			$this->themeCss = UserGetTheme($this->dbh, $this->id, 'css');
			$this->theme_name = $this->themeName;

			if (!$this->fullName)
				$this->fullName = $this->name;

			if (Ant::getSessionVar("tz"))
			{
				$this->timezoneName = Ant::getSessionVar("tz");
			}
			else if (function_exists("geoip_record_by_name") && function_exists("geoip_time_zone_by_country_and_region") && isset($_SERVER['REMOTE_ADDR']))
			{
				$region = @geoip_record_by_name($_SERVER['REMOTE_ADDR']);
				
				if ($region)
					$this->timezoneName = @geoip_time_zone_by_country_and_region($region['country_code'], $region['region']); 
			}

			if (!$this->timezoneName)
				$this->timezoneName = $this->userObj->getValue("timezone");
			//timezone_name_from_abbr($this->timezoneCode) : "";

			if (!$this->timezoneName)
				$this->timezoneName = "America/Los_Angeles"; // Default to pacific time if we failed to get the timestamp

			$this->dbh->SetTimezone($this->timezoneName);
		}
	}

	/**
	 * Get list of groups this user is a member of
	 *
	 * @return array(int) Array of groups this user is a member of
	 */
	public function load()
	{
	}

	/**
	 * Return the id of this user
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Load a user id by name
	 *
	 * If the database parameter is passed then this function can be called statically
	 *
	 * @param string $username The unique user name
	 * @param CDatabase $dbh Database handle
	 * @return AntUser if found
	 */
	public function getIdFromName($username, $dbh=null)
	{
		if (!$dbh && $this)
			$dbh = $this->dbh;

		if (!$dbh || !$username)
			return false;

		$id = false;

		$query = "select id from users where lower(name)=lower('".$dbh->Escape($username)."')";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
			$id = $dbh->GetValue($result, 0, "id");

		return $id;
	}

	/**
	 * Get list of groups this user is a member of
	 *
	 * @return array(int) Array of groups this user is a member of
	 */
	public function getGroups()
	{
		if ($this->groups == null && $this->id)
		{
			$dbh = $this->dbh;
			$UID = $this->id;
			$this->groups = $this->getValue("groups");

			/*
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
						$this->groups[] = intval($gid);
					}
				}
				$dbh->FreeResults($result);

				$cache->set($dbh->dbname."/users/$UID/groups", $this->groups);
			}
			else
			{
				$this->groups = $cval;
			}
			 */

			// Add "everyone" group because everyone is a member of everyone :)
			$this->groups[] = GROUP_EVERYONE;

			// Make sure workflow and system have full control
			if (($UID == USER_ADMINISTRATOR || $UID == USER_WORKFLOW || $UID == USER_SYSTEM) && in_array(GROUP_ADMINISTRATORS, $this->groups)==false)
				$this->groups[] = GROUP_ADMINISTRATORS;
		}

		return $this->groups;
	}

	/**
	 * Add this user to a group
	 *
	 * $param int $groupId The unique ID of the group to join this memeber to
	 * @return bool true on success, false on failure
	 */
	public function addToGroup($groupId)
	{
		$ret = false;

		if ($this->id && is_numeric($groupId))
		{
			$this->userObj->setMValue("groups", $groupId);
			$this->groups = null;
			// Refresh local groups array
			$this->getGroups();
			$ret = $this->userObj->save();

			/*
			$dbh = $this->dbh;

			// Make sure the user is not already a member of this group
			if ($this->isGroupMember($groupId))
				return false;

			$dbh->Query("INSERT INTO user_group_mem(user_id, group_id) VALUES('".$this->id."', '$groupId')");
			
			// Mark cache as dirty so groups will reaload
			$this->clearCache();
			// Reset groups for this user
			$this->getGroups();
			 */
		}

		return $ret;
	}

	/**
	 * Find out if the user is a member of this group
	 *
	 * $param int $groupId The unique ID of the group to join this memeber to
	 * @return bool true if user is a meber of $groupId, false if not
	 */
	public function isGroupMember($groupId)
	{
		$ret = false; // Default to not a member

		// Get current membership and check for a match
		$groups = $this->getGroups();
		foreach ($groups as $gid)
		{
			if ($groupId == $gid)
			{
				$ret = true;
			}
		}

		return $ret;
	}

	/**
	 * Set user object value
	 *
	 * @param string $vname The name of the field to get the value for
	 * @param string $vval The value to set for field named $vname
	 */
	public function setValue($vname, $vval)
	{
		$this->userObj->setValue($vname, $vval);
	}

	/**
	 * Set the password
	 */
	public function setPassword($password)
	{
		// We do not need to hash it here because the new user entity will hash
		$this->userObj->setValue("password", $password);
	}

	/**
	 * Get user object value
	 *
	 * @param string $vname The name of the field to get the value for
	 * @return string Value of $vname
	 */
	public function getValue($vname)
	{
		return $this->userObj->getValue($vname);
	}

	/**
	 * Save user object
	 */
	public function save()
	{
		// Send email > username to antsystem.account_users table
		if ($this->userObj->fieldValueChanged("email"))
		{
			$antsys = new AntSystem();
			$antsys->setAccountUserEmail($this->accountId, $this->name, $this->getValue("email"));
		}
		
		return $this->userObj->save();
	}

	/**
	 * Delete a user, this is never recommended except in extremely rare circumstances like unit tests
	 */
	public function removeHard()
	{
		$this->userObj->removeHard();
		$result = $this->dbh->Query("delete from user_group_mem where user_id='".$this->id."'");
	}

	/**
	 * Get Aereus customer id for this user
	 *
	 * @return integer The unique Aereus customer ID
	 */
	public function getAereusCustomerId()
	{
		$ret = "";
		if ($this->id)
		{
			$result = $this->dbh->Query("select customer_number from users where id='".$this->id."'");
			if ($this->dbh->GetNumberRows($result))
			{
				$ret = $this->dbh->GetValue($result, 0, "customer_number");
			}

			if (!$ret)
			{
				$ret = $this->setAereusCustomerId();
			}
		}

		return $ret;
	}

	/**
	 * Set aereus customer id for this user
	 *
	 * If $cid param is set, then just update the users table. If it is not set,
	 * then this function will create a new customer.
	 *
	 * @param integer $cid The id of the customer to set for this usr
	 * @return integer The unique Aereus customer ID
	 */
	public function setAereusCustomerId($cid=null)
	{
		$ret = null;
		$dbh = $this->dbh;

		if ($cid)
		{
			$this->dbh->Query("update users set customer_number='$cid' where id='".$this->id."';");
			return $cid;
		}

		$sm = ServiceLocatorLoader::getInstance($dbh)->getServiceManager();
		$settings = $sm->get("Netric/Settings/Settings");
		$acustid = $settings->get("general/customer_id");

		// If we do not have a customer id, then let's get it from AntSystem and save it in the settings after
		if(!$acustid) {
			$antsys = new AntSystem();
			$acustid = $antsys->getAereusCustomerId($this->accountId);
			$settings->set("general/customer_id", $acustid);
		}

		//echo $acustid = Ant::getAereusCustomerId($this->dbh, $this->accountId);

		// Create customer record for this users
		$api = new AntApi(AntConfig::getInstance()->aereus['server'], 
						  AntConfig::getInstance()->aereus['user'],
						  AntConfig::getInstance()->aereus['password']);
		$cust = $api->getCustomer();

		// Check if is already a contact
		$cid = $cust->getIdByEmail($this->getEmail());
		if ($cid)
			$cust->open($cid);

		// Update customer record
		$parts = explode(" ", $this->fullName);
		if ($parts[0])
			$cust->setValue("first_name", $parts[0]);
            
		if (isset($parts[1]))
			$cust->setValue("last_name", $parts[1]);
            
		$cust->setValue("email", $this->getEmail());
		$cust->setValue("notes", "ANT User automatically generated from $acustid");
		$cust->setValue("status_id", 1); // Active
		$cust->setValue("type_id", CUST_TYPE_CONTACT);
		$cust->setMValue('groups', "210"); // ANT Users Group
		$custid_contact = $cust->save();

		// Save the customer number for future calls
		$this->setValue("customer_number", $custid_contact);
		$this->save();

		// Add relationship to customer account
		if ($acustid)
		{
			// Add user association to main account customer record
			$custapi = $api->getCustomer($acustid);
			if (!$custapi->getValue("primary_contact")) // if not yet set, the first login should be
				$custapi->setValue("primary_contact", $custid_contact);
			$custapi->addRelationship($custid_contact);
			$ret = $custapi->save();
		}

		return $custid_contact;
	}

	// Get form for this user based on object type and team id
	public function getObjectFormXml($obj_type)
	{
		if ($this->id)
		{
			$otid = objGetAttribFromName($this->dbh, $obj_type, "id");
			if ($otid)
			{
				$result = $this->dbh->Query("select form_layout_xml from app_object_type_frm_layouts, users
												where app_object_type_frm_layouts.team_id=users.team_id and 
												app_object_type_frm_layouts.type_id='$otid' and users.id='".$this->id."';");
				if ($this->dbh->GetNumberRows($result))
					return $this->dbh->GetValue($result, 0, "form_layout_xml");
			}
		}
		return "";
	}

	/**
	 * Depricated: there is no longer any such thing as an email user
	 */
	public function getEmailUserId()
	{
		return null;
	}

	function getEmailUserName()
	{
		$dbh = $this->dbh;

		if (!$this->emailUserName)
			$this->emailUserName = EmailGetUserName($dbh, $this->id);

		return $this->emailUserNames;
	}

	/**
	 * Get users default email address
	 *
	 * @param CDatabase $dbh Optional, must be set if calling statucally
	 * @param integer $userid Optional, must be set if calling statucally
	 */
	public function getEmail($dbh = null, $userid = null)
	{
		// Make sure we have dbh & user
		if (!$dbh && isset($this) && get_class($this) == __CLASS__)
			$dbh = $this->dbh;

		if (!$userid && isset($this) && get_class($this) == __CLASS__)
			$userid = $this->id;

		if (!$dbh || !$userid)
			return null;

		if (isset($this) && get_class($this) == __CLASS__)
		{
			if (!$this->email)
			{
				if ($this->getValue("email"))
					$this->email = $this->getValue("email");
				else
					$this->email = UserGetEmail($dbh, $userid);
			}

			return $this->email;
		}
		else
		{
			return UserGetEmail($dbh, $userid);
		}
	}
    
	/**
	 * Make sure that email accounts exist for each available email domain
	 *
	 * @param string $pass Optional password, if set then update cached passwords in email accounts
	 * @return string The default email address
	 */
	public function verifyEmailDomainAccounts($pass=null)
	{
		$dbh = $this->dbh;

		$ant = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator()->getAnt();
		$defDomain = $ant->getEmailDefaultDomain($this->accountName, $dbh);

		// Get the current default email address
		$result = $dbh->Query("SELECT address from email_accounts WHERE user_id='".$this->id."' AND f_default='t'");
		if ($dbh->GetNumberRows($result))
			$defEmail = $dbh->GetValue($result, 0, "address");

		$antsys = new AntSystem();
		$domains = $antsys->getEmailDomains($this->accountId);
        
		if (is_array($domains))
		{
			foreach ($domains as $domain)
			{
				$email = $dbh->Escape($this->name."@".$domain);

				if (!$dbh->GetNumberRows($dbh->Query("SELECT id FROM email_accounts WHERE address='$email' and user_id='".$this->id."'")))
				{
					$def = (isset($defEmail) && $defDomain == $domain) ? 't' : 'f'; // If no default is set
					$dbh->Query("INSERT INTO email_accounts(name, address, reply_to, user_id, f_default, f_system) 
								 VALUES('".$dbh->Escape($this->fullName)."', '$email', '$email', '".$this->id."', '$def', 't');");

					// Make sure that the email user exists in the mailsystem
					$antsys->verifyEmailUser($this->accountId, $email, true, $pass);
				}
				else if ($pass)
				{
					// Make sure that the domain account is updated
					$dbh->Query("UPDATE email_accounts SET password='".encrypt($pass)."' WHERE address='$email' and user_id='".$this->id."'");
					$antsys->verifyEmailUser($this->accountId, $email, true, $pass);
				}
			}
		}

		$emlObj = CAntObject::factory($this->dbh, "email_message", null, $this);
		$emlObj->getGroupingEntryByName("mailbox_id", "Inbox");
	}

	/**
	 * Get email accounts for this user
	 *
	 * @param bool $filterParams Only retrieve the default email account
	 * @param bool $dataArray If true return accounts as a multi-dim array
	 * @return array If $dataArray param is set, return associative array. Otherwise an array of AntMail_Account objects.
	 */
	public function getEmailAccounts($filterParams=array(), $dataArray=false)
	{
		if (!$this->id)
			return false;

		$dbh = $this->dbh;
		/*$dbh = $this->dbh;
		$sql = "SELECT id FROM email_accounts";

		if($filterParams) {
			foreach ($filterParams as $pname=>$pval)
				$sql .= " AND $pname='$pval'";
		}

		$result = $dbh->Query($sql);*/

		// Setup the netric service locator
		$sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
		$index = $sl->get("EntityQuery_Index");

		// Get the entity query for email_account object type
		$query = new \Netric\EntityQuery('email_account');

		if($filterParams) {
			foreach ($filterParams as $pname=>$pval)
			{
				// Use the Where model for our filter
				$filter = new \Netric\EntityQuery\Where($pname);
				$filter->equals($pval);
				$where[] = $filter->toArray();
			}

			// Parse the query and apply the where params
			$params["where"] = $where;
			\Netric\EntityQuery\FormParser::buildQuery($query, $params);
		}

		// Execute the query
		$result = $index->executeQuery($query);

		$ret = array();
		for ($i = 0; $i < $result->getNum(); $i++)
		{
			$row = $result->getEntity($i)->toArray();

			$acc = new AntMail_Account($dbh, $row['id'], $this);
			$ret[] = ($dataArray) ? $acc->toArray() : $acc;
		}

		return $ret;
	}

	/**
	 * Create required users like administrator
	 */
	public function initAdminAccount()
	{
		$dbh = $this->dbh;

		if (!$dbh->GetNumberRows($dbh->Query("SELECT id from user_groups where id='".GROUP_ADMINISTRATORS."'")))
		{
			$dbh->Query("insert into user_groups(id, name, f_system) 
								 values('".GROUP_ADMINISTRATORS."', 'Administrators', 't');");
		}

		if (!$dbh->GetNumberRows($dbh->Query("SELECT id from users where id='".USER_ADMINISTRATOR."'")))
		{
			// Add user
			$dbh->Query("insert into users(id, name, password, full_name) 
								 values('".USER_ADMINISTRATOR."', 'administrator', '2ac9cb7dc02b3c0083eb70898e549b63', 'Admin Account');");

			// Add to administrators group
			$this->dbh->Query("insert into user_group_mem(user_id, group_id) values('".USER_ADMINISTRATOR."', '".GROUP_ADMINISTRATORS."');");
		}
	}

	/**
	 * Get the name of the team of the current user
	 *
	 * @return string the team title/name
	 */
	public function getTeamName()
	{
		$ret = "";
		$dbh = $this->dbh;

		if ($this->teamId)
		{
			$result = $dbh->Query("select name from user_teams where id='".$this->teamId."'");
			if ($dbh->GetNumberRows($result))
				$ret = $dbh->GetValue($result, 0, "name");
		}

		return $ret;

	}

	/**
	 * Get setting for user
	 *
	 * @param string $key	The key of the setting to retrieve
	 * @return string return value
	 */
	public function getSetting($key)
	{
		$dbh = $this->dbh;
        $key_val = null;

		if (is_numeric($this->id) && $key)
		{
			$cache = CCache::getInstance();
			$cval = $cache->get($dbh->dbname."/users/preferences/".$this->id."/$key");
			if ($cval === false)
			{
				$query = "select key_val from system_registry where user_id='".$this->id."' and key_name='$key'";
				$result = $dbh->Query($query);
				if ($dbh->GetNumberRows($result))
				{
					$row = $dbh->GetNextRow($result, 0);
					$key_val = $row["key_val"];
					$cache->set($dbh->dbname."/users/preferences/".$this->id."/$key", $key_val);
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

	/**
	 * Set setting for user
	 *
	 * @param string $key	The key of the setting to set
	 * @param string $value	The value to store for the associated key
	 * @return string return value
	 */
	public function setSetting($key, $value)
	{
		$dbh = $this->dbh;

		if (is_numeric($this->id) && $key)
		{
			$cache = CCache::getInstance();
			$cache->remove($dbh->dbname."/users/preferences/".$this->id."/$key");

			// Look to see if preference value is already set
			$result = $dbh->Query("select id from system_registry where key_name='$key' and user_id='".$this->id."' and key_val='$value'");
			if ($dbh->GetNumberRows($result))
			{
				$dbh->FreeResults($result);
				return false;
			}
			
			// Look to see if this preference has already been saved
			$result = $dbh->Query("select id from system_registry where key_name='$key' and user_id='".$this->id."'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$dbh->FreeResults($result);
				$dbh->Query("update system_registry set key_val='".$dbh->Escape($value)."' where id='".$row['id']."'");
			}
			else
			{
				$dbh->Query("insert into system_registry(user_id, key_val, key_name) values('".$this->id."', '".$dbh->Escape($value)."', '$key')");
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @depricated This is being replaced with Netric\Authentication\AuthenticationService
	 *
	 * Authenticate a user
	 *
	 * May be called statically so long as $dbh is passed
	 *
	 *
	 * @param string $user The user name
	 * @param string $password Password of user
	 * @param CDatabase $dbh This is required if function is called statically
	 * @return integer The user id on success and = on failure
	 */
	public static function authenticate($username, $password, $dbh=null)
	{
		if (!$dbh && self::$dbh_static)
			$dbh = self::$dbh_static;
		else if (!$dbh)
			return 0;

		// Get new netric authentication service
		$sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
		$authService = $sl->get("AuthenticationService");

		$query = "SELECT id, password, password_salt FROM users 
				  WHERE lower(users.name)=lower('" . $dbh->Escape($username) . "')";

		//			and (users.password='" . $dbh->Escape($password) . "' or users.password=md5('" . $dbh->Escape($password) . "'))";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);

			// Check to see if the user is on old authentication scheme
			if (!$row['password_salt'] && ($row['password'] == $password || $row['password'] == md5($password)))
			{
				/*
				 * If we force an update in the user entity it will create
				 * a new salt and encrypt the password appropriately. This is a
				 * perfect time to do the upgrade since we have the user-supplied
				 * clear text password
				 */
				$loader = $sl->get("EntityLoader");
				$user = $loader->get("user", $row['id']);
				$dm = $sl->get("Entity_DataMapper");

				// Change to temp password to force reset
				$user->setValue("password", "TMPWILLRESET");
				$dm->save($user);

				// Now reset to real password which will encode properly
				$user->setValue("password", $password);
				$dm->save($user);
			}

			// Authenticate using the auth service
			$ret = $authService->authenticate($username, $password);
			if ($ret)
			{
				$res = $row['id'];
				Stats::increment("logins.success");
			}
			else
			{
				$res = 0;
				Stats::increment("logins.failed");
			}		
		}
		else
		{
			$res = 0;
			Stats::increment("logins.failed");
		}
		
		$dbh->FreeResults($result);
		
		return $res;
	}

	/**
	 * @depricated This has been replaced with the AuthenticationService
	 * "Authentication" header which is far more secure and unified so
	 * every client authenticates the same way.
	 *
	 * Right now it is left in place soley to interact with legacy API
	 * but it is a ticking timebomb because as soon as someone logs into
	 * netric with the administator account it will cause the APIs
	 * to fail so we should probably move it over to the new contorllers
	 * and authentication service as soon as possible.
	 *
	 * Authenticate a user encoded string
	 *
	 * @param string $auth The user and password encoded into username:md5(pass)
	 * @param CDatabase $dbh This is required if function is called statically
	 * @return integer The user id on success and 0 on failure
	 */
	public function authenticateEnc($auth, $dbh=null)
	{
		if (!$dbh && $this && $this->dbh)
			$dbh = $this->dbh;
		else if (!$dbh)
			return 0;

		$parts = explode(":", $auth);
		$username = base64_decode($parts[0]);

		$query = "select users.id, users.name 
					from users where lower(users.name)='" . $dbh->Escape(strtolower($username)) . "' and active is not false
					and (users.password='". $dbh->Escape($parts[1]) . "' or md5(users.password)='". $dbh->Escape($parts[1]) . "')";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$res = $row['id'];
		}
		else
		{
			$res = 0;
		}
		
		$dbh->FreeResults($result);
		
		return $res;
	}

	/**
	 * Get authentication string for the current user that can be used in a url
	 *
	 * @return string Encoded auth string on success and false on failure
	 */
	public function getAuthString()
	{
		if ($this->id == null)
			return false;

		$query = "SELECT name, password FROM users WHERE id='".$this->id."'";
		$result = $this->dbh->Query($query);
		if ($this->dbh->GetNumberRows($result))
		{
			$row = $this->dbh->GetRow($result, 0);
			if ($row['name'] && $row['password'])
				return base64_encode($row['name']) . ":" . $row['password'];
		}

		return false;
	}

	/**
	 * Update last login timestamp
	 */
	public function logLogin()
	{
		global $_SERVER;

		if (!$this->id)
			return false;

		$remoteAddr = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "unknown";
		$this->dbh->Query("update users set last_login='now', last_login_from='" . $remoteAddr . "' where id='".$this->id."'");
	}

	/**
	 * Update last login timestamp
	 *
	 * @return bool True if the user has never logged in before
	 */
	public function isFirstLogin()
	{
		global $_SERVER;

		if (!$this->id)
			return false;

		$ret = $this->dbh->GetNumberRows($this->dbh->Query("select id from users where id='".$this->id."' and last_login is null"));

		return ($ret) ? true : false;
	}

	/**
	 * Verify that default groups exist
	 */
	public function verifyDefaultUserGroups()
	{
		$dbh = $this->dbh;

		// Administrators
		if (!$dbh->GetNumberRows($dbh->Query("select id from user_groups where id='".GROUP_ADMINISTRATORS."'")))
			$dbh->Query("insert into user_groups(id, name, f_system) values('".GROUP_ADMINISTRATORS."', 'Administrators', 't')");

		// Creator Owner
		if (!$dbh->GetNumberRows($dbh->Query("select id from user_groups where id='".GROUP_CREATOROWNER."'")))
			$dbh->Query("insert into user_groups(id, name, f_system) values('".GROUP_CREATOROWNER."', 'Creator Owner', 't')");

		// Everyone
		if (!$dbh->GetNumberRows($dbh->Query("select id from user_groups where id='".GROUP_EVERYONE."'")))
			$dbh->Query("insert into user_groups(id, name, f_system) values('".GROUP_EVERYONE."', 'Everyone', 't')");
        
        // Users
		if (!$dbh->GetNumberRows($dbh->Query("select id from user_groups where id='".GROUP_USERS."'")))
			$dbh->Query("insert into user_groups(id, name, f_system) values('".GROUP_USERS."', 'Users', 't')");

	}

	/**
	 * Verify that default groups exist
	 */
	public function verifyDefaultUserTeam()
	{
		$dbh = $this->dbh;

		// Check for default team
		if (!$dbh->GetNumberRows($dbh->Query("select id from user_teams where parent_id is null")))
			$dbh->Query("insert into user_teams(name) values('All Teams')");

		// Make sure user is assigned to the default team
		if (!$this->teamId)
		{
			$result = $dbh->Query("select id from user_teams where parent_id is null");
			if ($dbh->GetNumberRows($result))
			{
				$this->teamId = $dbh->GetValue($result, 0, "id");
				$this->setValue("team_id", $this->teamId);
				$this->save();
			}
		}
	}

	/**
	 * Make sure default users like creator/owner, system, workflow, and administrator exist
	 */
	public function verifyDefaultUsers()
	{
		$dbh = $this->dbh;

		// administrator
		if ($dbh->GetNumberRows($dbh->Query("select id from users where id='".USER_ADMINISTRATOR."'")) == 0)
		{
			$dbh->Query("insert into users(id, name, password, full_name) 
							values('".USER_ADMINISTRATOR."', 'administrator', 'Password1', 'Administrator');");
			$dbh->Query("insert into user_group_mem(user_id, group_id) values('".USER_ADMINISTRATOR."', '".GROUP_ADMINISTRATORS."');");
		}

		// anonymous
		if ($dbh->GetNumberRows($dbh->Query("select id from users where id='".USER_ANONYMOUS."'")) == 0)
		{
			$dbh->Query("delete from users where name='anonymous' and id!='".USER_ANONYMOUS."';");
			$dbh->Query("insert into users(id, name, password, full_name) 
							values('".USER_ANONYMOUS."', 'anonymous', '', 'Anonymous User');");
		}

		// current user
		if ($dbh->GetNumberRows($dbh->Query("select id from users where id='".USER_CURRENT."'")) == 0)
		{
			$dbh->Query("insert into users(id, name, password, full_name) 
							values('".USER_CURRENT."', 'current.user', '', 'Current User');");
		}

		// system
		if ($dbh->GetNumberRows($dbh->Query("select id from users where id='".USER_SYSTEM."'")) == 0)
		{
			$dbh->Query("insert into users(id, name, password, full_name) 
							values('".USER_SYSTEM."', 'system', '', 'System');");
		}

		// workflow
		if ($dbh->GetNumberRows($dbh->Query("select id from users where id='".USER_WORKFLOW."'")) == 0)
		{
			$dbh->Query("insert into users(id, name, password, full_name) 
							values('".USER_WORKFLOW."', 'workflow', '', 'Workflow');");
		}
	}

	/**
	 * Clear user cache
	 */
	public function clearCache()
	{        
		if ($this->id == null)
			return;

		$cache = CCache::getInstance();
		// Groups cache
		$cache->remove($this->dbh->dbname."/users/".$this->id."/groups");
		// Themes cache
		$cache->remove($this->dbh->dbname."/users/".$this->id."/theme");
		// name cache
		$cache->remove($this->dbh->dbname."/users/".$this->id."/name");

		// Object cache
		if ($this->userObj)
			$this->userObj->clearCache();
	}
    
    /**
     * Delete the existing user groups
     */
    public function purgeGroupMembership()
    {
		$this->userObj->removeMValues("groups");
		$this->groups = null;
		/*
        $dbh = $this->dbh;
        $dbh->Query("delete from user_group_mem where user_id='{$this->id}';"); 
		 */
    }
    
    /**
     * Created temporary function to fix the error on missing function
     */
    public function deleteSetting($key)
    {
        return true;
    }

	/**
	 * Check if this is a system user (like workflow or system)
	 *
	 * @return bool true if system user, false if human user
	 */
	public function isSystemUser()
	{
		return ($this->id < 0) ? true : false;
	}


	/**
	 * Get the users default calendar
	 *
	 * @return CAntObject_Calendar|false Calendar object on success, false on failure
	 */
	public function getDefaultCalendar()
	{
		$cal = false;

		// Get users calendar
		// ----------------------------------------------
		$calList = new CAntObjectList($this->dbh, "calendar", $this);
		$calList->addCondition("and", "user_id", "is_equal", $this->id);
		$calList->addCondition("and", "def_cal", "is_equal", 't');
		$num = $calList->getObjects();
		if ($calList->getNumObjects())
		{
			$cal = $calList->getObject(0);
		}

		// Add default my calendar if none exists
		if (!$cal)
		{
			$cal = CAntObject::factory($this->dbh, "calendar", null, $this);
			$cal->setValue("user_id", $this->id);
			$cal->setValue("name", "My Calendar");
			$cal->setValue("f_view", "t");
			$cal->setValue("def_cal", "t");
			// $cal->setValue("color", "2A4BD7"); // Note: Field color no longer exists and is throwing an exception.
			$cid = $cal->save();
		}

		return $cal;
	}
}
