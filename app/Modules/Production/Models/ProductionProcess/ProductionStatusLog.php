<?php

namespace App\Modules\Production\Models\ProductionProcess;

use Illuminate\Database\Eloquent\Model;

class ProductionStatusLog extends Model
{
  protected $table = 't_production_status_log';
  protected $primaryKey = 'log_id';
  public $timestamps = false;

  protected $fillable = [
    'monitoring_id',
    'status',
    'start_time',
    'end_time',
    'duration_seconds',
    'notes',
    'created_at'
  ];

  protected $casts = [
    'start_time' => 'datetime',
    'end_time' => 'datetime',
    'created_at' => 'datetime',
  ];

  public function monitoring()
  {
    return $this->belongsTo(ProductionMonitoring::class, 'monitoring_id', 'monitoring_id');
  }
}
