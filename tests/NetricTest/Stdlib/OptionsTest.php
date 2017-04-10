<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace NetricTest\Stdlib;

use ArrayObject;
use Netric\Stdlib\Exception;
use NetricTest\Stdlib\Fixture\TestOptions;
use NetricTest\Stdlib\Fixture\TestOptionsDerived;
use NetricTest\Stdlib\Fixture\TestOptionsNoStrict;
use NetricTest\Stdlib\Fixture\TestOptionsWithoutGetter;

class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructionWithArray()
    {
        $options = new TestOptions(['test_field' => 1]);
        $this->assertEquals(1, $options->test_field);
    }
    public function testConstructionWithTraversable()
    {
        $config = new ArrayObject(['test_field' => 1]);
        $options = new TestOptions($config);
        $this->assertEquals(1, $options->test_field);
    }
    public function testConstructionWithOptions()
    {
        $options = new TestOptions(new TestOptions(['test_field' => 1]));
        $this->assertEquals(1, $options->test_field);
    }
    public function testInvalidFieldThrowsException()
    {
        $this->setExpectedException('BadMethodCallException');
        new TestOptions(['foo' => 'bar']);
    }
    public function testNonStrictOptionsDoesNotThrowException()
    {
        $this->assertInstanceOf(
            'NetricTest\Stdlib\Fixture\TestOptionsNoStrict',
            new TestOptionsNoStrict(['foo' => 'bar'])
        );
    }
    public function testConstructionWithNull()
    {
        $this->assertInstanceOf('NetricTest\Stdlib\Fixture\TestOptions', new TestOptions(null));
    }
    public function testUnsetting()
    {
        $options = new TestOptions(['test_field' => 1]);
        $this->assertEquals(true, isset($options->test_field));
        unset($options->testField);
        $this->assertEquals(false, isset($options->test_field));
    }
    public function testUnsetThrowsInvalidArgumentException()
    {
        $options = new TestOptions;
        $this->setExpectedException('InvalidArgumentException');
        unset($options->foobarField);
    }
    public function testGetThrowsBadMethodCallException()
    {
        $options = new TestOptions();
        $this->setExpectedException('BadMethodCallException');
        $options->fieldFoobar;
    }
    public function testSetFromArrayAcceptsArray()
    {
        $array = ['test_field' => 3];
        $options = new TestOptions();
        $this->assertSame($options, $options->setFromArray($array));
        $this->assertEquals(3, $options->test_field);
    }
    public function testSetFromArrayThrowsInvalidArgumentException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $options = new TestOptions;
        $options->setFromArray('asd');
    }
    public function testParentPublicProperty()
    {
        $options = new TestOptionsDerived(['parent_public' => 1]);
        $this->assertEquals(1, $options->parent_public);
    }
    public function testParentProtectedProperty()
    {
        $options = new TestOptionsDerived(['parent_protected' => 1]);
        $this->assertEquals(1, $options->parent_protected);
    }
    public function testParentPrivateProperty()
    {
        $this->setExpectedException(
            'Netric\Stdlib\Exception\BadMethodCallException',
            'The option "parent_private" does not have a callable "setParentPrivate" ("setparentprivate")'
            . ' setter method which must be defined'
        );
        new TestOptionsDerived(['parent_private' => 1]);
    }
    public function testDerivedPublicProperty()
    {
        $options = new TestOptionsDerived(['derived_public' => 1]);
        $this->assertEquals(1, $options->derived_public);
    }
    public function testDerivedProtectedProperty()
    {
        $options = new TestOptionsDerived(['derived_protected' => 1]);
        $this->assertEquals(1, $options->derived_protected);
    }
    public function testDerivedPrivateProperty()
    {
        $this->setExpectedException(
            'Netric\Stdlib\Exception\BadMethodCallException',
            'The option "derived_private" does not have a callable "setDerivedPrivate" ("setderivedprivate")'
            .' setter method which must be defined'
        );
        new TestOptionsDerived(['derived_private' => 1]);
    }
    public function testExceptionMessageContainsActualUsedSetter()
    {
        $this->setExpectedException(
            'BadMethodCallException',
            'The option "foo bar" does not have a callable "setFooBar" ("setfoo bar")'
            . ' setter method which must be defined'
        );
        new TestOptions([
            'foo bar' => 'baz',
        ]);
    }
    /**
     * @group 7287
     */
    public function testIssetReturnsFalseWhenMatchingGetterDoesNotExist()
    {
        $options = new TestOptionsWithoutGetter([
            'foo' => 'bar',
        ]);
        $this->assertFalse(isset($options->foo));
    }
    /**
     * @group 7287
     */
    public function testIssetDoesNotThrowExceptionWhenMatchingGetterDoesNotExist()
    {
        $options   = new TestOptionsWithoutGetter();
        try {
            isset($options->foo);
        } catch (Exception\BadMethodCallException $exception) {
            $this->fail("Unexpected BadMethodCallException raised");
        }
    }
    /**
     * @group 7287
     */
    public function testIssetReturnsTrueWithValidDataWhenMatchingGetterDoesNotExist()
    {
        $options = new TestOptions([
            'test_field' => 1,
        ]);
        $this->assertTrue(isset($options->testField));
    }
}