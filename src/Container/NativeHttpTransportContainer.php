<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Http\NativeHttpStreamIo;
use GregorJ\SerialPort\Interfaces\Http\HttpStreamIo;
use GregorJ\SerialPort\Interfaces\System\Error;
use GregorJ\SerialPort\System\NativeError;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 container for HttpTransport dependencies.
 */
final class NativeHttpTransportContainer extends AbstractContainer implements ContainerInterface
{
    /**
     * @param HttpStreamIo|null $streamIo
     * @param Error|null $error
     */
    public function __construct(HttpStreamIo $streamIo = null, Error $error = null)
    {
        $this->dependencies = [
            HttpStreamIo::class => $streamIo ?? new NativeHttpStreamIo(),
            Error::class => $error ?? new NativeError(),
        ];
    }
}
