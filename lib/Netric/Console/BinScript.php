<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Console;

use Netric\Application\Application;
use Netric\Account\Account;
use Netric\Console\Exception;
use Netric\Request\ConsoleRequest;

/**
 * The BinScript is a wrapper to execute simple scripts found in ../bin/scrips
 *
 * They are typically run like:
 *  $myProgram = new Netric\Console\BinScript($application);
 *  $myProgram->run('install/install.php');
 *
 * Or to run for a specific account only, then pass it as the second param:
 *  $myProgram = new Netric\Console\BinScript($application, $application->getAccount());
 *  $myProgram->run('update/main.php'); // will run only under current account
 *
 * You can also leave off the 'main.php' if the program name you provide is a folder.
 * For exmaple:
 *
 *  $myProgram->run('update');
 *
 * Will check if <netric_root>/bin/scripts/update is a directory, and if it is will look for
 * a file named 'main.php' within that directory to execute the program.
 */
class BinScript
{
    /**
     * The application we are running under
     *
     * @var Application
     */
    private $application = null;

    /**
     * Optional account we are running under
     *
     * This is used to limit the execution of this script to a single account.
     * Otherwise the BinScript program will execute against the application and
     * provide access to all accounts via getAccounts.
     *
     * @var Account
     */
    private $account = null;

    /**
     * Construct and set the application and possibly account to run under
     *
     * A new BinScript should be constructed for each program run since state about the
     * program's execution will be stored in the script.
     *
     * @param Application $application The application instance we are running under
     * @param Account $account If set, this script will be limited to run under a single account
     */
    public function __construct(Application $application, Account $account = null)
    {
        $this->application = $application;
        $this->account = $account;
    }

    /**
     * Get all accounts for this application
     *
     * We do filter for version by default
     *
     * @return Account[]
     */
    protected function getAccounts()
    {
        /*
         * If the calling code indicates that this script should only execute against
         * a single account by passing the account as the second param in construction,
         * then we will not allow a script to get all accounts.
         */
        if ($this->account) {
            throw new \RuntimeException("This script is set to execute under one account only: " . $this->account->getId());
        }

        // Return all accounts for this application - filtered by version automatically
        return $this->application->getAccounts();
    }

    /**
     * Get the account we are set to execute against
     *
     * @return Account
     */
    protected function getAccount()
    {
        return $this->account;
    }

    /**
     * Get the application instance we are running in
     *
     * @return Application
     */
    protected function getApplication()
    {
        return $this->application;
    }

    /**
     * Get the console request
     *
     * @return ConsoleRequest
     */
    protected function getRequest()
    {
        return $this->application->getRequest();
    }

    /**
     * Main execution function
     *
     * @param string $scriptPath Could be a directory name, in which case we will look for main.php
     * @return bool true on success (or throws an exception on failure)
     * @throws Exception\ScriptNotFoundException if the program script cannot be found
     */
    public function run($scriptPath)
    {
        // If the script path provided was a directory, look for main.php within in
        if (is_dir($scriptPath)) {
            $scriptPath .= "/main.php";
        }

        // Make sure the file exists
        if (!file_exists($scriptPath)) {
            throw new Exception\ScriptNotFoundException("Could not find $scriptPath to run");
        }

        // Run the script within the context of $this function
        include($scriptPath);

        return true;
    }

    /**
     * Print a line to the console
     *
     * @param string $line
     */
    protected function printLine($line)
    {
        echo $line . "\n";
    }
}
