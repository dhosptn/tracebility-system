# MQTT Timer Feature - Production Monitoring

## Overview

Timer "CURRENT TIME" di TV Display sekarang akan pause/resume berdasarkan status mesin, hanya menghitung waktu saat status **Running**.

## Cara Kerja

### Timer Behavior

#### Status Running ✅

- Timer **BERJALAN** dan menghitung waktu
- Menampilkan total waktu produksi aktif

#### Status Ready, Downtime, Stopped ⏸️

- Timer **BERHENTI/PAUSE**
- Waktu yang sudah terakumulasi tetap tersimpan
- Tidak menambah waktu saat status ini

#### Resume ke Running ▶️

- Timer **MELANJUTKAN** dari waktu yang tersimpan
- Menambahkan waktu baru ke akumulasi sebelumnya

---

## Contoh Skenario

### Scenario 1: Normal Production

```
08:00:00 - Start (Ready)          → Timer: 00:00:00 (tidak jalan)
08:05:00 - Status: Running         → Timer: 00:00:00 (mulai jalan)
08:35:00 - Status: Stopped         → Timer: 00:30:00 (berhenti)
08:40:00 - Status: Running         → Timer: 00:30:00 (lanjut jalan)
09:10:00 - Now                     → Timer: 01:00:00 (30 menit + 30 menit)
```

### Scenario 2: Production with Downtime

```
08:00:00 - Start (Ready)          → Timer: 00:00:00
08:05:00 - Status: Running         → Timer: 00:00:00 (mulai)
08:25:00 - Status: Downtime        → Timer: 00:20:00 (pause)
08:45:00 - Status: Running         → Timer: 00:20:00 (resume)
09:05:00 - Now                     → Timer: 00:40:00 (20 + 20 menit)

Total elapsed: 1 jam 5 menit
Running time: 40 menit
Downtime: 20 menit
```

---

## Technical Implementation

### Frontend (JavaScript)

#### State Variables:

```javascript
let currentStatus = "Ready"; // Current machine status
let accumulatedSeconds = 0; // Total running time
let lastRunningTime = null; // Timestamp when Running started
```

#### Timer Logic:

```javascript
// When status changes to Running
if (newStatus === "Running") {
    lastRunningTime = Date.now(); // Record start time
}

// When status changes from Running to other
if (currentStatus === "Running" && newStatus !== "Running") {
    const duration = (Date.now() - lastRunningTime) / 1000;
    accumulatedSeconds += duration; // Add to accumulated time
    lastRunningTime = null;
}

// Display calculation
displaySeconds = accumulatedSeconds;
if (currentStatus === "Running" && lastRunningTime) {
    displaySeconds += (Date.now() - lastRunningTime) / 1000;
}
```

### Backend (API)

#### Endpoint: GET `/production/production-monitoring/{id}/get-running-time`

**Response:**

```json
{
    "success": true,
    "accumulated_seconds": 2400,
    "current_status": "Running"
}
```

**Calculation:**

```php
// Sum all completed Running periods
foreach ($runningLogs as $log) {
    if ($log->end_time) {
        $totalRunningSeconds += $log->duration_seconds;
    } else {
        // Currently running
        $totalRunningSeconds += now()->diffInSeconds($log->start_time);
    }
}
```

---

## Database Schema

### Table: t_production_status_log

| Column           | Type        | Description                               |
| ---------------- | ----------- | ----------------------------------------- |
| log_id           | BIGINT      | Primary key                               |
| monitoring_id    | BIGINT      | FK to t_production_monitoring             |
| status           | VARCHAR(20) | Status: Ready, Running, Downtime, Stopped |
| start_time       | TIMESTAMP   | When status started                       |
| end_time         | TIMESTAMP   | When status ended (NULL if current)       |
| duration_seconds | INT         | Duration in seconds                       |
| notes            | TEXT        | Additional notes                          |
| created_at       | TIMESTAMP   | Record creation time                      |

### Status Flow:

```
Ready → Running → Downtime → Running → Stopped
  ↓       ↓          ↓          ↓         ↓
 Log1    Log2       Log3       Log4      Log5
```

Each status change creates a new log entry with:

- Previous log's `end_time` = current time
- Previous log's `duration_seconds` = calculated
- New log's `start_time` = current time
- New log's `end_time` = NULL (until next change)

---

## MQTT Integration

### When sending status via MQTT Control Panel:

1. **User clicks status button** (e.g., "Running")
2. **Frontend sends MQTT signal** via API
3. **Backend publishes to MQTT broker**
4. **MQTT Listener receives message**
5. **Listener updates database:**
    - Close previous status log
    - Create new status log
    - Update monitoring.current_status
6. **Frontend updates timer state** immediately
7. **Timer pause/resume** based on new status

