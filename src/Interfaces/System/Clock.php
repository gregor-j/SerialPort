<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\System;

/**
 * Abstraction for retrieving current time in microseconds.
 */
interface Clock
{
    /**
     * Return current Unix timestamp with microseconds.
     * @return float
     */
    public function now(): float;
}
