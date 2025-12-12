<?php

namespace App\Modules\Production\Models\PdMasterData;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
  protected $table = 'm_routing';
  protected $primaryKey = 'routing_id';
  public $timestamps = false;

  protected $fillable = [
    'routing_name',
    'part_no',
    'part_name',
    'part_desc',
    'routing_rmk',
    'routing_active_date',
    'routing_status',
    'input_by',
    'input_date',
    'edit_by',
    'edit_date',
    'is_delete'
  ];

  protected $casts = [
    'routing_active_date' => 'date',
    'input_date' => 'datetime',
    'edit_date' => 'datetime',
  ];

  // Relationship to routing details
  public function details()
  {
    return $this->hasMany(SettingDetail::class, 'routing_id', 'routing_id')
      ->orderBy('urutan_proses', 'asc');
  }
}
