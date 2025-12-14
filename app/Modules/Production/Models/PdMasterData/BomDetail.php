<?php

namespace App\Modules\Production\Models\PdMasterData;

use Illuminate\Database\Eloquent\Model;

class BomDetail extends Model
{
  protected $table = 'm_bom_detail';
  protected $primaryKey = 'bom_dtl_id';
  public $timestamps = false;

  protected $casts = [
    'bom_dtl_qty' => 'string',
  ];

  protected $fillable = [
    'bom_id',
    'part_no',
    'part_name',
    'part_desc',
    'uom',
    'bom_dtl_qty',
  ];
}
