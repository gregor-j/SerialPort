<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Http;

use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Http\SerialGatewayContract;
use JsonException;

use function base64_decode;
use function base64_encode;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;

/**
 * Default JSON contract implementation for the HTTP serial gateway.
 */
final class JsonSerialGatewayContract implements SerialGatewayContract
{
    public const REQUEST_COMMAND = 'commandBase64';
    public const REQUEST_WRITE_TERMINATOR = 'writeTerminatorBase64';
    public const REQUEST_READ_TERMINATOR = 'readTerminatorBase64';
    public const REQUEST_TIMEOUT = 'deviceTimeoutMs';
    public const REQUEST_DEVICE = 'deviceId';
    public const REQUEST_DEVICE_TYPE = 'deviceType';
    public const RESPONSE_VALUE = 'responseBase64';
    public const RESPONSE_TIMED_OUT = 'deviceTimedOut';
    public const RESPONSE_PARTIAL = 'partialResponseBase64';

    /**
     * @inheritDoc
     */
    public function encodeRequest(
        string $command,
        string $writeTerminator,
        string $readTerminator,
        int $deviceTimeoutMs,
        string $deviceId,
        string $deviceType
    ): string {
        try {
            return json_encode([
                self::REQUEST_COMMAND => base64_encode($command),
                self::REQUEST_WRITE_TERMINATOR => base64_encode($writeTerminator),
                self::REQUEST_READ_TERMINATOR => base64_encode($readTerminator),
                self::REQUEST_TIMEOUT => $deviceTimeoutMs,
                self::REQUEST_DEVICE => $deviceId,
                self::REQUEST_DEVICE_TYPE => $deviceType,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new WriteException('Failed to encode HTTP serial request payload.', (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function decodeResponse(string $responseBody): SerialGatewayResponse
    {
        try {
            $decodedResponse = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException('HTTP gateway returned invalid JSON response.', (int)$exception->getCode(), $exception);
        }

        if (!is_array($decodedResponse)) {
            throw new UnexpectedResponseException('HTTP gateway returned invalid JSON response.');
        }

        if (($decodedResponse[self::RESPONSE_TIMED_OUT] ?? false) === true) {
            $partialResponse = $this->extractPartialResponse($decodedResponse);
            return new SerialGatewayResponse(true, '', $partialResponse);
        }

        if (!isset($decodedResponse[self::RESPONSE_VALUE]) || !is_string($decodedResponse[self::RESPONSE_VALUE])) {
            throw new UnexpectedResponseException('HTTP gateway response field "responseBase64" is missing.');
        }

        $response = base64_decode($decodedResponse[self::RESPONSE_VALUE], true);
        if (!is_string($response)) {
            throw new UnexpectedResponseException('HTTP gateway returned invalid base64 in field "responseBase64".');
        }

        return new SerialGatewayResponse(false, $response, '');
    }

    /**
     * @param array<string, mixed> $response
     * @return string
     * @throws UnexpectedResponseException
     */
    private function extractPartialResponse(array $response): string
    {
        if (!isset($response[self::RESPONSE_PARTIAL])) {
            return '';
        }

        if (!is_string($response[self::RESPONSE_PARTIAL])) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway response field "%s" has invalid type.', self::RESPONSE_PARTIAL)
            );
        }

        $decoded = base64_decode($response[self::RESPONSE_PARTIAL], true);
        if (!is_string($decoded)) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway returned invalid base64 in field "%s".', self::RESPONSE_PARTIAL)
            );
        }

        return $decoded;
    }
}
