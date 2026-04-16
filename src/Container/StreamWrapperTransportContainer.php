<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Http\NativeStreamWrapperIo;
use GregorJ\SerialPort\Interfaces\Http\StreamWrapperIo;
use GregorJ\SerialPort\Interfaces\System\Error;
use GregorJ\SerialPort\System\NativeError;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 container for HttpTransport dependencies.
 */
final class StreamWrapperTransportContainer extends AbstractContainer implements ContainerInterface
{
    /**
     * @param StreamWrapperIo|null $streamIo
     * @param Error|null $error
     */
    public function __construct(StreamWrapperIo $streamIo = null, Error $error = null)
    {
        $this->dependencies = [
            StreamWrapperIo::class => $streamIo ?? new NativeStreamWrapperIo(),
            Error::class => $error ?? new NativeError(),
        ];
    }
}
