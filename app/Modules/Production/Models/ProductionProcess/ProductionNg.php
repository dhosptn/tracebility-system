<?php

namespace App\Modules\Production\Models\ProductionProcess;

use Illuminate\Database\Eloquent\Model;

class ProductionNg extends Model
{
  protected $table = 't_production_ng';
  protected $primaryKey = 'ng_id';
  public $timestamps = false;

  protected $fillable = [
    'monitoring_id',
    'ng_type',
    'ng_reason',
    'qty',
    'notes',
    'created_at'
  ];

  protected $casts = [
    'created_at' => 'datetime',
  ];

  public function monitoring()
  {
    return $this->belongsTo(ProductionMonitoring::class, 'monitoring_id', 'monitoring_id');
  }
}
