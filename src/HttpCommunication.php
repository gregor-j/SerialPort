<?php

declare(strict_types=1);

namespace GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Http\JsonSerialGatewayContract;
use GregorJ\SerialPort\Interfaces\Http\SerialGatewayContract;
use GregorJ\SerialPort\Interfaces\Communication;
use GregorJ\SerialPort\Interfaces\HttpTransport;
use GregorJ\ToString\ToString;

use function filter_var;
use function in_array;
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
    private SerialGatewayContract $gatewayContract;

    /**
     * @var string[]
     */
    private array $log = [];

    /**
     * @param HttpTransport $httpTransport
     * @param string $endpoint
     * @param string $deviceId
     * @param string $deviceType Allowed values: bluetooth, wired
     * @param SerialGatewayContract|null $gatewayContract
     * @throws InvalidParamException
     */
    public function __construct(
        HttpTransport $httpTransport,
        string $endpoint,
        string $deviceId,
        string $deviceType,
        SerialGatewayContract $gatewayContract = null
    ) {
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
        $this->gatewayContract = $gatewayContract ?? new JsonSerialGatewayContract();
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
        $this->log[] = sprintf('write "%s"', ToString::fromString($string . $writeTerminator));

        $jsonPayload = $this->gatewayContract->encodeRequest(
            $string,
            $writeTerminator,
            $readTerminator,
            (int)round($this->timeout * 1000),
            $this->deviceId,
            $this->deviceType
        );

        $httpResponse = $this->httpTransport->postJson(
            $this->endpoint,
            $jsonPayload
        );

        if ($httpResponse->getStatusCode() < 200 || $httpResponse->getStatusCode() >= 300) {
            throw new UnexpectedResponseException(
                sprintf('HTTP gateway returned unexpected status code %d.', $httpResponse->getStatusCode())
            );
        }

        $gatewayResponse = $this->gatewayContract->decodeResponse($httpResponse->getBody());

        if ($gatewayResponse->isDeviceTimedOut()) {
            $partialResponse = $gatewayResponse->getPartialResponse();
            $this->log[] = sprintf('read timed out. partial response "%s"', ToString::fromString($partialResponse));
            throw new TimeoutException('Response timed out on serial device.');
        }

        $response = $gatewayResponse->getResponse();

        $this->log[] = sprintf('read "%s"', ToString::fromString($response));

        return $response;
    }


    /**
     * @inheritDoc
     */
    public function getLog(): array
    {
        return $this->log;
    }
}
