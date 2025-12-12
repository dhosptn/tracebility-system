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
        'wo_no',
        'wo_id',
        'process_id',
        'process_name',
        'supervisor',
        'operator',
        'machine',
        'shift',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'remain_qty',
        'good_qty',
        'ng_qty',
        'downtime',
        'prod_time',
        'oee',
        'input_by',
        'input_time',
        'edit_by',
        'edit_time',
        'is_delete'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'input_time' => 'datetime',
        'edit_time' => 'datetime',
        'remain_qty' => 'float',
        'good_qty' => 'float',
        'ng_qty' => 'float',
        'oee' => 'float',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id', 'wo_id');
    }

    public function process()
    {
        return $this->belongsTo(MasterProcess::class, 'process_id', 'proces_id');
    }
}
