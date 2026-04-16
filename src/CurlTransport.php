<?php

declare(strict_types=1);

namespace GregorJ\SerialPort;

use CurlHandle;
use GregorJ\SerialPort\Container\CurlTransportContainer;
use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\ContainerException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Http\HttpResponse;
use GregorJ\SerialPort\Interfaces\Http\CurlIo;
use GregorJ\SerialPort\Interfaces\HttpTransport;
use GregorJ\SerialPort\Interfaces\System\Error;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function array_filter;
use function explode;
use function is_array;
use function is_string;
use function sprintf;
use function strtolower;
use function strpos;
use function substr;
use function trim;

/**
 * HTTP transport using cURL.
 */
final class CurlTransport implements HttpTransport
{
    public const DEFAULT_CONNECT_TIMEOUT = 2.0;
    public const DEFAULT_REQUEST_TIMEOUT = 5.0;

    private float $connectTimeout;
    private float $requestTimeout;
    private CurlIo $curlIo;
    private Error $error;

    /**
     * @param float $connectTimeoutSeconds
     * @param float $requestTimeoutSeconds
     * @param ContainerInterface|null $dependencies
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        float $connectTimeoutSeconds = self::DEFAULT_CONNECT_TIMEOUT,
        float $requestTimeoutSeconds = self::DEFAULT_REQUEST_TIMEOUT,
        ContainerInterface $dependencies = null
    ) {
        if ($connectTimeoutSeconds < 0.0) {
            throw new InvalidParamException('HTTP transport connect timeout for HttpCommunication has to be positive.');
        }
        $this->connectTimeout = $connectTimeoutSeconds;

        if ($requestTimeoutSeconds < 0.0) {
            throw new InvalidParamException('HTTP transport request timeout for HttpCommunication has to be positive.');
        }
        $this->requestTimeout = $requestTimeoutSeconds;

        $dependencies = $dependencies ?? new CurlTransportContainer();
        $this->curlIo = $this->resolveDependency($dependencies, CurlIo::class);
        $this->error = $this->resolveDependency($dependencies, Error::class);
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
    public function postJson(string $url, string $jsonPayload): HttpResponse
    {
        $this->error->clearLastError();

        $handle = $this->curlIo->init($url);
        if ($handle === false) {
            throw new ConnectionException(sprintf('HTTP request to %s failed: %s', $url, $this->buildFallbackErrorMessage()));
        }

        try {
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Connection: close',
            ];

            $options = [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_CONNECTTIMEOUT_MS => (int)($this->connectTimeout * 1000),
                CURLOPT_TIMEOUT_MS => (int)($this->requestTimeout * 1000),
            ];

            if (!$this->curlIo->setOptArray($handle, $options)) {
                throw new ConnectionException(sprintf('HTTP request to %s failed: %s', $url, $this->buildCurlErrorMessage($handle)));
            }

            $rawResponse = $this->curlIo->exec($handle);
            if ($rawResponse === false) {
                throw new ConnectionException(sprintf('HTTP request to %s failed: %s', $url, $this->buildCurlErrorMessage($handle)));
            }

            /** @var int $statusCode */
            $statusCode = $this->curlIo->getInfo($handle, CURLINFO_RESPONSE_CODE);
            if ($statusCode === 0) {
                throw new ConnectionException(sprintf('Could not determine HTTP status code for %s.', $url));
            }

            /** @var int $headerSize */
            $headerSize = $this->curlIo->getInfo($handle, CURLINFO_HEADER_SIZE);
            if ($headerSize <= 0) {
                return new HttpResponse($statusCode, $rawResponse, []);
            }

            $headerRaw = substr($rawResponse, 0, $headerSize);
            $body = substr($rawResponse, $headerSize);

            return new HttpResponse(
                $statusCode,
                $body,
                $this->extractHeaders($headerRaw)
            );
        } finally {
            $this->curlIo->close($handle);
        }
    }

    /**
     * @param CurlHandle $handle
     * @return string
     */
    private function buildCurlErrorMessage(CurlHandle $handle): string
    {
        $errorNumber = $this->curlIo->getErrNo($handle);
        $errorMessage = $this->curlIo->getError($handle);

        if ($errorNumber !== 0 || $errorMessage !== '') {
            return sprintf('cURL error %d: %s', $errorNumber, $errorMessage);
        }

        return $this->buildFallbackErrorMessage();
    }

    /**
     * @return string
     */
    private function buildFallbackErrorMessage(): string
    {
        $lastError = $this->error->getLastError();
        if (!is_array($lastError)) {
            return 'Unknown error.';
        }

        $message = $lastError['message'] ?? null;
        if (!is_string($message) || $message === '') {
            return 'Unknown error.';
        }

        return $message;
    }

    /**
     * @param string $rawHeaders
     * @return array<string, string>
     */
    private function extractHeaders(string $rawHeaders): array
    {
        $headerBlocks = array_filter(explode("\r\n\r\n", $rawHeaders), static fn(string $block): bool => $block !== '');
        if ($headerBlocks === []) {
            return [];
        }

        $lastHeaderBlock = (string)array_pop($headerBlocks);
        $headerLines = explode("\r\n", $lastHeaderBlock);

        $headers = [];
        foreach ($headerLines as $index => $headerLine) {
            $separatorPos = strpos($headerLine, ':');
            if ($index === 0 || $separatorPos === false) {
                continue;
            }

            $name = strtolower(trim(substr($headerLine, 0, $separatorPos)));
            $value = trim(substr($headerLine, $separatorPos + 1));
            $headers[$name] = $value;
        }

        return $headers;
    }
}
