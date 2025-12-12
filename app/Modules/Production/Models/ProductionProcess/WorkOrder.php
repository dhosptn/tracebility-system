<?php

namespace App\Models\Production\ProductionProcess;

use App\Models\Production\PdMasterData\Setting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class WorkOrder extends Model
{
    use HasFactory;

    protected $table = 't_wo';
    protected $primaryKey = 'wo_id';
    public $timestamps = false;

    protected $fillable = [
        'wo_no',
        'wo_date',
        'prod_date',
        'part_no',
        'part_name',
        'uom_id',
        'wo_qty',
        'ok_qty',
        'ng_qty',
        'wo_rmk',
        'wo_status',
        'lot_id',
        'input_by',
        'edit_by',
        'input_time',
        'edit_time',
        'is_delete'
    ];

    protected $casts = [
        'wo_date' => 'date',
        'prod_date' => 'date',
        'input_time' => 'datetime',
        'edit_time' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(WorkOrderDetail::class, 'wo_no', 'wo_no');
    }

    // Relationship to Routing (Settings Process) based on part_no
    public function routing()
    {
        return $this->hasOne(Setting::class, 'part_no', 'part_no')->where('is_delete', 'N');
    }

    // Relationship to Unit (UOM)
    public function unit()
    {
        return $this->belongsTo(\App\Models\Unit::class, 'uom_id', 'uom_id');
    }

    // Relationship to Lot
    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id', 'lot_id');
    }

    // Relationship to Item Master
    public function item()
    {
        return $this->hasOne(\App\Models\MasterData\Item::class, 'item_number', 'part_no')
            ->where('is_delete', 'N');
    }

    // Relationship to BOM
    public function bom()
    {
        return $this->hasOne(\App\Models\Production\BOM\MBom::class, 'part_no', 'part_no')
            ->where('is_delete', 'N');
    }

    // Relationship to WorkOrderCompletion
    // public function completion()
    // {
    //   return $this->hasOne(WorkOrderCompletion::class, 'wo_no', 'wo_no')
    //     ->where('is_delete', 'N');
    // }
}
