<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Http;

/**
 * Decoded gateway response data.
 */
final class SerialGatewayResponse
{
    /**
     * @param bool $deviceTimedOut
     * @param string $response
     * @param string $partialResponse
     */
    public function __construct(
        private readonly bool $deviceTimedOut,
        private readonly string $response,
        private readonly string $partialResponse
    ) {
    }

    /**
     * @return bool
     */
    public function isDeviceTimedOut(): bool
    {
        return $this->deviceTimedOut;
    }

    /**
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getPartialResponse(): string
    {
        return $this->partialResponse;
    }
}
