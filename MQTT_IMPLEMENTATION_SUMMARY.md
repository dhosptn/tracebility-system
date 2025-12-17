# MQTT Unified Payload - Implementation Summary

## ✅ Yang Sudah Dibuat

### 1. Database & Model

- ✅ Migration: `m_transaction_types` table
- ✅ Model: `TransactionType.php`
- ✅ Default data: status, qty_ok, ng, downtime

### 2. MQTT Listener (Updated)

- ✅ Handler untuk unified payload format
- ✅ Topic baru: `production/{machine_code}/signal`
- ✅ Auto-find monitoring_id by machine_code
- ✅ Backward compatibility dengan format lama

### 3. Backend API

- ✅ Controller method: `sendMqttSignal()`
- ✅ Route: `POST /production/production-monitoring/{id}/send-mqtt-signal`
- ✅ Validation untuk semua transaction types
- ✅ MQTT publish functionality

### 4. Frontend - MQTT Control Panel

- ✅ Floating button di TV Display
- ✅ Modal panel dengan 4 sections:
    - Status Control (Ready, Running, Downtime, Stopped)
    - Qty OK Control
    - NG Report Control
    - Downtime Report Control
- ✅ Real-time notifications
- ✅ Form validation
- ✅ Keyboard shortcut (ESC)

### 5. Dokumentasi

- ✅ `README_UNIFIED_MQTT.md` - Overview lengkap
- ✅ `MQTT_UNIFIED_PAYLOAD_GUIDE.md` - Format payload detail
- ✅ `MQTT_PAYLOAD_QUICK_REF.md` - Quick reference
- ✅ `SETUP_UNIFIED_MQTT.md` - Setup step by step
- ✅ `MQTT_TV_DISPLAY_GUIDE.md` - Panduan penggunaan control panel
- ✅ `MQTTX_PAYLOAD_EXAMPLES.json` - Contoh payload
- ✅ `test-unified-mqtt.php` - Testing script

---

## 🎯 Format Payload Baru

### Topic

```
production/{machine_code}/signal
```

### Payload Examples

**Status:**

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "12:00:00"
}
```

**Qty OK:**

```json
{ "trx_type": "qty_ok", "mesin": "MC001", "qty": 5, "time": "12:05:30" }
```

**NG:**

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

**Downtime:**

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

## 🚀 Cara Menggunakan

### 1. Setup (One-time)

```bash
# Run migration
php artisan migrate

