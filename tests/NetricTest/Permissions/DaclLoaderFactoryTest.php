<?php
namespace NetricTest\Permissions;

use Netric;
use PHPUnit_Framework_TestCase;

class DaclLoaderactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Permissions\DaclLoader',
            $sm->get('Netric\Permissions\DaclLoader')
        );
    }
}