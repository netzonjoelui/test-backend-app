<?php
/**
 * Base class for managing ANT account
 *
 * @category  Ant
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 * @author joe, sky.stebnicki@aereus.com
 */

/**
 * Initialize new Netric application code with autoloaders
 */
require_once("init_application.php");

require_once("lib/AntLog.php");
require_once("lib/CDatabase.awp");
require_once("lib/AntSystem.php");
require_once("lib/CAntObject.php");
require_once("settings/settings_functions.php");		
require_once("lib/aereus.lib.php/CCache.php");
require_once("lib/ServiceLocator.php");
require_once("lib/NetricApplicationLoader.php");
require_once("lib/ServiceLocatorLoader.php"); // Temp
//require_once("lib/aereus.lib.php/CSessions.php");

/**
 * Define editions
 */
define("EDITION_FREE", 1);
define("EDITION_PROFESSIONAL", 2);
define("EDITION_ENTERPRISE", 3);

/**
 * Base class for Ant account
 */
class Ant
{
	/**
	 * Account id
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * Same as $id above
	 *
	 * @depriacted
	 * @var int
	 */
	public $accountId;

	/**
	 * The unique name of this account
	 *
	 * @var string
	 */
	public $name = "";

	/**
	 * Same as $name above
	 *
	 * @depriacted
	 * @var string
	 */
	public $accountName;

	/**
	 * Handle to account database
	 *
	 * @var CDatabase
	 */
	public $dbh;

	/**
	 * Instance of cache
	 *
	 * @var CCache
	 */
	public $cache;

	/**
	 * The version of this account instance
	 *
	 * Non-null are non-release versions. Null is the current release.
	 *
	 * @var string
	 */
	public $version = "";

	/**
	 * Generic string for storing the last error
	 *
	 * @var string
	 */
	public $lastError = "";

	/**
	 * The edition of this ANT account
	 *
	 * @var integer
	 */
	private $edition = null;

	/**
	 * Validate if an account exists
	 *
	 * @var bool
	 */
	public $accountExists = false;
    
    /**
     * Handle to new Netric account
     * 
     * @var Netric\Account
     */
    private $netricAccount = null;

	/**
	 * Ant class constructor
	 *
	 * Will determine what account and database to load. If $account_id param is passed, then this instance of ANT will be
	 * exclusively tied to that account. If it is not, then the class will first try to determine what account is loaded
	 * from the url third level domain - 'test.ant.aereus.com' the account name would be 'test' - and that obviously
	 * only works when loading a page from the browser. If the account still cannot be determined, then the class will
	 * load the default account.
	 *
	 * @param int account_id defines what account to load for ANT.
	 */
	public function __construct($account_id=null)
	{
		$this->cache = CCache::getInstance();
        
		$antsys = new AntSystem();
		if ($account_id)
		{
			$this->accountId = $account_id;
			$acctinf = $antsys->getAccountInfoByUId($account_id);
			if (is_numeric($acctinf['id']))
			{
				$dbname = $acctinf['database'];
				$svr = $acctinf['server'];
				$svr = ($svr) ? $svr : AntConfig::getInstance()->db['host'];
                
                if(empty($dbname))
                    $dbname = AntConfig::getInstance()->db['db_name']; ;
                
				$this->dbh = new CDatabase($svr, $dbname);
				$this->accountName = $acctinf['name'];
				$this->version = $acctinf['version'];
			}
		}
		else
		{
            $configDbName = null;
            
            if(isset(AntConfig::getInstance()->db['db_name']))
                $configDbName = AntConfig::getInstance()->db['db_name'];
            
			$this->accountName = $this->detectAccount();
			$acctinf = $antsys->getAccountInfoByName($this->accountName);

			if (is_numeric($acctinf['id']))
			{
				$dbname = $acctinf['database'];
				$svr = $acctinf['server'];
				$svr = ($svr) ? $svr : AntConfig::getInstance()->db['host'];
                
                if(empty($dbname) && !empty($configDbName))
                    $dbname = $configDbName;
                
				$this->dbh = new CDatabase($svr, $dbname);
				$this->accountId = $acctinf['id'];
				$this->version = $acctinf['version'];
			}
		}

		if ($this->accountId)
			$this->accountExists = true;


		$this->id = $this->accountId;
		$this->name = $this->accountName;

		// Set the schema for this account
		$this->dbh->setSchema("acc_" . $this->id);

		// Set account id for namespaces
		$this->dbh->accountId = $this->id;

		// Initialize ServiceLocatorLoader
		// joe: This is a hack for backwards compatibility with old CAntObject classes
		// We shold remove this as soon as possible and practice better depedency injection patterns
		ServiceLocatorLoader::init($this); // Reference is set
	}

