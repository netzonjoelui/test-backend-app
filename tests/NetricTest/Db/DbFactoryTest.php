<?php

namespace NetricTest\Db;

use Netric;

use PHPUnit_Framework_TestCase;

class DbFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\Db\PgSql',
            $sm->get('Db')
        );

        $this->assertInstanceOf(
            'Netric\Db\PgSql',
            $sm->get('Netric\Db\Db')
        );
    }
}