# Production Monitoring System

## Fitur yang Sudah Dibuat

### 1. Form Start Production (`/production/production-monitoring`)

Form untuk memulai production monitoring dengan input:

- **WO No** (select) - Pilih Work Order yang aktif
- **WO Qty** (readonly) - Otomatis terisi dari WO yang dipilih
- **Process** (select) - Otomatis terisi berdasarkan routing WO
- **Cycle Time** (readonly) - Otomatis terisi dari process yang dipilih
- **Supervisor** (text input)
- **Operator** (text input)
- **Machine** (select)
- **Shift** (select)
- **Tombol Start Production**

### 2. Dashboard Monitoring (`/production/production-monitoring/{id}/dashboard`)

Dashboard real-time untuk monitoring produksi dengan fitur:

#### Status Control

- Start/Running
- Pause
- Stop

#### Metrics Display

- Target Qty
- OK Qty
- NG Qty
- Actual Qty
- Progress Bar
- Timer (running time)

#### Quick Actions

- Add OK Qty (+1)
- Record NG (dengan modal form)
- Record Downtime (dengan modal form)

#### Information Display

- Cycle Time Information
- Status History Log
- Production Progress

### 3. Database Tables

- `t_production_monitoring` - Main monitoring table
- `t_production_status_log` - Status change history
- `t_production_downtime` - Downtime records
- `t_production_ng` - NG (Not Good) records

### 4. Models

- `ProductionMonitoring`
- `ProductionStatusLog`
- `ProductionDowntime`
- `ProductionNg`

### 5. Controller Methods

- `index()` - Form start production
- `getWoDetails()` - AJAX get WO details
- `getProcessList()` - AJAX get process list
- `getCycleTime()` - AJAX get cycle time
- `startProduction()` - Start production & redirect to dashboard
- `dashboard()` - Display monitoring dashboard
- `updateStatus()` - Update production status
- `updateQtyOk()` - Add OK quantity
- `saveNg()` - Save NG record
- `saveDowntime()` - Save downtime record

## Cara Menggunakan

### 1. Start Production

1. Buka menu **Production > Production Monitoring**
2. Pilih **WO No** dari dropdown
3. WO Qty akan otomatis terisi
4. Pilih **Process** dari dropdown yang muncul
5. Cycle Time akan otomatis terisi
6. Isi **Supervisor** dan **Operator**
7. Pilih **Machine** dan **Shift**
8. Klik **Start Production**
9. Sistem akan redirect ke Dashboard Monitoring

### 2. Monitoring Dashboard

1. Dashboard akan menampilkan informasi real-time
2. Gunakan tombol status untuk mengubah status produksi:
    - **Running** - Produksi berjalan
    - **Paused** - Produksi di-pause
    - **Stopped** - Produksi dihentikan
3. Klik **Add OK Qty** untuk menambah jumlah produk OK
4. Klik **Record NG** untuk mencatat produk NG
5. Klik **Record Downtime** untuk mencatat downtime
6. Dashboard auto-refresh setiap 30 detik

### 3. Record NG

1. Klik tombol **Record NG**
2. Pilih **NG Type** (Material, Process, Machine, Human Error)
3. Isi **NG Reason**
4. Isi **Quantity**
5. Isi **Notes** (optional)
6. Klik **Save NG**

### 4. Record Downtime

1. Klik tombol **Record Downtime**
2. Pilih **Downtime Type** (Machine Breakdown, Material Shortage, dll)
3. Isi **Downtime Reason**
4. Isi **Notes** (optional)
5. Klik **Save Downtime**

## Routes

```php
GET  /production/production-monitoring                    - Form start production
GET  /production/production-monitoring/wo-details         - AJAX get WO details
GET  /production/production-monitoring/process-list       - AJAX get process list
POST /production/production-monitoring/start              - Start production
GET  /production/production-monitoring/{id}/dashboard     - Monitoring dashboard
POST /production/production-monitoring/{id}/update-status - Update status
POST /production/production-monitoring/{id}/update-qty-ok - Add OK qty
POST /production/production-monitoring/{id}/save-ng       - Save NG
POST /production/production-monitoring/{id}/save-downtime - Save downtime
```

## Migration

Jalankan migration untuk membuat tabel:

```bash
php artisan migrate --path=database/migrations/2025_12_13_102303_create_production_monitoring_tables.php
```

Jika tabel sudah ada, migration akan skip otomatis.

## Catatan

- Dashboard menggunakan dark theme untuk kenyamanan operator
- Timer berjalan otomatis sejak production dimulai
- Progress bar menampilkan persentase OK qty terhadap target
- Auto-refresh setiap 30 detik untuk update data terbaru
- Semua action menggunakan AJAX untuk pengalaman yang smooth
