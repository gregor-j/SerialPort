<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Interfaces\Stream\TcpSocketConnector;

use function fsockopen;

/**
 * Native connector based on fsockopen().
 */
final class NativeTcpSocketConnector implements TcpSocketConnector
{
    /**
     * Open Internet or Unix domain socket connection.
     * @link https://php.net/manual/en/function.fsockopen.php
     * @param string $hostname  If you have compiled in OpenSSL support, you may prefix the hostname with either ssl://
     * or tls:// to use an SSL or TLS client connection over TCP/IP to connect to the remote host.
     * @param int $port The port number.
     * @param int &$error_code [optional] If provided, holds the system level error number that occurred in the
     * system-level connect() call. If the value returned in errno is 0 and the function returned false, it is an
     * indication that the error occurred before the connect() call. This is most likely due to a problem initializing
     * the socket.
     * @param string &$error_message [optional] The error message as a string.
     * @param float|null $timeout [optional] The connection timeout, in seconds. If you need to set a timeout for
     * reading/writing data over the socket, use stream_set_timeout, as the timeout parameter to fsockopen only applies
     * while connecting the socket.
     * @return resource|false fsockopen returns a file pointer which may be used together with the other file functions
     * (such as fgets, fgetss, fwrite, fclose, and feof). If the call fails, it will return false
     */
    public function connect(string $hostname, int $port, int &$error_code, string &$error_message, float|null $timeout)
    {
        return @fsockopen($hostname, $port, $error_code, $error_message, $timeout);
    }
}
