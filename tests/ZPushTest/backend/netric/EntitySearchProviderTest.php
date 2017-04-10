<?php
/**
 * Test searching entities
 */
namespace ZPushTest\backend\netric;

use PHPUnit_Framework_TestCase;

// Add all z-push required files
require_once("z-push.includes.php");

// Include config
require_once(dirname(__FILE__) . '/../../../../config/zpush.config.php');

// Include backend classes
require_once('backend/netric/netric.php');
require_once('backend/netric/entityprovider.php');

class EntitySearchProviderTest extends PHPUnit_Framework_TestCase
{
    /**
     * Handle to account
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
    const TEST_USER = "test_auth";
    const TEST_USER_PASS = "testpass";

    /**
     * Entity provider for converting entities to and from SyncObjects
     *
     * @var \EntitySearchProvider
     */
    private $provider = null;

    /**
     * Test entities to cleanup
     *
     * @var \Netric\Entity\EntityInterface[]
     */
    private $testEntities = array();

    /**
     * Loader for opening, saving, and deleting entities
     *
     * @var \Netric\EntityLoader
     */
    private $entityLoader = null;

    /**
     * Test calendar
     *
     * @var \Netric\Entity\Entity
     */
    private $testCalendar = null;

    /**
     * Inbox
     *
     * @var \Netric\EntityGroupings\Group
     */
    private $groupInbox = null;


    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();

        $this->account = \NetricTest\Bootstrap::getAccount();

        // Setup entity datamapper for handling users
        $dm = $this->account->getServiceManager()->get("Entity_DataMapper");

        // Make sure old test user does not exist
        $query = new \Netric\EntityQuery("user");
        $query->where('name')->equals(self::TEST_USER);
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $res = $index->executeQuery($query);
        for ($i = 0; $i < $res->getTotalNum(); $i++) {
            $user = $res->getEntity($i);
            $dm->delete($user, true);
        }

        // Create a test user
        $loader = $this->account->getServiceManager()->get("EntityLoader");
        $user = $loader->create("user");
        $user->setValue("name", self::TEST_USER);
        $user->setValue("password", self::TEST_USER_PASS);
        $user->setValue("full_name", "Test User");
        $user->setValue("active", true);
        $user->setValue("email", "test@test.com");
        $dm->save($user);
        $this->user = $user;
        $this->testEntities[] = $user; // cleanup automatically

        // Get the entityLoader
        $this->entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create inbox mailbox for testing
        $groupingsLoader = $this->account->getServiceManager()->get("EntityGroupings_Loader");
        $groupings = $groupingsLoader->get("email_message", "mailbox_id", array("user_id" => $user->getId()));
        if (!$groupings->getByName("Inbox")) {
            $inbox = $groupings->create("Inbox");
            $inbox->user_id = $user->getId();
            $groupings->add($inbox);
            $groupingsLoader->save($groupings);
        }
        $this->groupInbox = $groupings->getByName("Inbox");

        // Create a calendar for the user to test
        $calendar = $this->entityLoader->create("calendar");
        $calendar->setValue("name", "UTest provider");
        $calendar->setValue("user_id", $this->user->getId());
        $this->entityLoader->save($calendar);
        $this->testEntities[] = $calendar;
        $this->testCalendar = $calendar;

        // Setup the provider service
        $this->provider = new \EntitySearchProvider($this->account, $this->user);
    }

    /**
     * Cleanup
     */
    protected function tearDown()
    {
        foreach ($this->testEntities as $entity) {
            $this->entityLoader->delete($entity, true);
        }
    }

    public function testGetGalSearchResults()
    {
        $items = $this->provider->GetGALSearchResults(self::TEST_USER, "0-100");
        $this->assertTrue(isset($items['range']));
        $this->assertGreaterThan(0, (int) $items['searchtotal']);

        $foundItem = null;

        foreach ($items as $item) {
            if ($item[SYNC_GAL_DISPLAYNAME] == self::TEST_USER) {
                $foundItem = $item;
            }
        }
        $this->assertNotNull($foundItem);
        $this->assertEquals("Test", $foundItem[SYNC_GAL_FIRSTNAME]);
        $this->assertEquals("User", $foundItem[SYNC_GAL_LASTNAME]);
        $this->assertEquals($this->user->getValue("email"), $foundItem[SYNC_GAL_EMAILADDRESS]);
    }

    public function testGetMailboxSearchResults()
    {
        // Add test email message to inbox
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $email = $entityLoader->create("email_message");
        $email->setValue("subject", "test message");
        $email->setValue("owner_id", $this->user->getId());
        $email->setValue("mailbox_id", $this->groupInbox->id);
        $entityLoader->save($email);
        $this->testEntities[] = $email;

        // Create content params object
        $cpo = new \ContentParameters();
        $cpo->SetSearchFreeText("test");
        $cpo->SetSearchRange("0-10");
        $cpo->GetSearchFolderid(\EntityProvider::FOLDER_TYPE_EMAIL . "-" . $this->groupInbox->id);

        // Run the search
        $items = $this->provider->GetMailboxSearchResults($cpo);

        $this->assertTrue(isset($items['range']));
        $this->assertGreaterThan(0, (int) $items['searchtotal']);

        $foundItem = null;

        foreach ($items as $item) {
            if ($item['longid'] == $email->getId()) {
                $foundItem = $item;
            }
        }
        $this->assertNotNull($foundItem);
    }

    public function testDisconnect()
    {
        $this->assertTrue($this->provider->Disconnect());
    }

    public function testTerminateSearch()
    {
        $this->assertTrue($this->provider->TerminateSearch());
    }
}