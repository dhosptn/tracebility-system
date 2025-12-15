# Realtime OEE Metrics Testing Guide

## Overview

Sistem OEE metrics sekarang sudah terintegrasi penuh dengan MQTT dan update realtime setiap 2 detik.

## Metrics yang Update Realtime

### 1. STD Cycle Time

- **Sumber**: Database (nilai tetap dari master data)
- **Update**: Tidak berubah (nilai standar)
- **Lokasi**: `$monitoring->cycle_time`

### 2. Actual Cycle Time (4 nilai)

- **Average**: Rata-rata waktu cycle dari semua OK events
- **Last Piece**: Waktu cycle terakhir
- **High**: Waktu cycle tertinggi
- **Low**: Waktu cycle terendah
- **Sumber**: Cache `ok_timestamps_{monitoring_id}`
- **Update**: Setiap ada sinyal qty_ok dari MQTT
- **Perhitungan**: Selisih timestamp antar OK events

### 3. OEE Metrics (5 nilai)

- **OEE**: Availability × Performance × Quality
- **Availability**: Operating Time / Planned Time × 100%
- **Performance**: (Ideal Cycle Time × (OK+NG)) / Operating Time × 100%
- **Quality**: OK / (OK+NG) × 100%
- **Uptime**: Operating Time / Planned Time × 100%

## Flow Realtime Update

### Saat MQTT Sinyal qty_ok Diterima:

1. MQTT Listener (`MqttProductionListener.php`) menerima sinyal
2. Update `qty_ok` dan `qty_actual` di database
3. Record timestamp OK ke cache: `recordOkTimestamp($monitoringId)`
4. Cache signal untuk frontend: `mqtt_qty_ok_{$monitoringId}`

### Saat MQTT Sinyal status Diterima:

1. MQTT Listener menerima sinyal (Run/Stop/Ready/Downtime)
2. Normalize status (Run → Running, Stop → Stopped)
3. Close previous status log dengan `end_time` dan `duration_seconds`
4. Create new status log dengan `start_time`
5. Update `current_status` di monitoring
6. Cache signal untuk frontend: `mqtt_status_signal_{$monitoringId}`

### Frontend Polling (Setiap 2 detik):

1. Call endpoint: `/production/production-monitoring/{id}/tv-data`
2. Controller call `OeeCalculationService::getRealtimeMetrics($id)`
3. Service calculate semua metrics dari database + cache
4. Return JSON dengan semua metrics
5. Frontend update display dengan animasi:
    - Green flash jika nilai naik
    - Red flash jika nilai turun
    - Smooth number animation

## Testing Steps

### 1. Test Cycle Time Update

```bash
# Kirim sinyal qty_ok dari MQTTX
Topic: production/1/qty_ok
Payload: {"monitoring_id": 1, "qty": 1}

# Tunggu 2 detik, cek di browser:
# - Average Cycle Time harus update
# - Last Cycle Time harus update
# - High/Low Cycle Time harus update
```

### 2. Test Status Change (Availability Update)

```bash
# Kirim sinyal Running
Topic: production/1/status
Payload: {"monitoring_id": 1, "status": "Run"}

# Tunggu 2 detik, cek di browser:
# - Machine Status badge berubah jadi "RUN" (hijau)
# - Availability harus naik (karena Operating Time bertambah)
# - OEE harus update

# Kirim sinyal Stop
Topic: production/1/status
Payload: {"monitoring_id": 1, "status": "Stop"}

# Tunggu 2 detik, cek di browser:
# - Machine Status badge berubah jadi "DOWNTIME" (merah)
# - Availability berhenti naik
```

### 3. Test Performance & Quality Update

