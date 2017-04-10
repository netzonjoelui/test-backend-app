<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;
use Netric\WorkFlow\Action\SendEmailAction;
use Netric\Mail\Transport\InMemory;
use Netric\WorkFlow\WorkFlowInstance;

class SendEmailActionTest extends AbstractActionTests
{
    /**
     * Test entities to delete
     *
     * @var EntityInterface[]
     */
    private $testEntities = array();

    /**
     * Cleanup entities
     */
    protected function tearDown()
    {
        foreach ($this->testEntities as $entity)
        {
            $this->entityLoader->delete($entity, true);
        }

        parent::tearDown();
    }

    /**
     * Setup the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("send_email");
    }

    /**
     * Test execute which should send an email message
     */
    public function testExecute()
    {
        $senderService = $this->account->getServiceManager()->get("Netric/Mail/SenderService");
        $transport = new InMemory();
        $senderService->setMailTransport($transport);

        // Create a test user
        $user = $this->entityLoader->create("user");
        $user->setValue("name", "user-test-" . rand());
        $user->setValue("email", "test@test.com");
        $this->entityLoader->save($user);
        $this->testEntities[] = $user;

        // Setup an action
        $action = new SendEmailAction($this->entityLoader, $this->actionFactory, $senderService);
        $action->setParam("to", array("<%user_id.email%>"));
        $action->setParam("subject", "Automated Email");
        $action->setParam("body", "Hello <%user_id.name%>");
        $action->setParam("from", "test@test.com");

        // Create a task that will email the owner when completed
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "test");
        $task->setValue("user_id", $user->getId());
        $task->setId(321);

        // Create a fake WorkFlowInstance since the action does not a saved workflow or instance
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // Now execute the action and make sure it updated the field in $task
        $this->assertTrue($action->execute($workFlowInstance));

        // Make sure the message was sent to the user specified
        $mailMessage = $transport->getLastMessage();
        $this->assertEquals(
            "<" . $user->getValue("email") . ">",
            $mailMessage->getTo()->current()->toString()
        );
        $this->assertEquals(
            "<test@test.com>",
            $mailMessage->getFrom()->current()->toString()
        );
        $this->assertEquals(
            "Automated Email",
            $mailMessage->getSubject()
        );
        $this->assertContains(
            "Hello " . $user->getName(),
            $mailMessage->getBodyText()
        );
    }
}
