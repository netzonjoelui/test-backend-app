<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;
use Netric\WorkFlow\WorkFlowInstance;

class WebhookActionTest extends AbstractActionTests
{
    /**
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("webhook");
    }

    /**
     * Test execution
     */
    public function testExecute()
    {
        $adapter = new \Zend\Http\Client\Adapter\Test();

        $adapter->setResponse(
            "HTTP/1.1 200 OK"         . "\r\n" .
            "Content-Type: text/html" . "\r\n" .
            "\r\n" .
            '<html>' .
            '  <body><p>OK</p></body>' .
            '</html>'
        );

        $action = $this->getAction();
        $action->setClientAdapter($adapter);
        $action->setParam('url', 'http://test.com/<%obj_type%>/<%oid%>');

        // Create a test task
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "test");
        $task->setId(321);

        // Create a fake WorkFlowInstance since the action does not a saved workflow or instance
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // Now execute the action and make sure it updated the field in $task
        $this->assertTrue($action->execute($workFlowInstance));
    }

    public function testExecuteFailResp()
    {
        $adapter = new \Zend\Http\Client\Adapter\Test();

        $adapter->setResponse(
            "HTTP/1.1 404 NOT FOUND" . "\r\n" .
            "Content-Type: text/html" . "\r\n" .
            "\r\n" .
            '<html>' .
            '  <body><p>Not OK</p></body>' .
            '</html>'
        );

        $action = $this->getAction();
        $action->setClientAdapter($adapter);
        $action->setParam('url', 'http://test.com/<%obj_type%>/<%oid%>');

        // Create a test task
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "test");
        $task->setId(321);

        // Create a fake WorkFlowInstance since the action does not a saved workflow or instance
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // This should fail since the clinet returns 404
        $this->assertFalse($action->execute($workFlowInstance));
    }
}
