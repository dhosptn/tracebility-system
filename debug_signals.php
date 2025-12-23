<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$signals = \Illuminate\Support\Facades\DB::table('t_production_pending_signals')->get();
echo json_encode($signals, JSON_PRETTY_PRINT);
