<?php
namespace Heartsentwined\ArgValidator\Exception;

use Heartsentwined\ArgValidator\ExceptionInterface;

class InvalidArgumentException
    extends \InvalidArgumentException
    implements ExceptionInterface
{
}
