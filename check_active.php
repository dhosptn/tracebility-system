<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$monitorings = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::with('machine')
    ->where('is_active', 1)
    ->get();
foreach ($monitorings as $m) {
    echo "ID: " . $m->monitoring_id . " | Machine: " . ($m->machine ? $m->machine->machine_code : 'N/A') . " | Status: " . $m->current_status . "\n";
}
