# Setup Unified MQTT Payload System

## Langkah-langkah Setup

### 1. Run Migration

Jalankan migration untuk membuat tabel `m_transaction_types`:

```bash
php artisan migrate
```

Output yang diharapkan:

```
Migrating: 2025_12_17_000001_create_transaction_types_table
Migrated:  2025_12_17_000001_create_transaction_types_table
```

### 2. Verifikasi Transaction Types

Cek apakah data transaction types sudah masuk:

```bash
php artisan tinker
```

Kemudian jalankan:

```php
App\Models\TransactionType::all();
```

Harusnya muncul 4 transaction types:

- status
- qty_ok
- ng
- downtime

### 3. Pastikan MQTT Broker Running

Jika menggunakan Docker:

```bash
docker-compose up -d mosquitto
```

Atau jika install manual:

```bash
# Windows
net start mosquitto

# Linux/Mac
sudo systemctl start mosquitto
```

### 4. Start MQTT Listener

Buka terminal baru dan jalankan:

```bash
php artisan mqtt:production-listener
```

Output yang diharapkan:

```
Starting MQTT Production Listener...
Configuration:
  Host: 127.0.0.1
  Port: 1883

✓ Connected to MQTT broker

Subscribed to production topics (unified + legacy)

Listening for MQTT messages... (Press Ctrl+C to stop)
```

### 5. Pastikan Ada Production Monitoring Aktif

Sebelum testing, pastikan ada production monitoring yang aktif untuk mesin yang akan ditest.

Cek di database:

```sql
SELECT
    pm.monitoring_id,
    pm.wo_no,
    m.machine_code,
    m.machine_name,
    pm.current_status,
    pm.is_active
FROM t_production_monitoring pm
JOIN m_machine m ON pm.machine_id = m.id
WHERE pm.is_active = 1;
```

Jika belum ada, buat production monitoring baru melalui aplikasi web atau database.

### 6. Testing dengan Script PHP

#### Option A: Menggunakan test-unified-mqtt.php

Edit file `test-unified-mqtt.php`, ganti `$machineCode` dengan machine code yang ada:

```php
$machineCode = 'MC001'; // Ganti dengan machine_code yang ada
```

Jalankan:

```bash
php test-unified-mqtt.php
```

#### Option B: Menggunakan MQTTX (GUI)

1. Download MQTTX dari https://mqttx.app/
2. Buat koneksi baru:
    - Name: `Local MQTT`
    - Host: `127.0.0.1`
    - Port: `1883`
    - Client ID: `mqttx_test`
3. Connect
4. Publish message dengan format:

**Topic:**

```
production/MC001/signal
```

**Payload:**

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "14:30:00"
}
```

### 7. Monitor Logs

Buka terminal baru untuk monitor logs:

```bash
# Windows
type storage\logs\laravel.log

# Linux/Mac
tail -f storage/logs/laravel.log
```

### 8. Verifikasi di Database

#### Cek Status Log:

```sql
SELECT * FROM t_production_status_log
ORDER BY start_time DESC
LIMIT 10;
```

#### Cek Qty OK:

```sql
SELECT monitoring_id, qty_ok, qty_ng, qty_actual
FROM t_production_monitoring
WHERE is_active = 1;
```

#### Cek NG Log:

```sql
SELECT * FROM t_production_ng
ORDER BY created_at DESC
LIMIT 10;
```

#### Cek Downtime Log:

```sql
SELECT * FROM t_production_downtime
ORDER BY start_time DESC
LIMIT 10;
```

---

## Format Payload yang Didukung

### 1. Status Update

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "12:00:00"
}
```

### 2. Qty OK

```json
{
    "trx_type": "qty_ok",
    "mesin": "MC001",
    "qty": 5,
    "time": "12:05:30"
}
```

### 3. NG Report

```json
{
    "trx_type": "ng",
    "mesin": "MC001",
    "qty": 2,
    "ng_type": "Scratch",
    "ng_reason": "Material defect",
    "time": "12:10:00"
}
```

### 4. Downtime Report

```json
{
    "trx_type": "downtime",
    "mesin": "MC001",
    "downtime_type": "Breakdown",
    "downtime_reason": "Motor failure",
    "time": "12:15:00"
}
```

---

## Troubleshooting

### Error: "No active monitoring found for machine"

**Penyebab:** Tidak ada production monitoring aktif untuk mesin tersebut

**Solusi:**

1. Cek apakah machine_code benar
2. Pastikan ada production monitoring aktif:

```sql
SELECT * FROM t_production_monitoring
WHERE machine_id IN (SELECT id FROM m_machine WHERE machine_code = 'MC001')
AND is_active = 1;
```

3. Jika belum ada, buat production monitoring baru melalui aplikasi

### Error: "Failed to connect to MQTT broker"

**Penyebab:** MQTT broker tidak running atau konfigurasi salah

**Solusi:**

1. Pastikan MQTT broker running
2. Cek konfigurasi di `.env`:

```
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
```

3. Test koneksi dengan MQTTX

### Error: "Unknown trx_type"

**Penyebab:** trx_type tidak valid

**Solusi:** Gunakan salah satu dari: `status`, `qty_ok`, `ng`, `downtime`

### Listener tidak menerima message

**Solusi:**

1. Pastikan listener running: `php artisan mqtt:production-listener`
2. Cek topic yang digunakan: `production/{machine_code}/signal`
3. Pastikan payload adalah valid JSON
4. Monitor logs: `tail -f storage/logs/laravel.log`

---

## Backward Compatibility

Sistem masih mendukung format lama:

### Legacy Topics:

- `production/{monitoring_id}/qty_ok`
- `production/{monitoring_id}/status`
- `production/{monitoring_id}/ng`

### Legacy Payload:

```json
{
    "monitoring_id": 123,
    "qty": 1
}
```

---

## Next Steps

1. ✅ Migration selesai
2. ✅ MQTT listener running
3. ✅ Testing berhasil
4. 🔄 Integrate dengan PLC/IoT device
5. 🔄 Update frontend untuk handle unified payload
6. 🔄 Add monitoring dashboard

---

## Support

Untuk dokumentasi lengkap, lihat:

- `MQTT_UNIFIED_PAYLOAD_GUIDE.md` - Format payload lengkap
- `MQTT_README.md` - Dokumentasi MQTT umum
- `MQTT_QUICK_START.md` - Quick start guide
