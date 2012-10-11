# Heartsentwined\ArgValidator

[![Build Status](https://secure.travis-ci.org/heartsentwined/arg-validator.png)](http://travis-ci.org/heartsentwined/arg-validator)

Solving several function argument validation/typehinting issues in PHP:

Primitive typehints:

```php
function foo(string $foo) {}
```

Mixed / advanced typehints:

```php
function foo(array|string|null $foo, int/*between 3-10*/ $bar) {}
```

"array of X" typehints:

```php
function foo(Array_of_strings $foo) {}
```

Validating / typehinting "pseudo named function paramters" through array members:

```php
/**
 * @param array $params
 *  - 'foo' => string
 *  - 'bar' => int
 */
function foo(array $params) {}
```

Declaring required constants in classes (interface-style function):

```php
class Foo {
    /* const BAR required */
    /* const BAZ required */
}
```

# Installation

[Composer](http://getcomposer.org/):

```json
{
    "require": {
        "heartsentwined/arg-validator": "1.*"
    }
}
```

# Usage

## Simple argument validation

Validates that `$foo` is an integer, throwing an `InvalidArgumentException` if validation failed.

```php
use Heartsentwined\ArgValidator\ArgValidator;

function foo($foo)
{
    ArgValidator::assert($foo, 'int');
    // throw InvalidArgumentException if $foo is not int
    // do something
}
```

Validates that `$foo` is an integer, return boolean.

```php
use Heartsentwined\ArgValidator\ArgValidator;

function foo($foo)
{
    $result = ArgValidator::assert($foo, 'int', false);
    // $result = false if invalid
    // do something
}
```

Full function signature:

```php
public static function assert($arg, $checks, $exception = true)
```

Valid argument types are specified through the `$checks` parameter. A string, or an array of the following accepted. `$arg` will be considered valid if it satisfies *any one* of the specified checks.

- Flags
    - `arrayof`: will check for an array of remaining specified types, instead of plain types, e.g. `array('arrayOf', 'string', 'int')` = an array of string, or an array of int. *Note: Empty array will be considered valid*
    - `min`, `max`
        - combine with `int`, `float`: min and max value
        - combine with `string`: min and max length
        - combine with `array`: min and max count
- Types
    - `int`
    - `float`
    - `numeric`
    - `string`
    - `array`
    - `null`
    - `callable`
    - `notEmpty`: equivalent to `!empty()`
    - (an array of scalars for in_array check), e.g. `array('foo', 'bar')` will check for `in_array($arg, array('foo', 'bar'))`. *Note: `ArgValidator::assert($arg, array('foo', 'bar'))` will be interpreted as instanceof checks against `foo` and `bar`. To specify an in_array check, wrap it in another array: `ArgValidator::assert($arg, array(array('foo', 'bar')))`.*
    - (a string): assumed to be an instanceof check, should be a fully-qualified name of Class/Interface

## "Named parameters" validation

```php
use Heartsentwined\ArgValidator\ArgValidator;

function foo(array $params)
{
    ArgValidator::arrayAssert($params, array(
        'foo' => 'float',
        'bar' => array('string', 'notSet'),
        'baz' => array('int', 'string', 'min' => 2),
    ));
    // $params['foo'] should be a float
    // $params['bar'] should be a string, if set, or not set at all
    // $params['baz'] can be either an int (>=2), or a string (strlen >= 2)
}
```

Full function signature:

```php
public static function arrayAssert(array $arg, array $checks, $exception = true)
```

Valid argument types are same as above, except with the addition of a `notSet` type.

## Check class constants

```php
namespace Foo;

use Heartsentwined\ArgValidator\ArgValidator;

class FooClass {
    const FOO = 'foo';
    const BAR = 2;
}

ArgValidator::assertClassConstant('Foo\FooClass', array('FOO', 'BAR'));
// \Foo\FooClass must have the constants 'FOO' and 'BAR' set
```

Full function signature:

```php
public static function assertClassConstant($className, $constants, $exception = true)
```

`$className` should be a fully-qualified class name; `$constants` should be an array of strings, each member being the constant name.

`ArgValidator::assertClassConstant()` will check for:

1. the class `$className` exists
2. the class has declared the required constants specified in `$constants`

## Misc

To centralize exception handling on argument validations, ArgValidator also provides two helper functions:

Assert that a class exists, throw `InvalidArgumentException` otherwise:

```php
public static function assertClass($className, $exception = true)
```

Throw an `InvalidArgumentException` about the given variable name is not set:

```php
public static function throwIssetException($argName)
```

*Note: this function doesn't actually perform the `isset` check. I can't find a way to abstract the `isset` check away, without the variable being set in the first place (in order to act as argument to call this function with).*
