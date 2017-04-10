<?php
/**
 * Test the EntitySeriesWriter service factory
 */

namespace NetricTest\Entity\Recurrence;

use Netric;
use PHPUnit_Framework_TestCase;

class RecurrenceSeriesManagerFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $seriesWriter = $sm->get('Netric/Entity/Recurrence/RecurrenceSeriesManager');
        $this->assertInstanceOf('Netric\Entity\Recurrence\RecurrenceSeriesManager', $seriesWriter);
    }
}