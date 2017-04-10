<?php
/**
 * Test account setup functions
 */
namespace NetricTest\Console;

use Netric\Account\Account;
use Netric\Application\Application;
use Netric\Console\BinScript;
use PHPUnit_Framework_TestCase;

class BinScriptTest extends PHPUnit_Framework_TestCase
{
    /**
     * Reference to account running for unit tests
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $sl = $this->account->getServiceManager();
    }

    /**
     * Make sure we can construct the class
     */
    public function testConstruct()
    {
        $binScript = new BinScript($this->account->getApplication());
        $this->assertInstanceOf('\Netric\Console\BinScript', $binScript);
    }

    /**
     * Test runing a simple script for the application
     */
    public function testRun()
    {
        // Get the account name before running the script
        $accountName = $this->account->getName();

        // Run the script which should change the name (but not save it)
        $binScript = new BinScript($this->account->getApplication(), $this->account);
        $binScript->run(__DIR__ . "/TestAssets/scripts/simple.php");

        $this->assertEquals("edited", $this->account->getDescription());
    }

    /**
     * Make sure that if a script tries to get all accounts on a BinScript that
     * was set to only run one account, that we throw an exception.
     *
     * @expectedException \RuntimeException
     */
    public function testRunOnlyAccount()
    {
        $binScript = new BinScript($this->account->getApplication(), $this->account);
        $binScript->run(__DIR__ . "/TestAssets/scripts/all-accounts.php");
    }
}