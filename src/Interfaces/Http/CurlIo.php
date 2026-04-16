<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\Http;

use CurlHandle;

/**
 * Abstraction for cURL operations used by HttpTransport implementations.
 */
interface CurlIo
{
    /**
     * Initialize a cURL session.
     * @link https://www.php.net/manual/en/function.curl-init.php
     * @param string|null $url If provided, the URL to fetch.
     * @return CurlHandle|false Returns a cURL handle on success, false on failure.
     */
    public function init(?string $url = null): CurlHandle|false;

    /**
     * Set multiple options for a cURL transfer.
     * @link https://www.php.net/manual/en/function.curl-setopt-array.php
     * @param CurlHandle $handle A cURL handle returned by curl_init().
     * @param array<int, mixed> $options An array specifying cURL options to set and their values.
     * @return bool Returns true if all options were successfully set. If an option could not be set, false is immediately returned.
     */
    public function setOptArray(CurlHandle $handle, array $options): bool;

    /**
     * Perform a cURL session.
     * @link https://www.php.net/manual/en/function.curl-exec.php
     * @param CurlHandle $handle A cURL handle returned by curl_init().
     * @return string|false Returns the result string on success when CURLOPT_RETURNTRANSFER is true, or false on failure.
     */
    public function exec(CurlHandle $handle): string|false;

    /**
     * Get information regarding a specific transfer.
     * @link https://www.php.net/manual/en/function.curl-getinfo.php
     * @param CurlHandle $handle A cURL handle returned by curl_init().
     * @param int|null $option One of the CURLINFO_ constants.
     * @return mixed Returns a value for the given option, or an associative array of all available information if option is null.
     */
    public function getInfo(CurlHandle $handle, ?int $option = null): mixed;

    /**
     * Return the last error number.
     * @link https://www.php.net/manual/en/function.curl-errno.php
     * @param CurlHandle $handle A cURL handle returned by curl_init().
     * @return int Returns the error number or 0 if no error occurred.
     */
    public function getErrNo(CurlHandle $handle): int;

    /**
     * Return a string containing the last error for the current session.
     * @link https://www.php.net/manual/en/function.curl-error.php
     * @param CurlHandle $handle A cURL handle returned by curl_init().
     * @return string Returns the error message or an empty string if no error occurred.
     */
    public function getError(CurlHandle $handle): string;

    /**
     * Close a cURL session.
     * @link https://www.php.net/manual/en/function.curl-close.php
     * @param CurlHandle $handle A cURL handle returned by curl_init().
     * @return void
     */
    public function close(CurlHandle $handle): void;
}
