<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
  protected $table = 'wms_m_item_cat';
  protected $primaryKey = 'item_cat_id';
  public $timestamps = false; // karena DB sudah punya input_date, edit_date

  protected $fillable = [
    'item_cat_name',
    'item_cat_desc',
    'transaction_status',
    'input_by',
    'input_date',
    'edit_by',
    'edit_date',
    'del_by',
    'del_date',
    'is_delete'
  ];
}
