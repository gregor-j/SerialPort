# SerialPort

[![License: MIT][license-mit]](LICENSE)

PHP class `SerialPort` to connect to serial ports using streams. Nothing more. Only socket TCP streams are implemented at the moment.

You need to create classes implementing `Command`, `Response` and `Value`. The implementations of these interfaces depend on the device you want to communicate with.

## Usage

Use [pySerial] to map a serial device to a TCP port.

Create a `TcpSocket` and pass it to a new `SerialPort` instance. Then implement a `Command` subclass and invoke it via `$command->invoke($serialPort)` to get either `null` or a `Response`.

A `Command` instance represents a string to send to a device via the `SerialPort`. Your implementation of `Command` needs to define the command string, its terminators, and how to read and parse the device's response into a `Response` object.

[pySerial]: https://pyserial.readthedocs.io/en/latest/examples.html
[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
