<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\ServiceManager;

use Netric\Account\Account;

/**
 * Account specific service manager will load services that are dependent on each
 * account and typically has a parent application service manager.
 *
 * Example could be a database factory, which will be different for each account.
 * We would not want the database factory to be cached across accounts to we will
 * instantiate a new service manager for each account.
 */
interface AccountServiceManagerInterface extends ServiceLocatorInterface
{
    /**
     * Get the current account/tenant
     *
     * @return Account
     */
    public function getAccount();
}
