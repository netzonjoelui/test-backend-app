<?php
/**
 * Test the RecurrenceIdentityMapper service factory
 */

namespace NetricTest\Entity\Recurrence;

use Netric;
use PHPUnit_Framework_TestCase;

class RecurrenceIdentityMapperFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $im = $sm->get("RecurrenceIdentityMapper"); // is mapped to this name
        $this->assertInstanceOf('Netric\Entity\Recurrence\RecurrenceIdentityMapper', $im);
    }
}