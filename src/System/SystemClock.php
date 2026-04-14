<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\System;

use GregorJ\SerialPort\Interfaces\System\Clock;
use function microtime;

/**
 * System clock based on microtime(true).
 */
final class SystemClock implements Clock
{
    /**
     * Return current Unix timestamp with microseconds.
     * A float (in seconds) is returned.
     * @link https://php.net/manual/en/function.microtime.php
     * @return float
     */
    public function now(): float
    {
        return microtime(true);
    }
}
