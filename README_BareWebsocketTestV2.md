# BareWebsocketTestV2 - Comprehensive Testing Tool

## Overview

`BareWebsocketTestV2` is a comprehensive testing tool for the BareWebsocketServerV2 implementation. It tests all connection types, message handling, and server functionality to ensure the V2 server is working correctly.

## Features

- **Connection Testing**: Tests all 6 connection types supported by V2 server
- **Message Testing**: Validates message handling for different message types
- **Real-time Monitoring**: Tracks connection status, message counts, and response times
- **Error Detection**: Identifies and reports connection/message errors
- **Comprehensive Reporting**: Detailed test results with pass/fail status

## Supported Connection Types

1. **WebClient** (`webclient`) - Web browser connections
2. **Dashboard** (`dashboard`) - Administrative dashboard connections  
3. **OpenAI** (`openai`) - OpenAI assistant connections
4. **Twilio** (`twilio`) - Twilio phone call connections
5. **Monitor** (`monitor`) - Call monitoring connections
6. **Remote Richbot** (`remote_richbot`) - Remote richbot system connections

## Supported Message Types

- **Basic Messages**: `text`, `media`, `dtmf`, `heartbeat`
- **Control Messages**: `control`, `status`, `command`, `broadcast`
- **Query Messages**: `get_all_clients`, `get_all_rooms`, `get_room_status`
- **OpenAI Messages**: Session, conversation, audio buffer, and response events

## Usage

### Prerequisites

1. **Start the V2 Server**:
   ```bash
   php artisan bare:serverv2
   ```

2. **Ensure Dependencies** (React, Ratchet):
   ```bash
   composer require react/socket ratchet/pawl
   ```

### Basic Usage

**Test All Connection Types**:
```bash
php artisan bare:test-v2 --test-all
```

**Test Specific Connection Types**:
```bash
# Test dashboard connection only
php artisan bare:test-v2 --test-dashboard

# Test OpenAI and Twilio connections
php artisan bare:test-v2 --test-openai --test-twilio

# Test all connections (equivalent to --test-all)
php artisan bare:test-v2 --test-connections
```

**Test Message Handling**:
```bash
php artisan bare:test-v2 --test-messages
```

### Advanced Options

**Custom Server Configuration**:
```bash
# Test against different host/port
php artisan bare:test-v2 --host=localhost --port=9503

# Test without SSL (for development)
php artisan bare:test-v2 --no-ssl

# Use custom API token
php artisan bare:test-v2 --api-token=your-custom-token
```

**Verbose Output**:
```bash
# Get detailed logging and error information
php artisan bare:test-v2 --test-all --verbose

# Or use the shorthand
php artisan bare:test-v2 --test-all -v
```

### Command Options

| Option | Description | Default |
|--------|-------------|---------|
| `--host` | Server hostname | `richbot9000.com` |
| `--port` | Server port | `9502` |
| `--api-token` | API token for authenticated connections | Auto-generated |
| `--test-all` | Run all tests | `false` |
| `--test-connections` | Test all connection types | `false` |
| `--test-messages` | Test message handling | `false` |
| `--test-webclient` | Test webclient connection | `false` |
| `--test-dashboard` | Test dashboard connection | `false` |
| `--test-openai` | Test OpenAI connection | `false` |
| `--test-twilio` | Test Twilio connection | `false` |
| `--test-monitor` | Test monitor connection | `false` |
| `--test-richbot` | Test remote richbot connection | `false` |
| `-v, --verbose` | Enable verbose logging (Laravel built-in) | `false` |
| `--no-ssl` | Disable SSL for testing | `false` |

## Example Test Output

