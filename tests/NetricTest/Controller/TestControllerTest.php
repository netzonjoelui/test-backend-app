<?php
/**
 * Test querying ElasticSearch server
 *
 * Most tests are inherited from IndexTestsAbstract.php.
 * Only define index specific tests here and try to avoid name collision with the tests
 * in the parent class. For the most part, the parent class tests all public functions
 * so private functions should be tested below.
 */
namespace NetricTest\Controller;

use Netric;
use PHPUnit_Framework_TestCase;

class TestControllerTest extends PHPUnit_Framework_TestCase 
{   
    /**
     * Test automatic pagination
     */
    public function testTest()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $con = new Netric\Controller\TestController($account->getApplication(), $account);
        $con->testMode = true;
        $ret = $con->getTestAction();
        $this->assertEquals("test", $ret);
    }
}