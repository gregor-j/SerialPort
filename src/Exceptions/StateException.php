<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Exceptions;

/**
 * Class StateException
 * The state exception is thrown in case the logic of opening and closing a connection is violated.
 * @package GregorJ\SerialPort\Exceptions
 * @author  Gregor J.
 */
final class StateException extends LogicException
{
}
