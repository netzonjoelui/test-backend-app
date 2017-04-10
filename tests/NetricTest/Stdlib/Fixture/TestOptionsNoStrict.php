<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace NetricTest\Stdlib\Fixture;

use Netric\Stdlib\AbstractOptions;

/**
 * Dummy TestOptions used to test Stdlib\Options
 */
class TestOptionsNoStrict extends AbstractOptions
{
    protected $__strictMode__ = false;
    protected $testField;
    public function setTestField($value)
    {
        $this->testField = $value;
    }
    public function getTestField()
    {
        return $this->testField;
    }
}