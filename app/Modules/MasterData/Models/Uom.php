<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
  protected $table = 'wms_m_uom';
  protected $primaryKey = 'uom_id';
  public $timestamps = true;
  const CREATED_AT = 'input_date';
  const UPDATED_AT = 'edit_date';
  protected $fillable = [
    'uom_code',
    'uom_desc',
    'input_by',
    'input_date',
    'edit_by',
    'edit_date',
    'del_by',
    'del_date',
    'is_delete',
  ];
}
