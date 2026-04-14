<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\System;

/**
 * Abstraction for retrieving and clearing errors.
 */
interface Error
{
    /**
     * Clear the most recent error
     * @return void
     */
    public function clearLastError(): void;

    /**
     * Get the last occurred error
     * @return array<string, int|string>|null
     */
    public function getLastError(): ?array;
}
