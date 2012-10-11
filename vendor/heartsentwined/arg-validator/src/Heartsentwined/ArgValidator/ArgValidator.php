<?php
namespace Heartsentwined\ArgValidator;

use Heartsentwined\ArgValidator\Exception;

class ArgValidator
{
    /**
     * assert that a given argument is of some type
     *
     * @param mixed        $arg  argument to validate
     * @param string|array $type argument valid types,
     *  one or more of the following:
     *  [FLAGS]
     *  - 'arrayOf'
     *      will check for an array of remaining specified types
     *      instead of plain types
     *      * empty array will be considered valid
     *  - 'min'
     *  - 'max'
     *      combine with 'int', 'float': min and max value
     *      combine with 'string': min and max length
     *      combine with 'array': min and max count
     *  [TYPES]
     *  - 'int'
     *  - 'float'
     *  - 'numeric'
     *  - 'string'
     *  - 'array'
     *  - 'null'
     *  - 'callable'
     *  - 'notEmpty'
     *  - (an array of scalars for in_array check)
     *    e.g. array('foo', 'bar') will check for
     *         in_array($arg, array('foo', 'bar'))
     *    [NOTE] self::assert($arg, array('foo', 'bar'))
     *      will be interpreted as instanceof checks against 'foo' and 'bar';
     *      wrap it in an array:
     *      write self::assert($arg, array(array('foo', 'bar'))) instead
     *  - otherwise, assumed to be an instanceof check:
     *    should be a fully-qualified name of Class/Interface
     * @param bool $exception = true
     *  if false,
     *  will return false instead of throw exception on failed assertion
     * @throws Exception\InvalidArgumentException
     * @return bool
     *  normally, return true on successful validation;
     *  throw error on failure,
     *  but see note on $exception
     */
    public static function assert($arg, $checks, $exception = true)
    {
        $checksValid = false;
        foreach ((array) $checks as $check) {
            if (!in_array($check, array('arrayOf', 'min', 'max'), true)) {
                $checksValid = true;
                break;
            }
        }
        if (!$checksValid) {
            throw new Exception\InvalidArgumentException(
                'invalid $checks given: no type specified'
            );
        }

        $valid = true;
        if (in_array('arrayOf', (array) $checks, true)) {
            if (!is_array($arg)) {
                $valid = false;
            } else {
                foreach ($arg as $member) {
                    if (!self::checkArg($member, $checks)) {
                        $valid = false;
                        break;
                    }
                }
            }
        } elseif (!self::checkArg($arg, $checks)) {
            $valid = false;
        }

        if (!$valid) {
            if (!$exception) return false;
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: failed to validate argument, given %s, expected %s',
                self::assembleCaller(debug_backtrace()),
                gettype($arg),
                self::assembleChecksError($checks)
            ));
        }

        return true;
    }

    /**
     * wrapper function to call a chain of self::assert()'s on array members
     * can handle isset() checks as well
     *
     * @see self::assert
     * @param array $arg    the array to validate
     * @param array $checks
     *  [(key of arg)] => (types)
     *      for types, additional key available:
     *      - 'notSet' allow non-existent keys
     *  e.g. array(
     *          'foo' => 'int',
     *          'bar' => array('string', 'notSet'),
     *       )
     *   = check that $arg['foo'] is int,
                  and $arg['bar'] is string, if set; or not set at all
     * @param bool $exception = true
     *  if false,
     *  will return false instead of throw exception on failed assertion
     *
     * @return bool
     */
    public static function arrayAssert(
        array $arg, array $checks, $exception = true)
    {
        foreach ($checks as $key => $memberChecks) {
            if (!isset($arg[$key])) {
                if (in_array('notSet', (array) $memberChecks, true)) {
                    continue;
                } elseif (!$exception) {
                    return false;
                } else {
                    self::throwIssetException("arg[$key]");
                }
            }

            if (is_array($memberChecks)
                && ($k = array_search('notSet', $memberChecks)) !== false) {
                unset($memberChecks[$k]);
            }

            try {
                $result = self::assert($arg[$key], $memberChecks, $exception);
            } catch (Exception\InvalidArgumentException $e) {
                if ($exception) {
                    throw $e;
                }
            }

            if (!$result && !$exception) {
                return false;
            }
        }

        return true;
    }

    /**
     * helper function for self::assert()
     * return true if $arg is valid according to $checks;
     * false otherwise
     *
     * @see self::assert()
     *
     * @param  mixed        $arg
     * @param  string|array $checks
     * @return bool
     */
    protected static function checkArg($arg, $checks)
    {
        $checks = (array) $checks;

        $min = isset($checks['min']) ? (float) $checks['min'] : null;
        $max = isset($checks['max']) ? (float) $checks['max'] : null;
        self::trimChecks($checks);

        foreach ($checks as $check) {
            if (is_array($check)) {
                if (in_array($arg, $check, true)) return true;
                continue;
            }
            switch ($check) {
                case 'int':
                    if (is_int($arg)
                        && self::checkNumRange($arg, $min, $max)) {
                        return true;
                    }
                    break;
                case 'float':
                    if (is_float($arg)
                        && self::checkNumRange($arg, $min, $max)) {
                        return true;
                    }
                    break;
                case 'numeric':
                    if (is_numeric($arg)
                        && self::checkNumRange($arg, $min, $max)) {
                        return true;
                    }
                    break;
                case 'string':
                    if (is_string($arg)
                        && self::checkStrlen($arg, $min, $max)) {
                        return true;
                    }
                    break;
                case 'array':
                    if (is_array($arg)
                        && self::checkCount($arg, $min, $max)) {
                        return true;
                    }
                    break;
                case 'null':
                    if ($arg === null) return true;
                    break;
                case 'callable':
                    if (is_callable($arg)) return true;
                    break;
                case 'notEmpty':
                    if (!empty($arg)) return true;
                    break;
                default:
                    if ($arg instanceof $check) return true;
            }
        }

        return false;
    }

    /**
     * helper function for self::checkArg()
     * checks that $arg is within the specified range, if given
     *
     * @param  mixed          $arg
     * @param  int|float|null $min set 'null' to skip min check
     * @param  int|float|null $max set 'null' to skip max check
     * @return bool
     */
    public static function checkNumRange($arg, $min, $max)
    {
        if ($min !== null && $arg < $min) return false;
        if ($max !== null && $arg > $max) return false;
        return true;
    }

    /**
     * helper function for self::checkArg()
     * checks that string length of $arg is within the specified range
     *
     * @param  string         $arg
     * @param  int|float|null $min set 'null' to skip min check
     * @param  int|float|null $max set 'null' to skip max check
     * @return bool
     */
    public static function checkStrlen($arg, $min, $max)
    {
        if ($min !== null && strlen($arg) < $min) return false;
        if ($max !== null && strlen($arg) > $max) return false;
        return true;
    }

    /**
     * helper function for self::checkArg()
     * checks that array member count of $arg is within the specified range
     *
     * @param  array          $arg
     * @param  int|float|null $min set 'null' to skip min check
     * @param  int|float|null $max set 'null' to skip max check
     * @return bool
     */
    public static function checkCount($arg, $min, $max)
    {
        if ($min !== null && count((array) $arg) < $min) return false;
        if ($max !== null && count((array) $arg) > $max) return false;
        return true;
    }

    /**
     * assert that a class exists
     *
     * @param string $className
     * @param bool   $exception = true
     *  if false,
     *  will return true instead of throw exception on failed assertion
     * @throws Exception\InvalidArgumentException
     * @return bool
     */
    public static function assertClass($className, $exception = true)
    {
        self::assert($className, 'string');
        if (!class_exists($className)) {
            if (!$exception) return false;
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: class %s does not exist',
                self::assembleCaller(debug_backtrace()),
                $className
            ));
        }

        return true;
    }

    /**
     * assert that
     * 1) class exists, and
     * 2) constants exist in the specified class
     *
     * @param string          $className
     * @param array of string $constants constant names
     * @param bool            $exception = true
     *  if true,
     *  will return false instead of throw exception on failed assertion
     * @return void
     */
    public static function assertClassConstant(
        $className, $constants, $exception = true)
    {
        self::assert($className, 'string');
        self::assert($constants, array('arrayOf', 'string'));
        if (!self::assertClass($className, $exception)) {
            return false;
        }
        foreach ($constants as $constant) {
            if (!defined("$className::$constant")) {
                if (!$exception) return false;
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s: %s::%s must be set',
                    self::assembleCaller(debug_backtrace()),
                    $className,
                    $constant
                ));
            }
        }

        return true;
    }

    /**
     * just throw an exception about an argument not being set
     * can't abstract 'isset' checks away
     * without the variable being set in the first place
     * (in order to act as arg to call this function with)
     *
     * @param string $argName
     * @throw new Exception\InvalidArgumentException
     * @return void
     */
    public static function throwIssetException($argName)
    {
        self::assert($argName, 'string');
        throw new Exception\InvalidArgumentException(sprintf(
            '%s: %s must be set',
            self::assembleCaller(debug_backtrace()),
            $argName
        ));
    }

    /**
     * build a human-readable string of function caller
     * from a debug_backtrace() array given
     *
     * @param  array                              $backtrace
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public static function assembleCaller(array $backtrace)
    {
        if (!isset($backtrace[0])) {
            throw new Exception\InvalidArgumentException(
                'invalid backtrace array given'
            );
        }

        $bt = $backtrace[0];

        $caller = array();
        if (isset($bt['file'])) $caller[] = $bt['file'];
        if (isset($bt['line'])) $caller[] = "({$bt['line']})";
        if (isset($bt['class'])) $caller[] = $bt['class'];
        if (isset($bt['type'])) $caller[] = $bt['type'];
        $caller[] = $bt['function'];

        return implode(' ', $caller);
    }

    /**
     * helper function for self::assert()
     * assemble a human-readable list of valid types
     *
     * @param  array|string $checks
     * @return string
     */
    protected static function assembleChecksError($checks)
    {
        $checks = (array) $checks;

        $min = isset($checks['min']) ? (float) $checks['min'] : null;
        $max = isset($checks['max']) ? (float) $checks['max'] : null;
        $oriChecks = $checks;
        self::trimChecks($checks);

        $validTypes = array();
        foreach ((array) $checks as $check) {
            if (is_array($check)) {
                $validTypes[] = 'one of ' . implode(', ', $check);
                continue;
            }
            switch ($check) {
                case 'int':
                case 'float':
                case 'numeric':
                    $range = array();
                    if ($min !== null) $range[] = "min $min";
                    if ($max !== null) $range[] = "max $max";
                    $validType = $check;
                    if ($range) {
                        $validType .= ' (' . implode(', ', $range) . ')';
                    }
                    $validTypes[] = $validType;
                    break;
                case 'string':
                    $range = array();
                    if ($min !== null) $range[] = "min length $min";
                    if ($max !== null) $range[] = "max length $max";
                    $validType = 'string';
                    if ($range) {
                        $validType .= ' (' . implode(', ', $range) . ')';
                    }
                    $validTypes[] = $validType;
                    break;
                case 'array':
                    $range = array();
                    if ($min !== null) $range[] = "min count $min";
                    if ($max !== null) $range[] = "max count $max";
                    $validType = 'array';
                    if ($range) {
                        $validType .= ' (' . implode(', ', $range) . ')';
                    }
                    $validTypes[] = $validType;
                    break;
                case 'notEmpty':
                    $validTypes[] = 'not empty';
                    break;
                case 'null':
                case 'callable':
                default:
                    $validTypes[] = $check;
            }
        }

        $message = '';
        if (isset($oriChecks['arrayOf'])) {
            $message .= 'an array of ';
        }
        $message .= '[' . implode(' / ', $validTypes) . ']';

        return $message;
    }

    /**
     * remove FLAGS from $checks
     *
     * @param array|string $checks
     */
    public static function trimChecks(&$checks)
    {
        $checks = (array) $checks;
        if (isset($checks['min'])) unset($checks['min']);
        if (isset($checks['max'])) unset($checks['max']);
        if (($k = array_search('arrayOf', $checks)) !== false) {
            unset($checks[$k]);
        }
    }
}
