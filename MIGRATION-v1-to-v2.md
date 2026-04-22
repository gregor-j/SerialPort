# Migration Guide: v1.1.0 → v2.0.0

This guide covers every breaking change and shows how to adapt existing code.

---

## Table of Contents

1. [Renamed classes](#1-renamed-classes)
2. [`Communication` interface – `write()` / `read()` → `query()`](#2-communication-interface--write--read--query)
3. [`Stream` interface – removed methods](#3-stream-interface--removed-methods)
4. [`Response` interface – no longer a container](#4-response-interface--no-longer-a-container)
5. [`StringResponse` – API change](#5-stringresponse--api-change)
6. [Exception hierarchy flattened](#6-exception-hierarchy-flattened)
7. [`TimeoutException` – removed `getPartialResponse()`](#7-timeoutexception--removed-getpartialresponse)
8. [`UnexpectedResponseException` – removed `getRawResponse()`](#8-unexpectedresponseexception--removed-getrawresponse)
9. [Removed classes](#9-removed-classes)
10. [New: HTTP communication stack](#10-new-http-communication-stack)
11. [New: `BasicVoidCommand`](#11-new-basicvoidcommand)
12. [New: PSR-11 container exceptions](#12-new-psr-11-container-exceptions)

---

## 1. Renamed classes

| v1.1.0                                     | v2.0.0                                           |
|--------------------------------------------|--------------------------------------------------|
| `GregorJ\SerialPort\SerialPort`            | `GregorJ\SerialPort\StreamCommunication`         |
| `GregorJ\SerialPort\Commands\BasicCommand` | `GregorJ\SerialPort\Commands\BasicStringCommand` |
| `GregorJ\SerialPort\Streams\TcpSocket`     | `GregorJ\SerialPort\TcpStream`                   |

**Before:**

```php
use GregorJ\SerialPort\SerialPort;
use GregorJ\SerialPort\Commands\BasicCommand;
use GregorJ\SerialPort\Streams\TcpSocket;

$stream = new TcpSocket('192.168.1.10', 9090);
$stream->open();
$serial = new SerialPort($stream);
$cmd    = new BasicCommand('*IDN?', "\n", "\r");
```

**After:**

```php
use GregorJ\SerialPort\StreamCommunication;
use GregorJ\SerialPort\Commands\BasicStringCommand;
use GregorJ\SerialPort\TcpStream;

$stream = new TcpStream('192.168.1.10', 9090);   // lazy connect – no open() call needed
$serial = new StreamCommunication($stream);
$cmd    = new BasicStringCommand('*IDN?', "\n", "\r");
```

---

## 2. `Communication` interface – `write()` / `read()` → `query()`

The two separate methods `write()` and `read()` have been merged into a single
`query()` call. If you call `Communication` directly (instead of going through
a `Command`), update your call sites as follows.

**Before:**

```php
$communication->write('*IDN?', "\n");
$response = $communication->read("\r");
```

**After:**

```php
$response = $communication->query('*IDN?', "\n", "\r");
```

The return value of `query()` is the raw response string **including** the read
terminator. Use `StringResponse` to strip it:

```php
use GregorJ\SerialPort\Responses\StringResponse;

$raw      = $communication->query('*IDN?', "\n", "\r");
$response = new StringResponse($raw, "\r\n");
echo $response->getResponse(); // trimmed value
```

If you implement the `Communication` interface yourself, remove `write()` and
`read()` from your class and add `query()`:

```php
public function query(string $string, string $writeTerminator = '', string $readTerminator = ''): string
{
    // ...
}
```

### `setTimeout()` return type

`setTimeout()` now returns `void` (previously `bool` in some implementations).
Remove any code that checks the return value.

---

## 3. `Stream` interface – removed methods

The following methods were removed from `Stream`:

| Removed method            | What to do                                                            |
|---------------------------|-----------------------------------------------------------------------|
| `isOpen(): bool`          | Connection is established lazily – no manual open/close cycle needed. |
| `open(): void`            | Remove the call; `TcpStream` connects on first use.                   |
| `close(): void`           | Remove the call; `TcpStream` closes in `__destruct()`.                |
| `setBlocking(bool): bool` | Blocking mode is set internally by `TcpStream`.                       |
| `getStatus(): Response`   | Use `timedOut()` for timeout detection.                               |

If you implement the `Stream` interface yourself, remove those methods from
your class.

---

## 4. `Response` interface – no longer a container

`Response` used to expose PSR-11-style `get(string)` / `has(string)` accessors.
These have been removed. The interface now only requires `__toString()` for
logging.

**Before:**

```php
if ($response->has(StringResponse::RESPONSE)) {
    $value = $response->get(StringResponse::RESPONSE);
}
$raw = $response->getRawResponse();
```

**After:**

```php
$value = $response->getResponse();   // StringResponse-specific method
// getRawResponse() is gone; the trimmed value is the only output
```

If you implement `Response` yourself, you only need to keep `__toString()`.
Remove `get()`, `has()`, and `getRawResponse()` from your class.

---

## 5. `StringResponse` – API change

| Removed                                 | Replacement             |
|-----------------------------------------|-------------------------|
| `StringResponse::RESPONSE` constant     | –                       |
| `StringResponse::RAW_RESPONSE` constant | –                       |
| `get(string $name): string`             | `getResponse(): string` |
| `has(string $name): bool`               | –                       |
| `getRawResponse(): string`              | –                       |

**Before:**

```php
$response = $cmd->invoke($communication);
$value    = $response->get(StringResponse::RESPONSE);
$raw      = $response->getRawResponse();
```

**After:**

```php
$response = $cmd->invoke($communication);
$value    = $response->getResponse();
// raw response (with terminator) is no longer stored separately
```

---

## 6. Exception hierarchy flattened

All domain exceptions previously extended internal base classes
(`RuntimeException`, `LogicException`) that were part of this library's
namespace. Those base classes are now removed; every exception extends PHP's
built-in `\Exception` directly.

**Removed base classes:**

- `GregorJ\SerialPort\Exceptions\RuntimeException`
- `GregorJ\SerialPort\Exceptions\LogicException`

**Removed exception class:**

- `GregorJ\SerialPort\Exceptions\InvalidValueException` →
  use `InvalidParamException` instead.
- `GregorJ\SerialPort\Exceptions\ReadException` →
  use `ConnectionException` or `TimeoutException` depending on the situation.

**Update catch blocks:**

```php
// Before
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\ReadException;

try {
    $cmd->invoke($communication);
} catch (InvalidValueException $e) {
    // ...
} catch (ReadException $e) {
    // ...
}

// After
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\ConnectionException;

try {
    $cmd->invoke($communication);
} catch (InvalidParamException $e) {
    // ...
} catch (TimeoutException $e) {
    // ...
} catch (ConnectionException $e) {
    // ...
}
```

If you type-hint against the old base classes, update to `\Exception`:

```php
// Before
} catch (\GregorJ\SerialPort\Exceptions\RuntimeException $e) {

// After
} catch (\Exception $e) {
```

---

## 7. `TimeoutException` – removed `getPartialResponse()`

The `$partialResponse` constructor parameter and `getPartialResponse()` method
have been removed. Partial response data is now logged via
`Communication::getLog()` instead.

**Before:**

```php
} catch (TimeoutException $e) {
    echo $e->getPartialResponse();
}
```

**After:**

```php
} catch (TimeoutException $e) {
    // inspect the log for the partial response
    $log = $communication->getLog();
    echo end($log); // last entry contains: 'read timed out. partial response "..."'
}
```

---

## 8. `UnexpectedResponseException` – removed `getRawResponse()`

The `$rawResponse` constructor parameter and `getRawResponse()` method have
been removed.

**Before:**

```php
} catch (UnexpectedResponseException $e) {
    var_dump($e->getRawResponse());
}
```

**After:**

Remove calls to `getRawResponse()`. Raw context is available via
`$e->getMessage()` or the communication log.

---

## 9. Removed classes

| Removed class                                         | Replacement                                |
|-------------------------------------------------------|--------------------------------------------|
| `GregorJ\SerialPort\SerialPort`                       | `GregorJ\SerialPort\StreamCommunication`   |
| `GregorJ\SerialPort\Streams\TcpSocket`                | `GregorJ\SerialPort\TcpStream`             |
| `GregorJ\SerialPort\Responses\TcpSocketStatus`        | Use `Stream::timedOut()` directly          |
| `GregorJ\SerialPort\Exceptions\InvalidValueException` | `InvalidParamException`                    |
| `GregorJ\SerialPort\Exceptions\ReadException`         | `ConnectionException` / `TimeoutException` |
| `GregorJ\SerialPort\Exceptions\RuntimeException`      | `\Exception`                               |
| `GregorJ\SerialPort\Exceptions\LogicException`        | `\Exception`                               |

---

## 10. New: HTTP communication stack

v2.0.0 introduces a full HTTP gateway stack to communicate with serial devices
via an HTTP(S) endpoint (e.g. a `pySerial` bridge server). It shares the same
`Communication` interface, so existing command/response code works unchanged.

```php
use GregorJ\SerialPort\HttpCommunication;
use GregorJ\SerialPort\CurlTransport;
use GregorJ\SerialPort\Commands\BasicStringCommand;

$transport     = new CurlTransport(connectTimeoutSeconds: 2.0, requestTimeoutSeconds: 10.0);
$communication = new HttpCommunication(
    httpTransport: $transport,
    endpoint:      'https://gateway.example.com/serial',
    deviceId:      'ttyUSB0',
    deviceType:    HttpCommunication::DEVICE_TYPE_WIRED,
);

$cmd      = new BasicStringCommand('*IDN?', "\r\n", "\r\n");
$response = $cmd->invoke($communication);
echo $response->getResponse();
```

Use `StreamWrapperTransport` as a drop-in replacement for `CurlTransport` if
the `ext-curl` extension is unavailable:

```php
use GregorJ\SerialPort\StreamWrapperTransport;

$transport = new StreamWrapperTransport();
```

---

## 11. New: `BasicVoidCommand`

Use `BasicVoidCommand` for commands that produce no meaningful response. It
throws `UnexpectedResponseException` if any unexpected characters appear in the
response.

```php
use GregorJ\SerialPort\Commands\BasicVoidCommand;

$cmd = new BasicVoidCommand('RESET', "\r\n", "\r\n");
$cmd->invoke($communication); // returns null
```

---

## 12. New: PSR-11 container exceptions

`NotFoundException` now implements `Psr\Container\NotFoundExceptionInterface`.
A new `ContainerException` class implements
`Psr\Container\ContainerExceptionInterface`. These are only relevant if you
build custom `ContainerInterface` implementations for dependency injection into
`TcpStream`, `CurlTransport`, or `StreamWrapperTransport`.
