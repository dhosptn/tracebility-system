# MQTT Quick Reference

## Start Everything

```bash
# Terminal 1: Start MQTT broker
docker-compose up -d mosquitto

# Terminal 2: Start listener
php artisan mqtt:production-listener

# Terminal 3: Test publish
docker exec mosquitto mosquitto_pub -h localhost -t "production/1/qty_ok" -m '{"monitoring_id":1,"qty":1}'
```

---

## Commands

```bash
# Test connection
php artisan mqtt:debug test

# Check status
php artisan mqtt:debug status

# View logs
php artisan mqtt:debug logs

# Start listener
php artisan mqtt:production-listener

# Test connection (old)
php artisan mqtt:test-connection
```

---

## Docker Commands

```bash
# Start MQTT
docker-compose up -d mosquitto

# Stop MQTT
docker-compose down

# View logs
docker logs mosquitto

# Publish test
docker exec mosquitto mosquitto_pub -h localhost -t "production/1/qty_ok" -m '{"monitoring_id":1,"qty":1}'

# Subscribe to all
docker exec -it mosquitto mosquitto_sub -h localhost -t "#" -v
```

---

## MQTT Topics

```
production/{monitoring_id}/qty_ok
  Payload: {"monitoring_id":1,"qty":1}

production/{monitoring_id}/status
  Payload: {"monitoring_id":1,"status":"Running"}
  Status: Ready, Running, Downtime, Stop

production/{monitoring_id}/ng
  Payload: {"monitoring_id":1}
```

---

## Frontend Integration

```html
<script src="{{ asset('js/mqtt-production-handler.js') }}"></script>
<script>
    const handler = new MqttProductionHandler({{ $monitoring->monitoring_id }});
    handler.start();

    window.addEventListener('beforeunload', () => {
      handler.stop();
    });
</script>
```

---

## Troubleshooting

```bash
# Check if MQTT running
netstat -ano | findstr :1883

# Check if listener running
tasklist | findstr php

# View Laravel logs
tail -f storage/logs/laravel.log

# Kill listener
taskkill /F /IM php.exe

# Restart listener
php artisan mqtt:production-listener
```

---

## Configuration (.env)

```env
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_CLIENT_ID=laravel_production
```

---

## Files

- `MQTT_SETUP_CHECKLIST.md` - Step-by-step setup
- `MQTT_QUICK_START.md` - Quick start guide
- `MQTT_SETUP_WINDOWS.md` - Windows-specific setup
- `MQTT_USAGE_GUIDE.md` - Complete usage guide
- `MQTT_TROUBLESHOOTING.md` - Troubleshooting guide
- `docker-compose.yml` - Docker configuration
- `app/Services/MqttService.php` - MQTT service
- `app/Console/Commands/MqttProductionListener.php` - Listener command
- `app/Console/Commands/MqttDebug.php` - Debug command
- `public/js/mqtt-production-handler.js` - Frontend handler

---

## Common Issues

| Issue                 | Solution                                     |
| --------------------- | -------------------------------------------- |
| Connection refused    | Start MQTT: `docker-compose up -d mosquitto` |
| Port already in use   | Kill process: `taskkill /F /IM php.exe`      |
| Messages not received | Check topic pattern: `production/1/qty_ok`   |
| Database not updating | Check monitoring ID exists                   |
| Frontend not updating | Check browser console for errors             |
| Listener crashes      | Check logs: `php artisan mqtt:debug logs`    |

---

## Test Flow

1. Start MQTT: `docker-compose up -d mosquitto`
2. Start listener: `php artisan mqtt:production-listener`
3. Publish: `docker exec mosquitto mosquitto_pub -h localhost -t "production/1/qty_ok" -m '{"monitoring_id":1,"qty":1}'`
4. Check listener output: Should show "QTY OK updated"
5. Check database: `php artisan tinker` → `App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::find(1)->qty_ok`
6. Check frontend: Open browser, should see qty_ok increment

---

## Production Checklist

- [ ] MQTT broker running (Mosquitto)
- [ ] Laravel listener running (supervisor)
- [ ] Database connection working
- [ ] Frontend polling working
- [ ] PLC/sensor publishing to correct topics
- [ ] Firewall allowing port 1883
- [ ] Logs being monitored
- [ ] Error handling in place
- [ ] Backup/recovery plan

---

## Support

See documentation files for detailed help:

- Setup issues → `MQTT_SETUP_CHECKLIST.md`
- Connection issues → `MQTT_TROUBLESHOOTING.md`
- Usage examples → `MQTT_USAGE_GUIDE.md`
