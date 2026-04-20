<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Interfaces\Response;
use GregorJ\ToString\ToString;

use function explode;
use function str_contains;

/**
 * Plain string response.
 */
final class StringResponse implements Response
{
    private string $response;

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
        $this->response = $response;
    }

    /**
     * Get the plain response.
     * Beware of non-printable characters. If you want only printable characters, cast this class to string.
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return ToString::fromString($this->response);
    }
}
