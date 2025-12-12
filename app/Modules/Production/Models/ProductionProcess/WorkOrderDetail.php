<?php

namespace App\Modules\Production\Models\ProductionProcess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderDetail extends Model
{
  use HasFactory;

  protected $table = 't_wo_detail';
  protected $primaryKey = 'wo_dtl_id';
  public $timestamps = false;

  protected $fillable = [
    'wo_no',
    'item_id',
    'item_name',
    'item_desc',
    'wo_qty',
    'bom_qty'
  ];
}
