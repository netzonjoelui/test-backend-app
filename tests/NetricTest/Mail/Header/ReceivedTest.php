<?php
/**
 * Netric Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Netric Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace NetricTest\Mail\Header;

use Netric\Mail\Header;

/**
 * @group      Netric_Mail
 */
class ReceivedTest extends \PHPUnit_Framework_TestCase
{
    public function testFromStringCreatesValidReceivedHeader()
    {
        $receivedHeader = Header\Received::fromString('Received: xxx');
        $this->assertInstanceOf('Netric\Mail\Header\HeaderInterface', $receivedHeader);
        $this->assertInstanceOf('Netric\Mail\Header\Received', $receivedHeader);
    }

    public function testGetFieldNameReturnsHeaderName()
    {
        $receivedHeader = new Header\Received();
        $this->assertEquals('Received', $receivedHeader->getFieldName());
    }

    public function testReceivedGetFieldValueReturnsProperValue()
    {
        $this->markTestIncomplete('Received needs to be completed');

        $receivedHeader = new Header\Received();
        $this->assertEquals('xxx', $receivedHeader->getFieldValue());
    }

    public function testReceivedToStringReturnsHeaderFormattedString()
    {
        $this->markTestIncomplete('Received needs to be completed');

        $receivedHeader = new Header\Received();

        // @todo set some values, then test output
        $this->assertEmpty('Received: xxx', $receivedHeader->toString());
    }

    /** Implementation specific tests here */

    public function headerLines()
    {
        return [
            'newline'      => ["Received: xx\nx"],
            'cr-lf'        => ["Received: xxx\r\n"],
            'cr-lf-fold'   => ["Received: xxx\r\n\r\n zzz"],
            'cr-lf-x2'     => ["Received: xx\r\n\r\nx"],
            'multiline'    => ["Received: x\r\nx\r\nx"],
        ];
    }

    /**
     * @dataProvider headerLines
     * @group ZF2015-04
     */
    public function testRaisesExceptionViaFromStringOnDetectionOfCrlfInjection($header)
    {
        $this->setExpectedException('Netric\Mail\Header\Exception\InvalidArgumentException');
        $received = Header\Received::fromString($header);
    }

    public function invalidValues()
    {
        return [
            'newline'      => ["xx\nx"],
            'cr-lf'        => ["xxx\r\n"],
            'cr-lf-wsp'    => ["xx\r\n\r\nx"],
            'multiline'    => ["x\r\nx\r\nx"],
        ];
    }

    /**
     * @dataProvider invalidValues
     * @group ZF2015-04
     */
    public function testConstructorRaisesExceptionOnValueWithCRLFInjectionAttempt($value)
    {
        $this->setExpectedException('Netric\Mail\Header\Exception\InvalidArgumentException');
        new Header\Received($value);
    }
}
