<?php
/**
 * Manage dynamic settings for users and accounts
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Settings;

use Netric\Account\Account;
use Netric\Cache\CacheInterface;
use Netric\Db\DbInterface;
use Netric\ServiceManager;
use Netric\Entity\ObjType\UserEntity;

/**
 * Get and set account and user settings
 */
class Settings
{
    /**
     * Handle to account database
     *
     * @var Database
     */
    private $dbh = null;

    /**
     * The current tennant's account
     *
     * @var Account
     */
    private $account = null;

    /**
     * Application cache - usually Memecache
     *
     * @var CacheInterface|null
     */
    private $cache = null;

    /**
     * Create new settings service
     *
     * @param DbInterface $dbh Handle to the account database
     * @param Account $account The account of the current tennant
     * @param CacheInterface $cache Cache settings to speed things up
     */
    public function __construct(DbInterface $dbh, Account $account, CacheInterface $cache)
    {
        $this->dbh = $dbh;
        $this->account = $account;
        $this->cache = $cache;
    }

    /**
     * Get a setting by name
     *
     * @param string $name
     * @return string
     */
    public function get($name)
    {
        // First try to get from cache (it's much faster that way)
        $ret = $this->getCached($name);

        if ($ret === null)
        {
            $ret = $this->getDb($name);
        }

        return $ret;
    }

    /**
     * Set a setting by name
     *
     * @param string $name
     * @param mixed $value
     * @return bool true on success, false on failure
     */
    public function set($name, $value)
    {
        // First save to the database and make sure it was a success
        $ret = $this->saveDb($name, $value);

        // Now save to cache for later retieval
        if ($ret)
            $this->setCache($name, $value);

        return $ret;
    }

    public function getForTeam($teamId, $name)
    {
        // TODO: Implement
    }

    public function setForTeam($teamId, $name, $value)
    {
        // TODO: Implement
    }

    /**
     * Get a setting for a user by name
     *
     * @param UserEntity $user
     * @param string $name
     * @return string
     */
    public function getForUser(UserEntity $user, $name)
    {
        // First try to get from cache (it's much faster that way)
        $ret = $this->getCached($name, $user->getId());

        if ($ret === null)
        {
            $ret = $this->getDb($name, $user->getId());
        }

        return $ret;
    }

    /**
     * Set a setting by name for a specific user
     *
     * @param UserEntity $user
     * @param string $name
     * @param mixed $value
     * @return bool true on success, false on failure
     */
    public function setForUser(UserEntity $user, $name, $value)
    {
        // First save to the database and make sure it was a success
        $ret = $this->saveDb($name, $value, $user->getId());

        // Now save to cache for later retieval
        if ($ret)
            $this->setCache($name, $value, $user->getId());

        return $ret;
    }

    /**
     * Get a setting from cache if it is set
     *
     * @param $name
     * @param null $userId
     * @return mixed
     */
    private function getCached($name, $userId=null)
    {
        $key = $this->getCachcedKey($name, $userId);
        return $this->cache->get($key);
    }

    /**
     * Save a setting to cache
     *
     * @param seting $name Unique name of the setting value to save
     * @param string $value Value to store
     * @param int $userId Optional user id if this is a user setting
     */
    private function setCache($name, $value, $userId=null)
    {
        $key = $this->getCachcedKey($name, $userId);
        $this->cache->set($key, $value);
    }

    /**
     * Construct a unique key to store the cache in
     *
     * @param $name The unique name of the settings key
     * @param int $userId Optional user id
     * @param int $teamId Optional team id
     * @return string
     */
    private function getCachcedKey($name, $userId=null, $teamId=null)
    {
        // Namespace by account id
        $cachedKey = $this->account->getId();

        if ($userId)
        {
            $cachedKey .= "/users/" . $userId . "/settings";
        }
        else
        {
            $cachedKey .= "/settings";
        }

        return $cachedKey . "/" . $name;
    }

    /**
     * Save a setting in the database
     *
     * @param string $name The unique setting name
     * @param string $value The value to save
     * @param int $userId Optional user id to save the setting for
     * @param int $teamId Optional team id to save the setting for
     * @return bool true on success, false on failure
     */
    private function saveDb($name, $value, $userId=null, $teamId=null)
    {
        $sql = "SELECT id FROM system_registry
                WHERE key_name='" . $this->dbh->escape($name) . "'";

        // Either add a user or explicitely exclude it
        if (is_numeric($userId))
        {
            $sql .= " AND user_id='" . $userId . "'";
        }
        else
        {
            $sql .= " AND user_id IS NULL";
        }

        $result = $this->dbh->query($sql);
        if ($this->dbh->getNumRows($result))
        {
            $row = $this->dbh->getRow($result, 0);
            $sql = "UPDATE system_registry
                    SET key_val='" . $this->dbh->escape($value) . "'
                    WHERE id='" . $this->dbh->escape($row['id']) . "'";
            if (!$this->dbh->query($sql))
            {
                return false;
            }
        }
        else
        {
            $sql = "INSERT INTO system_registry(key_name, key_val, user_id)
                    VALUES (
                      '" . $this->dbh->escape($name) . "',
                      '" . $this->dbh->escape($value) . "',
                      " . $this->dbh->escapeNumber($userId) . "
                    )";
            if (!$this->dbh->query($sql))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a value from the account database
     *
     * @param string $name The unique setting name
     * @param int $userId Optional user id to save the setting for
     * @param int $teamId Optional team id to save the setting for
     * @return null
     */
    private function getDb($name, $userId=null, $teamId=null)
    {
        $ret = null;

        $sql = "SELECT
                    key_val
                  FROM
                    system_registry
                  WHERE
                    key_name='" . $this->dbh->escape($name) . "'";

        // Either add a user or explicitely exclude it
        if (is_numeric($userId))
        {
            $sql .= " AND user_id='" . $userId . "'";
        }
        else
        {
            $sql .= " AND user_id IS NULL";
        }

        $result = $this->dbh->query($sql);
        if ($this->dbh->getNumRows($result))
        {
            $row = $this->dbh->getRow($result, 0);
            $ret = $row['key_val'];
        }

        return $ret;
    }
}