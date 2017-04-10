<?php
namespace NetricTest\WorkerMan;

use Netric\WorkerMan\WorkerService;
use Netric\WorkerMan\Queue;
use PHPUnit_Framework_TestCase;

class WorkFlowTest extends PHPUnit_Framework_TestCase
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
    protected $actionFactory = null;

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $sl = $this->account->getServiceManager();
    }

    public function testConstruct()
    {
        $queue = new Queue\InMemory();
        $service = new WorkerService($this->account->getApplication(), $queue);
        $this->assertInstanceOf('\Netric\WorkerMan\WorkerService', $service);
    }

    public function testDoWork()
    {
        $queue = new Queue\InMemory();
        $service = new WorkerService($this->account->getApplication(), $queue);
        $this->assertTrue($service->doWork("Test", array("mystring"=>"test")));
    }

    public function testDoWorkBackground()
    {
        $queue = new Queue\InMemory();
        $service = new WorkerService($this->account->getApplication(), $queue);
        $this->assertEquals("1", $service->doWorkBackground("Test", array("mystring"=>"test")));
    }

    public function testProcessJobQueue()
    {
        $queue = new Queue\InMemory();
        $service = new WorkerService($this->account->getApplication(), $queue);
        $service->doWorkBackground("Test", array("mystring"=>"test"));
        $this->assertTrue($service->processJobQueue());
    }

}