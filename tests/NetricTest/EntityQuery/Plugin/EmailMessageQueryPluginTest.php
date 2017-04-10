<?php
namespace NetricTest\EntityQuery\Plugin;

use Netric;
use Netric\EntityQuery;
use Netric\Entity\EntityInterface;
use Netric\EntityQuery\Plugin;
use PHPUnit_Framework_TestCase;
use Netric\WorkerMan;

/**
 * @group integration
 */
class EmailMessageQueryPluginTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tenant account
     *
     * @var \Netric\Account\Account
     */
    protected $account = null;

    /**
     * Test entities to delete
     *
     * @var EntityInterface[]
     */
    private $testEntities = array();

    /**
     * Test groupings to delete
     *
     * @var array(array('obj_type', 'field', 'grouping_id'))
     */
    private $testGroupings = array();

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
    }

    public function testOnBeforeQuery()
    {
        // Setup an in-memory worker queue for testing
        $queue = new WorkerMan\Queue\InMemory();
        $service = new WorkerMan\WorkerService($this->account->getApplication(), $queue);

        // Create plugin
        $plugin = new Plugin\EmailMessageQueryPlugin();
        $plugin->setWorkerService($service);

        // Setup query and run the plugin just like the index would right before a query
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals(123);
        $this->assertTrue($plugin->onBeforeExecuteQuery($this->account->getServiceManager(), $query));

        // Make sure the right job was queued with the right params
        $this->assertEquals(
            array(
                "EmailMailboxSync",
                array(
                    "account_id"=>$this->account->getId(),
                    "user_id"=>$this->account->getUser()->getId(),
                    "mailbox_id"=>123,
                ),
            ),
            $queue->queuedJobs[0]
        );
    }

    public function testOnAfterExecuteQuery()
    {
        $plugin = new Plugin\EmailMessageQueryPlugin();
        $query = new EntityQuery("email_message");
        $this->assertTrue($plugin->onAfterExecuteQuery($this->account->getServiceManager(), $query));
    }
}