### MQTT Payload:

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "14:30:00"
}
```

---

## Status Definitions

| Status       | Timer      | Description                 |
| ------------ | ---------- | --------------------------- |
| **Ready**    | ⏸️ Paused  | Mesin siap, belum produksi  |
| **Running**  | ▶️ Running | Mesin sedang produksi aktif |
| **Downtime** | ⏸️ Paused  | Mesin downtime/breakdown    |
| **Stopped**  | ⏸️ Paused  | Mesin berhenti              |
| **Paused**   | ⏸️ Paused  | Mesin pause sementara       |

---

## Features

✅ **Smart Timer** - Hanya hitung waktu saat Running
✅ **Persistent State** - Waktu tersimpan di database
✅ **Real-time Update** - Timer update setiap detik
✅ **Accurate Calculation** - Berdasarkan status logs
✅ **Resume Capability** - Lanjut dari waktu sebelumnya
✅ **MQTT Integration** - Sync dengan MQTT signals

---

## Testing

### Test Case 1: Timer Pause on Downtime

```
1. Buka TV Display
2. Klik MQTT button
3. Klik "Running" → Timer mulai jalan
4. Tunggu 30 detik
5. Klik "Downtime" → Timer berhenti di 00:00:30
6. Tunggu 20 detik
7. Timer tetap di 00:00:30 (tidak bertambah)
```

### Test Case 2: Timer Resume

```
1. Lanjut dari Test Case 1 (Timer: 00:00:30)
2. Klik "Running" → Timer lanjut dari 00:00:30
3. Tunggu 30 detik
4. Timer sekarang: 00:01:00
```

### Test Case 3: Multiple Status Changes

```
1. Running (30s) → Timer: 00:00:30
2. Stopped (20s) → Timer: 00:00:30 (pause)
3. Running (40s) → Timer: 00:01:10 (30s + 40s)
4. Downtime (15s) → Timer: 00:01:10 (pause)
5. Running (20s) → Timer: 00:01:30 (30s + 40s + 20s)
```

---

## Troubleshooting

### Issue: Timer tidak pause saat status berubah

**Solusi:**

1. Cek console browser untuk error
2. Pastikan `updateTimerStatus()` dipanggil
3. Cek status di database: `SELECT * FROM t_production_status_log ORDER BY start_time DESC LIMIT 5`

### Issue: Timer reset ke 0 setelah refresh

**Solusi:**

1. Pastikan endpoint `/get-running-time` berfungsi
2. Cek response API di Network tab browser
3. Verifikasi status logs tersimpan di database

### Issue: Accumulated time tidak akurat

**Solusi:**

1. Cek `duration_seconds` di status logs
2. Pastikan `end_time` terisi saat status berubah
3. Verifikasi perhitungan di backend

---

## API Endpoints

### 1. Get Running Time

```
GET /production/production-monitoring/{id}/get-running-time
```

**Response:**

```json
{
    "success": true,
    "accumulated_seconds": 3600,
    "formatted_time": "01:00:00",
    "current_status": "Running"
}
```

**Format:**

- `accumulated_seconds` - Total running time dalam detik (integer)
- `formatted_time` - Format HH:mm:ss (string) tanpa microseconds
- `current_status` - Status mesin saat ini

### 2. Send MQTT Signal

```
POST /production/production-monitoring/{id}/send-mqtt-signal
```

**Request:**

```json
{
    "trx_type": "status",
    "status": "Running"
}
```

**Response:**

```json
{
    "success": true,
    "message": "MQTT signal sent successfully",
    "topic": "production/MC001/signal",
    "payload": {
        "trx_type": "status",
        "mesin": "MC001",
        "status": "Running",
        "time": "14:30:00"
    }
}
```

---

## Database Queries

### Get Total Running Time:

```sql
SELECT
    SUM(duration_seconds) as total_running_seconds
FROM t_production_status_log
WHERE monitoring_id = 1
AND status = 'Running'
AND end_time IS NOT NULL;
```

### Get Current Status:

```sql
SELECT status, start_time, end_time
FROM t_production_status_log
WHERE monitoring_id = 1
ORDER BY start_time DESC
LIMIT 1;
```

### Get Status Timeline:

```sql
SELECT
    status,
    start_time,
    end_time,
    duration_seconds,
    CASE
        WHEN end_time IS NULL THEN 'Current'
        ELSE 'Completed'
    END as state
FROM t_production_status_log
WHERE monitoring_id = 1
ORDER BY start_time ASC;
```

---

## Summary

Timer "CURRENT TIME" sekarang:

- ✅ Hanya menghitung waktu saat status **Running**
- ✅ Pause saat status **Ready, Downtime, Stopped**
- ✅ Resume dan lanjut dari waktu sebelumnya
- ✅ Data tersimpan di `t_production_status_log`
- ✅ Terintegrasi dengan MQTT Control Panel
- ✅ Akurat berdasarkan status logs dari database

**Happy monitoring! 🚀**
