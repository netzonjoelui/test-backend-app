<?php
/**
 * Test saving and loading recurrence patterns from the DataMapper
 */
namespace NetricTest\Entity\Recurrence;

use Netric\Entity\Recurrence\RecurrenceDataMapper;
use Netric\Entity\Recurrence\RecurrencePattern;
use PHPUnit_Framework_TestCase;

class RecurrenceDataMapperTest extends PHPUnit_Framework_TestCase
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
     * DataMapper to test
     *
     * @var RecurrenceDataMapper
     */
    private $dataMapper = null;

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);

        // Get service manager for locading dependencies
        $sm = $this->account->getServiceManager();

        // Setup the recurrence datamapper
        $entDefLoader = $sm->get("EntityDefinitionLoader");
        $dbh = $sm->get("Db");
        $this->dataMapper = new RecurrenceDataMapper($this->account, $dbh, $entDefLoader);
    }

    public function testConstruct()
    {
        $this->assertInstanceOf('Netric\Entity\Recurrence\RecurrenceDataMapper', $this->dataMapper);
    }

    public function testSave()
    {
        $rp = new RecurrencePattern();
        $rp->setObjType("task");
        $rp->setRecurType(RecurrencePattern::RECUR_DAILY);
        $rp->setInterval(1);
        $rp->setDateStart(new \DateTime("1/1/2010"));
        $rp->setDateEnd( new \DateTime("3/1/2010"));

        $rid = $this->dataMapper->save($rp);
        $this->assertNotNull($rid);
    }

    public function testLoad()
    {
        $data = array(
            "recur_type" => RecurrencePattern::RECUR_WEEKLY,
            "obj_type" => "task",
            "interval" => 1,
            "date_start" => "2015-01-01",
            "date_end" => "2015-03-01",
            "day_of_week_mask" => RecurrencePattern::WEEKDAY_SUNDAY,
        );
        $rp = new RecurrencePattern();
        $rp->fromArray($data);

        $rid = $this->dataMapper->save($rp);
        $this->assertNotNull($rid);

        $opened = $this->dataMapper->load($rid);

        $this->assertTrue($opened->getId() > 0);
        $this->assertEquals($data['recur_type'], $opened->getRecurType());
        $this->assertEquals($data['obj_type'], $opened->getObjType());
        $this->assertEquals($data['interval'], $opened->getInterval());
        $this->assertEquals(new \DateTime($data['date_start']), $opened->getDateStart());
        $this->assertEquals(new \DateTime($data['date_end']), $opened->getDateEnd());
        $this->assertEquals(1, $opened->getDayOfWeekMask() & RecurrencePattern::WEEKDAY_SUNDAY);
    }

    public function testGetNextId()
    {
        $lastId = $this->dataMapper->getNextId();
        $nextId = $this->dataMapper->getNextId();
        $this->assertEquals(++$lastId, $nextId);
    }

    public function testDelete()
    {
        // Create
        $data = array(
            "recur_type" => RecurrencePattern::RECUR_DAILY,
            "obj_type" => "task",
            "interval" => 1,
            "date_start" => "2015-01-01",
            "date_end" => "2015-03-01"
        );
        $rp = new RecurrencePattern();
        $rp->fromArray($data);
        $rid = $this->dataMapper->save($rp);

        // Delete
        $this->dataMapper->delete($rp);

        // Assure we cannot load it
        $this->assertNull($this->dataMapper->load($rid));
    }

    public function testDeleteById()
    {
        // Create
        $data = array(
            "recur_type" => RecurrencePattern::RECUR_DAILY,
            "obj_type" => "task",
            "interval" => 1,
            "date_start" => "2015-01-01",
            "date_end" => "2015-03-01"
        );
        $rp = new RecurrencePattern();
        $rp->fromArray($data);
        $rid = $this->dataMapper->save($rp);

        // Delete
        $this->dataMapper->deleteById($rid);

        // Assure we cannot load it
        $this->assertNull($this->dataMapper->load($rid));
    }

    /**
     * Test query to get a list of stale patterns compared to a specific date
     *
     * This basiclaly means the recurrencePattern processedTo is earlier
     * than the challenge date specified.
     */
    public function testGetStalePatterns()
    {
        // Create
        $data = array(
            "recur_type" => RecurrencePattern::RECUR_DAILY,
            "obj_type" => "task",
            "interval" => 1,
            "date_start" => "2015-02-01",
            "date_end" => "2015-03-01",
            "date_processed_to" => "2015-02-01",
        );
        $rp = new RecurrencePattern();
        $rp->fromArray($data);
        $rid = $this->dataMapper->save($rp);

        // Check before date-start, $rid should not be returned
        $dateTo = new \DateTime("2015-01-01");
        $staleIds = $this->dataMapper->getStalePatternIds("task", $dateTo);
        $this->assertFalse(in_array($rid, $staleIds));

        // Check the day after start date which should create entities
        $dateTo = new \DateTime("2015-02-02");
        $staleIds = $this->dataMapper->getStalePatternIds("task", $dateTo);
        $this->assertTrue(in_array($rid, $staleIds));

        // Check a couple days out which should also return the above
        $dateTo = new \DateTime("2015-02-20");
        $staleIds = $this->dataMapper->getStalePatternIds("task", $dateTo);
        $this->assertTrue(in_array($rid, $staleIds));

        // Go beyond the end date which should NOT return the above pattern
        $dateTo = new \DateTime("2015-05-01");
        $staleIds = $this->dataMapper->getStalePatternIds("task", $dateTo);
        $this->assertFalse(in_array($rid, $staleIds));

        // Cleanup
        $this->dataMapper->deleteById($rid);
    }
}