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
class TestOptionsWithoutGetter extends AbstractOptions
{
    protected $foo;
    public function setFoo($value)
    {
        $this->foo = $value;
    }
}