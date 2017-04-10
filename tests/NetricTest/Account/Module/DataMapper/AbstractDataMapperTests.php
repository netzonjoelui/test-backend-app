<?php
/**
 * Abstract tests for CRUD on a Module
 */
namespace NetricTest\Account\Module\DataMapper;

use Netric\Account\Module\DataMapper;
use Netric\Account\Module\Module;
use PHPUnit_Framework_TestCase;

abstract class AbstractDataMapperTests extends PHPUnit_Framework_TestCase
{
    /**
     * Temp or test modules to cleanup on tearDown
     *
     * @var Module[]
     */
    protected $testModules = [];

    /**
     * Required by all DataMapper tests to construct implementation of DataMapper
     *
     * @return DataMapper\DataMapperInterface
     */
    abstract public function getDataMapper();

    /**
     * Cleanup any created assets
     */
    protected function tearDown()
    {
        $dataMapper = $this->getDataMapper();
        foreach ($this->testModules as $module) {
            $dataMapper->delete($module);
        }
    }

    public function testSave_create()
    {
        $dataMapper = $this->getDataMapper();

        $module = new Module();
        $module->setName("test-" . rand());
        $module->setSystem(false);
        $module->setTitle("Unit Test Module");
        $module->setShortTitle("Test");
        $module->setScope(Module::SCOPE_EVERYONE);

        // Save it
        $dataMapper->save($module);
        $this->assertNotEmpty($module->getId(), $dataMapper->getLastError());
        $this->testModules[] = $module; // For cleanup

        // Re-open and check
        $module2 = $dataMapper->get($module->getName());
        $this->assertEquals($module->toArray(), $module2->toArray());
    }

    public function testSave_update()
    {
        $dataMapper = $this->getDataMapper();

        // Save first
        $module = new Module();
        $module->setName("test-" . rand());
        $module->setSystem(false);
        $module->setTitle("Unit Test Module");
        $module->setShortTitle("Test");
        $module->setScope(Module::SCOPE_EVERYONE);

        // Save it initially
        $dataMapper->save($module);
        $this->testModules[] = $module; // For cleanup

        // Save changes
        $module->setTitle("Unit Test Module - edited");
        $this->assertTrue($dataMapper->save($module), $dataMapper->getLastError());

        // Re-open and check
        $module2 = $dataMapper->get($module->getName());
        $this->assertEquals($module->toArray(), $module2->toArray());
    }

    public function testGet()
    {
        $dataMapper = $this->getDataMapper();

        // Get a system module that will always exist
        $module = $dataMapper->get("notes");
        $this->assertNotNull($module);
        $this->assertNotEmpty($module->getId());

        // Make sure that we have a navigation set
        $this->assertNotNull($module->getNavigation());

        // Make sure that the navigation set is an array
        $this->assertTrue(is_array($module->getNavigation()));
    }

    public function testGetAll()
    {
        $dataMapper = $this->getDataMapper();
        $modules = $dataMapper->getAll();
        $this->assertNotNull($modules);
        $this->assertGreaterThan(0, count($modules), $dataMapper->getLastError());

        // Make sure that we have loaded the settings module
        $this->assertTrue(isset($modules['settings']));
        $this->assertEquals($modules['settings']->getName(), 'settings');
    }

    public function testSaving()
    {
        $dataMapper = $this->getDataMapper();

        // Get a system module that will be tested for saving
        $module = $dataMapper->get("notes");

        // Update the short title
        $module->setShortTitle("Personal Notes");
        $dataMapper->save($module);

        // It should only update the short title and not the notes
        $newModule = $dataMapper->get("notes");

        $this->assertEquals($newModule->getShortTitle(), "Personal Notes");
        $this->assertEquals($newModule->getNavigation(), $module->getNavigation());

        // Reset back the notes short title
        $module->setShortTitle("Notes");
        $dataMapper->save($module);
    }

    public function testNavigationSaving()
    {
        $dataMapper = $this->getDataMapper();

        // Get a system module that will be tested for saving
        $module = $dataMapper->get("notes");

        // Updat the navigation with new data
        $nav = array(
            array(
                "title" => "New Note",
                "type" => "entity",
                "route" => "new-note",
                "objType" => "note",
                "icon" => "plus",
            )
        );
        $module->setNavigation($nav);

        // Save the udpated navigation
        $dataMapper->save($module);

        // It should update the navigation
        $newModule = $dataMapper->get("notes");
        $newNav = $newModule->getNavigation();
        $this->assertEquals($newNav[0]['route'], $nav[0]['route']);

        // Reset the navigation
        $module->setNavigation(null);
        $dataMapper->save($module);
    }

    public function testDelete()
    {
        $dataMapper = $this->getDataMapper();

        // Save first
        $module = new Module();
        $module->setName("test-" . rand());
        $module->setSystem(false);
        $module->setTitle("Unit Test Module");
        $module->setShortTitle("Test");
        $module->setScope(Module::SCOPE_EVERYONE);

        // Save it initially
        $dataMapper->save($module);

        // Delete it
        $dataMapper->delete($module);

        // Make sure we cannot open it
        $this->assertNull($dataMapper->get($module->getName()));
    }
}