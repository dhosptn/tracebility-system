<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Modules\Production\Models\ProductionProcess\ProductionMonitoring;
use App\Models\Machine;

$machineCode = 'MC001';
$machine = \DB::table('m_machine')->where('machine_code', $machineCode)->first();

if ($machine) {
    $latest = ProductionMonitoring::where('machine_id', $machine->id)
        ->latest('monitoring_id')
        ->first();

    if ($latest) {
        ProductionMonitoring::where('machine_id', $machine->id)
            ->where('monitoring_id', '<>', $latest->monitoring_id)
            ->update(['is_active' => 0]);
        echo "Cleaned up MC001. Kept ID: {$latest->monitoring_id}\n";
    }
}
