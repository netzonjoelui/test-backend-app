<?php
namespace NetricTest\Permissions;

use PHPUnit_Framework_TestCase;
use Netric\Permissions;
use Netric\Entity\ObjType\UserEntity;
use Netric\Entity\EntityInterface;
use Netric\Permissions\Dacl;

class DaclTest extends PHPUnit_Framework_TestCase
{
    /**
     * Active test account
     *
     * @var Account
     */
    private $account = null;

    /**
     * The user that owns the email account
     *
     * @var UserEntity
     */
    private $user = null;

    /**
     * Any test entities created
     *
     * @var EntityInterface[]
     */
    private $testEntities = [];

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create a temporary user
        $this->user = $entityLoader->create("user");
        $this->user->setValue("name", "utest-email-receiver-" . rand());
        $this->user->addMultiValue("groups", UserEntity::GROUP_USERS);
        $entityLoader->save($this->user);
        $this->testEntities[] = $this->user;
    }

    protected function tearDown()
    {
        $serviceLocator = $this->account->getServiceManager();

        // Delete any test entities
        $entityLoader = $serviceLocator->get("EntityLoader");
        foreach ($this->testEntities as $entity) {
            $entityLoader->delete($entity, true);
        }
    }

    public function testAllowUser()
    {
        $dacl = new Permissions\Dacl();

        // First pass will fail since users was not given access
        $this->assertFalse($dacl->isAllowed($this->user));
        
        // Add USERS group and then test again
        $dacl->allowUser($this->user->getId());

        $this->assertTrue($dacl->isAllowed($this->user));
    }

    public function testAllowGroup()
    {
        $dacl = new Permissions\Dacl();

        // First pass will fail since users was not given access
        $this->assertFalse($dacl->isAllowed($this->user));

        // Add USERS group and then test again
        $dacl->allowGroup(UserEntity::GROUP_USERS);

        $this->assertTrue($dacl->isAllowed($this->user));
    }

    public function testDenyUser()
    {
        $dacl = new Permissions\Dacl();

        // Add user which should cause it to pass
        $dacl->allowUser($this->user->getId());
        $this->assertTrue($dacl->isAllowed($this->user));

        // Remove the user which should cause it to fail
        $dacl->denyUser($this->user->getId());
        $this->assertFalse($dacl->isAllowed($this->user));
    }

    public function testDenyGroup()
    {
        $dacl = new Permissions\Dacl();

        // Add user which should cause it to pass
        $dacl->allowGroup(UserEntity::GROUP_USERS);
        $this->assertTrue($dacl->isAllowed($this->user));

        // Remove the user which should cause it to fail
        $dacl->denyGroup(UserEntity::GROUP_USERS);
        $this->assertFalse($dacl->isAllowed($this->user));
    }

    public function testFromArray()
    {
        $data = array(
            "entries" => array(
                array(
                    "name" => Dacl::PERM_VIEW,
                    "groups" => [UserEntity::GROUP_USERS],
                    "users" => [$this->user->getId()]
                ),
            ),
        );

        $dacl = new Permissions\Dacl();
        $dacl->fromArray($data);

        // Make sure it was loaded
        $this->assertTrue($dacl->isAllowed($this->user, Dacl::PERM_VIEW));

        // Make a new user and add them to the group to test
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $user2 = $entityLoader->create("user");
        $user2->setValue("name", "utest-dacl-" . rand());
        $user2->addMultiValue("groups", UserEntity::GROUP_USERS);
        $entityLoader->save($user2);
        $this->testEntities[] = $user2;

        // Make make sure groups were populated
        $this->assertTrue($dacl->isAllowed($user2, Dacl::PERM_VIEW));
    }

    public function testToArray()
    {
        $dacl = new Permissions\Dacl();
        $dacl->allowGroup(UserEntity::GROUP_USERS);
        $dacl->allowUser($this->user->getId());

        $exported = $dacl->toArray();
        $this->assertEquals([UserEntity::GROUP_USERS], $exported['entries'][0]['groups']);
    }

    public function testGetUsers()
    {
        $dacl = new Permissions\Dacl();
        $dacl->allowUser($this->user->getId());

        $users = $dacl->getUsers();
        $this->assertEquals(1, count($users));
        $this->assertEquals([$this->user->getId()], $users);
    }

    public function testGetGroups()
    {
        $dacl = new Permissions\Dacl();
        $dacl->allowGroup(UserEntity::GROUP_USERS);

        $groups = $dacl->getGroups();
        $this->assertEquals(1, count($groups));
        $this->assertEquals([UserEntity::GROUP_USERS], $groups);
    }
}