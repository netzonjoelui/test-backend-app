<?php
/**
 * @copyright Copyright (c) 2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Mvc;

use Netric\Account\Account;
use Netric\Application\Application;
use Netric\Permissions\Dacl;

/**
 * Controller that MUST be executed with a valid account or will throw an exception
 */
abstract class AbstractAccountController extends AbstractController
{
    /**
     * class constructor. All calls to a controller class require a reference to $ant and $user classes
     *
     * @param Application $application The current application instance
     * @param Account $account The tenant we are running under
     */
    function __construct(Application $application, Account $account)
    {
        if (!$account) {
            throw new \RuntimeException("Account is required for an account controller");
        }

        parent::__construct($application, $account);
    }
}
