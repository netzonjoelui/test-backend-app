<?php

namespace NetricTest\EntityGroupings;

use Netric;

use PHPUnit_Framework_TestCase;

class LoaderFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\EntityGroupings\Loader',
            $sm->get('EntityGroupings_Loader')
        );

        $this->assertInstanceOf(
            'Netric\EntityGroupings\Loader',
            $sm->get('Netric\EntityGroupings\Loader')
        );
    }
}