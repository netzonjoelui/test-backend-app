<?php

namespace NetricTest\EntityQuery\Index;

use Netric;

use PHPUnit_Framework_TestCase;

class IndexFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\EntityQuery\Index\Pgsql',
            $sm->get('EntityQuery_Index')
        );

        $this->assertInstanceOf(
            'Netric\EntityQuery\Index\Pgsql',
            $sm->get('Netric\EntityQuery\Index\Index')
        );
    }
}