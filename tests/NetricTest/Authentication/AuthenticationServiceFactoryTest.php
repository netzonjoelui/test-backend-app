<?php
/**
 * Test the authentication service
 */
namespace NetricTest\Authentication;

use Netric;
use PHPUnit_Framework_TestCase;

class AuthenticationServiceFactoryTest extends PHPUnit_Framework_TestCase 
{   
    /**
     * Account used for testing
     *
     * @var \Netric\Account\Account
     */
    protected $account = null;

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
    }

    public function testCreate()
    {
        $serviceManager = $this->account->getServiceManager();
        $authService = $serviceManager->get("/Netric/Authentication/AuthenticationService");
        $this->assertInstanceOf("\\Netric\\Authentication\\AuthenticationService", $authService);
    }
}
