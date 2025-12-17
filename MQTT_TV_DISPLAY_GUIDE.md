# MQTT Control Panel - TV Display Guide

## Overview

Fitur MQTT Control Panel memungkinkan Anda mengirim sinyal MQTT langsung dari halaman TV Display Production Monitoring untuk testing dan simulasi.

## Cara Menggunakan

### 1. Akses TV Display

Buka halaman TV Display untuk production monitoring yang aktif:

```
http://your-domain/production/production-monitoring/{id}/tv-display
```

### 2. Buka MQTT Control Panel

Klik tombol floating **MQTT** di pojok kanan bawah layar (tombol biru dengan icon broadcast tower).

### 3. Kirim Sinyal

#### A. Status Control

Klik salah satu tombol status untuk mengubah status mesin:

- **Ready** (Kuning) - Mesin siap produksi
- **Running** (Hijau) - Mesin sedang produksi
- **Downtime** (Merah) - Mesin downtime
- **Stopped** (Abu-abu) - Mesin berhenti

**Payload yang dikirim:**

```json
{
    "trx_type": "status",
    "status": "Running",
    "mesin": "MC001",
    "time": "14:30:00"
}
```

#### B. Qty OK

1. Masukkan jumlah produk OK di field input
2. Klik tombol **Send**

**Payload yang dikirim:**

```json
{
    "trx_type": "qty_ok",
    "qty": 5,
    "mesin": "MC001",
    "time": "14:31:00"
}
```

#### C. NG Report

1. Masukkan **Qty** (jumlah NG)
2. Masukkan **NG Type** (contoh: Scratch, Dimension)
3. Masukkan **NG Reason** (contoh: Surface defect)
4. Klik tombol **Send NG**

**Payload yang dikirim:**

```json
{
    "trx_type": "ng",
    "qty": 2,
    "ng_type": "Scratch",
    "ng_reason": "Surface defect",
    "mesin": "MC001",
    "time": "14:32:00"
}
```

#### D. Downtime Report

1. Pilih **Downtime Type** dari dropdown
2. Masukkan **Downtime Reason**
3. Klik tombol **Send Downtime**

**Payload yang dikirim:**

```json
{
    "trx_type": "downtime",
    "downtime_type": "Breakdown",
    "downtime_reason": "Motor failure",
    "mesin": "MC001",
    "time": "14:33:00"
}
```

### 4. Notifikasi

Setelah mengirim sinyal, akan muncul notifikasi:

- ✓ **Hijau** - Sinyal berhasil dikirim
- ✗ **Merah** - Sinyal gagal dikirim

### 5. Tutup Panel

- Klik tombol **X** di pojok kanan atas panel
- Atau tekan tombol **ESC** di keyboard

---

## Flow Diagram

```
User Action → MQTT Control Panel → Backend API → MQTT Broker → MQTT Listener → Database → Frontend Update
```

### Detail Flow:

1. **User klik tombol** di MQTT Control Panel
2. **JavaScript** mengirim request ke backend API
3. **Backend** membuat payload dan publish ke MQTT broker
4. **MQTT Listener** menerima message dan memproses
5. **Database** diupdate sesuai jenis transaksi
6. **Frontend** (TV Display) otomatis refresh dan menampilkan data terbaru

---

## Testing Scenario

### Scenario 1: Normal Production Flow

```
1. Klik "Ready" → Status berubah ke Ready
2. Klik "Running" → Status berubah ke Running
3. Kirim Qty OK (5 pcs) → Qty OK bertambah 5
4. Kirim Qty OK (3 pcs) → Qty OK bertambah 3
5. Klik "Stopped" → Status berubah ke Stopped
```

### Scenario 2: Production with NG

```
1. Klik "Running" → Status Running
2. Kirim Qty OK (10 pcs) → Qty OK = 10
3. Kirim NG (2 pcs, Scratch, Surface defect) → Qty NG = 2
4. Kirim Qty OK (5 pcs) → Qty OK = 15
```

### Scenario 3: Production with Downtime

