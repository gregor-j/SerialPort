<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Http;

use CurlHandle;
use GregorJ\SerialPort\Interfaces\Http\CurlIo;

use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;

/**
 * Native cURL operations using PHP built-in functions.
 */
final class NativeCurlIo implements CurlIo
{
    /**
     * @inheritDoc
     */
    public function init(?string $url = null): CurlHandle|false
    {
        return curl_init($url);
    }

    /**
     * @inheritDoc
     */
    public function setOptArray(CurlHandle $handle, array $options): bool
    {
        return curl_setopt_array($handle, $options);
    }

    /**
     * @inheritDoc
     */
    public function exec(CurlHandle $handle): string|false
    {
        $result = curl_exec($handle);
        return $result === false ? false : (string)$result;
    }

    /**
     * @inheritDoc
     */
    public function getInfo(CurlHandle $handle, ?int $option = null): mixed
    {
        return $option === null ? curl_getinfo($handle) : curl_getinfo($handle, $option);
    }

    /**
     * @inheritDoc
     */
    public function getErrNo(CurlHandle $handle): int
    {
        return curl_errno($handle);
    }

    /**
     * @inheritDoc
     */
    public function getError(CurlHandle $handle): string
    {
        return curl_error($handle);
    }

    /**
     * @inheritDoc
     */
    public function close(CurlHandle $handle): void
    {
        curl_close($handle);
    }
}
