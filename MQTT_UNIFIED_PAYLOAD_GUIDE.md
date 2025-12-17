# MQTT Unified Payload Guide

## Overview

Sistem MQTT sekarang mendukung format payload terpadu dengan field `trx_type` untuk membedakan jenis transaksi.

## Format Payload Baru

### Topic

```
production/{machine_code}/signal
```

### Payload Structure

```json
{
  "trx_type": "status|qty_ok|ng|downtime",
  "mesin": "machine_code",
  "time": "HH:mm:ss",
  ...additional fields based on trx_type
}
```

## Transaction Types

### 1. Status Update (`trx_type: "status"`)

Update status mesin (Ready, Running, Downtime, Stop)

**Payload:**

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "12:00:00"
}
```

**Status Values:**

- `Ready` - Mesin siap produksi
- `Running` - Mesin sedang produksi
- `Downtime` - Mesin downtime
- `Stopped` - Mesin berhenti
- `Paused` - Mesin pause

**Example MQTTX:**

```bash
Topic: production/MC001/signal
Payload:
{
  "trx_type": "status",
  "mesin": "MC001",
  "status": "Running",
  "time": "12:00:00"
}
```

---

### 2. Quantity OK (`trx_type: "qty_ok"`)

Pencatatan produk OK

**Payload:**

```json
{
    "trx_type": "qty_ok",
    "mesin": "MC001",
    "qty": 1,
    "time": "12:05:30"
}
```

**Fields:**

- `qty` (optional) - Jumlah produk OK, default: 1

**Example MQTTX:**

```bash
Topic: production/MC001/signal
Payload:
{
  "trx_type": "qty_ok",
  "mesin": "MC001",
  "qty": 5,
  "time": "12:05:30"
}
```

---

### 3. NG Report (`trx_type: "ng"`)

Pencatatan produk NG/reject

**Payload:**

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

**Fields:**

- `qty` (optional) - Jumlah produk NG, default: 1
- `ng_type` (optional) - Jenis NG, default: "Unknown"
- `ng_reason` (optional) - Alasan NG, default: "From MQTT"

**Example MQTTX:**

```bash
Topic: production/MC001/signal
Payload:
{
  "trx_type": "ng",
  "mesin": "MC001",
  "qty": 2,
  "ng_type": "Scratch",
  "ng_reason": "Material defect",
  "time": "12:10:00"
}
```

---

### 4. Downtime Report (`trx_type: "downtime"`)

Pencatatan downtime mesin

**Payload:**

```json
{
    "trx_type": "downtime",
    "mesin": "MC001",
    "downtime_type": "Breakdown",
    "downtime_reason": "Motor failure",
    "time": "12:15:00"
}
```

**Fields:**

- `downtime_type` (optional) - Jenis downtime, default: "Unknown"
- `downtime_reason` (optional) - Alasan downtime, default: "From MQTT"

**Example MQTTX:**

```bash
Topic: production/MC001/signal
Payload:
{
  "trx_type": "downtime",
  "mesin": "MC001",
  "downtime_type": "Breakdown",
  "downtime_reason": "Motor failure",
  "time": "12:15:00"
}
```

---

## Backward Compatibility

Sistem masih mendukung format lama untuk kompatibilitas:

### Legacy Topics:

- `production/{machine_id}/qty_ok`
- `production/{machine_id}/status`
- `production/{machine_id}/ng`

### Legacy Payload:

```json
{
    "monitoring_id": 123,
    "qty": 1
}
```

---

## Testing dengan MQTTX

### 1. Install MQTTX

Download dari: https://mqttx.app/

### 2. Connect ke Broker

- Host: `127.0.0.1` atau sesuai .env
- Port: `1883`
- Client ID: `mqttx_test`

### 3. Publish Test Messages

**Test Status Update:**

```
Topic: production/MC001/signal
Payload:
{
  "trx_type": "status",
  "mesin": "MC001",
  "status": "Running",
  "time": "14:30:00"
}
```

**Test Qty OK:**

```
Topic: production/MC001/signal
Payload:
{
  "trx_type": "qty_ok",
  "mesin": "MC001",
  "qty": 3,
  "time": "14:31:00"
}
```

**Test NG:**

```
Topic: production/MC001/signal
Payload:
{
  "trx_type": "ng",
  "mesin": "MC001",
  "qty": 1,
  "ng_type": "Scratch",
  "ng_reason": "Surface defect",
  "time": "14:32:00"
}
```

---

## Database Schema

### Master Transaction Types Table

```sql
CREATE TABLE m_transaction_types (
  trx_type_id BIGINT PRIMARY KEY,
  trx_type_code VARCHAR(50) UNIQUE,
  trx_type_name VARCHAR(100),
  trx_type_desc TEXT,
  is_active BOOLEAN DEFAULT 1,
  input_by VARCHAR(100),
  input_date TIMESTAMP,
  edit_by VARCHAR(100),
  edit_date TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Default Transaction Types:

1. `status` - Status Update
2. `qty_ok` - Quantity OK
3. `ng` - NG Report
4. `downtime` - Downtime Report

---

## Migration & Setup

### 1. Run Migration

```bash
php artisan migrate
```

### 2. Start MQTT Listener

```bash
php artisan mqtt:production-listener
```

### 3. Verify Transaction Types

```bash
php artisan tinker
>>> App\Models\TransactionType::all();
```

---

## Troubleshooting

### Issue: "No active monitoring found for machine"

**Solution:** Pastikan ada production monitoring aktif untuk mesin tersebut

```sql
SELECT * FROM t_production_monitoring
WHERE machine_id IN (SELECT id FROM m_machine WHERE machine_code = 'MC001')
AND is_active = 1;
```

### Issue: "Unknown trx_type"

**Solution:** Pastikan `trx_type` adalah salah satu dari: status, qty_ok, ng, downtime

### Issue: "Failed to parse JSON"

**Solution:** Pastikan payload adalah valid JSON format

---

## Notes

- Field `time` bersifat opsional, jika tidak ada akan menggunakan waktu server (WIB/UTC+7)
- Field `mesin` bisa juga menggunakan `machine_code`
- Sistem akan otomatis mencari `monitoring_id` berdasarkan `machine_code`
- Semua log disimpan di `storage/logs/laravel.log`
