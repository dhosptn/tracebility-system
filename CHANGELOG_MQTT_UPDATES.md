# Ringkasan Perubahan - MQTT Production Monitoring

## Tanggal: 2025-12-17

### 1. Validasi Status untuk qty_ok dan ng ✅

**File yang diubah:**

-   `app/Console/Commands/MqttProductionListener.php`

**Perubahan:**

-   Menambahkan validasi pada `handleQtyOkUpdate()` dan `handleNgUpdate()`
-   Signal qty_ok dan ng **HANYA** akan diproses jika `current_status === 'Running'`
-   Jika status bukan Running (Ready, Stop, Downtime), signal akan ditolak dengan warning log

**Contoh Log:**

```
⚠ QTY OK signal REJECTED for monitoring 123: Status is 'Ready', not 'Running'
⚠ NG signal REJECTED for monitoring 123: Status is 'Downtime', not 'Running'
```

---

### 2. Modal Muncul untuk NG dan Downtime ✅

**File yang sudah ada:**

-   `app/Console/Commands/MqttProductionListener.php` (handleNgUpdate, handleDowntimeUpdate)
-   `public/js/tv-display.js` (checkMqttSignals, openNgModal, openDowntimeModal)

**Fitur yang sudah ada:**

-   Modal NG akan muncul otomatis dengan data:
    -   `ng_type` (dari MQTT signal)
    -   `ng_reason` (dari MQTT signal)
    -   `qty` (dari MQTT signal)
    -   Status `auto_saved: true` (sudah tersimpan ke database)
-   Modal Downtime akan muncul otomatis dengan data:
    -   `downtime_type` (dari MQTT signal)
    -   `downtime_reason` (dari MQTT signal)
    -   Status `auto_saved: true` (sudah tersimpan ke database)

**Cara Kerja:**

1. MQTT listener menerima signal dengan `trx_type: "ng"` atau `trx_type: "downtime"`
2. Data langsung disimpan ke database
3. Cache signal dikirim ke frontend dengan flag `show: true` dan `auto_saved: true`
4. Frontend polling setiap 500ms, mendeteksi signal, dan membuka modal
5. Modal menampilkan data yang sudah terisi dan tombol "Confirm & Close"

---

### 3. Timeline dengan Tooltip Lengkap ✅

**File yang diubah:**

-   `public/js/tv-display.js` (fungsi `updateTimeline`)

**Perubahan:**

-   Tooltip sekarang menampilkan informasi lengkap:
    -   **Status**: Running, Downtime, Ready, Stop
    -   **Start**: Waktu mulai (format HH:mm:ss dari database)
    -   **End**: Waktu selesai atau "Ongoing" jika masih berjalan
    -   **Duration**: Durasi dalam format HH:MM:SS

**Contoh Tooltip:**

```
Running
Start: 08:30:15
End: 09:45:30
Duration: 01:15:15
```

**Data Source:**

-   Data diambil dari tabel `t_production_status_log`
-   Field yang digunakan: `status`, `start_time`, `end_time`, `duration_seconds`
-   Lebar bar di timeline dihitung berdasarkan proporsi durasi terhadap total durasi

---

## Format MQTT Signal yang Didukung

### 1. Status Signal

```json
{
    "trx_type": "status",
    "mesin": "MC001",
    "status": "Running",
    "time": "22:20:00"
}
```

### 2. Qty OK Signal (Hanya diterima saat Running)

```json
{
    "trx_type": "qty_ok",
    "mesin": "MC001",
    "qty": 10,
    "time": "22:20:00"
}
```

### 3. NG Signal (Hanya diterima saat Running)

```json
{
    "trx_type": "ng",
    "mesin": "MC001",
    "qty": 10,
    "ng_type": "Material Defect",
    "ng_reason": "Scratch on surface",
    "time": "22:20:00"
}
```

### 4. Downtime Signal

```json
{
    "trx_type": "downtime",
    "mesin": "MC001",
    "downtime_type": "Machine Breakdown",
    "downtime_reason": "Motor failure",
    "time": "22:20:00"
}
```

---

## Testing Checklist

-   [ ] Test qty_ok signal saat status Running → Harus diterima
-   [ ] Test qty_ok signal saat status Ready → Harus ditolak
-   [ ] Test qty_ok signal saat status Stop → Harus ditolak
-   [ ] Test qty_ok signal saat status Downtime → Harus ditolak
-   [ ] Test ng signal saat status Running → Harus diterima + modal muncul
-   [ ] Test ng signal saat status Ready → Harus ditolak
-   [ ] Test downtime signal → Modal muncul dengan data lengkap
-   [ ] Hover timeline bar → Tooltip muncul dengan Start, End, Duration
-   [ ] Timeline bar width → Proporsional dengan durasi status

---

6. **Status-Triggered Downtime Modal**:
    - Jika menerima signal `trx_type: "status"` dengan `status: "Downtime"`, modal Downtime akan otomatis muncul (kosong) agar operator dapat mengisi alasan downtime.

## Catatan Penting

1. **Validasi Status**: Qty OK dan NG hanya diterima saat status Running untuk memastikan data produksi akurat
2. **Auto-Save**: NG dan Downtime (via signal `downtime`) langsung disimpan ke database
3. **Manual Entry**: Status Downtime akan memicu modal kosong untuk entry manual alasan downtime
4. **Modal Confirmation**: Modal tetap muncul untuk review user meskipun data sudah tersimpan
5. **Timeline Accuracy**: Timeline menggunakan data real dari database (t_production_status_log)
6. **Polling Interval**: Frontend polling MQTT signals setiap 500ms untuk responsivitas tinggi
