<?php
/**
 * Test the entity activity log
 */
namespace NetricTest\Entity;

use Netric;
use Netric\Entity\ActivityLog;
use Netric\EntityLoader;
use Netric\Entity\ObjType\ActivityEntity;
use Netric\Entity\ObjType\UserEntity;
use PHPUnit_Framework_TestCase;

class ActivityLogTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tenant account
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Administrative user
     *
     * @var \Netric\User
     */
    private $user = null;

    /**
     * Activity log
     *
     * @var ActivityLog
     */
    private $activityLog = null;

    /**
     * Entity loader for creating and saving entities
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(UserEntity::USER_SYSTEM);
        $this->activityLog = $this->account->getServiceManager()->get("Netric/Entity/ActivityLog");
        $this->entityLoader = $this->account->getServiceManager()->get("EntityLoader");
    }

    /**
     * Make sure we can log a basic activity
     */
    public function testLog()
    {
        // Create a test customer
        $customerEntity = $this->entityLoader->create("customer");
        $customerEntity->setValue("name", "Test Customer");
        $this->entityLoader->save($customerEntity);

        // Log the activity
        $act = $this->activityLog->log($this->user, ActivityEntity::VERB_CREATED, $customerEntity);
        $openedAct = $this->entityLoader->get("activity", $act->getId());

        // Test activity
        $this->assertNotNull($openedAct);
        $this->assertNotEmpty($openedAct->getValueName("type_id"));
        $this->assertNotEmpty($openedAct->getValueName("subject"));

        // Cleanup
        $this->entityLoader->delete($customerEntity, true);
    }
}