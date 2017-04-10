<?php
/**
 * Test the account controller
 */
namespace NetricTest\Controller;

use Netric;
use PHPUnit_Framework_TestCase;

class AccountControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Account used for testing
     *
     * @var \Netric\Account\Account
     */
    protected $account = null;

    /**
     * Controller instance used for testing
     *
     * @var \Netric\Controller\EntityController
     */
    protected $controller = null;

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();

        // Create the controller
        $this->controller = new Netric\Controller\AccountController($this->account);
        $this->controller->testMode = true;
    }

    public function testGetDefinitionForms()
    {

        $ret = $this->controller->getGetAction();

        // Make sure that modules that has xml_navigation
        $this->assertFalse(empty($ret['modules'][0]['navigation']));
    }
}