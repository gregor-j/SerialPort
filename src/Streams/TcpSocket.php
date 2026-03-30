<?php

namespace GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\OpenStreamException;
use GregorJ\SerialPort\Exceptions\StreamStateException;
use GregorJ\SerialPort\Exceptions\WriteStreamException;
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
use function stream_get_meta_data;
use function stream_set_timeout;
use function strlen;

/**
 * Class TcpSocket
 * Create a TCP connection stream.
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
     * @var resource
     */
    private $socket;

    /**
     * Create a TCP socket.
     * @param string     $host The hostname.
     * @param int        $port The port number.
     * @param float|null $timeout The optional connection timeout, in seconds.
     */
    public function __construct(string $host, int $port, float $timeout = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->connectionTimeout = $timeout ?: self::DEFAULT_CONNECTION_TIMEOUT;
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
     * @inheritDoc
     */
    public function open(): void
    {
        if ($this->isOpen()) {
            throw new StreamStateException('Stream already opened.');
        }
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->connectionTimeout);
        if (!is_resource($socket)) {
            throw new OpenStreamException($errstr, $errno);
        }
        stream_set_blocking($socket,  true);
        $this->socket = $socket;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->isOpen()) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function write(string $string): int
    {
        if (!$this->isOpen()) {
            throw new StreamStateException('Stream not opened.');
        }
        $length = strlen($string);
        $bytes = fwrite($this->socket, $string, $length);
        /**
         * This should never happen, but we prepare for it anyway.
         */
        // @codeCoverageIgnoreStart
        if ($bytes === false) {
            $lastError = error_get_last();
            if (!is_array($lastError)) {
                throw new WriteStreamException('Unknown error.');
            }
            throw new WriteStreamException($lastError['message'], $lastError['type']);
        }
        // @codeCoverageIgnoreEnd
        return $bytes;
    }

    /**
     * @inheritDoc
     */
    public function readChar(): ?string
    {
        if (!$this->isOpen()) {
            throw new StreamStateException('Stream not opened.');
        }
        $char = fgetc($this->socket);
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
        if (!$this->isOpen()) {
            throw new StreamStateException('Stream not opened.');
        }
        $timeoutSeconds = floor($seconds);
        $timeoutMicroseconds = ($seconds - $timeoutSeconds) * 1000000;
        return stream_set_timeout($this->socket, (int)$timeoutSeconds, (int)$timeoutMicroseconds);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): TcpSocketStatus
    {
        if (!$this->isOpen()) {
            throw new StreamStateException('Stream not opened.');
        }
        return new TcpSocketStatus(stream_get_meta_data($this->socket));
    }
}