	/**
	 * @deprecated We now require all account databases to store accountId for schemas
     * 
     * Static function used to load Ant object from a database name
	 *
	 * Typically called when we have a CDatabase object but need to load
	 * the account database from there.
	 *
	 * @param string $dbname The unique name of the database for ant account
	 * @return Ant on success, false on failure
	 *
	public function loadFromDbName($dbname)
	{
		$antsys = new AntSystem();
		$acctinf = $antsys->getAccountInfo(null, $dbname);
		if (is_numeric($acctinf['id']) && $acctinf['id'] != -1)
		{
			$ant = new Ant($acctinf['id']);
			return $ant;
		}
		else
		{
			return false;
		}
	}
     */

	/**
	 * Get system setting
	 *
	 * Function may be called statically as long as the $dbh param is passed
	 *
	 * @return bool true if the account is valid, false if it is not
	 */
	public function accountIsActive()
	{
		return ($this->accountId) ? true : false;
	}
	
	/**
	 * Get system setting
	 *
	 * Function may be called statically as long as the $dbh param is passed
	 *
	 * @param string $name the name of the key to retrieve
	 * @return string the value found for the 'key'
	 */
	public function settingsGet($name, $dbh=null)
	{
		if (!$dbh && isset($this)) $dbh = $this->dbh;

		if (!$dbh)
			return false;
		$ret = "";

		$query = "select key_val from system_registry where key_name='$name' AND user_id is NULL";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$ret = $row['key_val'];
		}

