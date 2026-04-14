<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Stream;
use GregorJ\SerialPort\Interfaces\Stream\StreamIo;
use GregorJ\SerialPort\Interfaces\Stream\TcpSocketConnector;
use GregorJ\SerialPort\Interfaces\System\Clock;
use GregorJ\SerialPort\Interfaces\System\Error;
use GregorJ\SerialPort\System\SystemClock;
use GregorJ\SerialPort\System\SystemError;
use GregorJ\ToString\ToString;

use function floor;
use function is_array;
use function is_resource;
use function max;
use function sprintf;
use function strlen;
use function substr;

/**
 * Create a TCP socket connection.
 *
 * Bluntly copied and adapted from Peter Gribanovs example:
 * @link https://github.com/jupeter/clean-code-php/issues/178
 */
final class TcpSocket implements Stream
{
    /**
     * Default connection timeout in seconds.
     */
    public const DEFAULT_CONNECTION_TIMEOUT = 2.0;

    /**
     * Default write timeout in seconds.
     */
    public const DEFAULT_WRITE_TIMEOUT = 2.0;

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

    private TcpSocketConnector $connector;
    private StreamIo $io;
    private Clock $clock;
    private Error $errors;

    /**
     * Create a TCP socket.
     * @param string                     $host The hostname.
     * @param int                        $port The port number.
     * @param float|null                 $timeoutSeconds The optional connection timeout, in seconds.
     * @param TcpSocketConnector|null    $connector Optional connector abstraction.
     * @param StreamIo|null              $io Optional IO abstraction.
     * @param Clock|null                 $clock Optional clock abstraction.
     * @throws InvalidParamException
     */
    public function __construct(
        string $host,
        int $port,
        float $timeoutSeconds = null,
        TcpSocketConnector $connector = null,
        StreamIo $io = null,
        Clock $clock = null,
        Error $errors = null
    ) {
        // set default timeout in case no timeout is provided
        $timeoutSeconds = $timeoutSeconds ?? self::DEFAULT_CONNECTION_TIMEOUT;
        if ($timeoutSeconds < 0.0) {
            throw new InvalidParamException('Connection timeout for TcpSocket has to be positive.');
        }
        $this->connectionTimeout = $timeoutSeconds;
        $this->host = $host;
        $this->port = $port;
        $this->connector = $connector ?? new NativeTcpSocketConnector();
        $this->io = $io ?? new NativeStreamIo();
        $this->clock = $clock ?? new SystemClock();
        $this->errors = $errors ?? new SystemError();
    }

    /**
     * TcpSocket destructor
     */
    public function __destruct()
    {
        if (is_resource($this->socket)) {
            $this->io->close($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Return the open socket resource or create it lazily.
     * @return resource
     * @throws ConnectionException
     */
    private function getSocket()
    {
        if (!is_resource($this->socket)) {
            $error_code = -1;
            $error_message = '';
            $socket = $this->connector->connect($this->host, $this->port, $error_code, $error_message, $this->connectionTimeout);
            if ($socket === false) {
                throw new ConnectionException(
                    sprintf('TCP connection to %s:%u failed: %s', $this->host, $this->port, $error_message),
                    $error_code
                );
            }
            $this->io->setBlocking($socket, true);
            $this->socket = $socket;
        }

        return $this->socket;
    }

    /**
     * @inheritDoc
     */
    public function write(string $string, float $timeoutSeconds = null): int
    {
        $socket = $this->getSocket();
        if ($string === '') {
            throw new InvalidParamException('Cannot write empty string.');
        }
        $timeoutSeconds = $timeoutSeconds ?? self::DEFAULT_WRITE_TIMEOUT;
        if ($timeoutSeconds < 0) {
            throw new InvalidParamException('Write timeout for TcpSocket must be positive.');
        }

        $this->errors->clearLastError();
        $length = strlen($string);
        $offset = 0;
        $totalBytes = 0;
        $zeroWriteStart = null;

        while ($offset < $length) {
            $bytes = $this->io->write($socket, substr($string, $offset), max($length - $offset, 1));

            if ($bytes === 0 && $zeroWriteStart !== null && ($this->clock->now() - $zeroWriteStart) > $timeoutSeconds) {
                throw new WriteException(
                    sprintf(
                        'Write operation timed out after %ds while writing %u bytes of "%s" to TCP connection %s:%s.',
                        $timeoutSeconds,
                        $totalBytes,
                        ToString::fromString($string),
                        $this->host,
                        $this->port
                    )
                );
            } elseif ($bytes === 0 && $zeroWriteStart === null) {
                $zeroWriteStart = $this->clock->now();
            } elseif ($bytes > 0) {
                $zeroWriteStart = null;
            }

            if ($bytes === false) {
                $lastError = $this->errors->getLastError();
                throw new WriteException(
                    sprintf(
                        'Failed to write "%s" to TCP connection %s:%s: %s',
                        ToString::fromString($string),
                        $this->host,
                        $this->port,
                        is_array($lastError) ? (string)$lastError['message'] : 'Unknown error.'
                    ),
                    is_array($lastError) ? (int)$lastError['type'] : 0
                );
            }

            $offset += $bytes;
            $totalBytes += $bytes;
        }

        return $totalBytes;
    }

    /**
     * @inheritDoc
     */
    public function readChar(): ?string
    {
        $char = $this->io->readChar($this->getSocket());
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
            throw new InvalidParamException('Response timeout for TcpSocket has to be positive.');
        }
        $timeoutSeconds = floor($seconds);
        $timeoutMicroseconds = ($seconds - $timeoutSeconds) * 1000000;
        return $this->io->setTimeout($this->getSocket(), (int)$timeoutSeconds, (int)$timeoutMicroseconds);
    }

    /**
     * @inheritDoc
     */
    public function timedOut(): bool
    {
        $metadata = $this->io->getMetadata($this->getSocket());
        return (bool)$metadata['timed_out'];
    }

    /**
     * String representation of the TCP socket connection details.
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('tcp://%s:%s', $this->host, $this->port);
    }
}
