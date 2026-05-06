# SerialPort

[![License: MIT][license-mit]](LICENSE)
![PHPStan level][level]
![Code Style][psr]

PHP classes to connect to serial devices using streams or HTTP(S) gateways.

This library separates the command model from the transport:

- `Command` defines command payload, terminators, timeout, and response mapping
- `Communication` executes the command (`StreamCommunication` or `HttpCommunication`) via `query()` or `write()`
- transport is provided by either `Stream` (for sockets) or `HttpTransport` (for gateways)

`BasicStringCommand`, `BasicVoidCommand`, and `StringResponse` are reference implementations for common command/response patterns.

## Usage

You can either:

1. bridge a serial device to TCP (for example with [pySerial] `tcp_serial_redirect`) and use `TcpStream`
2. call an HTTP(S) serial gateway and use `HttpCommunication` with `CurlTransport` or `StreamWrapperTransport`

### TCP stream communication

```php
<?php

use GregorJ\SerialPort\Commands\BasicStringCommand;
use GregorJ\SerialPort\StreamCommunication;
use GregorJ\SerialPort\TcpStream;

$stream = new TcpStream('127.0.0.1', 5000);
$communication = new StreamCommunication($stream);

$command = new BasicStringCommand('HELLO', "\n", "\n");
$response = $command->invoke($communication);

echo $response?->get('response');
```

### HTTP gateway communication

```php
<?php

use GregorJ\SerialPort\Commands\BasicStringCommand;
use GregorJ\SerialPort\CurlTransport;
use GregorJ\SerialPort\HttpCommunication;

$communication = new HttpCommunication(
	new CurlTransport(),
	'https://example.com/query',
	'ttyUSB0',
	HttpCommunication::DEVICE_TYPE_WIRED
);

$command = new BasicStringCommand('HELLO', "\n", "\n");
$response = $command->invoke($communication);

echo $response?->get('response');
```

`HttpCommunication::setTimeout()` configures the serial-device response timeout and sends it to the gateway as `deviceTimeoutMs`.
HTTP transport timeouts (connect and request) are configured separately in `CurlTransport` and `StreamWrapperTransport`.

### Fire-and-forget commands

If your command does not expect a response, call `Communication::write()` directly (or use `BasicVoidCommand`, which now delegates to `write()`):

```php
<?php

use GregorJ\SerialPort\Commands\BasicVoidCommand;
use GregorJ\SerialPort\StreamCommunication;
use GregorJ\SerialPort\TcpStream;

$communication = new StreamCommunication(new TcpStream('127.0.0.1', 5000));
$command = new BasicVoidCommand('AT+RESET', "\r\n");
$command->invoke($communication);
```

For custom command types, implement `GregorJ\SerialPort\Interfaces\Command` and decide per command whether to call `query()` or `write()`.

For the expected HTTP JSON contract and fields, see `src/Http/JsonSerialGatewayContract.php`.

[pySerial]: https://pyserial.readthedocs.io/en/latest/examples.html
[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
[level]: https://img.shields.io/badge/dynamic/yaml?url=https%3A%2F%2Fgithub.com%2Fgregor-j%2FSerialPort%2Fraw%2Frefs%2Fheads%2Fmain%2Fphpstan.neon&query=%24.parameters.level&prefix=level%20&style=flat-square&label=PHPStan
[psr]: https://img.shields.io/badge/dynamic/xml?url=https%3A%2F%2Fgithub.com%2Fgregor-j%2FSerialPort%2Fraw%2Frefs%2Fheads%2Fmain%2F.phpcs.xml&query=%2F%2Fruleset%2Frule%5Bstarts-with(%40ref%2C%20'PSR')%5D%2F%40ref&style=flat-square&label=Code%20Style
