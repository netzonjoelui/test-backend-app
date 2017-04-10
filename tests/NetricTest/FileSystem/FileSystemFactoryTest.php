<?php
/**
 * Test the FileSystem service factory
 */
namespace NetricTest\FileSystem;

use Netric;
use PHPUnit_Framework_TestCase;

class FileSystemFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\FileSystem\FileSystem',
            $sm->get('Netric\FileSystem\FileSystem')
        );
    }
}