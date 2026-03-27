# SerialPort

[![License: MIT][license-mit]](LICENSE)

PHP class `SerialPort` to connect to serial ports using streams. Nothing more. Only socket TCP streams are implemented at the moment.

You need to create classes implementing `Command`, `Response` and `Value`. The implementations of these interfaces depend on the device you want to communicate with.

## Usage

Use [pySerial] to map a serial device to a TCP port.

Use `TcpSocket` to create a connection to a `SerialPort` use `SerialPort->invoke()` to invoke a `Command` and get either `null` or a `Response` containing at least one `Value`.

A `Command` instance is a string sent to a `SerialPort` instance. The `SerialPort` instance invokes the `Command` using its `Stream` instance.

Your implementation of `Command` needs to define how to read the string returned by the device and either return a `Response` containing at least one `Value`, or `null`.

[pySerial]: https://pyserial.readthedocs.io/en/latest/examples.html
[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
