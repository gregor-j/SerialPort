<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\System;

use GregorJ\SerialPort\Interfaces\System\Error;

/**
 * Native error handling functions.
 */
final class SystemError implements Error
{
    /**
     * Clear the most recent error
     * @link https://php.net/manual/en/function.error-clear-last.php
     * @return void
     */
    public function clearLastError(): void
    {
        error_clear_last();
    }

    /**
     * Get the last occurred error
     * @link https://php.net/manual/en/function.error-get-last.php
     * @return array<string, int|string>|null an associative array describing the last error with keys "type",
     * "message", "file" and "line". Returns null if there hasn't been an error
     * yet.
     */
    public function getLastError(): ?array
    {
        return error_get_last();
    }
}
