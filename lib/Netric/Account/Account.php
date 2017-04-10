<?php
/**
 * Netric account instance
 */
namespace Netric\Account;

use Netric\Application\Application;
use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\ServiceManager\AccountServiceManager;
use Netric\Entity\ObjType\UserEntity;
use Netric\EntityQuery;

class Account
{
    /**
     * Unique account ID
     * 
     * @var string
     */
    private $id = "";
    
    /**
     * Unique account name
     * 
     * @var string
     */
    private $name = "";
    
    /**
     * The name of the database
     * 
     * @var string
     */
    private $dbname = "netric";
    
    /**
     * Instance of netric application
     * 
     * @var Application
     */
    private $application = null;
    
    /**
     * Handle to service manager for this account
     * 
     * @var AccountServiceManagerInterface
     */
    private $serviceManager = null;

    /**
     * Optional description
     *
     * @var string
     */
    private $description = "";

    /**
     * Property to set the current user rather than using the auth service
     * 
     * @var UserEntity
     */
    public $currentUserOverride = null;

    /**
     * The status of this account
     *
     * @var int
     */
    private $status = null;
    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_DELETED = 3;
    
    /**
     * Initialize netric account
     * 
     * @param \Netric\Application\Application $app
     */
    public function __construct(Application $app)
    {
        $this->application = $app;
        
        $this->serviceManager = new AccountServiceManager($this);

        // Set default status
        $this->status = self::STATUS_ACTIVE;
    }

    /**
     * Load application data from an associative array
     * 
     * @param array $data
     * @return bool true on successful load, false on failure
     */
    public function fromArray($data)
    {
        // Check required fields
        if (!$data['id'] || !$data['name'])
            return false;
        
        $this->id = $data['id'];
        $this->name = $data['name'];

        if (isset($data['database']) && $data['database'])
            $this->dbname = $data['database'];

        if (isset($data['description']) && $data['description'])
            $this->description = $data['description'];
                
        return true;
    }

    /**
     * Export internal properties to an associative array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            "id" => $this->id,
            "name" => $this->name,
            "database" => $this->dbname,
            "description" => $this->description,
        );
    }
    
    /**
     * Get account id
     * 
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Get account unique name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the optional description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get database name for this account
     * 
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->dbname;
    }
    
    /**
     * Get ServiceManager for this account
     * 
     * @return AccountServiceManagerInterface
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }
    
    /**
     * Get application object
     * 
     * @return \Netric\Application\Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Override the currently authenticated user with a specific user
     *
     * This is often used in testing and in background services where
     * there is no current authenticated user but we need to setup one
     * manually for act on behalf of a user.
     *
     * @param UserEntity $user
     */
    public function setCurrentUser(UserEntity $user)
    {
        $this->currentUserOverride = $user;

        // Clear the service locator since user is often injected as a dependency
        $this->getServiceManager()->clearLoadedServices();
    }
    
    /**
     * Get user by id or name
     * 
     * If neither id or username are defined, then try to get the currently authenticated user.
     * If no users are authenticated, then this function will return false.
     * 
     * @param string $userId The userId of the user to get
     * @param string $username Get user by name
     * @return UserEntity|bool user on success, false on failure
     */
    public function getUser($userId=null, $username=null)
    {      
        // Check to see if we have manually set the current user and if so skip session auth
        if ($this->currentUserOverride)
            return $this->currentUserOverride;

        // Entity loader will be needed once we have determined a user id to load
        $loader = $this->getServiceManager()->get("EntityLoader");
        
        /*
         * Try to get the currently logged in user from the authentication service if not provided
         */
        if (!$userId && !$username) 
        {
            // Get the authentication service
            $auth = $this->getServiceManager()->get("Netric/Authentication/AuthenticationService");

            // Check if the current session is authenticated
            $userId = $auth->getIdentity();
        } 

        /*
         * Load the user with the loader service.
         * This makes it unnecessary to cache the current user locally
         * since the loader handles making sure there is only one instance
         * of each user object in memory.
         */
        if ($userId)
        {
            $user = $loader->get("user", $userId);
            if ($user != false)
            {
                return $user;
            }
        }
        elseif ($username) 
        {
            $query = new EntityQuery("user");
            $query->where('name')->equals($username);
            $index = $this->getServiceManager()->get("EntityQuery_Index");
            $res = $index->executeQuery($query);
            if ($res->getTotalNum()) {
               return $res->getEntity(0);
            } else {
                return null;
            }
        }
                
        // Return anonymous user
        $anon = $loader->create("user");
        $anon->setId(UserEntity::USER_ANONYMOUS);
        $anon->setValue("name", "anonymous");
        return $anon;
    }

    /**
     * Set account and username for a user's email address and username
     *
     * @param string $username The user name - unique to the account
     * @param string $emailAddress The email address to pull from
     * @return bool true on success, false on failure
     */
    public function setAccountUserEmail($username, $emailAddress)
    {
        return $this->application->setAccountUserEmail($this->getId(), $username, $emailAddress);
    }

    /**
     * Get the url for this account
     *
     * @param bool $includeProtocol If true prepend the default protocol
     * @return string A url like https://aereus.netric.com
     */
    public function getAccountUrl($includeProtocol = true)
    {
        // Get application config
        $config = $this->getServiceManager()->get("Config");

        // Initialize return value
        $url = "";

        // Prepend protocol
        if ($includeProtocol)
            $url .= ($config->force_https) ? "https://" : "http://";

        // Add account third level
        $url .= $this->name . ".";

        // Add the rest of the domain name
        $url .= $config->localhost_root;

        return $url;
    }
}