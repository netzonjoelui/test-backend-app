<?php
/**
 * Netric Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Netric Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace NetricTest\Mail\Header;

use PHPUnit_Framework_TestCase as TestCase;
use Netric\Mail\Header;

class DateTest extends TestCase
{
    public function headerLines()
    {
        return [
            'newline'      => ["Date: xxx yyy\n"],
            'cr-lf'        => ["Date: xxx yyy\r\n"],
            'cr-lf-wsp'    => ["Date: xxx yyy\r\n\r\n"],
            'multiline'    => ["Date: xxx\r\ny\r\nyy"],
        ];
    }

    /**
     * @dataProvider headerLines
     * @group ZF2015-04
     */
    public function testFromStringRaisesExceptionOnCrlfInjectionAttempt($header)
    {
        $this->setExpectedException('Netric\Mail\Header\Exception\InvalidArgumentException');
        Header\Date::fromString($header);
    }

    /**
     * @group ZF2015-04
     */
    public function testPreventsCRLFInjectionViaConstructor()
    {
        $this->setExpectedException('Netric\Mail\Header\Exception\InvalidArgumentException');
        $address = new Header\Date("This\ris\r\na\nCRLF Attack");
    }
}
