<?php

namespace App\Modules\Production\Models\ProductionProcess;

use App\Modules\Production\Models\PdMasterData\MasterProcess;
use Illuminate\Database\Eloquent\Model;


class WoTransaction extends Model
{
    protected $table = 't_wo_transaction';
    protected $primaryKey = 'trx_id';
    public $timestamps = false;

    protected $fillable = [
        'trx_no',
        'trx_date',
        'wo_id',
        'wo_no',
        'process_id',
        'process_name',
        'cycle_time',
        'supervisor',
        'operator',
        'machine_id',
        'shift_id',
        'start_time',
        'end_time',
        'target_qty',
        'actual_qty',
        'ok_qty',
        'ng_qty',
        'status',
        'notes',
        'input_by',
        'input_time',
        'edit_by',
        'edit_time',
        'is_delete'
    ];

    protected $casts = [
        'trx_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'input_time' => 'datetime',
        'edit_time' => 'datetime',
        'target_qty' => 'integer',
        'actual_qty' => 'integer',
        'ok_qty' => 'integer',
        'ng_qty' => 'integer',
        'cycle_time' => 'integer',
        'machine_id' => 'integer',
        'shift_id' => 'integer',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id', 'wo_id');
    }

    public function process()
    {
        return $this->belongsTo(MasterProcess::class, 'process_id', 'proces_id');
    }

    public function machine()
    {
        return $this->belongsTo(\App\Modules\Production\Models\PdMasterData\Machine::class, 'machine_id', 'id');
    }
}
