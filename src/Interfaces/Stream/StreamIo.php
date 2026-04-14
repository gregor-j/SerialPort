<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\Stream;

/**
 * Abstraction for stream IO operations.
 */
interface StreamIo
{
    /**
     * Closes an open stream.
     * @param resource $socket
     * @return bool
     */
    public function close($socket): bool;

    /**
     * Binary-safe stream write.
     * @param resource $socket
     * @param string $string
     * @param int<0, max>|null $length
     */
    public function write($socket, string $string, int|null $length): int|false;

    /**
     * Gets a character from the stream.
     * @param resource $socket
     * @return string|false
     */
    public function readChar($socket): string|false;

    /**
     * Set timeout period on a stream.
     * @param resource $socket
     * @param int $seconds
     * @param int $microseconds
     * @return bool
     */
    public function setTimeout($socket, int $seconds, int $microseconds): bool;

    /**
     * Set blocking/non-blocking mode on a stream
     * @param resource $socket
     * @param bool $enable
     * @return bool
     */
    public function setBlocking($socket, bool $enable): bool;

    /**
     * Retrieves metadata from stream.
     * @param resource $socket
     * @return array<string, mixed>
     */
    public function getMetadata($socket): array;
}
