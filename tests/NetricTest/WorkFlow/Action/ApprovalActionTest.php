<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;

class ApprovalActionTest extends AbstractActionTests
{
    /**
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("approval");
    }

    /**
     * Make sure we can execute this action type and it works as designed
     */
    public function testExecute()
    {
        // TODO: define test here
    }
}
