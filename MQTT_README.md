# MQTT Production Monitoring System

Real-time production monitoring using MQTT protocol for Laravel application.

## Overview

This system enables real-time communication between production machines/sensors and the web application using MQTT protocol:

```
[PLC/Sensor] --MQTT--> [Mosquitto Broker] --MQTT--> [Laravel Listener] --> [Database]
                                                                                  |
                                                                                  v
                                                                            [Web Frontend]
```

## Quick Start

### 1. Prerequisites

- Docker Desktop (or Mosquitto installed)
- PHP 8.0+
- Laravel 10+
- Composer

### 2. Setup (Windows)

```bash
# Run setup script
setup-mqtt.bat

# Or manually:
docker-compose up -d mosquitto
php artisan mqtt:production-listener
```

### 3. Setup (Linux)

```bash
# Make script executable
chmod +x setup-mqtt.sh

# Run setup script
./setup-mqtt.sh

# Or manually:
docker-compose up -d mosquitto
php artisan mqtt:production-listener
```

### 4. Test

```bash
# In another terminal
docker exec mosquitto mosquitto_pub -h localhost -t "production/1/qty_ok" -m '{"monitoring_id":1,"qty":1}'
```

## Documentation

### Quick Reference

- **MQTT_QUICK_REFERENCE.md** - Commands and quick lookup

### Setup & Installation

- **MQTT_SETUP_CHECKLIST.md** - Step-by-step setup guide
- **MQTT_QUICK_START.md** - Quick start for Windows
- **MQTT_SETUP_WINDOWS.md** - Detailed Windows setup

### Usage & Integration

- **MQTT_USAGE_GUIDE.md** - Complete usage guide with examples
- **MQTT_TROUBLESHOOTING.md** - Troubleshooting guide

## Architecture

### Components

1. **MQTT Broker (Mosquitto)**
    - Receives messages from sensors/PLC
    - Routes messages to subscribers
    - Runs in Docker container

2. **Laravel Listener**
    - Subscribes to MQTT topics
    - Processes incoming messages
    - Updates database
    - Signals frontend via cache

3. **Frontend Handler**
    - Polls for updates every 2 seconds
    - Displays real-time data
    - Shows modal forms when signaled

### Topics

```
production/{monitoring_id}/qty_ok
  - Sent when product is OK
  - Payload: {"monitoring_id":1,"qty":1}

production/{monitoring_id}/status
  - Sent when machine status changes
  - Payload: {"monitoring_id":1,"status":"Running"}
  - Status: Ready, Running, Downtime, Stop

production/{monitoring_id}/ng
  - Sent when product is rejected
  - Payload: {"monitoring_id":1}
```

## Files

### Configuration

- `docker-compose.yml` - Docker configuration for Mosquitto
- `.env` - Environment variables (MQTT_HOST, MQTT_PORT, etc.)

### Backend

- `app/Services/MqttService.php` - MQTT service wrapper
- `app/Console/Commands/MqttProductionListener.php` - Main listener command
- `app/Console/Commands/MqttDebug.php` - Debug and diagnostic command
- `app/Console/Commands/TestMqttConnection.php` - Connection test command

### Frontend

- `public/js/mqtt-production-handler.js` - JavaScript handler for real-time updates

### Setup Scripts

- `setup-mqtt.bat` - Windows setup script
- `setup-mqtt.sh` - Linux setup script

## Commands

### Start MQTT Broker

```bash
docker-compose up -d mosquitto
```

### Start Listener

```bash
php artisan mqtt:production-listener
```

### Debug Commands

```bash
# Test connection
php artisan mqtt:debug test

# Check status
php artisan mqtt:debug status

# View logs
php artisan mqtt:debug logs
```

## Configuration

Edit `.env`:

```env
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_CLIENT_ID=laravel_production
```

## Frontend Integration

```html
<script src="{{ asset('js/mqtt-production-handler.js') }}"></script>
<script>
    // Initialize handler
    const handler = new MqttProductionHandler({{ $monitoring->monitoring_id }});

    // Start polling
    handler.start();

    // Stop on page unload
    window.addEventListener('beforeunload', () => {
      handler.stop();
    });
</script>
```

## Troubleshooting

### Connection Failed

```bash
# Check if MQTT broker is running
docker ps | grep mosquitto

# Start if not running
docker-compose up -d mosquitto
```

### Port Already in Use

```bash
# Find process using port 1883
netstat -ano | findstr :1883

# Kill process
taskkill /PID <PID> /F
```

### Messages Not Received

```bash
# Check listener is running
php artisan mqtt:debug status

# View logs
php artisan mqtt:debug logs

# Test publish
docker exec mosquitto mosquitto_pub -h localhost -t "production/1/qty_ok" -m '{"monitoring_id":1,"qty":1}'
```

See **MQTT_TROUBLESHOOTING.md** for more issues.

## Production Deployment

### Using Supervisor (Linux)

Create `/etc/supervisor/conf.d/mqtt-listener.conf`:

```ini
[program:mqtt-listener]
process_name=%(program_name)s
command=php /path/to/project/artisan mqtt:production-listener
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/mqtt-listener.log
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mqtt-listener
```

### Using Docker Compose

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f mosquitto
docker-compose logs -f laravel
```

## Security

### Enable Authentication

1. Create password file:

```bash
docker exec mosquitto mosquitto_passwd -c /mosquitto/config/passwd username
```

2. Update `.env`:

```env
MQTT_USERNAME=username
MQTT_PASSWORD=password
```

### Enable TLS/SSL

See MQTT_USAGE_GUIDE.md for TLS configuration.

## Monitoring

### View Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# MQTT logs
docker logs -f mosquitto
```

### Monitor Topics

```bash
# Subscribe to all topics
docker exec -it mosquitto mosquitto_sub -h localhost -t "#" -v
```

## Performance

### Optimization Tips

1. **Adjust polling interval** in `mqtt-production-handler.js`:

    ```javascript
    this.pollingInterval = 2000; // milliseconds
    ```

2. **Batch updates** for high-frequency signals

3. **Use Redis** for caching instead of database cache

4. **Monitor memory usage**:
    ```bash
    ps aux | grep mqtt:production-listener
    ```

## Support

For issues and questions:

1. Check **MQTT_QUICK_REFERENCE.md** for common commands
2. See **MQTT_TROUBLESHOOTING.md** for solutions
3. Review **MQTT_USAGE_GUIDE.md** for integration examples
4. Check Laravel logs: `storage/logs/laravel.log`
5. Check MQTT logs: `docker logs mosquitto`

## License

This MQTT system is part of the Traceability System project.

## Version

- MQTT Service: 1.0.0
- Mosquitto: Latest (eclipse-mosquitto)
- PHP MQTT Client: ^0.4.0

## Changelog

### v1.0.0

- Initial release
- MQTT listener for production monitoring
- Real-time frontend updates
- Docker support
- Comprehensive documentation
