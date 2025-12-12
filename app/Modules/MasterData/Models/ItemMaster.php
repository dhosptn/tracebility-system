<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Model;

class ItemMaster extends Model
{
  protected $table = 'wms_m_item';
  protected $primaryKey = 'item_id';

  protected $fillable = [
    'item_number',
    'item_name',
    'item_description',
    'stock_type',
    'model',
    'uom_id',
    'second_uom',
    'volume_m3',
    'spq_ctn',
    'spq_item',
    'spq_pallet',
    'spq_weight',
    'm3_pallet',
    'item_cat_id',
    'cust_id',
    'foreign_part_no',
    'foreign_part_desc',
    'foreign_part_uom',
    'default_exp',
    'item_status',
    'item_source',
    'barcode',
    'item_rmk',
    'standard_price',
    'coa_id',
    'net_weight',
    'gross_weight',
    'input_by',
    'input_date',
    'edit_by',
    'edit_date',
    'is_delete',
  ];

  public $timestamps = false;

  public function uom()
  {
    return $this->belongsTo(Uom::class, 'uom_id', 'uom_id');
  }

  public function secondUom()
  {
    return $this->belongsTo(Uom::class, 'second_uom', 'uom_id');
  }

  public function category()
  {
    return $this->belongsTo(ItemCategory::class, 'item_cat_id', 'item_cat_id');
  }

  public function bom()
  {
    return $this->hasOne(\App\Modules\Production\Models\PdMasterData\Bom::class, 'part_no', 'item_number')
      ->where('is_delete', 'N')
      ->where('bom_status', 1);
  }

  public function setting()
  {
    return $this->hasOne(\App\Modules\Production\Models\PdMasterData\Setting::class, 'part_no', 'item_number')
      ->where('is_delete', 'N')
      ->where('routing_status', 1);
  }
}
