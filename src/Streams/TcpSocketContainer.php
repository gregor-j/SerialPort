<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Stream\StreamIo;
use GregorJ\SerialPort\Interfaces\Stream\TcpSocketConnector;
use GregorJ\SerialPort\Interfaces\System\Clock;
use GregorJ\SerialPort\Interfaces\System\Error;
use GregorJ\SerialPort\System\SystemClock;
use GregorJ\SerialPort\System\SystemError;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 container for TcpSocket dependencies.
 */
final class TcpSocketContainer implements ContainerInterface
{
    /**
     * @var array<class-string, object>
     */
    private array $services;

    public function __construct(
        TcpSocketConnector $connector = null,
        StreamIo $io = null,
        Clock $clock = null,
        Error $errors = null
    ) {
        $this->services = [
            TcpSocketConnector::class => $connector ?? new NativeTcpSocketConnector(),
            StreamIo::class => $io ?? new NativeStreamIo(),
            Clock::class => $clock ?? new SystemClock(),
            Error::class => $errors ?? new SystemError(),
        ];
    }

    /**
     * Get the requested dependency.
     * @param string $id
     * @return object
     * @throws NotFoundException
     */
    public function get(string $id): object
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Service "%s" was not found in TcpSocketContainer.', $id));
        }

        return $this->services[$id];
    }

    /**
     * Check if the requested dependency is set.
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
