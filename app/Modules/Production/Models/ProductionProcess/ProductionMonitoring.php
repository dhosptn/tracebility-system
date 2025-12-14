<?php

namespace App\Modules\Production\Models\ProductionProcess;

use Illuminate\Database\Eloquent\Model;

class ProductionMonitoring extends Model
{
  protected $table = 't_production_monitoring';
  protected $primaryKey = 'monitoring_id';
  public $timestamps = false;

  protected $fillable = [
    'wo_no',
    'wo_qty',
    'process_id',
    'process_name',
    'cycle_time',
    'supervisor',
    'operator',
    'machine_id',
    'shift_id',
    'start_time',
    'end_time',
    'current_status',
    'qty_ok',
    'qty_ng',
    'qty_actual',
    'is_active',
    'created_by',
    'updated_by',
    'created_at',
    'updated_at'
  ];

  protected $casts = [
    'start_time' => 'datetime',
    'end_time' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  public function workOrder()
  {
    return $this->belongsTo(WorkOrder::class, 'wo_no', 'wo_no');
  }

  public function machine()
  {
    return $this->belongsTo(\App\Modules\Production\Models\PdMasterData\Machine::class, 'machine_id', 'id');
  }

  public function shift()
  {
    return $this->belongsTo(\App\Modules\Production\Models\PdMasterData\Shift::class, 'shift_id', 'shift_id');
  }

  public function statusLogs()
  {
    return $this->hasMany(ProductionStatusLog::class, 'monitoring_id', 'monitoring_id');
  }

  public function downtimeLogs()
  {
    return $this->hasMany(ProductionDowntime::class, 'monitoring_id', 'monitoring_id');
  }

  public function ngLogs()
  {
    return $this->hasMany(ProductionNg::class, 'monitoring_id', 'monitoring_id');
  }
}
