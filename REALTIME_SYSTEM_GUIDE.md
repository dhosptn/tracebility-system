# Real-time Production Monitoring System

## Overview

Sistem monitoring produksi real-time yang terintegrasi dengan MQTT untuk update data tanpa refresh halaman.

## Fitur Real-time

### 1. **Data Updates Tanpa Refresh**

- **KPI Cards**: Qty Actual, NG Qty, Target Qty, Progress
- **OEE Metrics**: OEE, Availability, Performance, Quality, Uptime
- **Cycle Times**: Average, Last, Highest, Lowest
- **Timeline**: Status machine dari `t_production_status_log`

### 2. **Current Time Reset**

- Timer reset ke `00:00:00` setiap status berubah
- Menghitung durasi status aktif saat ini saja
- Update setiap detik dengan animasi

### 3. **MQTT Integration**

- **Auto-save**: Data langsung disimpan ke database
- **Modal Confirmation**: Tampilkan modal untuk review
- **Real-time Sync**: Update UI dalam 0.5 detik

## MQTT Signal Types

### Status Change

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "14:30:15"
}
```

**Behavior**: Timer reset ke 00:00:00, status badge berubah warna

### QTY OK

```json
{
    "trx_type": "qty_ok",
    "mesin": "MC001",
    "qty": 5,
    "time": "14:30:15"
}
```

**Behavior**: Update qty_ok dan qty_actual langsung, animasi flash

### NG Report

```json
{
    "trx_type": "ng",
    "mesin": "MC001",
    "qty": 2,
    "ng_type": "Scratch",
    "ng_reason": "Surface defect",
    "time": "14:30:15"
}
```

**Behavior**:

1. Auto-save ke database `production_ng`
2. Update qty_ng dan qty_actual
3. Tampilkan modal "NG Record (Auto-Saved)" untuk konfirmasi

### Downtime Report

```json
{
    "trx_type": "downtime",
    "mesin": "MC001",
    "downtime_type": "Machine Breakdown",
    "downtime_reason": "Motor overheating",
    "time": "14:30:15"
}
```

**Behavior**:

1. Auto-save ke database `production_downtime`
2. Tampilkan modal "Downtime Record (Auto-Saved)" untuk konfirmasi

## Real-time Update Intervals

| Component      | Interval    | Purpose                           |
| -------------- | ----------- | --------------------------------- |
| Clock & Timer  | 1 second    | Current time dan status timer     |
| Real-time Data | 1 second    | KPI, OEE, cycle times, timeline   |
| MQTT Signals   | 0.5 seconds | Modal triggers, status changes    |
| Status Sync    | 1 second    | Deteksi perubahan status via MQTT |

## Timeline System

### Data Source

Timeline mengambil data dari `t_production_status_log`:

- `status`: Ready, Running, Downtime, Stopped
- `start_time`: Waktu mulai status
- `end_time`: Waktu selesai status
- `duration_seconds`: Durasi dalam detik

### Visual Representation

- **Green**: Running
- **Red**: Downtime
- **Yellow**: Ready
- **Gray**: Stopped
- **Blue**: Other status

### Calculation

```javascript
// Percentage width per segment
const percentage = (duration / totalDuration) * 100;
```

## Modal System

### Auto-saved Records

Ketika MQTT signal diterima:

1. **Data disimpan** langsung ke database
2. **Modal ditampilkan** dengan data pre-filled
3. **Button berubah** menjadi "Confirm & Close" (hijau)
4. **Title berubah** menjadi "(Auto-Saved)"

### Manual Records

Ketika user input manual:

1. **Modal kosong** atau dengan default values
2. **Button normal** "Save NG" / "Save Downtime" (merah/kuning)
3. **Data disimpan** saat submit

## Animation Effects

### Flash Animation

```css
.animate-pulse {
    animation: pulse 1s ease-in-out;
}
```

Digunakan untuk:

- KPI cards saat nilai berubah
- Status badge saat status berubah

### Gauge Animation

```css
transform: rotate(-90deg); /* 0% */
transform: rotate(90deg); /* 100% */
```

OEE gauge needle bergerak smooth sesuai nilai.

## Testing

### 1. Start MQTT Listener

```bash
php artisan mqtt:production-listener
```

### 2. Run Real-time Test

```bash
php test-realtime-mqtt.php
```

### 3. Expected Results

- ✅ Status changes reset timer to 00:00:00
- ✅ QTY updates appear immediately without refresh
- ✅ NG modal shows with pre-filled data (auto-saved)
- ✅ Downtime modal shows with pre-filled data (auto-saved)
- ✅ Timeline updates from status logs
- ✅ OEE metrics update in real-time
- ✅ All animations work smoothly

## Troubleshooting

### Timer Tidak Reset

1. Check MQTT listener running
2. Check status sync interval (1 second)
3. Verify `updateTimerStatus()` function

### Data Tidak Update

1. Check real-time data interval (1 second)
2. Verify `/tv-data` endpoint response
3. Check browser console for errors

### Modal Tidak Muncul

1. Check MQTT signal interval (0.5 seconds)
2. Verify cache keys in controller
3. Check `show: true` in MQTT listener

### Timeline Tidak Update

1. Check `t_production_status_log` data
2. Verify `getTvData()` includes timeline
3. Check `updateTimeline()` function

## Performance Notes

- **Efficient Polling**: Hanya update yang berubah
- **Smart Caching**: MQTT signals di-cache 60 detik
- **Minimal DOM**: Update hanya element yang berubah
- **Error Handling**: Graceful fallback jika network error

## Database Tables

### Real-time Updates

- `t_production_monitoring`: qty_actual, qty_ng, qty_ok, current_status
- `t_production_status_log`: timeline data
- `t_production_ng`: NG records (auto-saved)
- `t_production_downtime`: Downtime records (auto-saved)

### Cache Keys

- `mqtt_ng_signal_{monitoring_id}`: NG modal data
- `mqtt_downtime_signal_{monitoring_id}`: Downtime modal data
- `mqtt_status_signal_{monitoring_id}`: Status change data
