<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Response;
use GregorJ\ToString\ToString;

use function array_key_exists;
use function explode;
use function str_contains;

/**
 * Plain string response.
 */
final class StringResponse implements Response
{
    public const RESPONSE = 'response';

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
            throw new NotFoundException(sprintf('StringResponse "%s" not found.', ToString::fromString($name)));
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
        $response = $this->response[self::RESPONSE] ?? '';
        return ToString::fromString($response);
    }
}
