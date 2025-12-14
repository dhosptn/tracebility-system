<?php

namespace App\Modules\Production\Models\ProductionProcess;

use Illuminate\Database\Eloquent\Model;

class ProductionDowntime extends Model
{
  protected $table = 't_production_downtime';
  protected $primaryKey = 'downtime_id';
  public $timestamps = false;

  protected $fillable = [
    'monitoring_id',
    'downtime_type',
    'downtime_reason',
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
