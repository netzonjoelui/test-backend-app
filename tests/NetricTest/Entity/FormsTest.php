<?php
/**
 * Test the forms factory for getting and setting entity forms for the UI
 *
 * We use the comment type entity since we do not allow the user to customize it
 */
namespace NetricTest\Entity;

use Netric\Entity;
use PHPUnit_Framework_TestCase;

class FormsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tennant account
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Form service
     *
     * @var \Netric\Entity\Form
     */
    private $formService = null;

    /**
     * Administrative user
     *
     * We test for this user since he will never have customized forms
     *
     * @var \Netric\User
     */
    private $user = null;

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $sm = $this->account->getServiceManager();
        $this->formService = $sm->get("Netric/Entity/Forms");
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
    }

    public function testCreateForUser()
    {
        $testXml = "<field name='name' />";
        $defLoader = $this->account->getServiceManager()->get("EntityDefinitionLoader");
        $def = $defLoader->get("comment");

        // Save new small form
        $this->formService->saveForUser($def, $this->user->getId(), "test", $testXml);

        // Get the form for the account and see if it matches what we just saved
        $testSaveXml = $this->formService->getFormUiXml($def, $this->user, "test");
        $this->assertEquals($testXml, $testSaveXml);

        // Cleanup by setting it to null
        $this->formService->saveForUser($def, $this->user->getId(), "test", null);
    }

    public function testCreateForAccount()
    {
        $testXml = "<field name='name' />";
        $defLoader = $this->account->getServiceManager()->get("EntityDefinitionLoader");
        $def = $defLoader->get("comment");

        // Save new small form
        $this->formService->saveForAccount($def, "test", $testXml);

        // Get the form for the account and see if it matches what we just saved
        $testSaveXml = $this->formService->getFormUiXml($def, $this->user, "test");
        $this->assertEquals($testXml, $testSaveXml);

        // Cleanup by setting it to null
        $this->formService->saveForAccount($def, "test", null);
    }
}