<?php

namespace App\Modules\Production\Models\PdMasterData;

use Illuminate\Database\Eloquent\Model;

class SettingDetail extends Model
{
  protected $table = 'm_routing_detail';
  protected $primaryKey = 'routing_dtl_id';
  public $timestamps = false;

  protected $fillable = [
    'routing_id',
    'process_id',
    'process_name',
    'process_desc',
    'cycle_time_second',
    'urutan_proses'
  ];

  // Relationship to routing header
  public function routing()
  {
    return $this->belongsTo(Setting::class, 'routing_id', 'routing_id');
  }

  // Relationship to master process
  public function masterProcess()
  {
    return $this->belongsTo(MasterProcess::class, 'process_id', 'proces_id');
  }
}
