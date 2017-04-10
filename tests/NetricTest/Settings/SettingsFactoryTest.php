<?php
/**
 * Test the Settings service factory
 */
namespace NetricTest\Settings;

use Netric\Settings;
use PHPUnit_Framework_TestCase;

class SettingsFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Settings\Settings',
            $sm->get('Netric\Settings\Settings')
        );
    }
}