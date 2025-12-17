# MQTT Unified Payload - Quick Reference

## 📌 Topic Format

```
production/{machine_code}/signal
```

## 📦 Payload Structure

```json
{
    "trx_type": "status|qty_ok|ng|downtime",
    "mesin": "machine_code",
    "time": "HH:mm:ss"
}
```

---

## 🎯 Quick Examples

### Status

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "12:00:00"
}
```

### Qty OK

```json
{ "trx_type": "qty_ok", "mesin": "MC001", "qty": 5, "time": "12:05:30" }
```

### NG

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

### Downtime

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

## 🚀 Quick Start

```bash
# 1. Run migration
php artisan migrate

# 2. Start listener
php artisan mqtt:production-listener

# 3. Test
php test-unified-mqtt.php
```

---

## 📋 Status Values

- `Ready` - Siap produksi
- `Running` - Sedang produksi
- `Downtime` - Mesin downtime
- `Stopped` - Mesin berhenti
- `Paused` - Mesin pause

---

## 🔧 MQTTX Settings

- **Host:** 127.0.0.1
- **Port:** 1883
- **Topic:** production/MC001/signal

---

## 📝 Notes

- Field `time` opsional (default: server time)
- Field `mesin` = machine_code dari tabel m_machine
- Sistem auto-find monitoring_id berdasarkan machine_code
- Backward compatible dengan format lama
