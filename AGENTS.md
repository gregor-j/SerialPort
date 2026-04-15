# AGENTS Guide

## Project Purpose
- This library provides serial-device communication over streams; currently only TCP socket streams are implemented (`README.md`, `src/Streams/TcpSocket.php`).
- Typical runtime setup is external: map a physical serial device to TCP (e.g. pySerial `tcp_serial_redirect`) and talk to it through this library (`README.md`).

## Architecture You Need First
- Core flow is `Command -> Communication -> Stream` (`src/Interfaces/Command.php`, `src/Interfaces/Communication.php`, `src/Interfaces/Stream.php`).
- `SerialStreamCommunication` is the orchestrator: it writes command+terminator, then reads char-by-char until read terminator or timeout (`src/SerialStreamCommunication.php`).
- `BasicCommand` is the reference command implementation; it sets per-command timeout and returns `StringResponse` (`src/Commands/BasicCommand.php`).
- `StringResponse` trims the configured read terminator and exposes values as a PSR-11 container (`src/Responses/StringResponse.php`).
- `TcpSocket` is the concrete `Stream`, with lazy connection creation and explicit handling for partial/zero-byte writes (`src/Streams/TcpSocket.php`).
- `TcpSocketContainer` injects infra abstractions (`TcpSocketConnector`, `StreamIo`, `Clock`, `Error`) to keep socket behavior unit-testable (`src/Streams/TcpSocketContainer.php`).

## Data-Flow and Behavior Contracts
- `Communication::query($cmd, $writeTerminator, $readTerminator)` must append write terminator before send and include read terminator in raw read result (`src/SerialStreamCommunication.php`).
- Read loop in `SerialStreamCommunication` throws `TimeoutException` only when a non-empty read terminator was requested and not reached before timeout.
- If no read terminator is provided, reads stop on stream timeout and return collected bytes (no exception).
- `BasicCommand::__toString()` and response/log rendering use printable escaping via `ToString::fromString(...)`; keep this for non-printable bytes (`src/Commands/BasicCommand.php`, `src/Responses/StringResponse.php`).

## Project-Specific Conventions
- `declare(strict_types=1);` is used everywhere; keep strict scalar typing and explicit nullable defaults.
- Classes are mostly `final`; prefer extension via interfaces + composition, not inheritance.
- Parameter validation throws domain exceptions with stable, test-asserted messages (see timeout validation tests).
- Public APIs expose domain exceptions (`ConnectionException`, `WriteException`, `TimeoutException`, etc.) instead of raw PHP warnings/errors.
- Tests heavily assert exact exception messages; update tests together with wording changes.

## Dev Workflows (Verified)
- Run tests: `./vendor/bin/phpunit --testdox` (26 tests passing on this repo).
- Run static analysis: `./vendor/bin/phpstan analyse --no-progress` (level 9 via `phpstan.neon`).
- Run style checks: `./vendor/bin/phpcs` (PSR-12 via `.phpcs.xml`).
- Fix all PSR-12 style violations before merging; use `./vendor/bin/phpcbf` for auto-fixes, then re-run `./vendor/bin/phpcs`.
- Integration-ish tests rely on `tests/LocalTcpServer.php` and require `ext-pcntl` + `ext-posix` (Linux/Unix-style process control).

## High-Value Files To Read Before Changing Behavior
- `src/SerialStreamCommunication.php` (query/write/read semantics + logging)
- `src/Streams/TcpSocket.php` (connection lifecycle, timeout/write edge cases)
- `tests/SerialStreamCommiunicationTest.php` and `tests/Streams/TcpSocketTest.php` (authoritative behavioral expectations)
- `tests/LocalTcpServer.php` (how real socket IO is emulated in tests)
