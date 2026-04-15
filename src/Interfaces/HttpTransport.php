<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Http\HttpResponse;

/**
 * HTTP transport abstraction used by HttpCommunication.
 */
interface HttpTransport
{
    /**
     * Send a JSON POST request.
     * @param string $url
     * @param string $jsonPayload
     * @return HttpResponse
     * @throws ConnectionException
     */
    public function postJson(
        string $url,
        string $jsonPayload
    ): HttpResponse;
}
