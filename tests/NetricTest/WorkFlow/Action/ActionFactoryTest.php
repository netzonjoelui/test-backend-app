<?php
/**
 * Test the ActionFactory class
 */
namespace NetricTest\WorkFlow\Action;

use PHPUnit_Framework_TestCase;
use Netric\WorkFlow\Action\ActionFactory;
use Netric\WorkFlow\Action\Exception\ActionNotFoundException;

class ActionFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * Reference to account running for unit tests
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Action factory for testing
     *
     * @var ActionFactory
     */
    private $actionFactory = null;

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $sl = $this->account->getServiceManager();
        $this->actionFactory = new ActionFactory($sl);
    }

    /**
     * Make sure we can construct an action by name
     */
    public function testCreate()
    {
        $testAction = $this->actionFactory->create("test");
        $this->assertInstanceOf('Netric\WorkFlow\Action\TestAction', $testAction);
    }

    /**
     * Check that trying to load a non-existing actions results in an exception
     *
     * @expectedException \Netric\WorkFlow\Action\Exception\ActionNotFoundException
     */
    public function testCreateNotFound()
    {
        $this->actionFactory->create("none-existing-action");
    }
}
