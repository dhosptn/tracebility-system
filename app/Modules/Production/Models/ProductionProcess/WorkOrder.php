<?php

namespace App\Modules\Production\Models\ProductionProcess;

use App\Modules\Production\Models\PdMasterData\Setting;
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
        return $this->belongsTo(\App\Modules\Production\Models\Unit::class, 'uom_id', 'uom_id');
    }

    // Relationship to Lot
    public function lot()
    {
        return $this->belongsTo(WoLot::class, 'lot_id', 'lot_id');
    }

    // Relationship to Item Master
    public function item()
    {
        return $this->hasOne(\App\Modules\Production\Models\MasterData\ItemMaster::class, 'item_number', 'part_no')
            ->where('is_delete', 'N');
    }

    // Relationship to BOM
    public function bom()
    {
        return $this->hasOne(\App\Modules\Production\Models\PdMasterData\Bom::class, 'part_no', 'part_no')
            ->where('is_delete', 'N');
    }

    // Relationship to WorkOrderCompletion
    // public function completion()
    // {
    //   return $this->hasOne(WorkOrderCompletion::class, 'wo_no', 'wo_no')
    //     ->where('is_delete', 'N');
    // }

    // Relationship to WO Transactions
    public function woTransactions()
    {
        return $this->hasMany(WoTransaction::class, 'wo_id', 'wo_id');
    }

    // Relationship to Process (Master Process)
    public function process()
    {
        return $this->belongsTo(\App\Modules\Production\Models\PdMasterData\MasterProcess::class, 'process_id', 'process_id');
    }

    // Relationship to Machine
    public function machine()
    {
        return $this->belongsTo(\App\Modules\Production\Models\PdMasterData\Machine::class, 'machine_id', 'machine_id');
    }
}
