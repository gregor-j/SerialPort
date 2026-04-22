# Contributing

Thanks for contributing to SerialPort.

## Scope and architecture

Before changing behavior, read these files:

- `src/StreamCommunication.php`
- `src/HttpCommunication.php`
- `src/TcpStream.php`
- `src/CurlTransport.php`
- `src/StreamWrapperTransport.php`
- `src/Http/JsonSerialGatewayContract.php`
- `tests/StreamCommunicationTest.php`
- `tests/HttpCommunicationTest.php`
- `tests/TcpStreamTest.php`

Core flow:

- `Command -> Communication -> Stream` (stream mode)
- `Command -> Communication -> HttpTransport` (HTTP gateway mode)

## Coding rules

- Use `declare(strict_types=1);` in PHP files.
- Prefer `final` classes and composition over inheritance.
- In namespaced files, import native/global functions with `use function ...;`.
- Do not use namespace fallback function calls.
- Keep exception messages stable when possible; tests assert exact wording.
- Throw domain exceptions (`ConnectionException`, `WriteException`, `TimeoutException`, `UnexpectedResponseException`, etc.) instead of leaking PHP warnings/errors.

## Behavior expectations

- `StreamCommunication::query(...)` must append the write terminator before sending.
- Stream reads are character-based and include the read terminator in raw collected bytes.
- Timeout exceptions in stream mode are raised only when a non-empty read terminator is requested and not reached in time.
- `HttpCommunication::query(...)` sends base64 fields (`commandBase64`, `writeTerminatorBase64`, `readTerminatorBase64`) and device metadata (`deviceTimeoutMs`, `deviceId`, `deviceType`).
- HTTP gateway `deviceTimedOut: true` maps to `TimeoutException`.
- Non-2xx responses, invalid JSON, or invalid `responseBase64` map to `UnexpectedResponseException`.

## Local quality checks

Run all checks before opening a PR:

```bash
./vendor/bin/phpunit --testdox
./vendor/bin/phpunit --filter UseFunctionImportConventionTest
./vendor/bin/phpstan analyse --no-progress
./vendor/bin/phpcs --standard=.phpcs.xml src tests
```

Composer script equivalents:

```bash
composer run test
composer run test:conventions
composer run analyse
composer run cs
```

## Pull request checklist

- Keep changes focused and minimal.
- Add or update tests for behavior changes.
- Preserve backward compatibility unless explicitly changing API behavior.
- Update documentation if usage or behavior changes.
