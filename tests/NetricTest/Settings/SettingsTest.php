<?php
/**
 * Test the Settings service factory
 */
namespace NetricTest\Settings;

use Netric\Settings;
use PHPUnit_Framework_TestCase;

class SettingsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Settings service to work with
     *
     * @var Settings
     */
    private $settings = null;

    /**
     * Test user
     *
     * @var Netric\Entity\ObjType\UserEntity
     */
    private $user = null;

    protected function setUp()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->settings = $sm->get('Netric\Settings\Settings');
        $this->user = $account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
    }

    public function testGetAndSet()
    {
        $testVal = "MyValue";
        $ret = $this->settings->set("utest/val",  $testVal);
        $this->assertTrue($ret);

        $this->assertEquals($testVal, $this->settings->get("utest/val"));
    }

    public function testGetAndSetForUser()
    {
        $testVal = "MyValue";
        $ret = $this->settings->setForUser($this->user, "utest/val",  $testVal);
        $this->assertTrue($ret);

        $this->assertEquals($testVal, $this->settings->getForUser($this->user, "utest/val"));
    }

    /**
     * Make sure values are being cached
     */
    public function testCache()
    {
        $testVal = "MyValue";
        $key = "utest/val1";
        $this->settings->set($key,  $testVal);

        // Test to see if it is cached
        $refSettings = new \ReflectionObject($this->settings);
        $getCached = $refSettings->getMethod("getCached");
        $getCached->setAccessible(true);
        $this->assertEquals($testVal, $getCached->invoke($this->settings, $key));
    }

    /**
     * By pass cache to make sure it is getting saved right to the database
     */
    public function testDb()
    {
        $testVal = "MyValue";
        $key = "utest/val2";
        $this->settings->set($key,  $testVal);

        // Test to see if it is cached
        $refSettings = new \ReflectionObject($this->settings);
        $getDb = $refSettings->getMethod("getDb");
        $getDb->setAccessible(true);
        $this->assertEquals($testVal, $getDb->invoke($this->settings, $key));
    }
}