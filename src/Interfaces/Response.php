<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces;

use Psr\Container\ContainerInterface;

/**
 * The command defines which Response will be returned. The Response decides
 * how to interpret the raw response of the Command.
 * In case the constructor of the Response fails to interpret the raw response
 * of the Command, throw an UnexpectedResponseException.
 */
interface Response extends ContainerInterface
{
    /**
     * Transform the Response to a printable string for logging.
     * Non-printable characters are expected to be displayed as printable!
     * @return string
     */
    public function __toString(): string;
}