```
1. Klik "Running" → Status Running
2. Kirim Qty OK (5 pcs) → Qty OK = 5
3. Klik "Downtime" → Status Downtime
4. Kirim Downtime (Breakdown, Motor failure) → Downtime recorded
5. Klik "Running" → Status Running kembali
6. Kirim Qty OK (3 pcs) → Qty OK = 8
```

---

## Troubleshooting

### Issue: "Failed to send MQTT signal"

**Penyebab:**

- MQTT broker tidak running
- Koneksi ke MQTT broker gagal
- MQTT listener tidak aktif

**Solusi:**

1. Pastikan MQTT broker running:
    ```bash
    docker-compose up -d mosquitto
    ```
2. Pastikan MQTT listener aktif:
    ```bash
    php artisan mqtt:production-listener
    ```
3. Cek logs:
    ```bash
    tail -f storage/logs/laravel.log
    ```

### Issue: "Data tidak update di TV Display"

**Penyebab:**

- MQTT listener tidak menerima message
- Database tidak terupdate
- Frontend tidak refresh

**Solusi:**

1. Cek MQTT listener output
2. Cek database apakah data terupdate
3. Refresh halaman TV Display (F5)
4. Cek browser console untuk error

### Issue: "Machine not found"

**Penyebab:**

- Production monitoring tidak memiliki machine yang valid
- Machine code tidak ada di database

**Solusi:**

1. Pastikan production monitoring memiliki machine_id yang valid
2. Cek tabel m_machine apakah machine_code ada

---

## Technical Details

### API Endpoint

```
POST /production/production-monitoring/{id}/send-mqtt-signal
```

### Request Body

```json
{
    "trx_type": "status|qty_ok|ng|downtime",
    "status": "Running", // for status type
    "qty": 5, // for qty_ok and ng type
    "ng_type": "Scratch", // for ng type
    "ng_reason": "Defect", // for ng type
    "downtime_type": "Breakdown", // for downtime type
    "downtime_reason": "Motor" // for downtime type
}
```

### Response

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

### MQTT Topic Format

```
production/{machine_code}/signal
```

---

## Features

✅ **Real-time Testing** - Test MQTT integration langsung dari browser
✅ **User-Friendly UI** - Interface yang mudah digunakan
✅ **Instant Feedback** - Notifikasi sukses/gagal langsung muncul
✅ **Auto-fill Machine Code** - Machine code otomatis terisi dari monitoring
✅ **Validation** - Form validation untuk mencegah data tidak valid
✅ **Responsive** - Bekerja di berbagai ukuran layar
✅ **Keyboard Shortcut** - ESC untuk menutup panel

---

## Best Practices

1. **Testing Environment**
    - Gunakan fitur ini di environment testing/development
    - Jangan gunakan di production kecuali untuk troubleshooting

2. **Data Validation**
    - Pastikan data yang diinput valid dan sesuai
    - Gunakan NG Type dan Reason yang jelas

3. **Monitoring**
    - Selalu monitor logs saat testing
    - Cek database untuk memastikan data tersimpan

4. **Documentation**
    - Catat hasil testing untuk referensi
    - Dokumentasikan issue yang ditemukan

---

## Integration with PLC/IoT

Setelah testing berhasil dengan MQTT Control Panel, Anda bisa integrate dengan PLC/IoT device:

1. **PLC/IoT** publish message ke topic yang sama: `production/{machine_code}/signal`
2. **Format payload** harus sama dengan yang digunakan di control panel
3. **MQTT Listener** akan memproses message dari PLC/IoT sama seperti dari control panel

---

## Next Steps

1. ✅ Test semua fitur di MQTT Control Panel
2. ✅ Verifikasi data tersimpan di database
3. ✅ Verifikasi TV Display update real-time
4. 🔄 Integrate dengan PLC/IoT device
5. 🔄 Setup monitoring dan alerting
6. 🔄 Deploy ke production

---

## Support

Untuk dokumentasi lengkap MQTT, lihat:

- `README_UNIFIED_MQTT.md` - Overview sistem unified MQTT
- `MQTT_UNIFIED_PAYLOAD_GUIDE.md` - Format payload lengkap
- `MQTT_PAYLOAD_QUICK_REF.md` - Quick reference
- `SETUP_UNIFIED_MQTT.md` - Setup guide
