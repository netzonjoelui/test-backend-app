<?php
/**
 * IdentityMapper for loading accounts
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\Account;

use Netric\Application\DataMapperInterface;
use Netric\Application\Application;
use Netric\Cache;
use Netric\Error\Error;
use Netric\Error\ErrorAwareInterface;

class AccountIdentityMapper implements ErrorAwareInterface
{
	/**
	 * Application datamapper
	 *
	 * @var \Netric\Application\DataMapperInterface
	 */
	private $appDm = null;

    /**
     * System cache used to spare the db from too many hits
     *
     * @var \Netric\Cache\CacheInterface
     */
    private $cache = null;

    /**
     * In memory cache of loaded accounts
     *
     * @var \Netric\Account\Account[]
     */
    private $loadedAccounts = [];

    /**
     * In memory maps from name to id
     */
    private $nameToIdMap = [];

    /**
     * Array of errors
     *
     * @var Error[]
     */
    private $errors = [];

	/**
	 * Construct and setup dependencies
	 *
	 * @param \Netric\Application\DataMapperInterface $appDm Application DataMapper
     * @param \Netric\Cache\CacheInterface $cache
     * @throws \Exception If all required dependencies were not passed
	 */
	public function __construct(DataMapperInterface $appDm, Cache\CacheInterface $cache)
	{
        if (!$appDm)
            throw new \Exception("Application datamapper is required");

		$this->appDm = $appDm;
        $this->cache = $cache;
	}

	/**
     * Load an account by id
     * 
     * @param string $id The unique id of the account to get
     * @param \Netric\Application\Application $application Reference to Application instance
     * @return \Netric\Account\Account on success, null on failure
     */
    public function loadById($id, \Netric\Application\Application $application)
    {
        // First check to see if we have it cached in local memory
        $account = $this->loadFromMemory($id);

        // Return already loaded account
        if ($account)
            return $account;

        // Account is not already loaded so create a new instance
        $account = new \Netric\Account\Account($application);

        // Try from cache if not loaded in memeory
        if ($this->loadFromCache($id, $account))
            return $account;
        
        // Load from the datamapper
        $ret = $this->appDm->getAccountById($id, $account);

        // Save the data to cache and memory
        if ($ret)
        {
            $this->setLocalMemory($account);
            $this->setCache($account);
            return $account;
        }
        else
        {
            return null;
        }
    }

    /**
     * Get an account by the unique name
     * 
     * @param string $name
     * @param \Netric\Application\Application $application Reference to Application instance
     * @return \Netric\Account\Account on success, null on failure
     */
    public function loadByName($name, Application $application)
    {
        // Try local memory first
        if (isset($this->nameToIdMap[$name]))
        {
            return $this->loadById($this->nameToIdMap[$name], $application);
        }

        // Now try cache
        $cachedId = $this->cache->get("netric/account/nametoidmap/$name");
        if ($cachedId)
        {
            return $this->loadById($cachedId, $application);
        }

        // Load from the datamapper by name
        $account = new Account($application);
        if ($this->appDm->getAccountByName($name, $account))
        {
            // Save the data to cache and memory
            $this->setLocalMemory($account);
            $this->setCache($account);

            // Save the maps
            $this->nameToIdMap[$name] = $account->getId();
            $this->cache->set("netric/account/nametoidmap/$name", $account->getId());
            return $account;
        }
        else
        {
            return null;
        }
    }

    /**
     * Delete an account
     *
     * @param Account $account The account to delete
     * @return bool true on success, false on failure
     * @throws \RuntimeException If account is not a valid account with an ID
     */
    public function deleteAccount(Account $account)
    {
        // Make sure this account is valid with an ID
        if (!$account->getId())
            throw new \RuntimeException("Cannot delete an account that does not exist");

        $accountId = $account->getId();
        $accountName = $account->getName();
        if ($this->appDm->deleteAccount($accountId))
        {
            // Clear cache
            $this->cache->delete("netric/account/" . $accountId);

            // Remove from in-memory cache
            if (isset($this->loadedAccounts[$accountId]))
            {
                unset($this->loadedAccounts[$accountId]);
            }

            // Clear save the maps
            $this->cache->delete("netric/account/nametoidmap/$accountName");

            if (isset($this->nameToIdMap[$accountName]))
            {
                unset($this->nameToIdMap[$accountName]);
            }

            return true;
        }

        // Something failed
        $this->errors[] = $this->appDm->getLastError();
        return false;
    }

    /**
     * Create a new account and return the ID
     *
     * @param string $name A unique name for this account
     * @return int Unique id of the created account, 0 on failure
     */
    public function createAccount($name)
    {
        return $this->appDm->createAccount($name);
    }

    /**
     * Get the last error
     *
     * @return Error|null
     */
    public function getLastError()
    {
        return (count($this->errors)) ? array_pop($this->errors) : null;
    }

    /**
     * Get array of errors that have occurred
     *
     * @return \Netric\Error\Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get an account details from cache and load
     *
     * @param string $id The unique id of the account to get
     * @param \Netric\Account\Account $account Account to load data into
     * @return bool true on success, false on failure/not found
     */
    private function loadFromCache($id, Account &$account)
    {
        $data = $this->cache->get("netric/account/" . $id);
        if ($data)
        {
            if (isset($data["id"]) && isset($data["name"]))
            {
                $account->fromArray($data);
                // Put in local memory for even faster retrieval next time
                $this->setLocalMemory($account);
                
                return true;
            }
        }

        // Not found
        return false;
    }

    /**
     * Load from local memory
     *
     * @param string $id The unique id of the account to get
     * @return bool true on success, false on failure/not found
     */
    private function loadFromMemory($id)
    {
        if (isset($this->loadedAccounts[$id]))
            return $this->loadedAccounts[$id];

        // Not found
        return false;
    }

    /**
     * Cache an account in local memory
     *
     * @param \Netric\Account\Account $account Reference to Account object to initialize
     */
    private function setLocalMemory(Account &$account)
    {
        $this->loadedAccounts[$account->getId()] = $account;
    }

    /**
     * Cache an account
     *
     * @param \Netric\Account\Account $account Reference to Account object to initialize
     * @return bool true on success, false on failure
     */
    private function setCache(Account &$account)
    {
        return $this->cache->set("netric/account/" . $account->getId(), $account->toArray());
    }

}
