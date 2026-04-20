<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Http;

use Exception;
use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Http\HttpResponseInterface;
use GregorJ\SerialPort\Interfaces\Http\SerialGatewayContract;
use JsonException;

use function base64_decode;
use function base64_encode;
use function gettype;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function round;
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
    public const RESPONSE_INVALID_PARAM_ERROR = 'invalidParamError';
    public const RESPONSE_CONNECTION_ERROR = 'connectionError';
    public const RESPONSE_TIMEOUT_ERROR = 'timeoutError';
    public const RESPONSE_ERROR = 'error'; //any other error
    public const RESPONSE_VALUE = 'responseBase64';

    /**
     * @inheritDoc
     */
    public function encodeRequest(
        string $command,
        string $writeTerminator,
        string $readTerminator,
        float $deviceTimeout,
        string $deviceId,
        string $deviceType
    ): string {
        try {
            return json_encode([
                self::REQUEST_COMMAND => base64_encode($command),
                self::REQUEST_WRITE_TERMINATOR => base64_encode($writeTerminator),
                self::REQUEST_READ_TERMINATOR => base64_encode($readTerminator),
                self::REQUEST_TIMEOUT => (int)round($deviceTimeout * 1000),
                self::REQUEST_DEVICE => $deviceId,
                self::REQUEST_DEVICE_TYPE => $deviceType,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new WriteException('Failed to encode HTTP serial request payload.', (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function decodeResponse(HttpResponseInterface $httpResponse): string
    {
        if ($httpResponse->getStatusCode() < 200 || $httpResponse->getStatusCode() >= 300) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway returned unexpected status code %d.', $httpResponse->getStatusCode())
            );
        }
        $responseBody = $httpResponse->getBody();
        try {
            $decodedResponse = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException('HTTP gateway returned invalid JSON response.', (int)$exception->getCode(), $exception);
        }
        if (!is_array($decodedResponse)) {
            throw new UnexpectedResponseException('HTTP gateway returned invalid JSON response.');
        }
        /**
         * Check for errors.
         */
        if (isset($decodedResponse[self::RESPONSE_INVALID_PARAM_ERROR]) && is_string($decodedResponse[self::RESPONSE_INVALID_PARAM_ERROR])) {
            throw new InvalidParamException($decodedResponse[self::RESPONSE_INVALID_PARAM_ERROR]);
        }
        if (isset($decodedResponse[self::RESPONSE_CONNECTION_ERROR]) && is_string($decodedResponse[self::RESPONSE_CONNECTION_ERROR])) {
            throw new ConnectionException($decodedResponse[self::RESPONSE_CONNECTION_ERROR]);
        }
        if (isset($decodedResponse[self::RESPONSE_TIMEOUT_ERROR]) && is_string($decodedResponse[self::RESPONSE_TIMEOUT_ERROR])) {
            throw new TimeoutException($decodedResponse[self::RESPONSE_TIMEOUT_ERROR]);
        }
        if (isset($decodedResponse[self::RESPONSE_ERROR]) && is_string($decodedResponse[self::RESPONSE_ERROR])) {
            throw new Exception($decodedResponse[self::RESPONSE_ERROR]);
        }
        /**
         * Decode response.
         */
        if (!isset($decodedResponse[self::RESPONSE_VALUE])) {
            throw new UnexpectedResponseException(sprintf('HTTP gateway response field "%s" is missing.', self::RESPONSE_VALUE));
        }
        if (!is_string($decodedResponse[self::RESPONSE_VALUE])) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway response field "%s" is %s, not string.', self::RESPONSE_VALUE, gettype($decodedResponse[self::RESPONSE_VALUE]))
            );
        }
        $decodedMessage = base64_decode($decodedResponse[self::RESPONSE_VALUE], true);
        if (!is_string($decodedMessage)) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway returned invalid base64 in field "%s".', self::RESPONSE_VALUE)
            );
        }
        return $decodedMessage;
    }
}
