# Unified MQTT Payload System - Summary

## 📋 Yang Sudah Dibuat

### 1. Database Migration

**File:** `database/migrations/2025_12_17_000001_create_transaction_types_table.php`

Membuat tabel `m_transaction_types` dengan data default:

- `status` - Status Update
- `qty_ok` - Quantity OK
- `ng` - NG Report
- `downtime` - Downtime Report

### 2. Model

**File:** `app/Models/TransactionType.php`

Model untuk mengelola transaction types dengan method:

- `getByCode($code)` - Get transaction type by code
- `getActive()` - Get all active transaction types

### 3. MQTT Listener (Updated)

**File:** `app/Console/Commands/MqttProductionListener.php`

Ditambahkan handler baru:

- `handleUnifiedSignal()` - Handler untuk format payload baru
- `findMonitoringIdByMachine()` - Cari monitoring_id berdasarkan machine_code
- `handleStatusUpdate()` - Update status dari unified signal
- `handleQtyOkUpdate()` - Update qty OK dari unified signal
- `handleNgUpdate()` - Update NG dari unified signal
- `handleDowntimeUpdate()` - Update downtime dari unified signal

**Topic baru:** `production/{machine_code}/signal`

**Backward compatibility:** Masih support topic lama

- `production/{monitoring_id}/qty_ok`
- `production/{monitoring_id}/status`
- `production/{monitoring_id}/ng`

### 4. Dokumentasi

- **MQTT_UNIFIED_PAYLOAD_GUIDE.md** - Panduan lengkap format payload
- **SETUP_UNIFIED_MQTT.md** - Langkah-langkah setup
- **MQTTX_PAYLOAD_EXAMPLES.json** - Contoh payload untuk testing

### 5. Testing Script

**File:** `test-unified-mqtt.php`

Script PHP untuk testing semua jenis payload:

- Status update
- Qty OK
- NG report
- Downtime report
- Multiple rapid fire

---

## 🚀 Cara Menggunakan

### Format Payload Baru

**Topic:**

```
production/{machine_code}/signal
```

**Payload:**

```json
{
  "trx_type": "status|qty_ok|ng|downtime",
  "mesin": "machine_code",
  "time": "HH:mm:ss",
  ...additional fields
}
```

### Contoh Payload

#### 1. Status Update

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "12:00:00"
}
```

#### 2. Qty OK

```json
{
    "trx_type": "qty_ok",
    "mesin": "MC001",
    "qty": 5,
    "time": "12:05:30"
}
```

#### 3. NG Report

```json
{
    "trx_type": "ng",
    "mesin": "MC001",
    "qty": 2,
    "ng_type": "Scratch",
    "ng_reason": "Surface defect",
    "time": "12:10:00"
}
```

#### 4. Downtime Report

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

## 📝 Langkah Setup

### 1. Run Migration

```bash
php artisan migrate
```

### 2. Start MQTT Listener

```bash
php artisan mqtt:production-listener
```

### 3. Testing

**Option A - PHP Script:**

```bash
php test-unified-mqtt.php
```

**Option B - MQTTX:**

1. Connect ke broker (127.0.0.1:1883)
2. Publish ke topic: `production/MC001/signal`
3. Gunakan payload dari `MQTTX_PAYLOAD_EXAMPLES.json`

### 4. Monitor Logs

```bash
# Windows
type storage\logs\laravel.log

# Linux/Mac
tail -f storage/logs/laravel.log
```

---

## 🔑 Keuntungan Format Baru

### Sebelum (Format Lama):

```
Topic: production/123/qty_ok
Payload: {"monitoring_id": 123, "qty": 1}
```

❌ Harus tahu monitoring_id terlebih dahulu
❌ Topic berbeda untuk setiap jenis transaksi
❌ Tidak fleksibel

### Sesudah (Format Baru):

```
Topic: production/MC001/signal
Payload: {"trx_type": "qty_ok", "mesin": "MC001", "qty": 1}
```

✅ Cukup tahu machine_code
✅ Satu topic untuk semua transaksi
✅ Lebih fleksibel dan mudah dikembangkan
✅ Sistem otomatis cari monitoring_id

---

## 🔄 Backward Compatibility

Format lama masih tetap berfungsi untuk kompatibilitas:

```json
// Masih bisa menggunakan format lama
Topic: production/123/qty_ok
Payload: {"monitoring_id": 123, "qty": 1}
```

---

## 📊 Database Schema

### Tabel Baru: m_transaction_types

```sql
CREATE TABLE m_transaction_types (
  trx_type_id BIGINT PRIMARY KEY,
  trx_type_code VARCHAR(50) UNIQUE,
  trx_type_name VARCHAR(100),
  trx_type_desc TEXT,
  is_active BOOLEAN DEFAULT 1,
  ...
);
```

### Data Default:

| trx_type_code | trx_type_name   | Description          |
| ------------- | --------------- | -------------------- |
| status        | Status Update   | Update status mesin  |
| qty_ok        | Quantity OK     | Pencatatan produk OK |
| ng            | NG Report       | Pencatatan produk NG |
| downtime      | Downtime Report | Pencatatan downtime  |

---

## 🛠️ Troubleshooting

### "No active monitoring found for machine"

**Solusi:** Pastikan ada production monitoring aktif untuk mesin tersebut

```sql
SELECT * FROM t_production_monitoring
WHERE machine_id IN (SELECT id FROM m_machine WHERE machine_code = 'MC001')
AND is_active = 1;
```

### "Unknown trx_type"

**Solusi:** Gunakan salah satu dari: `status`, `qty_ok`, `ng`, `downtime`

### "Failed to parse JSON"

**Solusi:** Pastikan payload adalah valid JSON format

---

## 📚 Dokumentasi Lengkap

Untuk detail lebih lanjut, lihat:

- `MQTT_UNIFIED_PAYLOAD_GUIDE.md` - Format payload lengkap
- `SETUP_UNIFIED_MQTT.md` - Setup step by step
- `MQTTX_PAYLOAD_EXAMPLES.json` - Contoh payload
- `MQTT_README.md` - Dokumentasi MQTT umum

---

## ✅ Checklist

- [x] Buat tabel m_transaction_types
- [x] Buat model TransactionType
- [x] Update MQTT listener
- [x] Tambah handler unified signal
- [x] Support backward compatibility
- [x] Buat dokumentasi lengkap
- [x] Buat testing script
- [x] Buat contoh payload

**Next Steps:**

- [ ] Run migration: `php artisan migrate`
- [ ] Test dengan MQTTX atau script PHP
- [ ] Integrate dengan PLC/IoT device
- [ ] Update frontend untuk handle unified payload
