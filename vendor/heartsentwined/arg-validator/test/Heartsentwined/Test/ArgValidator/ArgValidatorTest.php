<?php
namespace Heartsentwined\Test\ArgValidator;

use Heartsentwined\ArgValidator\ArgValidator;

class ArgValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidExceptionOn()
    {
        $this->assertTrue(
            ArgValidator::assert(1, 'int', true));
    }

    public function testValidExceptionOff()
    {
        $this->assertTrue(
            ArgValidator::assert(1, 'int', false));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidExceptionOn()
    {
        ArgValidator::assert('string', 'int', true);
    }

    public function testInvalidExceptionOff()
    {
        $this->assertFalse(
            ArgValidator::assert('string', 'int', false));
    }

    public function testChecksForm()
    {
        $this->assertTrue(
            ArgValidator::assert(1, 'int', false));
        $this->assertTrue(
            ArgValidator::assert(1, array('int'), false));
    }

    public function testArrayOf()
    {
        $this->assertTrue(ArgValidator::assert(
            array(1), array('arrayOf', 'int'), false));
        $this->assertTrue(ArgValidator::assert(
            array(), array('arrayOf', 'int'), false));

        $this->assertFalse(ArgValidator::assert(
            1, array('arrayOf', 'int'), false));
        $this->assertFalse(ArgValidator::assert(
            array(1, '1'), array('arrayOf', 'int'), false));
    }

    public function testMinMaxSyntax()
    {
        $this->assertTrue(ArgValidator::assert(
            1, array('int', 'min' => 1), false));
        $this->assertTrue(ArgValidator::assert(
            1, array('int', 'max' => 1), false));
        $this->assertTrue(ArgValidator::assert(
            1, array('int', 'min' => 1, 'max' => 1), false));
    }

    public function testTrimChecks()
    {
        $checks = array('int', 'arrayOf', 'min' => 0, 'max' => 1);
        ArgValidator::trimChecks($checks);
        $this->assertSame(array('int'), $checks);
    }

    public function testCheckNumRange()
    {
        $this->assertTrue(
            ArgValidator::checkNumRange(1, 0, null));
        $this->assertTrue(
            ArgValidator::checkNumRange(1, 1, null));
        $this->assertFalse(
            ArgValidator::checkNumRange(1, 2, null));

        $this->assertTrue(
            ArgValidator::checkNumRange(1, null, 2));
        $this->assertTrue(
            ArgValidator::checkNumRange(1, null, 1));
        $this->assertFalse(
            ArgValidator::checkNumRange(1, null, 0));

        $this->assertTrue(
            ArgValidator::checkNumRange(1, null, null));

        $this->assertFalse(
            ArgValidator::checkNumRange(0, 1, 2));
        $this->assertTrue(
            ArgValidator::checkNumRange(1, 1, 2));
        $this->assertTrue(
            ArgValidator::checkNumRange(2, 1, 2));
        $this->assertFalse(
            ArgValidator::checkNumRange(3, 1, 2));
    }

    public function testInt()
    {
        $this->assertTrue(
            ArgValidator::assert(1, 'int', false));

        $this->assertFalse(
            ArgValidator::assert(1.0, 'int', false));
        $this->assertFalse(
            ArgValidator::assert(1.1, 'int', false));
        $this->assertFalse(
            ArgValidator::assert('string', 'int', false));
        $this->assertFalse(
            ArgValidator::assert('', 'int', false));
        $this->assertFalse(
            ArgValidator::assert(array(), 'int', false));
        $this->assertFalse(
            ArgValidator::assert(true, 'int', false));
        $this->assertFalse(
            ArgValidator::assert(false, 'int', false));
        $this->assertFalse(
            ArgValidator::assert(null, 'int', false));
        $this->assertFalse(
            ArgValidator::assert(function(){}, 'int', false));
        $this->assertFalse(
            ArgValidator::assert(new \StdClass, 'int', false));
    }

    public function testFloat()
    {
        $this->assertTrue(
            ArgValidator::assert(1.0, 'float', false));
        $this->assertTrue(
            ArgValidator::assert(1.1, 'float', false));

        $this->assertFalse(
            ArgValidator::assert(1, 'float', false));
        $this->assertFalse(
            ArgValidator::assert('string', 'float', false));
        $this->assertFalse(
            ArgValidator::assert('', 'float', false));
        $this->assertFalse(
            ArgValidator::assert(array(), 'float', false));
        $this->assertFalse(
            ArgValidator::assert(true, 'float', false));
        $this->assertFalse(
            ArgValidator::assert(false, 'float', false));
        $this->assertFalse(
            ArgValidator::assert(null, 'float', false));
        $this->assertFalse(
            ArgValidator::assert(function(){}, 'float', false));
        $this->assertFalse(
            ArgValidator::assert(new \StdClass, 'float', false));
    }

    public function testNumeric()
    {
        $this->assertTrue(
            ArgValidator::assert(1, 'numeric', false));
        $this->assertTrue(
            ArgValidator::assert(1.0, 'numeric', false));
        $this->assertTrue(
            ArgValidator::assert(1.1, 'numeric', false));
        $this->assertTrue(
            ArgValidator::assert('+1', 'numeric', false));
        $this->assertTrue(
            ArgValidator::assert('-1', 'numeric', false));
        $this->assertTrue(
            ArgValidator::assert('1e4', 'numeric', false));
        $this->assertTrue(
            ArgValidator::assert('1.0', 'numeric', false));
        $this->assertTrue(
            ArgValidator::assert('1.1', 'numeric', false));

        $this->assertFalse(
            ArgValidator::assert('string', 'numeric', false));
        $this->assertFalse(
            ArgValidator::assert('', 'numeric', false));
        $this->assertFalse(
            ArgValidator::assert(array(), 'numeric', false));
        $this->assertFalse(
            ArgValidator::assert(true, 'numeric', false));
        $this->assertFalse(
            ArgValidator::assert(false, 'numeric', false));
        $this->assertFalse(
            ArgValidator::assert(null, 'numeric', false));
        $this->assertFalse(
            ArgValidator::assert(function(){}, 'numeric', false));
        $this->assertFalse(
            ArgValidator::assert(new \StdClass, 'numeric', false));
    }

    public function testCheckStrlen()
    {
        $this->assertTrue(
            ArgValidator::checkStrlen('a', 0, null));
        $this->assertTrue(
            ArgValidator::checkStrlen('a', 1, null));
        $this->assertFalse(
            ArgValidator::checkStrlen('a', 2, null));

        $this->assertTrue(
            ArgValidator::checkStrlen('a', null, 2));
        $this->assertTrue(
            ArgValidator::checkStrlen('a', null, 1));
        $this->assertFalse(
            ArgValidator::checkStrlen('a', null, 0));

        $this->assertTrue(
            ArgValidator::checkStrlen('a', null, null));

        $this->assertFalse(
            ArgValidator::checkStrlen('', 1, 2));
        $this->assertTrue(
            ArgValidator::checkStrlen('a', 1, 2));
        $this->assertTrue(
            ArgValidator::checkStrlen('aa', 1, 2));
        $this->assertFalse(
            ArgValidator::checkStrlen('aaa', 1, 2));
    }

    public function testString()
    {
        $this->assertTrue(
            ArgValidator::assert('string', 'string', false));
        $this->assertTrue(
            ArgValidator::assert('', 'string', false));

        $this->assertFalse(
            ArgValidator::assert(1, 'string', false));
        $this->assertFalse(
            ArgValidator::assert(1.0, 'string', false));
        $this->assertFalse(
            ArgValidator::assert(array(), 'string', false));
        $this->assertFalse(
            ArgValidator::assert(true, 'string', false));
        $this->assertFalse(
            ArgValidator::assert(false, 'string', false));
        $this->assertFalse(
            ArgValidator::assert(null, 'string', false));
        $this->assertFalse(
            ArgValidator::assert(function(){}, 'string', false));
        $this->assertFalse(
            ArgValidator::assert(new \StdClass, 'string', false));
    }

    public function testCheckCount()
    {
        $this->assertTrue(
            ArgValidator::checkCount(array(1), 0, null));
        $this->assertTrue(
            ArgValidator::checkCount(array(1), 1, null));
        $this->assertFalse(
            ArgValidator::checkCount(array(1), 2, null));

        $this->assertTrue(
            ArgValidator::checkCount(array(1), null, 2));
        $this->assertTrue(
            ArgValidator::checkCount(array(1), null, 1));
        $this->assertFalse(
            ArgValidator::checkCount(array(1), null, 0));

        $this->assertTrue(
            ArgValidator::checkCount(array(1), null, null));

        $this->assertFalse(
            ArgValidator::checkCount(array(), 1, 2));
        $this->assertTrue(
            ArgValidator::checkCount(array(1), 1, 2));
        $this->assertTrue(
            ArgValidator::checkCount(array(1, 2), 1, 2));
        $this->assertFalse(
            ArgValidator::checkCount(array(1, 2, 3), 1, 2));
    }

    public function testArray()
    {
        $this->assertTrue(
            ArgValidator::assert(array(), 'array', false));

        $this->assertFalse(
            ArgValidator::assert(1, 'array', false));
        $this->assertFalse(
            ArgValidator::assert(1.0, 'array', false));
        $this->assertFalse(
            ArgValidator::assert('string', 'array', false));
        $this->assertFalse(
            ArgValidator::assert('', 'array', false));
        $this->assertFalse(
            ArgValidator::assert(true, 'array', false));
        $this->assertFalse(
            ArgValidator::assert(false, 'array', false));
        $this->assertFalse(
            ArgValidator::assert(null, 'array', false));
        $this->assertFalse(
            ArgValidator::assert(function(){}, 'array', false));
        $this->assertFalse(
            ArgValidator::assert(new \StdClass, 'array', false));
    }

    public function testNull()
    {
        $this->assertTrue(
            ArgValidator::assert(null, 'null', false));

        $this->assertFalse(
            ArgValidator::assert(1, 'null', false));
        $this->assertFalse(
            ArgValidator::assert(1.0, 'null', false));
        $this->assertFalse(
            ArgValidator::assert('string', 'null', false));
        $this->assertFalse(
            ArgValidator::assert('', 'null', false));
        $this->assertFalse(
            ArgValidator::assert(array(), 'null', false));
        $this->assertFalse(
            ArgValidator::assert(true, 'null', false));
        $this->assertFalse(
            ArgValidator::assert(false, 'null', false));
        $this->assertFalse(
            ArgValidator::assert(function(){}, 'null', false));
        $this->assertFalse(
            ArgValidator::assert(new \StdClass, 'null', false));
    }

    public function testCallable()
    {
        $this->assertTrue(
            ArgValidator::assert(function(){}, 'callable', false));
        $this->assertTrue(
            ArgValidator::assert('strpos', 'callable', false));
        $this->assertTrue(
            ArgValidator::assert(array($this, 'assertTrue'), 'callable', false));

        $this->assertFalse(
            ArgValidator::assert(1, 'callable', false));
        $this->assertFalse(
            ArgValidator::assert(1.0, 'callable', false));
        $this->assertFalse(
            ArgValidator::assert('string', 'callable', false));
        $this->assertFalse(
            ArgValidator::assert('', 'callable', false));
        $this->assertFalse(
            ArgValidator::assert(array(), 'callable', false));
        $this->assertFalse(
            ArgValidator::assert(true, 'callable', false));
        $this->assertFalse(
            ArgValidator::assert(false, 'callable', false));
        $this->assertFalse(
            ArgValidator::assert(null, 'callable', false));
        $this->assertFalse(
            ArgValidator::assert(new \StdClass, 'callable', false));
    }

    public function testNotEmpty()
    {
        $this->assertTrue(
            ArgValidator::assert(1, 'notEmpty', false));
        $this->assertTrue(
            ArgValidator::assert(1.0, 'notEmpty', false));
        $this->assertTrue(
            ArgValidator::assert('string', 'notEmpty', false));
        $this->assertTrue(
            ArgValidator::assert(array(1), 'notEmpty', false));
        $this->assertTrue(
            ArgValidator::assert(true, 'notEmpty', false));
        $this->assertTrue(
            ArgValidator::assert(function(){}, 'notEmpty', false));
        $this->assertTrue(
            ArgValidator::assert(new \StdClass, 'notEmpty', false));

        $this->assertFalse(
            ArgValidator::assert(0, 'notEmpty', false));
        $this->assertFalse(
            ArgValidator::assert(0.0, 'notEmpty', false));
        $this->assertFalse(
            ArgValidator::assert('', 'notEmpty', false));
        $this->assertFalse(
            ArgValidator::assert(array(), 'notEmpty', false));
        $this->assertFalse(
            ArgValidator::assert(false, 'notEmpty', false));
        $this->assertFalse(
            ArgValidator::assert(null, 'notEmpty', false));
    }

    public function testInArray()
    {
        $this->assertTrue(
            ArgValidator::assert('foo', array(array('foo', 'bar')), false));
        $this->assertTrue(
            ArgValidator::assert('bar', array(array('foo', 'bar')), false));

        $this->assertFalse(
            ArgValidator::assert('baz', array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert(array(), array(array('foo', 'bar')), false));

        $this->assertFalse(
            ArgValidator::assert(1, array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert(1.0, array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert('string', array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert('', array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert(true, array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert(false, array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert(null, array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert(function(){}, array(array('foo', 'bar')), false));
        $this->assertFalse(
            ArgValidator::assert(new \StdClass, array(array('foo', 'bar')), false));
    }

    public function testInstanceOf()
    {
        $this->assertTrue(
            ArgValidator::assert(new \StdClass, '\StdClass', false));

        $this->assertFalse(
            ArgValidator::assert(1, '\StdClass', false));
        $this->assertFalse(
            ArgValidator::assert(1.0, '\StdClass', false));
        $this->assertFalse(
            ArgValidator::assert('string', '\StdClass', false));
        $this->assertFalse(
            ArgValidator::assert('', '\StdClass', false));
        $this->assertFalse(
            ArgValidator::assert(array(), '\StdClass', false));
        $this->assertFalse(
            ArgValidator::assert(true, '\StdClass', false));
        $this->assertFalse(
            ArgValidator::assert(false, '\StdClass', false));
        $this->assertFalse(
            ArgValidator::assert(null, '\StdClass', false));
        $this->assertFalse(
            ArgValidator::assert(function(){}, '\StdClass', false));
    }

    public function testArrayAssert()
    {
        $this->assertTrue(
            ArgValidator::arrayAssert(
                array('foo' => 'bar'),
                array('foo' => 'string'),
                false
            ));
        $this->assertFalse(
            ArgValidator::arrayAssert(
                array('foo' => 'bar'),
                array('foo' => 'int'),
                false
            ));
    }

    public function testArrayAssertNotSet()
    {
        $this->assertTrue(
            ArgValidator::arrayAssert(
                array('foo' => 'bar'),
                array('foo' => array('string', 'notSet')),
                false
            ));
        $this->assertTrue(
            ArgValidator::arrayAssert(
                array(),
                array('foo' => array('string', 'notSet')),
                false
            ));
        $this->assertFalse(
            ArgValidator::arrayAssert(
                array(),
                array('foo' => 'string'),
                false
            ));
    }

    public function testAssertClass()
    {
        $this->assertTrue(
            ArgValidator::assertClass('\StdClass', false));
        $this->assertFalse(
            ArgValidator::assertClass('\Foo', false));
    }

    public function testAssertClassConstant()
    {
        require_once __DIR__ . '/ClassAsset.php';

        $this->assertTrue(ArgValidator::assertClassConstant(
            'ClassConst', array('FOO'), false));
        $this->assertTrue(ArgValidator::assertClassConstant(
            'ClassConst', array(), false));
        $this->assertTrue(ArgValidator::assertClassConstant(
            'ClassNoConst', array(), false));

        $this->assertFalse(ArgValidator::assertClassConstant(
            'ClassNoConst', array('BAR'), false));
        $this->assertFalse(ArgValidator::assertClassConstant(
            'ClassConst', array('BAR'), false));
        $this->assertFalse(ArgValidator::assertClassConstant(
            'Foo', array(), false));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowIssetException()
    {
        ArgValidator::throwIssetException('foo');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAssembleCaller()
    {
        ArgValidator::assembleCaller(array());
    }
}
