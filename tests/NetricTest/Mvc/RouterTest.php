<?php
/**
 * Test querying ElasticSearch server
 *
 * Most tests are inherited from IndexTestsAbstract.php.
 * Only define index specific tests here and try to avoid name collision with the tests
 * in the parent class. For the most part, the parent class tests all public functions
 * so private functions should be tested below.
 */
namespace NetricTest\Mvc;

use Netric;
use PHPUnit_Framework_TestCase;

class RouterTest extends PHPUnit_Framework_TestCase 
{   
    /**
     * Test automatic pagination
     */
    public function testRun()
    {
        $account = \NetricTest\Bootstrap::getAccount();

        $request = $account->getServiceManager()->get("Netric/Request/Request");
        $request->setParam("controller", "test");
        $request->setParam("function", "test");

        $svr = new Netric\Mvc\Router($account->getApplication());
        $svr->testMode = true;
        $ret = $svr->run($request);
		$this->assertEquals($ret, "test");
    }

    public function testAccessControl()
    {
        $account = \NetricTest\Bootstrap::getAccount();

        // Setup anonymous user which should be blocked
        $origCurrentUser = $account->getUser();
        $loader = $account->getServiceManager()->get("EntityLoader");
        $user = $loader->get("user", \Netric\Entity\ObjType\UserEntity::USER_ANONYMOUS);
        $account->setCurrentUser($user);

        $request = $account->getServiceManager()->get("Netric/Request/Request");
        $request->setParam("controller", "test");
        $request->setParam("function", "test");

        $svr = new Netric\Mvc\Router($account->getApplication());
        $svr->testMode = true;
        $ret = $svr->run($request);
        // Request should fail because test requires an authenticated user
        $this->assertEquals($ret, false);

        // Restore original
        $account->setCurrentUser($origCurrentUser);
    }
}