		return $ret;
	}

	/**
	 * Set system setting
	 *
	 * Function may be called statically as long as the $dbh param is passed
	 *
	 * @param string $name the name of the key to retrieve
	 * @return bool true on success false on failure
	 */
	public function settingsSet($name, $value, $dbh=null)
	{
		if (!$dbh && isset($this)) $dbh = $this->dbh;

		if (!$dbh)
			return false;

		$result = $dbh->Query("SELECT id FROM system_registry WHERE key_name='$name' AND user_id IS NULL");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$dbh->Query("UPDATE system_registry SET key_val='".$dbh->Escape($value)."' WHERE id='".$row['id']."'");
		}
		else
		{
			$dbh->Query("INSERT INTO system_registry(key_name, key_val) values('$name', '".$dbh->Escape($value)."');");
		}

		return true;
	}

	/**
	 * Every account in ANT should be associated with a customer id (account) in the Aereus account
	 *
	 * This function may be called statically so long as the $dbh is passed as a param
	 *
	 * @param CDatabase $dbh Must be set if called statically
	 * @param integer aid Account id must be set if called statically
	 * @return number The id of the customer(type=acount) that is the owner of this account
	 */
	public function getAereusCustomerId($dbh=null, $aid=null)
	{
		$ret = null;
		$cache = null;

		if (isset($this))
		{
			if (isset($this->cache))
				$cache = $this->cache;

			if ($this->dbh && !$dbh)
				$dbh = $this->dbh;

			if ($this->accountId && !$aid)
				$aid = $this->accountId;
		}

		if (!$dbh || !$aid)
			return false;

		if (!$cache)
			$cache = CCache::getInstance();


		// First try to get from local db which is the new system
		$cid = Ant::settingsGet("general/customer_id", $dbh);
		
		if ($cid)
			return $cid;

		// Now fall through to legacy code. This will need to be deleted eventually.
		$antsys = new AntSystem();
		$cval = $antsys->getAereusCustomerId($aid);
		if ($cval)
		{
			Ant::settingsSet("general/customer_id", $cval, $dbh);
			return $cval;
		}


		// Create new customer through the API
		return Ant::setAereusCustomerId(null, $dbh);
	}
	
	/**
	 * Set aereus customer id for this account
	 *
	 * @param CDatabase $dbh Optional database handle if passed statically
	 * #param array $params Associative array of customer params
	 * @return integer The unique Aereus customer ID
	 */
	public function setAereusCustomerId($params=array(), $dbh=null)
	{
		$ret = null;

		if (isset($this))
		{
			if ($this->dbh && !$dbh)
				$dbh = $this->dbh;
		}

		$companyName = Ant::settingsGet("general/company_name", $dbh);
		if ($companyName)
			$companyName = "Account: " . $dbh->getAccountNamespace();

		// Create customer record for this users
		$api = new AntApi(AntConfig::getInstance()->aereus['server'], 
						  AntConfig::getInstance()->aereus['user'],
						  AntConfig::getInstance()->aereus['password']);
		$cust = $api->getCustomer();
		$cust->setValue("name", $params['company']);
		$cust->setValue("type_id", CUST_TYPE_ACCOUNT);
		if (isset($params['phone']))
			$cust->setValue("phone_work", $params['phone']);
		$cust->setValue("website", Ant::settingsGet("general/company_name", $dbh));
		if (isset($params['email']))
			$cust->setValue("email", $params['email']);
		//$cust->setValue("email2", $params['username']."@".$data['account_name'].".".AntConfig::getInstance()->localhost_root);
		//$cust->setValue("company", $params['company']);
		if (isset($params['job_title']))
			$cust->setValue("job_title", $params['job_title']);
		$cust->setValue("notes", "Automatically generated organization from netric account");
		$cust->setValue("status_id", 1); // Active
		//$cust->setValue("stage_id", 17); // Free Trial
		$cust->setMValue("groups", 149); // Put into ANT Accounts, if reseller then "ANT Resellers" group
		$cid = $cust->save();

		if ($cid)
			Ant::settingsSet("general/customer_id", $cid, $dbh);

		return $cid;
	}

	/**
	 * Every account in ANT should be associated with a custom object called co_ant_account
	 *
	 * @param integer aid Account id must be set if called statically
	 * @return number The id of the customer(type=acount) that is the owner of this account
	 */
	public function getAereusAccount($aid=null)
	{
		$ret = null;

		if (isset($this))
		{
			if ($this->accountId && !$aid)
				$aid = $this->accountId;
		}

		if (!$aid)
			return false;

		$antsys = new AntSystem();
		$ret = $antsys->getAereusAccount($aid);

		return $ret;
	}

	/**
	 * Verify default that a default email domain exists
	 *
	 * This function may be called statically so long as $accName 
	 * and $dbh are both passed as valid params.
	 *
	 * @param string $accName Optional 
	 * @return string The default email domain
	 */
	public function getEmailDefaultDomain($accName="", $dbh=null)
	{
		if ($this && (!$accName || !$dbh))
		{
			$accName = $this->accountName;
			$dbh = $this->dbh;
		}

		if (!$accName || !$dbh)
			return false;

		$defDom = Ant::settingsGet("email/defaultdomain", $dbh);

		if (!$defDom)
		{
			$defDom = $accName.".".AntConfig::getInstance()->localhost_root;
			Ant::settingsSet("email/defaultdomain", $defDom, $dbh);
		}

		return $defDom;
	}

	/**
	 * Get the edition id of this account
	 */
	public function getEdition()
	{
		if ($this->edition)
			return $this->edition;

		// Edition
		$ret = $this->settingsGet("system/edition");
		if ($ret)
			$this->edition = $ret;
		else
			$this->edition = EDITION_FREE;

		return $this->edition;
	}

	/**
	 * Get the edition description of this account
	 */
	public function getEditionName()
	{
		switch ($this->edition)
		{
		case EDITION_PROFESSIONAL:
			return "Professional";
		case EDITION_ENTERPRISE:
			return "Enterprise";
        case EDITION_FREE:
        default:
			return "Personal";
		}
	}

	/**
	 * Get the edition price of this account
	 * 
	 * @param int $edition Optional edition, otherwise current edition is used
	 */
	public function getEditionPrice($edition=null)
	{
		if ($edition == null) $edition = $this->edition;

		switch ($edition)
		{
		case EDITION_PROFESSIONAL:
			return 20;
		case EDITION_ENTERPRISE:
			return 30;
		case EDITION_FREE:
		default:
			return 0;
		}
	}

	/**
	 * Change the edition of this account
	 *
	 * @param int $edition A valid edition
	 */
	public function setEdition($edition)
	{
		// Put in switch to verify valid id
		switch ($edition)
		{
		case EDITION_FREE:
		case EDITION_PROFESSIONAL:
		case EDITION_ENTERPRISE:
			return $this->settingsSet("system/edition", $edition);
		default:
			return false;
		}
	}

	/**
	 * Get the edition description of this account
	 */
	public function getEditionDesc()
	{
		switch ($this->edition)
		{
		case EDITION_FREE:
			return "Describe the free edition";
		case EDITION_PROFESSIONAL:
			return "Describe the professional edition";
		case EDITION_ENTERPRISE:
			return "Describe the enterprise edition";
		}
	}

	/**
	 * Get list of available themes
	 *
	 * The first item in the array will always be treated as the default
	 *
	 * @param string $theme Get the details for a specific theme
	 * @return array('name', 'title') associative array of themes or a single array of selected them if $theme param is passed
	 */
	public static function getThemes($theme="")
	{
		$themes = array(
			array("name"=>'softblue', 'title'=>'Default'),
			array("name"=>'cheery', 'title'=>'Cheery'),
			array("name"=>'nova', 'title'=>'Nova'),
			array("name"=>'green', 'title'=>'Light Green'),
			//array("name"=>'armygreen', 'title'=>'Army Green'),
			//array("name"=>'earth', 'title'=>'Earth Tones'),
			//array("name"=>'social', 'title'=>'Social'),
		);

		if ($theme)
		{
			foreach ($themes as $th)
			{
				if ($th['name'] == $theme)
					$ret = $th;
			}
		}
		else
		{
			$ret = $themes;
		}

		return $ret;
	}

	/**
	 * Create a new ANT user
	 *
	 * @param string $username The unique username
	 * @param string $password The password of the new user
	 * @return AntUser $user Or false on failure
	 */
	public function createUser($username, $password)
	{
		if (!$username || !$password)
		{
			$this->lastError = "Please specify both a user name and password";
			return false;
		}

		// Escape username
		if (preg_match( "[~|\|\S|[^a-zA-Z0-9_.@]]", $username))
		{
			$this->lastError = "User name ($username) can only contain letters, numbers _ and .";
			return false;
		}

		// Make sure the user name and password do not exist already
		$ol = new CAntObjectList($this->dbh, "user");
		$ol->addCondition("and", "name", "is_equal", $username);
		//$ol->hideDeleted = false;
		$ol->getObjects();
		if ($ol->getNumObjects())
		{
			$this->lastError = "Username $username already exists";
			return false;
		}

		// Now that we know we have a valid user, lets create it
		$user = new CAntObject($this->dbh, "user");
		$user->setValue("name", $username);
		// If it an email, then save to email as well as name
		if (strpos($username, "@"))
		{
			$user->setValue("email", $username);
		}
		$user->setValue("full_name", $username);
		$user->setValue("password", $password);
		$user->setValue("active", 't');
		$user->save();

		$antuser = new AntUser($this->dbh, $user->id, $this);

		return $antuser;
	}

	/**
	 * Create initial schema for this account
	 */
	public function schemaCreate()
	{
		// Create schema if it does not exist
		//if (!$this->dbh->setSchema("acc_" . $this->id))
		//{
			$this->dbh->Query("CREATE SCHEMA acc_" . $this->id . ";", false);
			$this->dbh->setSchema("acc_" . $this->id);
		//}

		$schema = array(); // This will be set in the include below
		include("system/schema/create.php");

		// Make sure the create script has set its revision id
		if (!$schema_version)
		{
			$this->lastError = "The schema creation script - create.php - is missing the \$schema_revision variable.";
			return false;
		}

		foreach ($schema as $table_name=>$table_data)
		{
			$sql = $this->schemaTableToSql($table_name, $table_data);
			$result = $this->dbh->Query($sql);
			if ($result === false)
			{
				$this->lastError = "Query failed: $sql";
				return false;
			}
		}

		// $schema_version is set in the included create script above
		return $schema_version;
	}

	/**
	 * Update schema to latest revision
	 *
	 * @param bool $processAlways If set to false scripts in the /system/schema/always will not run
	 * @param bool $printOutput If true the update class will print progress
	 * @return bool true on success and false on failure
	 */
	public function schemaUpdate($processAlways=true, $printOutput=true)
	{
		require_once("lib/System/SchemaUpdater.php");

		$sup = new AntSystem_SchemaUpdater($this, $printOutput);

		return $sup->update($processAlways);
	}

	/**
	 * Load default data into a newly craeted schema
	 *
	 * @param bool $printOutput If true the update class will print progress
	 * @return bool true on success and false on failure
	 */
	public function loadDefaultSchemaData($printOutput=true)
	{
		require_once("lib/System/SchemaUpdater.php");

		$sup = new AntSystem_SchemaUpdater($this, $printOutput);

		return $sup->loadData();
	}

	/**
	 * Convert ant ANT schema table structure to native SQL
	 *
	 * @param string $table_name The name of the table we are creating
	 * @param array $table_data Array of properties for the table we are creating
	 * @return string The SQL used to create this table
	 */
	private function schemaTableToSql($table_name, $table_data)
	{
		$query = "CREATE TABLE $table_name(\n";
		// Create columns
		// -----------------------------------------------
		foreach ($table_data['COLUMNS'] as $column_name=>$column_data)
		{
			// Make sure the column names are not too long
			if (strlen($column_name) > 64)
			{
				trigger_error("Column name '$column_name' on table '$table_name' is too long. The maximum is 64 characters.", E_USER_ERROR);
			}
			if (isset($column_data['default']) && $column_data['default'] == 'auto_increment' && strlen($column_name) > 61) // "${column_name}_gen"
			{
				trigger_error("Index name '${column_name}_gen' on table '$table_name' is too long. The maximum is 64 characters.", E_USER_ERROR);
			}
			
			// Set column type
			if (isset($column_data['subtype']) && $column_data['subtype'])
				$column_type = $column_data['type'] . " " . $column_data['subtype'];
			else
				$column_type = $column_data['type'];

			// Add column definition
			$query .= "\t{$column_name} {$column_type}";

			// Add column defaults
			if (isset($column_data['default']) && $column_data['default'] == 'auto_increment')
			{
				$query .= " DEFAULT nextval('{$table_name}_id_seq'),\n";

				// Make sure the sequence will be created before creating the table
				$query = "CREATE SEQUENCE {$table_name}_id_seq;\n\n" . $query;
			}
			else
			{
                if(isset($column_data['default']))
                    $query .= " DEFAULT '{$column_data['default']}'";
				//$query .= "NOT NULL";

				// Unsigned? Then add a CHECK contraint
				/*
				if (in_array($orig_column_type, $unsigned_types))
				{
					$query .= " CHECK ({$column_name} >= 0)";
				}
				 */

				$query .= ",\n";
			}
			
		}

		// Create primary key
		// -----------------------------------------------
		if (isset($table_data['PRIMARY_KEY']))
		{
			if (!is_array($table_data['PRIMARY_KEY']))
			{
				$table_data['PRIMARY_KEY'] = array($table_data['PRIMARY_KEY']);
			}
			
			$query .= "\tCONSTRAINT {$table_name}_pkey PRIMARY KEY (" . implode(', ', $table_data['PRIMARY_KEY']) . "),\n";
		}

		// Constraints
		// -----------------------------------------------
		if (isset($table_data['CONSTRAINTS']) && is_array($table_data['CONSTRAINTS']))
		{
			foreach ($table_data['CONSTRAINTS'] as $coname=>$con)
				$query .= "\tCONSTRAINT {$table_name}_".$coname." CHECK (" . $con . "),\n";
		}

		// Remove last query delimiter...
		$query = substr($query, 0, -2);

		// Finish table definition
		$query .= "\n)";

		// Does this table inherit?
		// -----------------------------------------------
		if (isset($table_data['INHERITS']))
		{
			$query .= "\nINHERITS (".$table_data['INHERITS'].")\n";
		}

		// Add finishing semicolon
		$query .= ";\n\n";
		

		// Create keys/indexes
		// -----------------------------------------------
		if (isset($table_data['KEYS']))
		{
			foreach ($table_data['KEYS'] as $key_name => $key_data)
			{
				if (!is_array($key_data[1]))
				{
					$key_data[1] = array($key_data[1]);
				}

				if (strlen($table_name . $key_name) > 64)
				{
					trigger_error("Index name '${table_name}_$key_name' on table '$table_name' is too long. The maximum is 64 characters.", E_USER_ERROR);
				}

				if ($key_data[0] == 'FKEY')
				{
					// For now do nothing
				}
				else
				{
					$query .= ($key_data[0] == 'INDEX') ? 'CREATE INDEX' : '';
					$query .= ($key_data[0] == 'UNIQUE') ? 'CREATE UNIQUE INDEX' : '';

					$query .= " {$table_name}_{$key_name}_idx ON {$table_name} (" . implode(', ', $key_data[1]) . ");\n";
				}
			}
		}


		return $query;
	}

	/**
	 * Function for formatting text
	 *
	 * @param string $value The text to format
	 * @param string $type A data type to use like 'money'
	 * @return string The formatted text if applicable
	 */
	public function formatText($value, $type="")
	{
		switch ($type)
		{
		case 'money':
			return "$" . $this->formatText($value, "number");
			break;
		case 'number':
			if (!$value)
				$value = 0;

			return number_format($value, 2);
			break;
		default:
			return $value;
		}
	}

	/**
	 * Get session variable if exists
	 *
	 * These functions can be called statically
	 * This currently uses cookies for sessions
	 *
	 * @param string $name The name of the session variable to get
	 * @return string The value of the session variable
	 */
	static public function getSessionVar($name)
	{
		global $_COOKIE;
        
        if(isset($_COOKIE[$name]))
		    return base64_decode($_COOKIE[$name]);
        else
            return null;
	}

	/**
	 * Set session variable
	 *
	 * This function can be called statically
	 * This currently uses cookies for sessions
	 *
	 * @param string $name The name of the session variable to get
	 * @param string $value The value to set the names variable to
	 * @param int $expires Set the number of seconds until this expires
	 */
	public function setSessionVar($name, $value, $expires=null)
	{
		setcookie($name, base64_encode($value), $expires);
	}

	/**
	 * Determine what account we are working with.
	 *
	 * This is usually done by the third level url, but can fall
	 * all the way back to the system default account if needed.
	 *
	 * @return string The unique account name for this instance of ANT
	 */
	public function detectAccount()
	{
		global $_SERVER, $_GET, $_POST, $ANT, $_SERVER;

		$ret = null;

		// 1 check session
		$ret = Ant::getSessionVar('aname');

		// 2 check url - 3rd level domain is the account name
		if (!$ret && AntConfig::getInstance()->localhost != AntConfig::getInstance()->localhost_root 
			 && strpos(AntConfig::getInstance()->localhost, "." . AntConfig::getInstance()->localhost_root))
		{
			$left = str_replace("." . AntConfig::getInstance()->localhost_root, '', AntConfig::getInstance()->localhost);
			if ($left)
				$ret = $left;
		}
		
		// 3 check get - less common
		if (!$ret && isset($_GET['account']))
		{
			$ret = $_GET['account'];
		}

		// 4 check post - less common
		if (!$ret && isset($_POST['account']))
		{
			$ret = $_POST['account'];
		}

		// 5 check for any third level domain
		if (!$ret && isset($_SERVER['HTTP_HOST']) && substr_count($_SERVER['HTTP_HOST'], '.')>=2)
		{
			$left = substr($_SERVER['HTTP_HOST'], 0, strpos($_SERVER['HTTP_HOST'], '.'));
			if ($left)
				$ret = $left;
		}

		// 6 get default account from the system settings
		if (!$ret)
			$ret = AntConfig::getInstance()->default_account;

		return $ret; 
	}

	/**
	 * Get account url
	 *
	 * @param bool $includePro If true include protocol http:// or https:// at the beginning of the string
	 */
	public function getAccBaseUrl($includePro=true)
	{
		$url = "";

		if ($includePro)
			$url .= (AntConfig::getInstance()->force_https) ? "https://" : "http://";

		if (AntConfig::getInstance()->localhost) // get from HTTP_HOST in AntConfig
			$url .= AntConfig::getInstance()->localhost;
		else if (AntConfig::getInstance()->localhost_root != "localhost")
			$url .= $this->accountName . "." . AntConfig::getInstance()->localhost_root;
		else
			$url .= AntConfig::getInstance()->localhost_root;

		return $url;
	}

	/**
	 * Get number of non-system active users
	 */
	public function getNumUsers()
	{
        $objList = new CAntObjectList($this->dbh, "user");
        $objList->addCondition("and", "active", "is_equal", "t");        
        $objList->addCondition("and", "id", "is_greater", "0");
        $objList->addCondition("and", "name", "is_not_equal", "aereus");
        $objList->addCondition("and", "name", "is_not_equal", "administrator");
        $objList->getObjects();
        return $objList->getNumObjects();
	}

	/**
	 * Get a user by id
	 *
	 * @param int $id Optional user id to load, otherwise current user is loaded
	 * @return AntUser
	 */
	public function getUser($id=null)
	{
		if ($id === null) {
			// Get the authentication service
			$sm = $this->getNetricAccount()->getServiceManager();
			$auth = $sm->get("Netric/Authentication/AuthenticationService");

			// Check if the current session is authenticated
			$id = $auth->getIdentity();
		}

		return new AntUser($this->dbh, $id, $this);
	}

	/**
	 * Get a user by email address
	 *
	 * @param string $email The email address that should be set for a user
	 * @return AntUser
	 */
	public function getUserByEmail($email)
	{
        $objList = new CAntObjectList($this->dbh, "user");
        $objList->addCondition("and", "active", "is_equal", "t");        
        $objList->addCondition("and", "email", "is_equal", $email);
        $objList->getObjects();
		if ($objList->getNumObjects())
		{
			$dat = $objList->getObjectMin(0);
			if ($dat["id"])
				return new AntUser($this->dbh, $dat["id"], $this);
		}

		return null;
	}

	/**
	 * Get email no-reply for this account
	 *
	 * @param bool $full If true then get a full "Company Name" <email@address.com> formated address
	 */
	public function getEmailNoReply($full=false)
	{
		$accSetting= $this->settingsGet("email/noreply");
		$ret = ($accSetting) ? $accSetting : AntConfig::getInstance()->email['noreply'];

		if ($full && $this->settingsGet("general/company_name"))
			$ret = '"' . $this->settingsGet("general/company_name") . '" ' . "<" . $ret . ">";

		return $ret;
	}

	/**
	 * Get the service locator for this account
	 */
	public function getServiceLocator()
	{
		return ServiceLocator::getInstance($this);
	}
    
    /**
     * Get netric account
     * 
     * This is a pass-through account to make it easier for us to transition to the new V4 framework
     * 
     * @return Netric\Account
     */
    public function getNetricAccount()
    {
        if ($this->netricAccount)
            return $this->netricAccount;
        
        // Initialize Netric Application and Account
        // ------------------------------------------------
        //$config = new Netric\Config\Config();

		// Create the instance of config loader
		$this->nconfigLoader = new \Netric\Config\ConfigLoader();
		$applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";

		// Setup the new config
		$config = $this->nconfigLoader->fromFolder(__DIR__ . "/../config", $applicationEnvironment);

        // Initialize application
        //$application = new Netric\Application($config);
        require_once("lib/NetricApplicationLoader.php");

        $application = NetricApplicationLoader::getInstance($config)->getApplication();

        // Initialize account
        $account = $application->getAccount($this->id);
        $this->netricAccount = $account;
        
        return $account;
    }
}
