<?php
/**
 * Test entity activity class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use PHPUnit_Framework_TestCase;

class CommentTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tennant account
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
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
    }

    /**
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $def = $this->account->getServiceManager()->get("EntityDefinitionLoader")->get("comment");
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("comment");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\CommentEntity", $entity);
    }

    /**
     * When we add a comment to an entity, the referenced entity has a num_comments field that is updated
     */
    public function testHasCommentsOnReferencedEntity()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $customer = $this->account->getServiceManager()->get("EntityFactory")->create("customer");
        $comment = $this->account->getServiceManager()->get("EntityFactory")->create("comment");

        // Save customer so we have it to work with
        $customer->setValue("name", "test num_comments");
        $cid = $entityLoader->save($customer);

        // Now save the comment which should increment the num_comments of $customer
        $comment->setValue("obj_reference", "customer:" . $cid, $customer->getName());
        $comment->setValue("comment", "Test Comment");
        $entityLoader->save($comment);

        // Now re-open the referenced customer just to make sure it was saved right
        $openedCustomer = $entityLoader->get("customer", $cid);
        $this->assertEquals(1, $openedCustomer->getValue("num_comments"));

        // Delete the comment and make sure num_comments is decremented
        $entityLoader->delete($comment);
        $this->assertEquals(0, $openedCustomer->getValue("num_comments"));

        // Cleanup
        $entityLoader->delete($comment, true);
        $entityLoader->delete($openedCustomer, true);
    }

    /**
     * Entity followers are synchronized with the comment followers
     *
     * This makes sure that all interested parties are notified when we add
     * a new comment to an entity.
     */
    public function testSyncFollowers()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $customer = $this->account->getServiceManager()->get("EntityFactory")->create("customer");
        $comment = $this->account->getServiceManager()->get("EntityFactory")->create("comment");

        // Save customer with a fake user callout for testing
        $customer->setValue("name", "test sync followers");
        $customer->setValue("notes", "Hey [user:456:Dave], check this out please.");
        $cid = $entityLoader->save($customer);

        // Now create a comment on the customer which should sync the followers
        $comment->setValue("obj_reference", "customer:" . $cid, $customer->getName());
        $comment->setValue("comment", "Test Comment");
        $entityLoader->save($comment);

        // Check to make sure the comment has user 456 as a follower copied from customer
        $followers = $comment->getValue("followers");
        $this->assertTrue(in_array(456, $followers));

        // Cleanup
        $entityLoader->delete($comment, true);
        $entityLoader->delete($customer, true);
    }
}