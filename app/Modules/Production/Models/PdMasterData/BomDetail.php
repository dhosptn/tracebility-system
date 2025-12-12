<?php

namespace App\Modules\Production\Models\PdMasterData;

use Illuminate\Database\Eloquent\Model;

class BomDetail extends Model
{
  protected $table = 'm_bom_detail';
  protected $primaryKey = 'bom_dtl_id';

  protected $casts = [
    'bom_dtl_qty' => 'float',
    'bom_unit_cost' => 'float',
    'bom_total_cost' => 'float',
  ];

  protected $fillable = [
    'bom_id',
    'part_no',
    'part_name',
    'part_desc',
    'uom',
    'bom_dtl_qty',
    'bom_unit_cost',
    'bom_total_cost',
  ];
}