```bash
# Kirim sinyal qty_ok
Topic: production/1/qty_ok
Payload: {"monitoring_id": 1, "qty": 5}

# Tunggu 2 detik, cek di browser:
# - Actual Qty naik +5
# - Performance harus update (karena total produced bertambah)
# - Quality tetap 100% (jika belum ada NG)

# Kirim sinyal NG
Topic: production/1/ng
Payload: {"monitoring_id": 1, "qty": 2}

# Isi form NG yang muncul, lalu submit
# Tunggu 2 detik, cek di browser:
# - NG Qty naik +2
# - Quality turun (karena ada NG)
# - Performance update (karena total produced bertambah)
# - OEE turun (karena Quality turun)
```

## Debugging

### Check Browser Console

```javascript
// Setiap 2 detik akan muncul log:
Realtime Data: {
  wo_qty: 100,
  qty_actual: 45,
  qty_ng: 2,
  qty_ok: 43,
  current_status: "Running",
  oee: 85.5,
  availability: 92.3,
  performance: 95.2,
  quality: 97.7,
  uptime: 92.3,
  avg_cycle_time: 12.5,
  last_cycle_time: 11.8,
  high_cycle_time: 15.2,
  low_cycle_time: 10.1,
  timeline: [...]
}
```

### Check Laravel Log

```bash
# Lihat log di storage/logs/laravel.log
tail -f storage/logs/laravel.log

# Akan muncul:
[2024-12-15 10:30:45] local.INFO: OEE Metrics for monitoring 1 {"availability":92.3,"performance":95.2,...}
[2024-12-15 10:30:45] local.INFO: MQTT QTY OK: Monitoring 1, Qty: 1
[2024-12-15 10:30:50] local.INFO: MQTT Status: Monitoring 1, Status: Running
```

### Check MQTT Listener Terminal

```bash
# Jalankan listener:
php artisan mqtt:production-listener

# Output yang diharapkan:
✓ Connected to MQTT broker
Subscribed to production topics
Listening for MQTT messages...

DEBUG: Received message on topic: production/1/qty_ok
DEBUG: Message content: {"monitoring_id": 1, "qty": 1}
DEBUG: Parsed - monitoring_id: 1, qty: 1
✓ QTY OK updated for monitoring 1: +1
```

## Expected Behavior

### Saat Production Running:

- **Availability**: Terus naik (Operating Time bertambah)
- **Performance**: Update saat ada OK/NG (tergantung cycle time vs actual)
- **Quality**: Update saat ada OK/NG (persentase OK dari total)
- **OEE**: Update mengikuti ketiga metrics di atas
- **Uptime**: Sama dengan Availability
- **Cycle Times**: Update setiap ada OK event

### Saat Production Stopped/Downtime:

- **Availability**: Berhenti naik (Operating Time tidak bertambah)
- **Performance**: Tetap (tidak ada produksi)
- **Quality**: Tetap (tidak ada produksi)
- **OEE**: Turun (karena Availability turun)
- **Uptime**: Turun (karena tidak running)

### Saat Ada NG:

- **Quality**: Turun (persentase OK menurun)
- **Performance**: Bisa naik/turun (tergantung total produced)
- **OEE**: Turun (karena Quality turun)

## Troubleshooting

### Metrics Tidak Update

1. Check browser console untuk error
2. Check Laravel log untuk error di service
3. Check MQTT listener masih running
4. Check cache Redis/File working
5. Refresh browser (Ctrl+F5)

### Cycle Time Tidak Berubah

1. Pastikan sinyal qty_ok diterima MQTT listener
2. Check cache `ok_timestamps_{monitoring_id}` ada isi
3. Minimal butuh 2 OK events untuk calculate cycle time
4. Check browser console log data.avg_cycle_time

### Status Tidak Update

1. Check MQTT listener normalize status (Run → Running)
2. Check status log di database ada record baru
3. Check frontend polling berjalan (console log setiap 2 detik)
4. Check badge color berubah di UI

## Files Modified

1. **OeeCalculationService.php** - Calculate all metrics
2. **ProductionMonitoringController.php** - Return metrics in getTvData()
3. **MqttProductionListener.php** - Record OK timestamps
4. **tv-display.blade.php** - Display and animate metrics
