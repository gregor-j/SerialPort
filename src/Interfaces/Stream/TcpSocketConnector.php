<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\Stream;

/**
 * Abstraction for opening a TCP socket stream.
 */
interface TcpSocketConnector
{
    /**
     * Open Internet or Unix domain socket connection.
     * @param string $hostname
     * @param int $port
     * @param int &$error_code
     * @param string &$error_message
     * @param float|null $timeout
     * @return resource|false
     */
    public function connect(string $hostname, int $port, int &$error_code, string &$error_message, float|null $timeout);
}
