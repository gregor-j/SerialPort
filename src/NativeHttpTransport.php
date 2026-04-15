<?php

declare(strict_types=1);

namespace GregorJ\SerialPort;

use GregorJ\SerialPort\Container\NativeHttpTransportContainer;
use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\ContainerException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Http\HttpResponse;
use GregorJ\SerialPort\Interfaces\Http\HttpStreamIo;
use GregorJ\SerialPort\Interfaces\HttpTransport;
use GregorJ\SerialPort\Interfaces\System\Error;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;
use function trim;

/**
 * HTTP transport using PHP stream wrappers.
 */
final class NativeHttpTransport implements HttpTransport
{
    public const DEFAULT_CONNECT_TIMEOUT = 2.0;
    public const DEFAULT_REQUEST_TIMEOUT = 5.0;

    private float $connectTimeout;
    private float $requestTimeout;
    private HttpStreamIo $streamIo;
    private Error $error;

    /**
     * @param float $connectTimeoutSeconds
     * @param float $requestTimeoutSeconds
     * @param ContainerInterface|null $container
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        float $connectTimeoutSeconds = self::DEFAULT_CONNECT_TIMEOUT,
        float $requestTimeoutSeconds = self::DEFAULT_REQUEST_TIMEOUT,
        ContainerInterface $container = null
    ) {
        if ($connectTimeoutSeconds < 0.0) {
            throw new InvalidParamException('HTTP transport connect timeout for HttpCommunication has to be positive.');
        }
        $this->connectTimeout = $connectTimeoutSeconds;
        if ($requestTimeoutSeconds < 0.0) {
            throw new InvalidParamException('HTTP transport request timeout for HttpCommunication has to be positive.');
        }
        $this->requestTimeout = $requestTimeoutSeconds;
        $container = $container ?? new NativeHttpTransportContainer();
        $this->streamIo = $this->resolveDependency($container, HttpStreamIo::class);
        $this->error = $this->resolveDependency($container, Error::class);
    }

    /**
     * Resolve a typed dependency from the DI container.
     * @template T of object
     * @param ContainerInterface $container
     * @param class-string<T> $class
     * @return T
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function resolveDependency(ContainerInterface $container, string $class): object
    {
        if (!$container->has($class)) {
            throw new NotFoundException(sprintf('Missing required dependency "%s" in container.', $class));
        }
        $dependency = $container->get($class);
        if (!$dependency instanceof $class) {
            throw new ContainerException(sprintf('Dependency "%s" must implement %s.', $class, $class));
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $dependency;
    }

    /**
     * @inheritDoc
     */
    public function postJson(
        string $url,
        string $jsonPayload
    ): HttpResponse {
        $this->error->clearLastError();
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Connection: close',
        ];
        $context = $this->streamIo->createStreamContext([
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => $jsonPayload,
                'ignore_errors' => true,
                'timeout' => $this->requestTimeout,
                'protocol_version' => 1.1,
            ],
            // Best effort for connect timeout with stream wrappers.
            'socket' => [
                'connect_timeout' => $this->connectTimeout,
            ],
        ]);
        $body = $this->streamIo->getContents($url, $context);
        if ($body === false) {
            $error = $this->error->getLastError();
            $message = is_array($error)
                ? (string)$error['message']
                : 'Unknown error.';
            throw new ConnectionException(sprintf('HTTP request to %s failed: %s', $url, $message));
        }

        // Extract status code from the raw header lines provided by stream I/O.
        $responseHeaders = $this->streamIo->getResponseHeaders();
        $statusCode = 0;
        if (!empty($responseHeaders) && preg_match('/^HTTP\/\S+\s+(\d{3})/', (string)$responseHeaders[0], $matches) === 1) {
            $statusCode = (int)$matches[1];
        }

        if ($statusCode !== 0) {
            return new HttpResponse($statusCode, $body, $this->extractHeaders($responseHeaders));
        }

        throw new ConnectionException(sprintf('Could not determine HTTP status code for %s.', $url));
    }

    /**
     * @param array<int, string> $responseHeaders
     * @return array<string, string>
     */
    private function extractHeaders(array $responseHeaders): array
    {
        $headers = [];
        foreach ($responseHeaders as $index => $headerLine) {
            if ($index === 0 || !is_string($headerLine)) {
                continue;
            }
            $separatorPos = strpos($headerLine, ':');
            if ($separatorPos === false) {
                continue;
            }
            $name = strtolower(trim(substr($headerLine, 0, $separatorPos)));
            $value = trim(substr($headerLine, $separatorPos + 1));
            $headers[$name] = $value;
        }
        return $headers;
    }
}
