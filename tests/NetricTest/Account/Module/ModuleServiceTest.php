<?php
/**
 * Make sure the module service works
 */
namespace NetricTest\Account\Module;

use Netric\Account\Module\Module;
use Netric\Account\Module\ModuleService;
use PHPUnit_Framework_TestCase;

class ModuleServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Module service instance to test
     *
     * @var ModuleService
     */
    private $moduleService = null;

    /**
     * Temp or test modules to cleanup on tearDown
     *
     * @var Module[]
     */
    protected $testModules = [];

    protected function setUp()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->moduleService = $sm->get("Netric/Account/Module/ModuleService");
    }

    /**
     * Cleanup any created assets
     */
    protected function tearDown()
    {
        foreach ($this->testModules as $module)
        {
            $this->moduleService->delete($module);
        }
    }

    public function testSave()
    {
        $module = new Module();
        $module->setName("test-" . rand());
        $module->setSystem(false);
        $module->setTitle("Unit Test Module");
        $module->setShortTitle("Test");
        $module->setScope(Module::SCOPE_EVERYONE);

        // Save it
        $this->assertTrue($this->moduleService->save($module));
        $this->testModules[] = $module;
    }

    public function testDelete()
    {
        // Save first
        $module = new Module();
        $module->setName("test-" . rand());
        $module->setSystem(false);
        $module->setTitle("Unit Test Module");
        $module->setShortTitle("Test");
        $module->setScope(Module::SCOPE_EVERYONE);

        // Save it initially
        $this->moduleService->save($module);

        // Delete it
        $this->assertTrue($this->moduleService->delete($module));
    }

    public function testGetByName()
    {
        // Get a system module that will always exist
        $module = $this->moduleService->getByName("notes");
        $this->assertNotNull($module);
        $this->assertNotEmpty($module->getId());
    }

    public function testGetById()
    {
        // First get by name
        $module = $this->moduleService->getByName("notes");
        $this->assertNotNull($module);
        $this->assertNotEmpty($module->getId());

        // Now try to get by id
        $module2 = $this->moduleService->getById($module->getId());
        $this->assertEquals($module->getId(), $module2->getId());
    }

    public function testGetForUser()
    {
        // Create a temp user
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $entityLoader = $sm->get("EntityLoader");
        $user = $entityLoader->create("user");

        // Make sure we can get modules for this entity
        $modules = $this->moduleService->getForUser($user);
        $this->assertGreaterThan(0, count($modules));
    }
}