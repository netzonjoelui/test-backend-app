<?php
/**
 * ant system class used to interact with the system gateway (not local account db/instance)
 *
 * This class should be used any time ANT needs to interact with the AntSystem gateway
 * for things like getting global account info, mail changes, and workerpool
 *
 * @category	ant
 * @package		system
 * @copyright copyright (c) 2003-2011 aereus corporation (http://www.aereus.com)
 */
require_once(dirname(__file__).'/../lib/aereus.lib.php/CCache.php');
require_once(dirname(__file__).'/../lib/aereus.lib.php/antapi.php');

/**
 * ANT System class
 */
class AntSystem
{
	/**
	 * The antsystem database
	 *
	 * @var CDatabase
	 */
	public $dbh = null;

	/**
	 * Generic string for storing the last error
	 *
	 * @var string
	 */
	public $lastError = "";

	/**
	 * Add cache for retrieving account information which should never change
	 *
	 * @var bool
	 */
	private $cache = null;

	/**
	 * Constructor
	 *
	 * Initialize database connection to the ansystem database
	 */
	function __construct()
	{
		$this->dbh = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);
		$this->cache = CCache::getInstance();
	}

	/**
	 * Get account info by name
	 *
	 * @param string $name The unique name of this account
	 * @return array('id','name','server')
	 */
	public function getAccountInfoByName($name)
	{
		return $this->getAccountInfo($name);
	}

	/**
	 * Get account info by id
	 *
	 * @param integer $id The account id
	 * @return array('id','name','server')
	 */
	public function getAccountInfoByUId($id)
	{
		return $this->getAccountInfo(null, null, $id);
	}

	/**
     * @deprecated Because dbs are now shared with schemas
     * 
	 * Get account info by database name
	 *
	 * @param string $dbname The name of the database for this account
	 * @return array('id','name','server')
	 
	public function getAccountInfoByDb($dbname)
	{
		return $this->getAccountInfo(null, $dbname);
	}
     */

	/**
	 * Get account info by name
	 *
	 * @param string $name The unique name of this account
	 * @param string $dbname Can also get info with dbname
	 * @param integer $id Can also get info with the account id
	 * @return array('id','name','server')
	 */
	public function getAccountInfo($name=null, $dbname=null, $id=null)
	{
		$ret = array('id'=>-1, 'name'=>'', 'database'=>'', 'server'=>'', 'version'=>'');

		if (!$name && !$dbname && !$id)
			return $ret;

		$cached = $this->cache->get("/antsystem/accounts/$name-$dbname-$id");

		if ($cached)
		{
			$ret = $cached;
		}
		else
		{
			$query = "SELECT id, name, \"database\", server, version FROM accounts WHERE ";
			if ($name)
				$query .= "name='$name'";
			if ($dbname)
				$query .= "database='$dbname'";
			if ($id)
				$query .= "id='$id'";
			
			$result = $this->dbh->Query($query);
			if ($this->dbh->GetNumberRows($result))
			{
				$ret = $this->dbh->GetRow($result, 0);
				$this->cache->set("/antsystem/accounts/$name-$dbname-$id", $ret);
			}
		}
        
		return $ret;
	}

	/**
	 * Create a new ANT account and create schema
	 *
	 * @param string $name The unique name of this account
	 * @return array('id','name','server') or false on failure
	 */
	public function createAccount($name, $version="", $customer_number="")
	{
		if (empty($name))
			throw new \Exception("\$name is a required param for creating an account!");

		// Escape name
		$name = str_replace(" ", "_", $name);
		$name = str_replace("'", "", $name);
		$name = str_replace("\"", "", $name);
		$name = strtolower($name);
		//$dbname = "ant_".$name;
		// We are now merging accounts into a single database as per recommendations from the postgresql team
		$dbname = AntConfig::getInstance()->db['accdb']; ;

		// Check if account exists
		$info = $this->getAccountInfoByName($name);
		if ($info['id'] != -1)
		{
			$this->lastError = "Could not create account because an account with that name already exists";
			return false;
		}

		// Create account in antsystem
		$ret = $this->dbh->Query("INSERT INTO accounts(name, database) 
									VALUES('".$this->dbh->Escape($name)."', '".$this->dbh->Escape($dbname)."');
								   SELECT currval('accounts_id_seq') as id;");
		if (!$this->dbh->GetNumberRows($ret))
		{
			$this->lastError = "Could not create account in antsystem database";
			return false;
		}

		$aid = $this->dbh->GetValue($ret, 0, "id");
		if (!$aid)
		{
			$this->lastError = "Unable to retrieve id of new account";
			return false;
		}

		if ($version)
			$this->dbh->Query("UPDATE accounts SET version='$version' WHERE id='$aid'");

		if ($customer_number)
			$this->dbh->Query("UPDATE accounts SET customer_number='$customer_number', billing_customer_number='$customer_number' WHERE id='$aid'");

		// Create accounts database if it does not already exist
		if (!$this->dbh->GetNumberRows($this->dbh->Query("SELECT 1 as result from pg_database WHERE datname='$dbname'")))
			$this->dbh->Query("CREATE DATABASE $dbname;");

		// Create ant account object
		$acct = new Ant($aid);

		// Initize schema
		$sversion = $acct->schemaCreate();
		if (!$ret)
		{
			$this->lastError = "Schema creation failed. Error = ".$acct->lastError;
			return false;
		}
		// Set schema version from create script
		$acct->settingsSet("system/schema_version", $sversion);

		// Run updates (if any) but do not process the updates in the 'always' (first param false) 
		// directory until after the default data has been imported.
		$ret = $acct->schemaUpdate(false, false);
		if (!$ret)
		{
			$this->lastError = "Could not update the schema. Error = ".$acct->lastError;
			return false;
		}

		// Import default data
		$ret = $acct->loadDefaultSchemaData(false);
		if (!$ret)
		{
			$this->lastError = "Unable to load default data";
			return false;
		}

		// Now run updates again which should only process scripts in the 'always directory (first param set to true)
		$ret = $acct->schemaUpdate(true, false);
		if (!$ret)
		{
			$this->lastError = "Could not run 'always' updates. Error = ".$acct->lastError;
			return false;
		}

		return array('id'=>$aid, 'name'=>$name, 'database'=>$dbname);
	}

	/**
	 * Delete an account and it's database.
	 *
	 * WARNING: This will permanantly delete the data
	 *
	 * @param string $name The unique name of this account
	 * @return bool true on success, false on failure
	 */
	public function deleteAccount($name, $disconnect=true)
	{
		/* No longer needed now that we use schemas
		if ($disconnect)
		{
			$this->dbh->Query("SELECT pg_catalog.pg_terminate_backend(procpid)
								FROM pg_catalog.pg_stat_activity WHERE datname='ant_".$this->dbh->Escape($name)."';");
		}
		 */

		// Get account info
		$acc = $this->getAccountInfoByName($name);

		// Make sure account was found
		if (!$acc['id'] == -1 || !$acc['id'] == "-1")
			return false;

		// Delete from system accounts database
		$this->dbh->Query("DELETE FROM accounts WHERE name='".$this->dbh->Escape($name)."'");

		// Clear cache with this account name - next time it will have a new id
		$this->cache->remove("/antsystem/accounts/$name--");
		$this->cache->remove("/antsystem/accounts/-{$acc['database']}-");
		$this->cache->remove("/antsystem/accounts/--{$acc['id']}");

		// Clear common system objects in case we re-create
		$objs = array("user", "activity", "customer");
		foreach ($objs as $oname)
			$this->cache->remove($acc['database'] . "/objects/" . $oname);
		
		// Drop the scehma if it exists
		$accdb = new CDatabase(AntConfig::getInstance()->db['syshost'], $acc['database']);
		$accdb->Query("DROP SCHEMA acc_" . $acc['id'] . " CASCADE;");

		/*
		// Drop database if exists
		$ret = $this->dbh->Query("SELECT datname from pg_catalog.pg_database where datname='ant_".$this->dbh->Escape($name)."';");
		if ($this->dbh->GetNumberRows($ret))
			$this->dbh->Query("DROP DATABASE ant_".$this->dbh->Escape($name).";");
		 */
	}

	/**
	 * @depricated This is now a function only of Ant class and stored locally in the db
	 * Every account in ANT should be associated with a customer id (account) in the Aereus account
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @return integer The id of the customer(type=acount) that is the owner of this account
	 */
	public function getAereusCustomerId($aid)
	{
		$ret = null;

		// Get database to use from account
		$result = $this->dbh->Query("select customer_number from accounts where id='$aid'");
		if ($this->dbh->GetNumberRows($result))
		{
			$ret = $this->dbh->GetValue($result, 0, "customer_number");

			// TODO: if customer number is still blank, then add a generic customer account in Aereus ANT account
		}

		return $ret;
	}

	/**
	 * Every account in ANT should be associated with an account in Netric
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @return AntApi_Object type=co_ant_account on success, null on failure
	 */
	public function getAereusAccount($aid)
	{
		$obj = null;

		$api = new AntApi(AntConfig::getInstance()->aereus['server'], 
						  AntConfig::getInstance()->aereus['user'],
						  AntConfig::getInstance()->aereus['password']);
		
		$olist = $api->getObjectList("co_ant_account");
		$olist->addCondition("and", "aid", "is_equal", $aid);
		$olist->getObjects();
		if ($olist->getNumObjects())
		{
			$obj = $olist->getObject(0);
		}
		else
		{
			// Create the account
			$accInfo = $this->getAccountInfoByUId($aid);
			$obj = $api->getObject("co_ant_account");
			$obj->setValue("name", $accInfo['name']);
			$obj->setValue("aid", $aid);
			//$api->debug = true;
			$oid = $obj->save();

			if (!$oid)
				$obj = null; // error
		}

		return $obj;
	}

	/**
	 * Every account in ANT should be associated with a customer id (account) in the Aereus account
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @param integer $customerid The customer id to set for this account
	 * @return bool true on success false on failure
	 */
	public function setAereusCustomerId($aid, $customerid)
	{
		$result = $this->dbh->Query("UPDATE accounts SET customer_number='".$this->dbh->Escape($customerid)."', 
									billing_customer_number='".$this->dbh->Escape($customerid)."' WHERE id='$aid'");

		return ($result === false) ? false : true;
	}

	/**
	 * Get list of email domains associated with this account
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @return string[] Array of domains. The default is a local setting for each account.
	 */
	public function getEmailDomains($aid)
	{
		$ret = array();

		if (!$aid)
			return $ret;

		// Get database to use from account
        $query = "SELECT domain FROM email_domains WHERE account_id='$aid'";
		$result = $this->dbh->Query($query);
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
            $domain = $this->dbh->GetValue($result, $i, "domain");
			$ret[$domain] = $domain;
		}

		return $ret;
	}

	/**
	 * Add a domain to the mailsystem
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @param string $domains The domain name to add
	 * @return bool true on success, false on failure
	 */
	public function addEmailDomain($aid, $domain)
	{
		if (!$aid || !$domain)
			return false;

		// Add to mail system
		if (!$this->dbh->GetNumberRows($this->dbh->Query("select domain from email_domains where domain='$domain' and account_id='$aid'")))
        {
            $query = "insert into email_domains(domain, description, account_id, active) values('$domain', '$domain', '$aid', 't')";
            $this->dbh->Query($query);
        }

		return true;
	}

	/**
	 * Delete a domain from the mailsystem
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @param string $domains The domain name to add
	 * @return bool true on success, false on failure
	 */
	public function deleteEmailDomain($aid, $domain)
	{
		if (!$aid || !$domain)
			return false;

		// Clean out email users
		$this->dbh->Query("delete from email_users where email_address like '%@$domain' and account_id='$aid'");
        
        // clean out aliases too
        $this->dbh->Query("delete from email_alias where address like '%@$domain' and account_id='$aid'");

		// Remove domain
		$this->dbh->Query("delete from email_domains where domain='$domain' and account_id='$aid'");

		return true;
	}
    
    /**
     * Add an alias to the mailsystem
     *
     * @param integer $aid The account id gather the customer number for     
     * @return array
     */
    public function addAlias($aid, $address, $goto, $insertMode)
    {
        if ($aid && $address)
        {            
            $num = 0;
            if ($insertMode)
            {
                $query = "select * from email_alias where address='$address' and account_id='$aid'";
                $result = $this->dbh->Query($query);
                $num = $this->dbh->GetNumberRows($result);                
            }
            
            if($num > 0)
                $ret = array("error" => "Alias already exists", "errorId" => 1);
            else
            {
                $query = "insert into email_alias(address, goto, account_id, active) values('$address', '$goto', '$aid', 't')";
                $this->dbh->Query($query);
                
                $arrAddress = explode("@", $address);
                $ret = array("address" => $address, "gotoAddress" => $goto, "aliasName" => $arrAddress[0], "domainName" => $arrAddress[1], "insertMode" => $insertMode);
            }
            
        }
        else
            $ret = array("error" => "account_id and address are required params", "errorId" => 1);
        
        return $ret;
    }
    
    /**
     * Delete an alias
     *
     * @param integer $aid The account id gather the customer number for
     * @return array
     */
    public function deleteAlias($aid, $address)
    {
        if (!$aid || !$address)
            return false;

        // Clean out email users
        $this->dbh->Query("delete from email_alias where address = '$address' and account_id='$aid'");

        return true;
    }

	/**
	 * Delete an email user
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @param string $emailAddress The address to delete
	 * @return bool true on success, false on failure
	 */
	public function deleteEmailUser($aid, $emailAddress)
	{
		if (!$aid || !$emailAddress)
			return false;

		// Clean out email users
		$this->dbh->Query("delete from email_users where email_address='$emailAddress' and account_id='$aid'");

		return true;
	}

	/**
	 * Get list of email aliases associated with this account
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @return array(array('address', 'goto')) Array of associated array of aliases.
	 */
	public function getEmailAliases($aid)
	{
		$ret = array();

		if (!$aid)
			return $ret;

		// Get database to use from account
		$result = $this->dbh->Query("SELECT address, goto FROM email_alias WHERE account_id='$aid'");
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
            $row = $this->dbh->GetRow($result, $i);
            
            $address = $row['address'];
            $arrAddress = explode("@", $address);
			$ret[$address] = array("address"=>$address, "gotoAddress"=>$row['goto'], "aliasName" => $arrAddress[0], "domainName" => $arrAddress[1]);
		}

		return $ret;
	}

	/**
	 * Verify that an email address exists in the mailsystem
	 *
	 * If it is not already in the mailsystem gateway it will be added
	 *
	 * @param integer $aid The account id gather the customer number for
	 * @param string $emailAddress The email address to check
	 * @param bool $createIfMissing If set to try (default), it will create the email user if missing
	 * @param string $pass Optional password, if set then update cached passwords in email accounts
	 * @return bool true on success, false on failure
	 */
	public function verifyEmailUser($aid, $emailAddress, $createIfMissing=true, $pass=null)
	{
		if (!$aid || !$emailAddress)
			return false;

		// Look for user
		$query = "SELECT email_address FROM email_users WHERE email_address='".$this->dbh->Escape($emailAddress)."'";
		if (!$this->dbh->GetNumberRows($this->dbh->Query($query)))
		{
			if ($createIfMissing)
			{
				$passEsc = ($pass) ? md5($pass) : '';
				$this->dbh->Query("INSERT INTO email_users(email_address, maildir, account_id, password) 
								   VALUES('".$this->dbh->Escape($emailAddress)."', '".$this->dbh->Escape($emailAddress)."', '$aid', '$passEsc');");
			}
			else
			{
				return false;
			}
		}
		else if ($pass)
		{
			$this->dbh->Query("UPDATE email_users SET password=md5('".$this->dbh->Escape($pass)."') 
								WHERE email_address='".$this->dbh->Escape($emailAddress)."'");
		}

		return true;
	}

	/**
	 * Get zipcode data
	 *
	 * @param int $zipcode The zipcide to query
	 */
	public function getZipcodeData($zipcode)
	{
		$ret = array();

		// First get lat and long
		$result = $this->dbh->Query("select city, state, latitude, longitude from zipcodes where zipcode='$zipcode'");
		if ($this->dbh->GetNumberRows($result))
		{
			$row = $this->dbh->GetNextRow($result, 0);
			$ret = array(
				'city' => $row['city'],
				'state' => $row['state'],
				'latitude' => $row['latitude'],
				'longitude' => $row['longitude'],
			);
		}
		$this->dbh->FreeResults($result);

		return $ret;
	}

	/**
	 * Get account and username from email address
	 *
	 * @param string $emailAddress The email address to pull from
	 * @return array("account"=>"accountname", "username"=>"the login username")
	 */
	public function getAccountFromEmail($emailAddress)
	{
		$ret = array("account"=>"", "username"=>"");

		// Check accounts for a username matching this address
		$result = $this->dbh->Query("select accounts.name as account, account_users.username FROM accounts, account_users WHERE
										accounts.id=account_users.account_id AND 
										account_users.email_address='" . $this->dbh->Escape($emailAddress) . "';");
		if ($this->dbh->GetNumberRows($result))
		{
			$row = $this->dbh->GetNextRow($result, 0);
			$ret = array(
				'account' => $row['account'],
				'username' => $row['username'],
			);
		}
		$this->dbh->FreeResults($result);

		return $ret;
	}

	/**
	 * Get account and username from email address
	 *
	 * @param int $accountId The id of the account user is interacting with
	 * @param string $username The user name - unique to the account
	 * @param string $emailAddress The email address to pull from
	 * @return bool true on success, false on failure
	 */
	public function setAccountUserEmail($accountId, $username, $emailAddress)
	{
		if (!is_numeric($accountId))
			return false;

		// Delete any existing entries for this user name attached to this account
		$this->dbh->Query("DELETE FROM account_users WHERE account_id='$accountId' AND 
									username='" . $this->dbh->Escape($username) . "'");

		// Insert into account_users table if an active email address was passed
		if ($emailAddress)
		{
			$ret = $this->dbh->Query("INSERT INTO account_users(account_id, email_address, username)
								  	VALUES(
								  		'$accountId', '" . $this->dbh->Escape($emailAddress) . "', 
								  		'" . $this->dbh->Escape($username) . "'
									);");
		}
		

		return $ret;
	}
}
