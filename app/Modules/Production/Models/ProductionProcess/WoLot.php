<?php

namespace App\Modules\Production\Models\ProductionProcess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoLot extends Model
{
    use HasFactory;

    protected $table = "t_lot";
    protected $primaryKey = "lot_id";

    protected $fillable = [
        'lot_no',
        'lot_date',
        'qty_per_lot',
        'item_desc',
        'charge_no',
        'lot_create_by',
    ];

    protected $casts = [
        'lot_date' => 'datetime', // Assuming 'lot_date' is a date field that should be cast to a Carbon instance.
    ];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'lot_id', 'lot_id');
    }
}
