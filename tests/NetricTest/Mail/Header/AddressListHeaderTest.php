<?php
/**
 * Netric Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Netric Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace NetricTest\Mail\Header;

use Netric\Mail\Address;
use Netric\Mail\AddressList;
use Netric\Mail\Header\Bcc;
use Netric\Mail\Header\Cc;
use Netric\Mail\Header\From;
use Netric\Mail\Header\ReplyTo;
use Netric\Mail\Header\To;

/**
 * @group      Netric_Mail
 */
class AddressListHeaderTest extends \PHPUnit_Framework_TestCase
{
    public static function getHeaderInstances()
    {
        return [
            [new Bcc(), 'Bcc'],
            [new Cc(), 'Cc'],
            [new From(), 'From'],
            [new ReplyTo(), 'Reply-To'],
            [new To(), 'To'],
        ];
    }

    /**
     * @dataProvider getHeaderInstances
     */
    public function testConcreteHeadersExtendAbstractAddressListHeader($header)
    {
        $this->assertInstanceOf('Netric\Mail\Header\AbstractAddressList', $header);
    }

    /**
     * @dataProvider getHeaderInstances
     */
    public function testConcreteHeaderFieldNamesAreDiscrete($header, $type)
    {
        $this->assertEquals($type, $header->getFieldName());
    }

    /**
     * @dataProvider getHeaderInstances
     */
    public function testConcreteHeadersComposeAddressLists($header)
    {
        $list = $header->getAddressList();
        $this->assertInstanceOf('Netric\Mail\AddressList', $list);
    }

    public function testFieldValueIsEmptyByDefault()
    {
        $header = new To();
        $this->assertEquals('', $header->getFieldValue());
    }

    public function testFieldValueIsCreatedFromAddressList()
    {
        $header = new To();
        $list   = $header->getAddressList();
        $this->populateAddressList($list);
        $expected = $this->getExpectedFieldValue();
        $this->assertEquals($expected, $header->getFieldValue());
    }

    public function populateAddressList(AddressList $list)
    {
        $address = new Address('devteam@netric.com', 'Netric DevTeam');
        $list->add($address);
        $list->add('contributors@lists.netric.com');
        $list->add('fw-announce@lists.netric.com', 'Netric Announce List');
        $list->add('first@last.zend.com', 'Last, First');
    }

    public function getExpectedFieldValue()
    {
        return "Netric DevTeam <devteam@netric.com>,\r\n contributors@lists.netric.com,\r\n Netric Announce List <fw-announce@lists.netric.com>,\r\n \"Last, First\" <first@last.zend.com>";
    }

    /**
     * @dataProvider getHeaderInstances
     */
    public function testStringRepresentationIncludesHeaderAndFieldValue($header, $type)
    {
        $this->populateAddressList($header->getAddressList());
        $expected = sprintf('%s: %s', $type, $this->getExpectedFieldValue());
        $this->assertEquals($expected, $header->toString());
    }

    public function getStringHeaders()
    {
        $value = $this->getExpectedFieldValue();
        return [
            'cc'       => ['Cc: ' . $value, 'Netric\Mail\Header\Cc'],
            'bcc'      => ['Bcc: ' . $value, 'Netric\Mail\Header\Bcc'],
            'from'     => ['From: ' . $value, 'Netric\Mail\Header\From'],
            'reply-to' => ['Reply-To: ' . $value, 'Netric\Mail\Header\ReplyTo'],
            'to'       => ['To: ' . $value, 'Netric\Mail\Header\To'],
        ];
    }

    /**
     * @dataProvider getStringHeaders
     */
    public function testDeserializationFromString($headerLine, $class)
    {
        $callback = sprintf('%s::fromString', $class);
        $header   = call_user_func($callback, $headerLine);
        $this->assertInstanceOf($class, $header);
        $list = $header->getAddressList();
        $this->assertEquals(4, count($list));
        $this->assertTrue($list->has('devteam@netric.com'));
        $this->assertTrue($list->has('contributors@lists.netric.com'));
        $this->assertTrue($list->has('fw-announce@lists.netric.com'));
        $this->assertTrue($list->has('first@last.zend.com'));
        $address = $list->get('devteam@netric.com');
        $this->assertEquals('Netric DevTeam', $address->getName());
        $address = $list->get('contributors@lists.netric.com');
        $this->assertNull($address->getName());
        $address = $list->get('fw-announce@lists.netric.com');
        $this->assertEquals('Netric Announce List', $address->getName());
        $address = $list->get('first@last.zend.com');
        $this->assertEquals('Last, First', $address->getName());
    }

    public function getStringHeadersWithNoWhitespaceSeparator()
    {
        $value = $this->getExpectedFieldValue();
        return [
            'cc'       => ['Cc:' . $value, 'Netric\Mail\Header\Cc'],
            'bcc'      => ['Bcc:' . $value, 'Netric\Mail\Header\Bcc'],
            'from'     => ['From:' . $value, 'Netric\Mail\Header\From'],
            'reply-to' => ['Reply-To:' . $value, 'Netric\Mail\Header\ReplyTo'],
            'to'       => ['To:' . $value, 'Netric\Mail\Header\To'],
        ];
    }

    /**
     * @dataProvider getHeadersWithComments
     */
    public function testDeserializationFromStringWithComments($value)
    {
        $header = From::fromString($value);
        $list = $header->getAddressList();
        $this->assertEquals(1, count($list));
        $this->assertTrue($list->has('user@example.com'));
    }

    public function getHeadersWithComments()
    {
        return [
            ['From: user@example.com (Comment)'],
            ['From: user@example.com (Comm\\)ent)'],
            ['From: (Comment\\\\)user@example.com(Another)'],
        ];
    }

    /**
     * @group 3789
     * @dataProvider getStringHeadersWithNoWhitespaceSeparator
     */
    public function testAllowsNoWhitespaceBetweenHeaderAndValue($headerLine, $class)
    {
        $callback = sprintf('%s::fromString', $class);
        $header   = call_user_func($callback, $headerLine);
        $this->assertInstanceOf($class, $header);
        $list = $header->getAddressList();
        $this->assertEquals(4, count($list));
        $this->assertTrue($list->has('devteam@netric.com'));
        $this->assertTrue($list->has('contributors@lists.netric.com'));
        $this->assertTrue($list->has('fw-announce@lists.netric.com'));
        $this->assertTrue($list->has('first@last.zend.com'));
        $address = $list->get('devteam@netric.com');
        $this->assertEquals('Netric DevTeam', $address->getName());
        $address = $list->get('contributors@lists.netric.com');
        $this->assertNull($address->getName());
        $address = $list->get('fw-announce@lists.netric.com');
        $this->assertEquals('Netric Announce List', $address->getName());
        $address = $list->get('first@last.zend.com');
        $this->assertEquals('Last, First', $address->getName());
    }

    /**
     * @dataProvider getAddressListsWithGroup
     */
    public function testAddressListWithGroup($input, $count, $sample)
    {
        $header = To::fromString($input);
        $list = $header->getAddressList();
        $this->assertEquals($count, count($list));
        if ($count > 0) {
            $this->assertTrue($list->has($sample));
        }
    }

    public function getAddressListsWithGroup()
    {
        return [
            ['To: undisclosed-recipients:;', 0, null],
            ['To: friends: john@example.com; enemies: john@example.net, bart@example.net;', 3, 'john@example.net'],
        ];
    }
}
