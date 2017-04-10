<?php
 /**
 * Manage the table email_accounts including setting the type and storing the host/authentication information. 
 * 
 * @category  AntMail
 * @package   IMAP
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once('security/security_functions.php');
require_once('lib/AntObjectSync.php');
 
class AntMail_Account
{
    /**
     * Instance of CDatabase
     *
     * @var CAnt 
     */
    private $dbh = null;

    /**
     * Reference to current user object
     *
     * @var AntUser
     */
    private $user = null;

	/**
	 * The unique id of this account
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * Is a system account which cannot be deleted
	 *
	 * @var bool
	 */
	public $fSystem = true; // Assume true

	/**
	 * The type of account
	 *
	 * This is usually imap or pop3. If blank a default type from the Netric config may be applied
	 *
	 * @var string
	 */
	public $type = "";

	/**
	 * Full name for this account - usually First Last - used in email <name> address string
	 *
	 * @var string
	 */
	public $name = "";

	/**
	 * The email address associated with this account
	 *
	 * @var string
	 */
	public $emailAddress = "";

	/**
	 * The reply-to address associated with this account (optional)
	 *
	 * @var string
	 */
	public $replyTo = "";

	/**
	 * An optional account signature
	 *
	 * @var string
	 */
	public $signature = "";

	/**
	 * The backend host - if blank default may be applied with Netric config email['backend_host']
	 *
	 * @var string
	 */
	public $host = "";

	/**
	 * Username associated with this account
	 *
	 * @var string
	 */
	public $username = "";

	/**
	 * Password for this account - if blank then user password is used
	 *
	 * @var string
	 */
	public $password = "";
	
	/**
	 * Flag to indicate this is the default account for the selected user
	 *
	 * @var bool
	 */
	public $fDefault = false;

	/**
	 * The port to use
	 *
	 * @var int
	 */
	public $port = null;

	/**
	 * Use ssl
	 *
	 * @var bool
	 */
	public $ssl = false;

	/**
	 * The unique id of the owner of this account
	 *
	 * @var int
	 */
	public $userId = null;
    
    /**
     * Sync Email Ids
     *
     * @var string
     */
    public $syncData = null;

	/**
	 * Timestamp of last full sync (including all folders)
	 *
	 * @var epoch timestamp
	 */
	public $tsLastFullSync = null;

	/**
	 * Backend engine used to retrieve messages
	 *
	 * @var int
	 */
	private $backend = null;

	/**
	 * Does this server require authentication to send email
	 *
	 * @var bool
	 */
	public $fOutgoingAuth = false;

	/**
	 * Smtp host to use when sending
	 *
	 * @var string
	 */
	public $hostOut = null;
	
	/**
	 * Smtp user to use if authentication is required
	 *
	 * @var string
	 */
	public $userOut = null;

    /**
     * Smtp username to use if authentication is required and different from incoming
     *
     * @var string
     */
    public $usernameOut = null;

	/**
	 * Smtp password to use if authentication is required and different from incoming
	 *
	 * @var string
	 */
	public $passwordOut = null;
	
	/**
	 * Optional alternate port to use when sending messages to an SMTP server
	 *
	 * @var int
	 */
	public $portOut = null;

	/**
	 * Use ssl
	 *
	 * @var bool
	 */
	public $sslOut = false;
    
    /**
     * Forward to another email address
     * 
     * @var string
     */
    public $forward = "";

    /**
     * Class constructor
     *     
	 * @param CAntObject $dbh Handle to acount database
	 * @param int $aid The unique id of this account
	 * @param AntUser $user Optional user object
     */
    public function __construct($dbh, $aid=null, $user=null) 
    {
        $this->dbh = $dbh;
        $this->id = $aid;

		if ($user)
			$this->user = $user;

		if ($aid)
			$this->open($aid);
    }

	/**
	 * Open account from the database
     *     
	 * @param int $aid The unique id of this account
	 */
	public function open($aid)
	{
		if (!is_numeric($aid))
			return false;

		/*
		 * Removing this block of code since we have alreardy created the email_account entity
		 * We are now using the Netric/EntityLoader to load the email_account
		 * Marl Tumulak 05-02-16
		 *
		 *$dbh = $this->dbh;
		$result = $dbh->Query("SELECT * FROM email_accounts WHERE id='$aid';");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetRow($result, 0);

			$this->id = $row['id'];
			$this->type = $row['type'];
			$this->name = $row['name'];
			$this->emailAddress = $row['address'];
			$this->replyTo = $row['reply_to'];
			$this->signature = $row['signature'];
			$this->host = $row['host'];
			$this->username = $row['username'];
			$this->password = decrypt($row['password']);
			$this->port = $row['port'];
			$this->userId = $row['owner_id'];
			$this->fDefault = ($row['f_default'] == 't') ? true : false;
            $this->ssl = ($row['f_ssl'] == 't') ? true : false;
			$this->syncData = $row['sync_data'];
			$this->tsLastFullSync = $row['ts_last_full_sync'];
			$this->fSystem = ($row['f_system'] == 't') ? true : false;
			$this->fOutgoingAuth = ($row['f_outgoing_auth'] == 't') ? true : false;
			$this->hostOut = $row['host_out'];
			$this->portOut = $row['port_out'];
            $this->sslOut = ($row['f_ssl_out'] == 't') ? true : false;
			$this->usernameOut = $row['username_out'];
			$this->passwordOut = decrypt($row['password_out']);
            $this->forward = $row['forward'];
		}*/


		// We will use the service locator to get the entity loader and load the email_account entity
		$sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$entityLoader = $sl->get("EntityLoader");
		$entity = $entityLoader->get("email_account", $aid);

		if($entity)
		{
			$details = $entity->toArray();

			$this->id = $details['id'];
			$this->type = $details['type'];
			$this->name = $details['name'];
			$this->emailAddress = $details['address'];
			$this->replyTo = $details['reply_to'];
			$this->signature = $details['signature'];
			$this->host = $details['host'];
			$this->username = $details['username'];
			$this->password = decrypt($details['password']);
			$this->port = $details['port'];
			$this->userId = $details['owner_id'];
			$this->fDefault = ($details['f_default'] == 't') ? true : false;
			$this->ssl = ($details['f_ssl'] == 't') ? true : false;
			$this->syncData = $details['sync_data'];
			$this->tsLastFullSync = $details['ts_last_full_sync'];
			$this->fSystem = ($details['f_system'] == 't') ? true : false;
			$this->fOutgoingAuth = ($details['f_outgoing_auth'] == 't') ? true : false;
			$this->hostOut = $details['host_out'];
			$this->portOut = $details['port_out'];
			$this->sslOut = ($details['f_ssl_out'] == 't') ? true : false;
			$this->usernameOut = $details['username_out'];
			$this->passwordOut = decrypt($details['password_out']);
			$this->forward = $details['forward'];
		}

		// Look for system default type and host
		if (AntConfig::getInstance()->email['default_type']  && AntConfig::getInstance()->email['backend_host'] && $this->fSystem)
		{
			$this->type = AntConfig::getInstance()->email['default_type'];
			$this->host = AntConfig::getInstance()->email['backend_host'];
		}

		if (!$this->username)
			$this->username = $details['address'];
	}

	/**
	 * Save account to database
	 */
	public function save()
	{
		$dbh = $this->dbh;

        if($this->userId)
            $userId = $this->userId;
        else
            $userId = 0; // No user id was defined
        
		$values = array(
			"name" => $this->name,
			"type" => $this->type,
			"owner_id" => $userId,
			"address" => $this->emailAddress,
			"reply_to" => $this->replyTo,
			"signature" => $this->signature,
			"host" => $this->host,
			"username" => $this->username,
			"password" => encrypt($this->password),
			"port" => $this->port,
            "f_ssl" => ($this->ssl) ? 't' : 'f',
			"f_default" => ($this->fDefault) ? 't' : 'f',
			"f_system" => ($this->fSystem) ? 't' : 'f',
			"sync_data" => $this->syncData,
			"ts_last_full_sync" => ($this->tsLastFullSync) ? $this->tsLastFullSync : "0",
			"f_outgoing_auth" => ($this->fOutgoingAuth) ? 't' : 'f',
			"host_out" => $this->hostOut,
			"username_out" => $this->usernameOut,
			"password_out" => encrypt($this->passwordOut),
			"port_out" => $this->portOut,
            "forward" => $this->forward,
            "f_ssl_out" => ($this->sslOut) ? 't' : 'f',
		);

		/*if ($this->id)
		{
			$valq = "";
			foreach ($values as $colname=>$val)
			{
				if ($valq) $valq .= ", ";
				$valq .= $colname . "='" . $dbh->Escape($val) . "'";
			}
			$query = "UPDATE email_accounts SET $valq WHERE id='".$this->id."';";
		}
		else
		{
			$valq = "";
			$colq = "";
			foreach ($values as $colname=>$val)
			{
				if ($valq)
				{
					$colq .= ", ";
					$valq .= ", ";
				}

				$colq .= $colname;
				$valq .= "'" . $dbh->Escape($val) . "'";
			}
			$query = "INSERT INTO email_accounts($colq) VALUES($valq); select currval('email_accounts_id_seq') as id;";
		}

		$res = $dbh->Query($query);
		if (!$this->id && $res)
		{
			$this->id = $dbh->GetValue($res, 0, "id");
		}

		return $this->id;*/

		// Get the Entity Loader
		$sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$entityLoader = $sl->get("EntityLoader");

		if ($this->id)
		{
			$entity = $entityLoader->get("email_account", $this->id);
			$values["id"] = $this->id;
		}
		else
		{
			$entity = $entityLoader->create("email_account");
		}

		// Import the email account values
		$entity->fromArray($values);

		// Save the entity
		$entityLoader->save($entity);

		// Return the entity id saved
		return $entity->getId();
	}

	/**
	 * To data array
	 *
	 * @return array Associative array of account properties
	 */
	public function toArray()
	{
		return array(
			"id" => $this->id,
			"name" => $this->name,
			"type" => $this->type,
			"user_id" => $userId,
			"email_address" => $this->emailAddress,
			"reply_to" => $this->replyTo,
			"signature" => $this->signature,
			"host" => $this->host,
			"username" => $this->username,
			"password" => $this->password,
			"port" => $this->port,
            "ssl" => $this->ssl,
			"f_default" => $this->fDefault,
			"f_system" => $this->fSystem,
			"f_outgoing_auth" => $this->fOutgoingAuth,
			"host_out" => $this->hostOut,
			"username_out" => $this->usernameOut,
			"password_out" => $this->passwordOut,
			"port_out" => $this->portOut,
            "ssl_out" => $this->sslOut,
            "forward" => $this->forward,
		);
	}

	/**
	 * Delete account
	 */
	public function remove()
	{
		if (!$this->id || !is_numeric($this->id))
			return;

		// Check to see if sync partnership exists
		$partner = $this->getSyncPartner(false);
		if ($partner) {
			$serviceManager = ServiceLocatorLoader::getInstance($this->dbh)->getServiceManager();
			$entitySync = $serviceManager->get("EntitySync");
			$entitySync->deletePartner($partner);
		}

		//$ret = $this->dbh->Query("DELETE FROM email_accounts WHERE id='".$this->id."'");

		$sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();

		// Get the new netric entity DataMapper
		$dataMapper = $sl->get("Entity_DataMapper");

		// Get the new netric entity loader
		$loader = $sl->get("EntityLoader");

		// Get the email_account entity using the $this->id
		$entity = $loader->get("email_account", $this->id);

		// Delete the entity and return true if successful, else return false
		if ($dataMapper->delete($entity))
			return true;
		else
			return false;
	}

	/**
	 * Get sync partner
	 *
	 * @param bool $createIfMissing If true then partnership will be added if missing along with collection
	 */
	public function getSyncPartner($createIfMissing=true)
	{
		$serviceManager = ServiceLocatorLoader::getInstance($this->dbh)->getServiceManager();
		$entitySync = $serviceManager->get("EntitySync");
        $partner = $entitySync->getPartner("EmailAccounts/" . $this->id);
        if (!$partner && $createIfMissing)
        {
            $partner = $entitySync->createPartner("EmailAccounts/" . $this->id, $this->user->id);
        }

        /*
		$sync = new AntObjectSync($this->dbh, "email_message", $this->user);
		$partner = $sync->getPartner("EmailAccounts/" . $this->id);

		// Make sure collection exists
		$cond = array(array("blogic"=>"and", "field"=>"email_account", "operator"=>"is_equal", "condValue"=>$this->id));
		if (!$partner->getCollection("email_message", null, $cond))
		{
			$partner->addCollection("email_message", null, $cond);
			$coll = $partner->getCollection("email_message", null, $cond);
			$coll->fInitialized = true; // Do not copy existing messages so we avoid duplicates
			$coll->save();
		}
        */

		return $partner;
	}

	/**
	 * Get backend for this account
	 *
	 * @return AntMail_Backend_*
	 */
	public function getBackend()
	{
		if (!$this->backend)
			$this->backend = new AntMail_Backend($this->type, $this->host, $this->username, $this->password, $this->port, $this->ssl);

		return $this->backend;
	}
    
    /**
     * Inserts/Updates an email account
	 *
	 * @depricated
     * @param array $params     An array of email account information
     * (e.g. id, name, address, userid, signature, type, username, password, host)
     */
    public function saveEmailAccount($params)
    {
		return $this->save();

        /*
		 * Removing this block of code since we have alreardy created the email_account entity
         * We are now using the Netric/EntityLoader to insert/update the email_account
		 * Marl Tumulak 05-02-16
		 *
         * $dbh = $this->dbh;
        $userId = $this->user->id;
        $id = null;
        
        if(isset($params['accountId']) && $params['accountId']>0)
        {
            $id = $params['accountId'];
            $updateFields = array();
            
            if(isset($params['yourName']))
                $updateFields[] = "name='".$dbh->Escape($params['yourName'])."'";
                
            if(isset($params['emailAddress']))
                $updateFields[] = "address='".$dbh->Escape($params['emailAddress'])."'";
                
            if(isset($params['replyTo']))
                $updateFields[] = "reply_to='".$dbh->Escape($params['replyTo'])."'";
                
            if(isset($params['signature']))
                $updateFields[] = "signature='".$dbh->Escape($params['signature'])."'";
                
            if(isset($params['type']))
                $updateFields[] = "type='".$dbh->Escape($params['type'])."'";
                
            if(isset($params['username']))
                $updateFields[] = "username='".$dbh->Escape($params['username'])."'";
                
            if(isset($params['password']))
                $updateFields[] = "password='".$dbh->Escape($params['password'])."'";
                
            if(isset($params['host']))
                $updateFields[] = "host='".$dbh->Escape($params['host'])."'";
                
            $fDefault = (isset($params['defaultAccount']) && $params['defaultAccount']==1) ? "'t'" : "'f'";
            $updateFields[] = "f_default=$fDefault";
            
            $sql = "UPDATE email_accounts SET " . implode(", ", $updateFields) . " WHERE id='".$params['accountId']."'";            
        }
        else
        {
            $insertFields = array();
            $insertValues = array();
            
            if(isset($params['yourName']))
            {
                $insertFields[] = "name";
                $insertValues[] = "'" . $dbh->Escape($params['yourName']) . "'";
            }
            
            if(isset($params['emailAddress']))
            {
                $insertFields[] = "address";
                $insertValues[] = "'"  . $dbh->Escape($params['emailAddress']) . "'" ;
            }
            
            if(isset($params['replyTo']))
            {
                $insertFields[] = "reply_to";
                $insertValues[] = "'" . $dbh->Escape($params['replyTo']) . "'" ;
            }
            
            if(isset($params['signature']))
            {
                $insertFields[] = "signature";
                $insertValues[] = "'"  . $dbh->Escape($params['signature']) . "'" ;
            }
            
            if(isset($params['type']))
            {
                $insertFields[] = "type";
                $insertValues[] = "'"  . $dbh->Escape($params['type']) . "'" ;
            }
            
            if(isset($params['username']))
            {
                $insertFields[] = "username";
                $insertValues[] = "'"  . $dbh->Escape($params['username']) . "'" ;
            }
            
            if(isset($params['password']))
            {
                $insertFields[] = "password";
                $insertValues[] = "'"  . encrypt($dbh->Escape($params['password'])) . "'" ;
            }
            
            if(isset($params['host']))
            {
                $insertFields[] = "host";
                $insertValues[] = "'"  . $dbh->Escape($params['host']) . "'" ;
            }
                        
            $insertFields[] = "f_default";
            $insertValues[] = (isset($params['defaultAccount']) && $params['defaultAccount']==1) ? "'t'" : "'f'";
            
            $insertFields[] = "user_id";
            $insertValues[] = "'$userId'";
            
			$sql = "INSERT INTO email_accounts(" . implode(", ", $insertFields) . ") 
					VALUES(" . implode(", ", $insertValues) . ");
                    SELECT currval('email_accounts_id_seq') as id;";
        }
                
        $result = $dbh->Query($sql);
        
        if ($dbh->GetNumberRows($result))
            $id = $dbh->GetValue($result, 0, "id");
        
        return $id;*/
    }

	/**
     * Retrieves the email account information
     *
	 * @depricated
     * @param array $params     An array of email account information
     * (e.g. accountId)
     */
    public function retrieveEmailAccount($params)
    {
        $dbh = $this->dbh;
        $userId = $this->user->id;
        $ret = array();
        $whereClause = array();
        
        if(isset($params['accountId']) && $params['accountId'] > 0)
            $whereClause[] = "id = " . $dbh->EscapeNumber($params['accountId']);
            
        if(isset($params['type']))
            $whereClause[] = "type = '" . $dbh->Escape($params['type']) . "'";

        if(isset($params['f_default']))
            $whereClause[] = "f_default= '" . $dbh->Escape($params['f_default']) . "'";
        
        if(sizeof($whereClause) > 0)
            $whereSql = "and " . implode("and ", $whereClause);
        
		$query = "SELECT id, name, address, reply_to, f_default, signature, type, username, password, host 
					FROM email_accounts WHERE owner_id='$userId' $whereSql";
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $ret[] = $row;
        }
        
        $dbh->FreeResults($result);

		// Loop through accounts and set default for account types along with user/pass
		for ($i = 0; $i < count($ret); $i++)
		{
			if (!$ret[$i]['type'] && AntConfig::getInstance()->email['default_type'])
			{
				$ret[$i]['type'] = AntConfig::getInstance()->email['default_type'];
				$ret[$i]['host'] = AntConfig::getInstance()->email['imap_host'];
			}

			if (!$ret[$i]['username'])
				$ret[$i]['username'] = $ret[$i]['address'];

			if ($ret[$i]['password'])
				$ret[$i]['password'] = decrypt($ret[$i]['password']);
		}
        
        return $ret;
    }
    
    /**
     * Deletes the email account
     *
	 * @deprecated
     * @param array $params     An array of email account information
     * (e.g. accountId)
     */
    public function deleteEmailAccount($params)
    {
		return $this->remove();
        /*
		 * Removing this block of code since we have alreardy created the email_account entity
         * We are now using the Netric/Entity/DataMapper to delete an email account
		 * Marl Tumulak 05-02-16
		 *
         * $dbh = $this->dbh;
        $userId = $this->user->id;
        
        $accountId = $params['accountId'];
        $dbh->Query("DELETE FROM email_accounts WHERE id='$accountId' AND owner_id='$userId'");
        
        return true;*/
    }
    
    /**
     * Checkes if the email uniqueId is already synced
     *
     * @param integer $emailUid     Email Unique Id
     */
    public function checkEmailSynced($emailUid)
    {
        $ret = null;
        $syncDataParts = explode(",", $this->syncData);
        if(!empty($emailUid) && is_array($syncDataParts))
        {
            foreach($syncDataParts as $data)
            {
                $dataParts = explode(":", $data);
                if($dataParts[0] == $emailUid)
                {
                    $ret = $dataParts[1];
                    break;
                }
            }
        }
        
        return $ret;
    }
}
