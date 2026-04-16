# SerialPort

[![License: MIT][license-mit]](LICENSE)

PHP classes to connect to serial devices using streams or HTTP(S) gateways.

This library separates the command model from the transport:

- `Command` defines command payload, terminators, timeout, and response mapping
- `Communication` executes the command (`SerialStreamCommunication` or `HttpCommunication`)
- transport is provided by either `Stream` (for sockets) or `HttpTransport` (for gateways)

## Usage

You can either:

1. bridge a serial device to TCP (for example with [pySerial] `tcp_serial_redirect`) and use `Streams\TcpSocket`
2. call an HTTP(S) serial gateway and use `HttpCommunication`

### TCP stream communication

```php
<?php

use GregorJ\SerialPort\Commands\BasicCommand;use GregorJ\SerialPort\StreamCommunication;use GregorJ\SerialPort\TcpStream;

$stream = new TcpStream('127.0.0.1', 5000);
$communication = new StreamCommunication($stream);

$command = new BasicCommand('HELLO', "\n", "\n");
$response = $command->invoke($communication);

echo $response?->get('response');
```

### HTTP gateway communication

```php
<?php

use GregorJ\SerialPort\Commands\BasicCommand;
use GregorJ\SerialPort\CurlTransport;
use GregorJ\SerialPort\HttpCommunication;

$communication = new HttpCommunication(
	new CurlTransport(),
	'https://example.com/query',
	'ttyUSB0',
	HttpCommunication::DEVICE_TYPE_WIRED
);

$command = new BasicCommand('HELLO', "\n", "\n");
$response = $command->invoke($communication);

echo $response?->get('response');
```

`HttpCommunication::setTimeout()` configures the serial-device response timeout and sends it to the gateway as `deviceTimeoutMs`.
HTTP transport timeouts (connect and request) are configured separately in `CurlTransport`.

For the expected HTTP JSON contract and fields, see `src/Http/JsonSerialGatewayContract.php` and `AI_PROMPT_HTTP_SERIAL_GATEWAY.md`.

[pySerial]: https://pyserial.readthedocs.io/en/latest/examples.html
[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
