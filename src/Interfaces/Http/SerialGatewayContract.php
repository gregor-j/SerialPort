<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\Http;

use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Http\SerialGatewayResponse;

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
     * @param int $deviceTimeoutMs
     * @param string $deviceId
     * @param string $deviceType
     * @return string
     * @throws WriteException
     */
    public function encodeRequest(
        string $command,
        string $writeTerminator,
        string $readTerminator,
        int $deviceTimeoutMs,
        string $deviceId,
        string $deviceType
    ): string;

    /**
     * Decode and validate the JSON response returned by the gateway.
     * @param string $responseBody
     * @return SerialGatewayResponse
     * @throws UnexpectedResponseException
     */
    public function decodeResponse(string $responseBody): SerialGatewayResponse;
}
