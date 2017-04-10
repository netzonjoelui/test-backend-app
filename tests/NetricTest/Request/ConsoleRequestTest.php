<?php
namespace NetricTest\Request;

use Netric\Request\ConsoleRequest;
use PHPUnit_Framework_TestCase;

class ConsoleRequestTest extends PHPUnit_Framework_TestCase
{
    /**
     * Make sure we can parse args into an array of params
     */
    public function testParseArgs()
    {
        $args = array(
            "nonoptionarray",
            "-v",
            "-f", "myfile.txt",
            "--username",  "sky",
            "--password=\"test -pass\""
        );
        $reflectionMethod = new \ReflectionMethod('Netric\Request\ConsoleRequest', 'parseArgs');
        $reflectionMethod->setAccessible(true);
        $ret = $reflectionMethod->invoke(new ConsoleRequest(), $args);

        $expects = array(
            0 => 'nonoptionarray',
            'v' => true,
            'f' => 'myfile.txt',
            'username' => 'sky',
            'password' => '"test -pass"',
        );
        $this->assertEquals($expects, $ret);
    }
}