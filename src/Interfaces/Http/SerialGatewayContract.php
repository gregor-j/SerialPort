<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\Http;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;

/**
 * Encodes/decodes the HTTP JSON contract for the serial gateway.
 */
interface SerialGatewayContract
{
    /**
     * Build the JSON payload expected by the HTTP serial gateway.
     * @param string $command
     * @param string $writeTerminator
     * @param string $readTerminator
     * @param float $deviceTimeout
     * @param string $deviceId
     * @param string $deviceType
     * @return string
     * @throws WriteException
     */
    public function encodeRequest(
        string $command,
        string $writeTerminator,
        string $readTerminator,
        float $deviceTimeout,
        string $deviceId,
        string $deviceType
    ): string;

    /**
     * Decode and validate the JSON response returned by the gateway.
     * @param HttpResponseInterface $httpResponse
     * @return string
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     */
    public function decodeResponse(HttpResponseInterface $httpResponse): string;
}
