<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Response;

use function array_key_exists;
use function explode;
use function str_contains;

/**
 * Plain string response.
 * @package GregorJ\SerialPort
 * @author  Gregor J.
 */
final class StringResponse implements Response
{
    public const RESPONSE = 'response';
    public const RAW_RESPONSE = 'raw_response';

    /**
     * @var array<string, string>
     */
    private array $response;

    /**
     * @param string $response
     * @param string $readTerminator
     */
    public function __construct(string $response, string $readTerminator = '')
    {
        $this->response[self::RAW_RESPONSE] = $response;
        if ($readTerminator !== '' && str_contains($response, $readTerminator)) {
            $parts = explode($readTerminator, $response);
            $response = $parts[0];
        }
        $this->response[self::RESPONSE] = $response;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): string
    {
        if (!$this->has($name)) {
            throw new NotFoundException(sprintf('StringResponse "%s" not found.', $name));
        }
        return $this->response[$name];
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->response);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->response[self::RESPONSE] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getRawResponse(): string
    {
        return $this->response[self::RAW_RESPONSE];
    }
}
