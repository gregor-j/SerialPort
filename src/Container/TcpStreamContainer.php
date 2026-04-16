<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Interfaces\Stream\StreamIo;
use GregorJ\SerialPort\Interfaces\Stream\TcpStreamConnector;
use GregorJ\SerialPort\Interfaces\System\Clock;
use GregorJ\SerialPort\Interfaces\System\Error;
use GregorJ\SerialPort\Streams\NativeStreamIo;
use GregorJ\SerialPort\Streams\NativeTcpStreamConnector;
use GregorJ\SerialPort\System\NativeClock;
use GregorJ\SerialPort\System\NativeError;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 container for TcpStream dependencies.
 */
final class TcpStreamContainer extends AbstractContainer implements ContainerInterface
{
    /**
      * @param TcpStreamConnector|null $connector
      * @param StreamIo|null $io
      * @param Clock|null $clock
      * @param Error|null $error
      */
    public function __construct(
        TcpStreamConnector $connector = null,
        StreamIo           $io = null,
        Clock              $clock = null,
        Error              $error = null
    ) {
        $this->dependencies = [
            TcpStreamConnector::class => $connector ?? new NativeTcpStreamConnector(),
            StreamIo::class => $io ?? new NativeStreamIo(),
            Clock::class => $clock ?? new NativeClock(),
            Error::class => $error ?? new NativeError(),
        ];
    }
}
