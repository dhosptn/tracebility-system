# MQTT Quick Start - Windows

## Opsi Tercepat: Docker Compose

### Prerequisite

- Docker Desktop installed (https://www.docker.com/products/docker-desktop)

### Step 1: Start Mosquitto

```cmd
cd C:\WORK TRANSISI\tracebility-system

# Start MQTT broker
docker-compose up -d mosquitto

# Verify running
docker ps
```

Output seharusnya:

```
CONTAINER ID   IMAGE                    STATUS
abc123def456   eclipse-mosquitto:latest Up 2 minutes
```

### Step 2: Test Connection

```cmd
# Test dengan Laravel
php artisan mqtt:test-connection
```

### Step 3: Start Listener

```cmd
# Terminal 1 - Start listener
php artisan mqtt:production-listener
```

Output seharusnya:

```
Starting MQTT Production Listener...
MQTT Connected successfully
Subscribed to production topics
```

### Step 4: Test Publish

```cmd
# Terminal 2 - Publish test message
docker exec mosquitto mosquitto_pub -h localhost -t "production/1/qty_ok" -m "{\"monitoring_id\":1,\"qty\":1}"
```

Seharusnya di Terminal 1 (listener) akan muncul:

```
QTY OK updated for monitoring 1: +1
```

---

## Jika Docker Tidak Tersedia

### Opsi: Install Mosquitto Standalone

1. Download dari https://mosquitto.org/download/
2. Install (next-next-finish)
3. Buka Command Prompt sebagai Administrator:

```cmd
cd "C:\Program Files\mosquitto"

# Install sebagai service
mosquitto.exe -v -c mosquitto.conf -install

# Start service
net start mosquitto

# Verify
netstat -an | findstr 1883
```

4. Jalankan Laravel listener:

```bash
php artisan mqtt:production-listener
```

---

## Troubleshooting

### Docker tidak bisa start

```cmd
# Check Docker running
docker ps

# Jika error, start Docker Desktop manually
# Atau gunakan Mosquitto standalone
```

### Port 1883 sudah digunakan

```cmd
# Find process using port 1883
netstat -ano | findstr :1883

# Kill process (ganti PID)
taskkill /PID <PID> /F

# Atau stop container
docker-compose down
```

### Laravel listener tidak connect

```cmd
# Check .env MQTT config
type .env | findstr MQTT

# Should be:
# MQTT_HOST=127.0.0.1
# MQTT_PORT=1883
```

### Test dengan mosquitto_pub

```cmd
# Jika menggunakan Docker
docker exec mosquitto mosquitto_pub -h localhost -t "test" -m "hello"

# Jika menggunakan standalone
mosquitto_pub -h 127.0.0.1 -t "test" -m "hello"
```

---

## Monitoring

### View MQTT Logs

```cmd
# Docker
docker logs -f mosquitto

# Standalone
type "C:\Program Files\mosquitto\mosquitto.log"
```

### Subscribe to All Topics

```cmd
# Docker
docker exec -it mosquitto mosquitto_sub -h localhost -t "#" -v

# Standalone
mosquitto_sub -h 127.0.0.1 -t "#" -v
```

---

## Stop Services

```cmd
# Stop Docker container
docker-compose down

# Stop Mosquitto service (standalone)
net stop mosquitto
```

---

## Next: Test Full Flow

Setelah MQTT running dan listener connected:

1. Buka browser: http://localhost/production/production-monitoring
2. Start production
3. Di terminal, publish signal:
    ```cmd
    docker exec mosquitto mosquitto_pub -h localhost -t "production/1/qty_ok" -m "{\"monitoring_id\":1,\"qty\":1}"
    ```
4. Lihat di browser, qty_ok harus increment
5. Lihat di database, data harus terupdate
