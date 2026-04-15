# AI Prompt: Build an HTTP JSON Serial Gateway for `HttpCommunication`

Use this prompt with a coding model to generate a complete service implementation.

---

You are a senior backend engineer. Build a production-ready HTTP JSON gateway service that is fully compatible with the PHP class `HttpCommunication` from the `gregorj/SerialPort` library.

## Goal
Implement a web service that receives JSON requests containing base64-encoded serial command data, communicates with a serial device backend, and responds with JSON in the exact contract expected by `HttpCommunication`.

## Required API Contract

### Endpoint
- `POST /query`
- Content-Type: `application/json`
- Accept: `application/json`

### Request JSON schema (all fields required)
```json
{
  "commandBase64": "string(base64)",
  "writeTerminatorBase64": "string(base64)",
  "readTerminatorBase64": "string(base64)",
  "deviceTimeoutMs": "integer",
  "deviceId": "string",
  "deviceType": "bluetooth|wired"
}
```

### Meaning
- `commandBase64`: base64 bytes of the command (without write terminator)
- `writeTerminatorBase64`: base64 bytes appended before sending
- `readTerminatorBase64`: base64 bytes to detect end of response
- `deviceTimeoutMs`: device read timeout in milliseconds
- `deviceId`: identifier of the selected serial target device (for example `ttyUSB0`, `BT-COM7`)
- `deviceType`: transport category of selected device, allowed values: `bluetooth`, `wired`

### Success response (HTTP 200)
```json
{
  "responseBase64": "string(base64)"
}
```
- `responseBase64` must contain the raw response bytes as base64 (including read terminator if the backend returns it).

### Device timeout response (HTTP 200)
```json
{
  "deviceTimedOut": true,
  "partialResponseBase64": "string(base64, optional)"
}
```
- Use this when no full response is available within `deviceTimeoutMs`.
- `partialResponseBase64` is optional and should contain already received bytes.

## Compatibility Requirements (must match PHP behavior)
1. Request/response body format must be valid JSON.
2. Base64 decoding must be strict; invalid input should produce a JSON error response.
3. For a normal successful device read, return HTTP 200 with `responseBase64`.
4. For a device read timeout, return HTTP 200 with `deviceTimedOut: true`.
5. Do not require any additional mandatory request fields.
6. Keep payload field names exactly as specified.
7. Validate `deviceType` strictly (`bluetooth` or `wired`) and reject unsupported values.

## Backend behavior to implement
- Provide an abstraction for device I/O so that real serial transport can be replaced/mocked.
- Include one real transport implementation over TCP socket (for scenarios where serial is bridged to TCP, e.g. `tcp_serial_redirect`).
- Read loop behavior:
  - send `command + writeTerminator`
  - read bytes until:
    - `readTerminator` matched, OR
    - timeout reached
  - if no read terminator is configured, read until timeout and return collected bytes

## Error handling
- Return JSON error objects for malformed JSON or invalid base64.
- Suggested status codes:
  - 400 for invalid request payload
  - 502/503 for backend connectivity failures
  - 500 for unexpected internal errors
- Always return machine-readable JSON errors with fields like:
```json
{
  "error": {
    "code": "string",
    "message": "string"
  }
}
```

## Non-functional requirements
- Structured logging (request id, duration, backend target, outcome)
- Configuration via environment variables
- Reasonable defaults and timeout limits
- Clean architecture (API layer, service layer, transport layer)
- Strong input validation

## Deliverables (generate all of these)
1. Full runnable source code for the service
2. Unit tests for core logic
3. Integration test for `POST /query`
4. `README.md` with:
   - quick start
   - environment variables
   - sample requests/responses
5. Dependency manifest (`requirements.txt` or equivalent)
6. Optional Docker setup (`Dockerfile`, `docker-compose.yml`)

## Acceptance examples (must work)

### Example request
```json
{
  "commandBase64": "SEVMTE8=",
  "writeTerminatorBase64": "Cg==",
  "readTerminatorBase64": "DQo=",
  "deviceTimeoutMs": 1200,
  "deviceId": "ttyUSB0",
  "deviceType": "wired"
}
```
(Represents command `HELLO`, write terminator `\n`, read terminator `\r\n`.)

Send this payload to `POST /query`.

### Example success response
```json
{
  "responseBase64": "V09STEQNCg=="
}
```
(Represents `WORLD\r\n`.)

### Example timeout response
```json
{
  "deviceTimedOut": true,
  "partialResponseBase64": "V08="
}
```
(Represents partial response `WO`.)

## Output format for your answer
- First: architecture summary
- Then: complete code files
- Then: tests
- Then: run instructions
- Then: example curl commands

---

Important: prioritize strict contract compatibility with the PHP `HttpCommunication` client over framework-specific conventions.
