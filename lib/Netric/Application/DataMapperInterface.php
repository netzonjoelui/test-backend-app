<?php
/*
 * Short description for file
 * 
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  @author joe <sky.stebnicki@aereus.com>
 *  @copyright 2014 Aereus
 */
namespace Netric\Application;

/**
 * Description of DataMapperInterface
 *
 * @author joe
 */
interface DataMapperInterface 
{
    /**
     * Get an account by id
     * 
     * @param string $id The unique id of the account to get
     * @param \Netric\Account\Account $account Reference to Account object to initialize
     * @return bool true on success, false on failure/not found
     */
    public function getAccountById($id, &$account);
    
    /**
     * Get an account by the unique name
     * 
     * @param string $name
     * @param \Netric\Account\Account $account Reference to Account object to initialize
     * @return bool true on success, false on failure/not found
     */
    public function getAccountByName($name, &$account);
    
    /**
     * Get an associative array of account data
     * 
     * @param string $version If set the only get accounts that are at a current version
     * @return array
     */
    public function getAccounts($version="");

    /**
     * Get account and username from email address
     *
     * @param string $emailAddress The email address to pull from
     * @return array("account"=>"accountname", "username"=>"the login username")
     */
    public function getAccountsByEmail($emailAddress);

    /**
     * Set account and username from email address
     *
     * @param int $accountId The id of the account user is interacting with
     * @param string $username The user name - unique to the account
     * @param string $emailAddress The email address to pull from
     * @return bool true on success, false on failure
     */
    public function setAccountUserEmail($accountId, $username, $emailAddress);

    /**
     * Adds an account to the database
     *
     * @param string $name A unique name for this account
     * @return int Unique id of the created account
     */
    public function createAccount($name);

    /**
     * Delete an account by id
     *
     * @param $accountId
     * @return bool true on success, false on failure - call getLastError for details
     */
    public function deleteAccount($accountId);

    /**
     * Create the application database if it does not already exist
     *
     * @return bool true if exists, false if not and could not create it with $this->getLastError set
     */
    public function createDatabase();

    /**
     * Create a new email domain
     *
     * @param int $accountId
     * @param string $domainName
     * @return bool true on success, false on failure
     */
    public function createEmailDomain($accountId, $domainName);

    /**
     * Delete an existing email domain
     *
     * @param int $accountId
     * @param string $domainName
     * @return bool true on success, false on failure
     */
    public function deleteEmailDomain($accountId, $domainName);

    /**
     * Create or update an email alias
     *
     * @param int $accountId
     * @param string $emailAddress
     * @param string $goto
     * @return bool true on success, false on failure
     */
    public function createOrUpdateEmailAlias($accountId, $emailAddress, $goto);

    /**
     * Delete an email alias
     *
     * @param int $accountId
     * @param string $emailAddress
     * @return bool true on success, false on failure
     */
    public function deleteEmailAlias($accountId, $emailAddress);

    /**
     * Create a new or update an existing email user in the mail system
     *
     * @param int $accountId
     * @param string $emailAddress
     * @param string $password
     * @return bool true on success, false on failure
     */
    public function createOrUpdateEmailUser($accountId, $emailAddress, $password);

    /**
     * Delete an email user from the mail system
     *
     * @param int $accountId
     * @param string $emailAddress
     * @return bool true on success, false on failure
     */
    public function deleteEmailUser($accountId, $emailAddress);
}
