# SerialPort

[![License: MIT][license-mit]](LICENSE)

PHP classes to connect to serial ports using streams or HTTP(S) gateways.

You need to create classes implementing `Command` and `Response`.
The implementations of these interfaces depend on the device you want to communicate with.

## Usage

Use [pySerial] to map a serial device to a TCP port.

Create a `Streams\TcpSocket` class and pass it to a new `SerialStreamCommunication` instance.
Alternatively use `HttpCommunication` with a `Interfaces\Http\HttpTransport` implementation such as `Http\NativeHttpTransport`.
Then implement a `Command` class and invoke it via `$command->invoke($serialPort)` to get either `null` or a `Response`.

A `Command` instance represents a string to send to a device using a `Communication` instance.
Your implementation of `Command` needs to define the command string, its terminators, and how to read and parse the device's response into a `Response` object.

When using `HttpCommunication`, `Communication::setTimeout()` is interpreted as device response timeout and sent to the gateway.
HTTP transport timeouts are configured separately in the `HttpCommunication` constructor.

[pySerial]: https://pyserial.readthedocs.io/en/latest/examples.html
[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
