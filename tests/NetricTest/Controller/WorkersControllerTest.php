<?php
/**
 * Test calling the workers controller
 */
namespace NetricTest\Controller;

use Netric;
use PHPUnit_Framework_TestCase;

class WorkersControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Account used for testing
     *
     * @var \Netric\Account\Account
     */
    protected $account = null;

    /**
     * Controller instance used for testing
     *
     * @var \Netric\Controller\WorkersController
     */
    protected $controller = null;

    /**
     * Test user
     *
     * @var \Netric\Entity\ObjType\UserEntity
     */
    private $user = null;

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();

        // Create the controller
        $this->controller = new Netric\Controller\WorkersController($this->account->getApplication(), $this->account);
        $this->controller->testMode = true;
    }

    public function testConsoleProcessAction()
    {
        // Set params in the request
        $req = $this->controller->getRequest();
        $req->setParam("runtime", 1); // Only run for 1 second
        $req->setParam("suppressoutput", 1); // Do not allow echo

        // Setup a test job
        $appServiceManager = $this->account->getApplication()->getServiceManager();
        $workerService = $appServiceManager->get("Netric/WorkerMan/WorkerService");
        $workerService->doWorkBackground("Test", array("mystring"=>"test"));

        // Run the process action
        $ret = $this->controller->consoleProcessAction();
        $this->assertInstanceOf(
            'Netric\Application\Response\ConsoleResponse',
            $ret
        );
        $outputBuffer = $ret->getOutputBuffer();
        $this->assertEquals("Processed 1 jobs", trim(array_pop($outputBuffer)));
    }
}
