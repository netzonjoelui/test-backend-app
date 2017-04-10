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

class SetupControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test automatic pagination
     */
    public function testTest()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $con = new Netric\Controller\SetupController($account->getApplication(), $account);
        $request = $con->getRequest();
        // Queue to run the first script which does not really do anything
        $request->setParam("script", "update/once/004/001/001.php");
        $ret = $con->consoleRunAction();
        // If the return code is 0, then it executed successfully
        $this->assertEquals(0, $ret->getReturnCode());
    }
}
