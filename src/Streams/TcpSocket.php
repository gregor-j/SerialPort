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
use GregorJ\ToString\ToString;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
     * @param string $host The hostname.
     * @param int $port The port number.
     * @param float|null $timeoutSeconds The optional connection timeout, in seconds.
     * @param ContainerInterface|null $container Optional container for infrastructure services.
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        string $host,
        int $port,
        float $timeoutSeconds = null,
        ContainerInterface $container = null
    ) {
        // set default timeout in case no timeout is provided
        $timeoutSeconds = $timeoutSeconds ?? self::DEFAULT_CONNECTION_TIMEOUT;
        if ($timeoutSeconds < 0.0) {
            throw new InvalidParamException('Connection timeout for TcpSocket has to be positive.');
        }
        $this->connectionTimeout = $timeoutSeconds;
        $this->host = $host;
        $this->port = $port;
        $container = $container ?? new TcpSocketContainer();
        $this->connector = $this->resolveDependency($container, TcpSocketConnector::class);
        $this->io = $this->resolveDependency($container, StreamIo::class);
        $this->clock = $this->resolveDependency($container, Clock::class);
        $this->errors = $this->resolveDependency($container, Error::class);
    }

    /**
     * Resolve a typed dependency from the DI container.
     * @template T of object
     * @param ContainerInterface $container
     * @param class-string<T> $id
     * @return T
     * @throws InvalidParamException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveDependency(ContainerInterface $container, string $id): object
    {
        if (!$container->has($id)) {
            throw new InvalidParamException(sprintf('Missing required TcpSocket dependency "%s" in container.', $id));
        }

        $dependency = $container->get($id);
        if (!$dependency instanceof $id) {
            throw new InvalidParamException(sprintf('TcpSocket dependency "%s" must implement %s.', $id, $id));
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $dependency;
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
        $timeoutSeconds = $timeoutSeconds ?? self::DEFAULT_WRITE_TIMEOUT;
        if ($timeoutSeconds < 0) {
            throw new InvalidParamException('Write timeout for TcpSocket must be positive.');
        }
        if ($string === '') {
            return 0;
        }
        $socket = $this->getSocket();
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
