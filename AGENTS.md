# AGENTS Guide

## Project Purpose
- This library provides serial-device communication over streams and HTTP(S) gateways (`README.md`, `src/StreamCommunication.php`, `src/HttpCommunication.php`).
- Typical runtime setup is external: map a physical serial device to TCP (e.g. pySerial `tcp_serial_redirect`) and talk to it through this library (`README.md`).

## Architecture You Need First
- Core flow is `Command -> Communication -> Stream` (`src/Interfaces/Command.php`, `src/Interfaces/Communication.php`, `src/Interfaces/Stream.php`).
- `StreamCommunication` is the stream orchestrator: it writes command+terminator, then reads char-by-char until read terminator or timeout (`src/StreamCommunication.php`).
- `HttpCommunication` is the HTTP orchestrator: it maps command/query inputs to a JSON gateway contract and returns decoded response bytes (`src/HttpCommunication.php`, `src/Interfaces/HttpTransport.php`).
- `BasicStringCommand` is the reference command implementation; it sets per-command timeout and returns `StringResponse` (`src/Commands/BasicStringCommand.php`).
- `StringResponse` trims the configured read terminator and exposes values as a PSR-11 container (`src/Responses/StringResponse.php`).
- `TcpStream` is the concrete `Stream`, with lazy connection creation and explicit handling for partial/zero-byte writes (`src/TcpStream.php`).
- `CurlTransport` is the primary concrete `HttpTransport`, implemented via cURL with separate connect/request timeouts (`src/CurlTransport.php`, `src/Http/NativeCurlIo.php`).
- `StreamWrapperTransport` is an alternative `HttpTransport` using PHP stream wrappers (`src/StreamWrapperTransport.php`, `src/Http/NativeStreamWrapperIo.php`).
- `TcpStreamContainer`, `CurlTransportContainer`, and `StreamWrapperTransportContainer` inject infra abstractions to keep transport behavior unit-testable (`src/Container/`).

## Data-Flow and Behavior Contracts
- `Communication::query($cmd, $writeTerminator, $readTerminator)` in `StreamCommunication` must append write terminator before send and include read terminator in raw read result (`src/StreamCommunication.php`).
- Read loop in `StreamCommunication` throws `TimeoutException` only when a non-empty read terminator was requested and not reached before timeout.
- If no read terminator is provided, stream reads stop on stream timeout and return collected bytes (no exception).
- `HttpCommunication::query(...)` sends JSON with base64 fields (`commandBase64`, `writeTerminatorBase64`, `readTerminatorBase64`) plus `deviceTimeoutMs`, `deviceId`, and `deviceType` (`src/HttpCommunication.php`, `tests/HttpCommunicationTest.php`).
- `HttpCommunication` treats gateway-level device timeout (`deviceTimedOut: true`) as `TimeoutException`, but transport/network failures remain `ConnectionException` from `HttpTransport`.
- `HttpCommunication` requires HTTP 2xx plus valid JSON and valid base64 `responseBase64`; otherwise it throws `UnexpectedResponseException` (`tests/HttpCommunicationTest.php`).
- `BasicStringCommand::__toString()` and response/log rendering use printable escaping via `ToString::fromString(...)`; keep this for non-printable bytes (`src/Commands/BasicStringCommand.php`, `src/Responses/StringResponse.php`).

## Project-Specific Conventions
- `declare(strict_types=1);` is used everywhere; keep strict scalar typing and explicit nullable defaults.
- Classes are mostly `final`; prefer extension via interfaces + composition, not inheritance.
- Parameter validation throws domain exceptions with stable, test-asserted messages (see timeout and endpoint validation tests).
- Public APIs expose domain exceptions (`ConnectionException`, `WriteException`, `TimeoutException`, `UnexpectedResponseException`, etc.) instead of raw PHP warnings/errors.
- Tests heavily assert exact exception messages; update tests together with wording changes.

### Naming Conventions
| Pattern          | Meaning                                                                       | Examples                                                                              |
|------------------|-------------------------------------------------------------------------------|---------------------------------------------------------------------------------------|
| `*Communication` | Orchestrates commands over a transport                                        | `StreamCommunication`, `HttpCommunication`                                            |
| `*Transport`     | HTTP transport implementation                                                 | `CurlTransport`, `StreamWrapperTransport`                                             |
| `*Stream`        | Socket stream implementation                                                  | `TcpStream`                                                                           |
| `*Command`       | Command producing a response                                                  | `BasicStringCommand`                                                                  |
| `*Response`      | Implements the `Response` interface; returned by a `Command`                  | `StringResponse`                                                                      |
| `*Container`     | PSR-11 DI container for a class                                               | `TcpStreamContainer`, `CurlTransportContainer`, `StreamWrapperTransportContainer`     |
| `*Io`            | Interface for native I/O operations                                           | `CurlIo`, `StreamWrapperIo`, `StreamIo`                                               |
| `*Connector`     | Interface for establishing a connection                                       | `TcpStreamConnector`                                                                  |
| `*Contract`      | Interface for a serialization contract                                        | `SerialGatewayContract`                                                               |
| `*Exception`     | Domain exception                                                              | `ConnectionException`, `TimeoutException`                                             |
| `Native*`        | Concrete implementation using PHP built-ins; name = `Native` + interface name | `NativeCurlIo`, `NativeStreamWrapperIo`, `NativeStreamIo`, `NativeTcpStreamConnector` |

## Dev Workflows (Verified)
- Run tests: `./vendor/bin/phpunit --testdox` (114 tests currently passing on this repo).
- Run static analysis: `./vendor/bin/phpstan analyse --no-progress` (level 9 via `phpstan.neon`).
- Fix PSR-12 style issues with `./vendor/bin/phpcbf` every time before presenting code changes (phpcbf-only workflow for faster iteration).
- Integration-ish stream tests rely on `tests/LocalTcpServer.php` and require `ext-pcntl` + `ext-posix` (Linux/Unix-style process control).

## High-Value Files To Read Before Changing Behavior
- `src/StreamCommunication.php` (stream query/write/read semantics + logging)
- `src/HttpCommunication.php` (HTTP gateway payload/response contract and timeout/error mapping)
- `src/TcpStream.php` (connection lifecycle, timeout/write edge cases)
- `src/CurlTransport.php` (primary HTTP transport – cURL-based, connect/request timeout handling)
- `src/StreamWrapperTransport.php` (alternative HTTP transport – PHP stream wrappers, status/header parsing)
- `tests/StreamCommunicationTest.php` and `tests/TcpStreamTest.php` (authoritative stream behavior expectations)
- `tests/HttpCommunicationTest.php`, `tests/CurlTransportTest.php`, and `tests/StreamWrapperTransportTest.php` (authoritative HTTP behavior expectations)
- `tests/LocalTcpServer.php` (how real socket IO is emulated in tests)
