<?php

namespace NetricTest\EntitySync\Commit;

use Netric;

use PHPUnit_Framework_TestCase;

class CommitManagerFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\EntitySync\Commit\CommitManager',
            $sm->get('EntitySyncCommitManager')
        );

        $this->assertInstanceOf(
            'Netric\EntitySync\Commit\CommitManager',
            $sm->get('Netric\EntitySync\Commit\CommitManager')
        );
    }
}