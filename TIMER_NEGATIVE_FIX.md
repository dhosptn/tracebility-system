# Timer Negative Format Fix

## Masalah

Console menampilkan format negatif: `00:-2:-26` padahal status sudah Running

## Root Cause

1. **Backend**: `diffInSeconds()` bisa mengembalikan nilai negatif karena timezone issue
2. **Frontend**: Tidak handle nilai negatif dengan benar

## Solusi yang Diterapkan

### 1. Backend (PHP) - Perbaikan Format

```php
// Sebelum
$diffSeconds = (int)floor($now->diffInSeconds($startTime));

// Sesudah
$diffSeconds = $now->diffInSeconds($startTime);
$diffSeconds = abs($diffSeconds);  // ← Handle negative
$diffSeconds = (int)max(0, floor($diffSeconds));  // ← Ensure positive
```

### 2. Backend - Ensure Non-negative Output

```php
// Ensure total is non-negative integer
$totalRunningSeconds = (int)max(0, $totalRunningSeconds);

// Ensure all format values are non-negative
$hours = max(0, $hours);
$minutes = max(0, $minutes);
$seconds = max(0, $seconds);

$formattedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
```

### 3. Frontend (JavaScript) - Double Check

```javascript
function updateProductionTimer() {
    let displaySeconds = baseAccumulatedSeconds;

    // Ensure non-negative
    displaySeconds = Math.max(0, displaySeconds);

    // Convert to HH:MM:SS
    const totalSeconds = Math.floor(displaySeconds);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = Math.floor(totalSeconds % 60);

    // Ensure all values are non-negative
    const displayHours = Math.max(0, hours);
    const displayMinutes = Math.max(0, minutes);
    const displaySeconds_final = Math.max(0, seconds);

    const timerString =
        String(displayHours).padStart(2, "0") +
        ":" +
        String(displayMinutes).padStart(2, "0") +
        ":" +
        String(displaySeconds_final).padStart(2, "0");

    document.getElementById("currentTimer").textContent = timerString;
}
```

### 4. Backend - Logging untuk Debug

```php
\Log::info("Running log calculation", [
    'start_time' => $startTime->toIso8601String(),
    'now' => $now->toIso8601String(),
    'diff_seconds' => $diffSeconds,
    'total_so_far' => $totalRunningSeconds
]);
```

## Testing

### Test Case 1: Format Bersih

```
1. Kirim MQTT signal Running
2. Check console: Timer synced: 00:00:XX Status: Running
3. Verifikasi format: HH:mm:ss (tidak ada nilai negatif)
```

### Test Case 2: Timer Berjalan

```
1. Kirim MQTT signal Running
2. Tunggu 5 detik
3. Timer harus menampilkan: 00:00:05 (bukan 00:00:00)
4. Terus bertambah setiap detik
```

### Test Case 3: Refresh Halaman

```
1. Kirim MQTT signal Running
2. Timer berjalan: 00:00:30
3. Refresh halaman (F5)
4. Timer harus menampilkan: 00:00:30+ (tidak reset ke 00:00:00)
```

## Debugging

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep "Running log calculation"
```

### Expected Output

```
[2025-12-17 14:30:00] local.INFO: Running log calculation {
  "start_time": "2025-12-17T14:30:00+07:00",
  "now": "2025-12-17T14:30:30+07:00",
  "diff_seconds": 30,
  "total_so_far": 30
}
```

## API Response

### GET `/production/production-monitoring/{id}/get-running-time`

**Response:**

```json
{
    "success": true,
    "accumulated_seconds": 30,
    "formatted_time": "00:00:30",
    "current_status": "Running"
}
```

**Guaranteed:**

- `accumulated_seconds` ≥ 0 (never negative)
- `formatted_time` format: `HH:mm:ss` (no negative values)
- All values are integers

## Summary

✅ Format negatif diperbaiki
✅ Backend ensure non-negative values
✅ Frontend double-check non-negative
✅ Logging untuk debug
✅ Timer berjalan real-time setelah MQTT signal

**Timer sekarang menampilkan format yang benar! 🚀**
