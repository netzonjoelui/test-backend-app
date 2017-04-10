<?php
/**
 * Netric Framework (http://framework.Netric.com/)
 *
 * @link      http://github.com/Netricframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Netric Technologies USA Inc. (http://www.Netric.com)
 * @license   http://framework.Netric.com/license/new-bsd New BSD License
 */

namespace NetricTest\Mail;

use Netric\Mail\Address;

class AddressTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesNotRequireNameForInstantiation()
    {
        $address = new Address('devteam@netric.com');
        $this->assertEquals('devteam@netric.com', $address->getEmail());
        $this->assertNull($address->getName());
    }

    public function testAcceptsNameViaConstructor()
    {
        $address = new Address('devteam@netric.com', 'Netric DevTeam');
        $this->assertEquals('devteam@netric.com', $address->getEmail());
        $this->assertEquals('Netric DevTeam', $address->getName());
    }

    public function testToStringCreatesStringRepresentation()
    {
        $address = new Address('devteam@netric.com', 'Netric DevTeam');
        $this->assertEquals('Netric DevTeam <devteam@netric.com>', $address->toString());
    }

    /**
     * @dataProvider invalidSenderDataProvider
     * @group ZF2015-04
     * @param string $email
     * @param null|string $name
     */
    public function testSetAddressInvalidAddressObject($email, $name)
    {
        $this->setExpectedException('Netric\Mail\Exception\InvalidArgumentException');
        new Address($email, $name);
    }

    public function invalidSenderDataProvider()
    {
        return [
            // Description => [sender address, sender name],
            'Empty' => ['', null],
            'any ASCII' => ['azAZ09-_', null],
            'any UTF-8' => ['ázÁZ09-_', null],

            // CRLF @group ZF2015-04 cases
            ["foo@bar\n", null],
            ["foo@bar\r", null],
            ["foo@bar\r\n", null],
            ["foo@bar", "\r"],
            ["foo@bar", "\n"],
            ["foo@bar", "\r\n"],
            ["foo@bar", "foo\r\nevilBody"],
            ["foo@bar", "\r\nevilBody"],
        ];
    }
}
