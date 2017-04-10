<?php
/**
 * Makes sure our service factory works
 */

namespace NetricTest\Entity\BrowserView;

use NetricTest;
use Netric;
use PHPUnit_Framework_TestCase;

class BrowserViewServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $bvs = $sm->get('Netric\Entity\BrowserView\BrowserViewService');
        $this->assertInstanceOf('Netric\Entity\BrowserView\BrowserViewService', $bvs);
    }
}