# Start MQTT listener
php artisan mqtt:production-listener
```

### 2. Testing via TV Display

1. Buka TV Display: `/production/production-monitoring/{id}/tv-display`
2. Klik tombol **MQTT** (floating button pojok kanan bawah)
3. Pilih action yang ingin dikirim:
    - Klik tombol status (Ready/Running/Downtime/Stopped)
    - Input qty dan klik Send untuk Qty OK
    - Isi form dan klik Send NG untuk NG report
    - Isi form dan klik Send Downtime untuk downtime report
4. Lihat notifikasi sukses/gagal
5. Data akan otomatis update di TV Display

### 3. Testing via MQTTX

1. Connect ke broker (127.0.0.1:1883)
2. Publish ke topic: `production/MC001/signal`
3. Gunakan payload dari `MQTTX_PAYLOAD_EXAMPLES.json`

### 4. Testing via PHP Script

```bash
php test-unified-mqtt.php
```

---

## 📊 Flow Diagram

```
┌─────────────────┐
│   TV Display    │
│  (User clicks)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  MQTT Control   │
│     Panel       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Backend API    │
│ sendMqttSignal()│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  MQTT Broker    │
│   (Mosquitto)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ MQTT Listener   │
│ (Background)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Database     │
│   (Updated)     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   TV Display    │
│ (Auto Refresh)  │
└─────────────────┘
```

---

## 🔧 Files Modified/Created

### Created:

```
database/migrations/2025_12_17_000001_create_transaction_types_table.php
app/Models/TransactionType.php
test-unified-mqtt.php
README_UNIFIED_MQTT.md
MQTT_UNIFIED_PAYLOAD_GUIDE.md
MQTT_PAYLOAD_QUICK_REF.md
SETUP_UNIFIED_MQTT.md
MQTT_TV_DISPLAY_GUIDE.md
MQTTX_PAYLOAD_EXAMPLES.json
MQTT_IMPLEMENTATION_SUMMARY.md
```

### Modified:

```
app/Console/Commands/MqttProductionListener.php
app/Modules/Production/Http/Controllers/ProductionProcess/ProductionMonitoringController.php
app/Modules/Production/routes/web.php
app/Modules/Production/resources/views/production-process/production-monitoring/tv-display.blade.php
```

---

## 🎨 UI Features

### MQTT Control Panel

- **Floating Button**: Tombol biru dengan icon broadcast tower di pojok kanan bawah
- **Modal Panel**: Panel dengan 4 sections untuk control berbeda
- **Color Coding**:
    - 🟡 Yellow - Ready
    - 🟢 Green - Running / Qty OK
    - 🔴 Red - Downtime / NG
    - ⚫ Gray - Stopped
    - 🟠 Orange - Downtime Report
- **Notifications**: Toast notification untuk feedback
- **Responsive**: Bekerja di berbagai ukuran layar

---

## ✨ Key Features

1. **Unified Payload Format**
    - Satu topic untuk semua jenis transaksi
    - Field `trx_type` untuk membedakan jenis
    - Lebih fleksibel dan mudah dikembangkan

2. **Auto Machine Detection**
    - Sistem otomatis cari monitoring_id berdasarkan machine_code
    - Tidak perlu tahu monitoring_id di PLC/IoT

3. **Backward Compatible**
    - Format lama masih tetap berfungsi
    - Migrasi bertahap tanpa downtime

4. **Real-time Testing**
    - Test langsung dari browser
    - Instant feedback
    - No need external tools

5. **Comprehensive Documentation**
    - 7 file dokumentasi lengkap
    - Quick reference untuk developer
    - Step-by-step guide untuk user

---

## 🔍 Testing Checklist

- [ ] Run migration berhasil
- [ ] MQTT listener running
- [ ] MQTT broker running
- [ ] Buka TV Display
- [ ] Klik tombol MQTT
- [ ] Test Status Control (4 status)
- [ ] Test Qty OK
- [ ] Test NG Report
- [ ] Test Downtime Report
- [ ] Verifikasi notifikasi muncul
- [ ] Verifikasi data update di TV Display
- [ ] Verifikasi data tersimpan di database
- [ ] Test dengan MQTTX
- [ ] Test dengan PHP script

---

## 📝 Next Steps

1. **Testing**
    - [ ] Test semua fitur di MQTT Control Panel
    - [ ] Verifikasi data di database
    - [ ] Test dengan multiple machines

2. **Integration**
    - [ ] Integrate dengan PLC/IoT device
    - [ ] Setup monitoring dan alerting
    - [ ] Performance testing

3. **Documentation**
    - [ ] Training untuk operator
    - [ ] Video tutorial
    - [ ] Troubleshooting guide

4. **Production**
    - [ ] Deploy ke production
    - [ ] Monitor logs
    - [ ] Collect feedback

---

## 🆘 Quick Troubleshooting

### MQTT Broker tidak running

```bash
docker-compose up -d mosquitto
```

### MQTT Listener tidak aktif

```bash
php artisan mqtt:production-listener
```

### Check logs

```bash
tail -f storage/logs/laravel.log
```

### Test koneksi MQTT

```bash
php test-unified-mqtt.php
```

---

## 📚 Documentation Links

- **Overview**: `README_UNIFIED_MQTT.md`
- **Payload Format**: `MQTT_UNIFIED_PAYLOAD_GUIDE.md`
- **Quick Reference**: `MQTT_PAYLOAD_QUICK_REF.md`
- **Setup Guide**: `SETUP_UNIFIED_MQTT.md`
- **TV Display Guide**: `MQTT_TV_DISPLAY_GUIDE.md`
- **Examples**: `MQTTX_PAYLOAD_EXAMPLES.json`

---

## 🎉 Summary

Sistem unified MQTT payload sudah siap digunakan! Anda sekarang bisa:

1. ✅ Kirim sinyal MQTT dengan format terpadu
2. ✅ Test langsung dari TV Display
3. ✅ Auto-detect monitoring berdasarkan machine_code
4. ✅ Support backward compatibility
5. ✅ Real-time update di frontend

**Selamat mencoba! 🚀**
