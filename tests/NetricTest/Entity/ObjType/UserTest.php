<?php
/**
 * Test entity activity class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use PHPUnit_Framework_TestCase;

class UserTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Tennant account
     * 
     * @var \Netric\Account\Account
     */
    private $account = null;
    
    /**
     * Test user
     * 
     * @var \Netric\Entity\ObjType\UserEntity
     */
    private $user = null;

    /**
     * Common constants used
     *
     * @cons string
     */
    const TEST_USER = "entity_objtype_test";
    const TEST_USER_PASS = "testpass";
    const TEST_EMAIL = "entity_objtype_test@netric.com";

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
        
        // Setup entity datamapper for handling users
        $dm = $this->account->getServiceManager()->get("Entity_DataMapper");
        
        // Make sure old test user does not exist
        $query = new \Netric\EntityQuery("user");
        $query->where('name')->equals(self::TEST_USER);
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $res = $index->executeQuery($query);
        for ($i = 0; $i < $res->getTotalNum(); $i++)
        {
            $user = $res->getEntity($i);
            $dm->delete($user, true);
        }

        // Create a test user
        $loader = $this->account->getServiceManager()->get("EntityLoader");
        $user = $loader->create("user");
        $user->setValue("name", self::TEST_USER);
        $user->setValue("password", self::TEST_USER_PASS);
        $dm->save($user);
        $this->user = $user;
    }

    protected function tearDown()
    {
        if ($this->user)
        {
            $dm = $this->account->getServiceManager()->get("Entity_DataMapper");
            $dm->delete($this->user, true);
        }
    }

    /**
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $def = $this->account->getServiceManager()->get("EntityDefinitionLoader")->get("user");
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("user");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\UserEntity", $entity);
    }

    public function testOnBeforeSave()
    {
        $oldPass = $this->user->getValue("password");
        $newPass = "newvalue";

        // onBeforeSave copies obj_reference to the 'associations' field
        $this->user->setValue("password", "newvalue");
        $this->user->onBeforeSave($this->account->getServiceManager());

        // Make sure we have hashed and encoded the password
        $this->assertNotEquals($this->user->getValue("password"), $newPass);

        // And that the old password has also changed
        $this->assertNotEquals($this->user->getValue("password"), $oldPass);
    }

    public function testOnBeforeSaveNewUserPasswordSet()
    {
        $user = $this->account->getServiceManager()->get("EntityFactory")->create("user");
        $user->setValue("name", self::TEST_USER);
        $user->setValue("password", self::TEST_USER_PASS);
        $user->onBeforeSave($this->account->getServiceManager());

        // Make sure we have hashed and encoded the password
        $this->assertNotEquals($user->getValue("password"), self::TEST_USER_PASS);
    }

    public function testOnAfterSaveAppicationEmailMapSet()
    {
        $user = $this->account->getServiceManager()->get("EntityFactory")->create("user");
        $user->setValue("name", self::TEST_USER);
        $user->setValue("email", self::TEST_EMAIL);
        $user->setValue("password", self::TEST_USER_PASS);
        $user->onAfterSave($this->account->getServiceManager());

        // Make sure the application can get the username from the email now
        $app = $this->account->getApplication();
        $accounts = $app->getAccountsByEmail(self::TEST_EMAIL);
        $this->assertEquals(1, count($accounts));
        $this->assertEquals($accounts[0]['username'], self::TEST_USER);
    }

    public function testOnAfterSaveAppicationEmailMapSetChanged()
    {
        $app = $this->account->getApplication();

        $user = $this->account->getServiceManager()->get("EntityFactory")->create("user");
        $user->setValue("name", self::TEST_USER);
        $user->setValue("email", self::TEST_EMAIL);
        $user->setValue("password", self::TEST_USER_PASS);
        $user->onAfterSave($this->account->getServiceManager());

        // Change the username and make sure the old username was deleted
        $user->setValue("name", self::TEST_USER . "-changed");
        $user->onAfterSave($this->account->getServiceManager());

        // Make sure the application can get the username from the email now
        $accounts = $app->getAccountsByEmail(self::TEST_EMAIL);
        $this->assertEquals(1, count($accounts));
        $this->assertEquals($accounts[0]['username'], self::TEST_USER . "-changed");

        // Reset
        $user->setValue("name", self::TEST_USER);
        $user->onAfterSave($this->account->getServiceManager());

        // Change the email and make sure the old username was deleted
        $user->setValue("email", self::TEST_EMAIL . "-changed");
        $user->onAfterSave($this->account->getServiceManager());

        // Make sure the application can get the username from the email now
        $accounts = $app->getAccountsByEmail(self::TEST_EMAIL);
        $this->assertEquals(0, count($accounts));
    }

    public function testGetGroups()
    {
        $this->user->addMultiValue("groups", Entity\ObjType\UserEntity::GROUP_ADMINISTRATORS);
        
        $groups = $this->user->getGroups();

        // Make sure administrators was added
        $this->assertTrue(in_array(Entity\ObjType\UserEntity::GROUP_ADMINISTRATORS, $groups));

        // Make sure default users was also added
        $this->assertTrue(in_array(Entity\ObjType\UserEntity::GROUP_USERS, $groups));
    }

    // Test before adding any groups that the default USERS groups was added
    public function testGetGroupsDefault()
    {
        $groups = $this->user->getGroups();
        $this->assertTrue(in_array(Entity\ObjType\UserEntity::GROUP_USERS, $groups));
        $this->assertTrue(in_array(Entity\ObjType\UserEntity::GROUP_EVERYONE, $groups));
    }
}