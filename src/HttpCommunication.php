<?php

declare(strict_types=1);

namespace GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Communication;
use GregorJ\SerialPort\Interfaces\HttpTransport;
use GregorJ\ToString\ToString;
use JsonException;

use function base64_decode;
use function base64_encode;
use function filter_var;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function parse_url;
use function round;
use function sprintf;

/**
 * Invoke serial commands over an HTTP(S) gateway.
 */
final class HttpCommunication implements Communication
{
    public const DEFAULT_TIMEOUT = 2.0;
    public const DEVICE_TYPE_BLUETOOTH = 'bluetooth';
    public const DEVICE_TYPE_WIRED = 'wired';

    private HttpTransport $httpTransport;
    private string $endpoint;
    private float $timeout;
    private string $deviceId;
    private string $deviceType;

    /**
     * @var string[]
     */
    private array $log = [];

    /**
     * @param HttpTransport $httpTransport
     * @param string $endpoint
     * @param string $deviceId
     * @param string $deviceType Allowed values: bluetooth, wired
     * @throws InvalidParamException
     */
    public function __construct(HttpTransport $httpTransport, string $endpoint, string $deviceId, string $deviceType)
    {
        if (!is_string(filter_var($endpoint, FILTER_VALIDATE_URL))) {
            throw new InvalidParamException('HTTP endpoint for HttpCommunication has to be a valid URL.');
        }
        $scheme = parse_url($endpoint, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidParamException('HTTP endpoint for HttpCommunication has to use scheme http or https.');
        }
        if ($deviceId === '') {
            throw new InvalidParamException('Device ID must not be empty.');
        }

        if (!in_array($deviceType, [self::DEVICE_TYPE_BLUETOOTH, self::DEVICE_TYPE_WIRED], true)) {
            throw new InvalidParamException(
                sprintf('Device type must be "%s" or "%s".', self::DEVICE_TYPE_BLUETOOTH, self::DEVICE_TYPE_WIRED)
            );
        }
        $this->httpTransport = $httpTransport;
        $this->endpoint = $endpoint;
        $this->deviceId = $deviceId;
        $this->deviceType = $deviceType;
        $this->timeout = self::DEFAULT_TIMEOUT;
        $this->log[] = sprintf('default timeout %f seconds', $this->timeout);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->endpoint;
    }

    /**
     * @inheritDoc
     */
    public function setTimeout(float $seconds): void
    {
        if ($seconds < 0.0) {
            throw new InvalidParamException('Response timeout for HttpCommunication has to be positive.');
        }

        $this->log[] = sprintf('set timeout to %f seconds', $seconds);
        $this->timeout = $seconds;
    }

    /**
     * @inheritDoc
     */
    public function query(string $string, string $writeTerminator = '', string $readTerminator = ''): string
    {
        $commandWithTerminator = $string . $writeTerminator;
        $this->log[] = sprintf('write "%s"', ToString::fromString($commandWithTerminator));

        try {
            $jsonPayload = json_encode([
                'commandBase64' => base64_encode($string),
                'writeTerminatorBase64' => base64_encode($writeTerminator),
                'readTerminatorBase64' => base64_encode($readTerminator),
                'deviceTimeoutMs' => (int)round($this->timeout * 1000),
                'deviceId' => $this->deviceId,
                'deviceType' => $this->deviceType,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new WriteException('Failed to encode HTTP serial request payload.', (int)$exception->getCode(), $exception);
        }

        $httpResponse = $this->httpTransport->postJson(
            $this->endpoint,
            $jsonPayload
        );

        if ($httpResponse->getStatusCode() < 200 || $httpResponse->getStatusCode() >= 300) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway returned unexpected status code %d.', $httpResponse->getStatusCode())
            );
        }

        try {
            $decodedResponse = json_decode($httpResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException('HTTP gateway returned invalid JSON response.', (int)$exception->getCode(), $exception);
        }

        if (!is_array($decodedResponse)) {
            throw new UnexpectedResponseException('HTTP gateway returned invalid JSON response.');
        }

        if (($decodedResponse['deviceTimedOut'] ?? false) === true) {
            $partialResponse = $this->extractOptionalBase64Response($decodedResponse, 'partialResponseBase64');
            $this->log[] = sprintf('read timed out. partial response "%s"', ToString::fromString($partialResponse));
            throw new TimeoutException('Response timed out on serial device.');
        }

        if (!isset($decodedResponse['responseBase64']) || !is_string($decodedResponse['responseBase64'])) {
            throw new UnexpectedResponseException('HTTP gateway response field "responseBase64" is missing.');
        }

        $response = base64_decode($decodedResponse['responseBase64'], true);
        if (!is_string($response)) {
            throw new UnexpectedResponseException('HTTP gateway returned invalid base64 in field "responseBase64".');
        }

        $this->log[] = sprintf('read "%s"', ToString::fromString($response));

        return $response;
    }

    /**
     * @param array<string, mixed> $response
     * @param string $field
     * @return string
     * @throws UnexpectedResponseException
     */
    private function extractOptionalBase64Response(array $response, string $field): string
    {
        if (!isset($response[$field])) {
            return '';
        }

        if (!is_string($response[$field])) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway response field "%s" has invalid type.', $field)
            );
        }

        $decoded = base64_decode($response[$field], true);
        if (!is_string($decoded)) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway returned invalid base64 in field "%s".', $field)
            );
        }

        return $decoded;
    }

    /**
     * @inheritDoc
     */
    public function getLog(): array
    {
        return $this->log;
    }
}
