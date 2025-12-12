#!/bin/bash

MODULE=Production
BASE=app/Modules/$MODULE

echo "Creating module: $MODULE"

# === Folder Structure ===
mkdir -p $BASE/Http/Controllers
mkdir -p $BASE/Models
mkdir -p $BASE/resources/views/workorder
mkdir -p $BASE/resources/views/wotransaction
mkdir -p $BASE/resources/js
mkdir -p $BASE/resources/css
mkdir -p $BASE/routes

# === Routes ===
cat << 'EOF' > $BASE/routes/web.php
<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Production\Http\Controllers\WorkOrderController;
use App\Modules\Production\Http\Controllers\WoTransactionController;

Route::prefix('production')->group(function () {

    // Work Order
    Route::get('/wo', [WorkOrderController::class, 'index'])->name('production.wo.index');
    Route::post('/wo', [WorkOrderController::class, 'store'])->name('production.wo.store');

    // WO Transaction
    Route::get('/wo-transaction', [WoTransactionController::class, 'index'])->name('production.wotransaction.index');
    Route::post('/wo-transaction', [WoTransactionController::class, 'store'])->name('production.wotransaction.store');

});
EOF

# === Controllers ===
cat << 'EOF' > $BASE/Http/Controllers/WorkOrderController.php
<?php

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\WorkOrder;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index()
    {
        return view('Production::workorder.index');
    }

    public function store(Request $request)
    {
        WorkOrder::create($request->all());
        return back()->with('success', 'Work Order created');
    }
}
EOF

cat << 'EOF' > $BASE/Http/Controllers/WoTransactionController.php
<?php

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\WoTransaction;
use Illuminate\Http\Request;

class WoTransactionController extends Controller
{
    public function index()
    {
        return view('Production::wotransaction.index');
    }

    public function store(Request $request)
    {
        WoTransaction::create($request->all());
        return back()->with('success', 'WO Transaction created');
    }
}
EOF

# === Models ===
cat << 'EOF' > $BASE/Models/WorkOrder.php
<?php

namespace App\Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $fillable = [
        'wo_no',
        'item',
        'wo_qty',
        'production_date',
    ];
}
EOF

cat << 'EOF' > $BASE/Models/WoTransaction.php
<?php

namespace App\Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class WoTransaction extends Model
{
    protected $fillable = [
        'work_order_id',
        'qty_ok',
        'qty_ng',
        'machine',
        'operator',
        'transaction_time',
    ];
}
EOF

# === Views ===
echo "<h1>Work Order Index</h1>" > $BASE/resources/views/workorder/index.blade.php
echo "<h1>WO Transaction Index</h1>" > $BASE/resources/views/wotransaction/index.blade.php

echo "Module Production Created Successfully!"
