<?php

namespace App\Modules\Production\Models\PdMasterData;

use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
  protected $table = 'm_bom';
  protected $primaryKey = 'bom_id';
  public $timestamps = false;

  protected $fillable = [
    'bom_no',
    'bom_name',
    'part_no',
    'part_name',
    'part_desc',
    'bom_rmk',
    'bom_active_date',
    'bom_status',
    'input_by',
    'input_date',
    'edit_by',
    'edit_date',
    'is_delete',
  ];

  // Relasi ke detail
  public function details()
  {
    return $this->hasMany(BomDetail::class, 'bom_id');
  }
}
