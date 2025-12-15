# Test MQTT Signals untuk Monitoring ID 1

## Langkah Testing:

### 1. Kirim Sinyal Running (untuk membuat Availability naik)

```
Topic: production/1/status
Payload: {"monitoring_id": 1, "status": "Run"}
```

Tunggu 10 detik, lalu cek browser:

- Availability harus mulai naik dari 0
- Uptime harus mulai naik dari 0
- Machine Status badge harus hijau "RUN"

### 2. Kirim Sinyal QTY OK (untuk membuat Cycle Time dan Performance update)

```
Topic: production/1/qty_ok
Payload: {"monitoring_id": 1, "qty": 1}
```

Tunggu 2 detik, kirim lagi:

```
Topic: production/1/qty_ok
Payload: {"monitoring_id": 1, "qty": 1}
```

Tunggu 2 detik, kirim lagi:

```
Topic: production/1/qty_ok
Payload: {"monitoring_id": 1, "qty": 1}
```

Setelah 3x kirim sinyal qty_ok:

- Actual Cycle Time (Average, Last, High, Low) harus berubah dari 200
- Performance harus mulai muncul (tidak 0 lagi)
- OEE harus mulai muncul (tidak 0 lagi)

### 3. Kirim Sinyal Stop (untuk melihat Availability berhenti naik)

```
Topic: production/1/status
Payload: {"monitoring_id": 1, "status": "Stop"}
```

Tunggu 5 detik:

- Availability berhenti naik
- Machine Status badge harus merah "DOWNTIME"

### 4. Kirim Sinyal Running lagi

```
Topic: production/1/status
Payload: {"monitoring_id": 1, "status": "Run"}
```

- Availability mulai naik lagi
- Machine Status badge hijau "RUN"

## Penjelasan Kenapa Sekarang Nilai 0:

Dari log Laravel, terlihat:

- **availability: 0.0** → Tidak ada status "Running" di database
- **performance: 0.0** → Operating Time = 0 (karena tidak ada Running)
- **quality: 79.9** → Ini benar (ada OK dan NG di database)
- **avg_cycle_time: 200** → Fallback ke cycle_time default (tidak ada OK timestamps di cache)

Jadi sistem sudah bekerja dengan benar, hanya saja:

1. Belum ada status "Running" → Availability = 0
2. Belum ada OK timestamps di cache → Cycle times pakai default

Setelah kirim sinyal di atas, semua nilai akan mulai berubah realtime!
