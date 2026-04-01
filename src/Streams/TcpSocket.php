<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\StateException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Stream;
use GregorJ\SerialPort\Responses\TcpSocketStatus;

use function error_get_last;
use function fclose;
use function fgetc;
use function floor;
use function fsockopen;
use function fwrite;
use function is_array;
use function is_resource;
use function max;
use function stream_get_meta_data;
use function stream_set_timeout;
use function strlen;

/**
 * Class TcpSocket
 * Create a TCP socket connection.
 *
 * Bluntly copied and adapted from Peter Gribanovs example:
 * @link https://github.com/jupeter/clean-code-php/issues/178
 *
 * @package GregorJ\SerialPort\Streams
 * @author  Gregor J.
 * @author  Peter Gribanov
 */
final class TcpSocket implements Stream
{
    /**
     * Default connection timeout in seconds.
     */
    public const DEFAULT_CONNECTION_TIMEOUT = 2.0;

    /**
     * @var string Hostname/IP
     */
    private string $host;

    /**
     * @var int TCP port
     */
    private int $port;

    /**
     * @var float Connection timeout
     */
    private float $connectionTimeout;

    /**
     * @var resource|null
     */
    private $socket = null;

    /**
     * Create a TCP socket.
     * @param string     $host The hostname.
     * @param int        $port The port number.
     * @param float|null $timeout The optional connection timeout, in seconds.
     * @throws InvalidValueException
     */
    public function __construct(string $host, int $port, float $timeout = null)
    {
        // set default timeout in case no timeout is provided
        $timeout = $timeout ?? self::DEFAULT_CONNECTION_TIMEOUT;
        if ($timeout < 0.0) {
            throw new InvalidValueException('Timeout has to be positive.');
        }
        $this->connectionTimeout = $timeout;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * TcpSocket destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function isOpen(): bool
    {
        return is_resource($this->socket);
    }

    /**
     * Return the open socket resource or throw if the socket is closed.
     *
     * @return resource
     * @throws StateException
     */
    private function getSocket()
    {
        if (!is_resource($this->socket)) {
            throw new StateException('TCP connection not established.');
        }
        return $this->socket;
    }

    /**
     * @inheritDoc
     */
    public function open(): void
    {
        if ($this->isOpen()) {
            throw new StateException('TCP connection already established.');
        }
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->connectionTimeout);
        if (!is_resource($socket)) {
            throw new ConnectionException($errstr, $errno);
        }
        $this->socket = $socket;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->isOpen()) {
            fclose($this->getSocket());
            $this->socket = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function write(string $string): int
    {
        $socket = $this->getSocket();
        if ($string === '') {
            throw new InvalidValueException('Cannot write empty string.');
        }
        $length = strlen($string);
        $offset = 0;
        $totalBytes = 0;

        /**
         * partial write loop: fwrite() may not write all requested bytes on a single call.
         * This is normal TCP behavior, especially with larger strings or network congestion.
         * Continue writing until all bytes are sent, tracking offset and total bytes written.
         */
        while ($offset < $length) {
            /**
             * Write the remaining portion of the string, starting from the current offset.
             * max() ensures we always request at least 1 byte to prevent zero-length writes.
             */
            $bytes = fwrite($socket, substr($string, $offset), max($length - $offset, 1));

            /**
             * This should never happen, but we prepare for it anyway.
             */
            // @codeCoverageIgnoreStart
            if ($bytes === false) {
                $lastError = error_get_last();
                if (!is_array($lastError)) {
                    throw new WriteException('Unknown error.');
                }
                throw new WriteException($lastError['message'], $lastError['type']);
            }
            // @codeCoverageIgnoreEnd

            // Move offset forward by the number of bytes actually written.
            $offset += $bytes;
            // Accumulate total bytes written across all iterations.
            $totalBytes += $bytes;
        }

        // Return the total number of bytes written to the socket.
        return $totalBytes;
    }

    /**
     * @inheritDoc
     */
    public function readChar(): ?string
    {
        $char = fgetc($this->getSocket());
        if ($char === false) {
            return null;
        }
        return $char;
    }

    /**
     * @inheritDoc
     */
    public function setTimeout(float $seconds): bool
    {
        if ($seconds < 0.0) {
            throw new InvalidValueException('Timeout has to be positive.');
        }
        $timeoutSeconds = floor($seconds);
        $timeoutMicroseconds = ($seconds - $timeoutSeconds) * 1000000;
        return stream_set_timeout($this->getSocket(), (int)$timeoutSeconds, (int)$timeoutMicroseconds);
    }

    /**
     * @inheritDoc
     */
    public function setBlocking(bool $blocking): bool
    {
        return stream_set_blocking($this->getSocket(), $blocking);
    }

    /**
     * @inheritDoc
     */
    public function timedOut(): bool
    {
        return $this->getStatus()->timedOut();
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): TcpSocketStatus
    {
        return new TcpSocketStatus(stream_get_meta_data($this->getSocket()));
    }
}