```
Starting BareWebsocketServerV2 Comprehensive Tests
=============================================
Host: richbot9000.com
Port: 9502
Test Room: test-room-abc123
SSL: Enabled
API Token: test-token...
=============================================

✅ webclient connection successful
✅ dashboard connection successful  
✅ openai connection successful
🔌 twilio connection closed normally
❌ monitor connection failed: Connection refused

================================================================================
BareWebsocketServerV2 Test Results
================================================================================
✅ WebClient Connection Test    | COMPLETED  |   2.15s |  3 msgs sent |  2 msgs recv |  0 errors
✅ Dashboard Connection Test    | COMPLETED  |   1.98s |  5 msgs sent |  3 msgs recv |  0 errors
✅ OpenAI Connection Test       | COMPLETED  |   2.33s |  4 msgs sent |  4 msgs recv |  0 errors
✅ Twilio Connection Test       | COMPLETED  |   1.87s |  4 msgs sent |  0 msgs recv |  0 errors
❌ Monitor Connection Test      | FAILED     |   0.05s |  0 msgs sent |  0 msgs recv |  1 errors
✅ Remote Richbot Connection Test| COMPLETED  |   2.01s |  3 msgs sent |  1 msgs recv |  0 errors
--------------------------------------------------------------------------------
Total: 6 tests | Passed: 5 | Failed: 1 | Duration: 10.39s

✅ Most tests passed successfully!
================================================================================
```

## Test Scenarios

### 1. Connection Validation
- Establishes WebSocket connections using correct URL patterns
- Validates SSL/TLS handshake
- Tests API token authentication
- Verifies room joining functionality

### 2. Message Exchange
- Sends test messages appropriate for each connection type
- Validates message format and structure
- Tests bidirectional communication
- Verifies message routing and broadcasting

### 3. Error Handling
- Tests invalid message formats
- Validates error responses
- Tests connection failure scenarios
- Verifies timeout handling

### 4. Dashboard Functionality
- Tests `get_all_clients` queries
- Tests `get_all_rooms` queries  
- Tests `get_room_status` queries
- Tests control and broadcast messages

### 5. OpenAI Integration
- Tests OpenAI Realtime API message formats
- Validates session and conversation events
- Tests audio buffer events
- Tests response handling

## Troubleshooting

### Common Issues

**Connection Refused**:
```bash
❌ webclient connection failed: Connection refused
```
- **Solution**: Ensure BareWebsocketServerV2 is running: `php artisan bare:serverv2`

**SSL Certificate Issues**:
```bash
❌ SSL certificate verification failed
```
- **Solution**: Use `--no-ssl` for development or check certificate configuration

**Authentication Failures**:
```bash
❌ dashboard connection failed: Missing API token
```
- **Solution**: Provide valid API token: `--api-token=your-token`

**Timeout Issues**:
```bash
⏳ Test timeout reached, stopping tests...
```
- **Solution**: Server may be overloaded or not responding. Check server logs.

### Debug Mode

Enable verbose logging to see detailed information:
```bash
php artisan bare:test-v2 --test-all --verbose
# or
php artisan bare:test-v2 --test-all -v
```

This will show:
- WebSocket connection details
- Message send/receive logs
- Detailed error information
- Timing information

### Server Logs

Check server logs for additional debugging:
```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/swoole.log
```

## Development Usage

For development and debugging:

```bash
# Test local development server
php artisan bare:test-v2 --host=localhost --port=9502 --no-ssl -v

# Test specific problematic connection
php artisan bare:test-v2 --test-dashboard -v --api-token=dev-token

# Quick connection test
php artisan bare:test-v2 --test-connections
```

## Integration with CI/CD

The test command can be used in automated testing:

```bash
#!/bin/bash
# Start server in background
php artisan bare:serverv2 --daemonize

# Wait for server to start
sleep 2

# Run tests
php artisan bare:test-v2 --test-all

# Check exit code
if [ $? -eq 0 ]; then
    echo "All tests passed"
else
    echo "Tests failed"
    exit 1
fi
```

## Contributing

When adding new connection types or message types to the V2 server, update the test accordingly:

1. Add new connection type to `$connectionTypes` array
2. Add corresponding test method (`testNewConnectionType()`)
3. Add message validation for the new type
4. Update this documentation

## See Also

- [BareWebsocketServerV2 Documentation](doc/BareWebsocketServerV2.md)
- [Server Comparison Guide](doc/Comparison.md)
- [Original BareWebsocketServer](doc/BareWebsocketServer.md) 