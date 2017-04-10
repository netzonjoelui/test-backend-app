<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace NetricTest\Config;

use Netric\Config\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruction()
    {
        $testValues = array(
            'database' => array(
                'host' => 'localhost',
                'user' => 'netric'
            )
        );

        $config = new Config($testValues);

        $this->assertEquals($config->database->host, $testValues['database']['host']);
        $this->assertEquals($config->database->user, $testValues['database']['user']);
        $this->assertNull($config->database->notSetProperty);
    }

    /**
     * An unset property should return null
     */
    public function testPropertyNotSetNull()
    {
        $testValues = array('dbname'=>'test');

        $config = new Config($testValues);

        $this->assertEquals($testValues['dbname'], $config->dbname);

        // Make sure that the undefined property is set to null
        $this->assertNull($config->test2);
    }

    /**
     * Test isset overloading
     */
    public function testIsset()
    {
        $testValues = array(
            'database'=> array(
                'host'=>'localhost'
            )
        );
        $config = new Config($testValues);

        $this->assertTrue(isset($config->database));
        $this->assertTrue(isset($config->database->host));
        $this->assertFalse(isset($config->database->fake));
        $this->assertFalse(isset($config->fake));
        $this->assertFalse(isset($config->fake->database));
    }

    /**
     * Make sure we cannot write to a property once constructed
     *
     * @expectedException \Netric\Config\Exception\ViolatedReadOnlyException
     */
    public function testVerifyReadOnly()
    {
        $config = new Config(array("database"=>"test"));
        // This is not allowed
        $config->database = "my customvalue;";
    }

    /**
     * Make sure we cannot write to an unset property
     *
     * @expectedException \Netric\Config\Exception\ViolatedReadOnlyException
     */
    public function testVerifyReadOnlyWhenNotSet()
    {
        $config = new Config(array("database"=>"test"));
        // This should throw an exception
        $config->notset = "my customvalue;";
    }

    /**
     * Make sure we can't set a property to an object
     *
     * @expectedException \Netric\Config\Exception\RuntimeException
     */
    public function testNotAllowObjects()
    {
        $genericObject = new \stdClass();
        // This should throw an exception
        $config = new Config(array("database"=>$genericObject));
    }

    /**
     * Test to array
     */
    public function testToArray()
    {
        $testValues = array(
            'database'=> array(
                'db1' => array(
                    'host'=>'localhost'
                )
            )
        );
        $oConfig = new Config($testValues);
        $aConfig = $oConfig->toArray();

        $this->assertTrue(is_array($aConfig));
        $this->assertTrue(is_array($aConfig['database']));
        $this->assertTrue(is_array($aConfig['database']['db1']));
        $this->assertTrue(isset($aConfig['database']['db1']['host']));
    }
}
