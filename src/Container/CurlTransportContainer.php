<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Http\NativeCurlIo;
use GregorJ\SerialPort\Interfaces\Http\CurlIo;
use GregorJ\SerialPort\Interfaces\System\Error;
use GregorJ\SerialPort\System\NativeError;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 container for CurlTransport dependencies.
 */
final class CurlTransportContainer extends AbstractContainer implements ContainerInterface
{
    /**
     * @param CurlIo|null $curlIo
     * @param Error|null $error
     */
    public function __construct(CurlIo $curlIo = null, Error $error = null)
    {
        $this->dependencies = [
            CurlIo::class => $curlIo ?? new NativeCurlIo(),
            Error::class => $error ?? new NativeError(),
        ];
    }
}
