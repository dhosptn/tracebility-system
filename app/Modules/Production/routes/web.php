<?php

use Illuminate\Support\Facades\Route;

Route::prefix('production')->name('production.')->group(function () {

    // PD Master Data
    Route::resource('bom', \App\Modules\Production\Http\Controllers\PdMasterData\BomController::class);
    Route::resource('master-process', \App\Modules\Production\Http\Controllers\PdMasterData\MasterProcessController::class);
    Route::resource('setting-process', \App\Modules\Production\Http\Controllers\PdMasterData\SettingProcessController::class)->names('setting-process');

    // Work Order
    Route::get('wo-transaction/wo-details', [\App\Modules\Production\Http\Controllers\ProductionProcess\WoTransactionController::class, 'getWoDetails'])->name('wo-transaction.wo-details');
    Route::resource('wo-transaction', \App\Modules\Production\Http\Controllers\ProductionProcess\WoTransactionController::class);
    Route::get('work-order/part-details', [\App\Modules\Production\Http\Controllers\ProductionProcess\WorkOrderController::class, 'getPartDetails'])->name('work-order.part-details');
    Route::resource('work-order', \App\Modules\Production\Http\Controllers\ProductionProcess\WorkOrderController::class);
    // WO Lot
    Route::get('lot_number/next', [\App\Modules\Production\Http\Controllers\ProductionProcess\WoLotController::class, 'getNextLotNumber'])->name('lot_number.next');
    Route::get('wo_report/lot-details/{id}', [\App\Modules\Production\Http\Controllers\ProductionProcess\WoLotController::class, 'getLotDetails'])->name('wo_report.lot-details');
    Route::resource('lot_number', \App\Modules\Production\Http\Controllers\ProductionProcess\WoLotController::class);

    // Legacy route alias for backward compatibility
    Route::resource('wo-lot', \App\Modules\Production\Http\Controllers\ProductionProcess\WoLotController::class);

    // Production Monitoring
    Route::get('production-monitoring/wo-details', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'getWoDetails'])->name('production-monitoring.wo-details');
    Route::get('production-monitoring/process-list', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'getProcessList'])->name('production-monitoring.process-list');
    Route::get('production-monitoring/cycle-time', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'getCycleTime'])->name('production-monitoring.cycle-time');
    Route::post('production-monitoring/start', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'startProduction'])->name('production-monitoring.start');
    Route::get('production-monitoring/{id}/dashboard', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'dashboard'])->name('production-monitoring.dashboard');
    Route::get('production-monitoring/{id}/tv-display', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'tvDisplay'])->name('production-monitoring.tv-display');
    Route::get('production-monitoring/{id}/tv-data', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'getTvData'])->name('production-monitoring.tv-data');
    Route::post('production-monitoring/{id}/update-status', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'updateStatus'])->name('production-monitoring.update-status');
    Route::post('production-monitoring/{id}/update-qty-ok', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'updateQtyOk'])->name('production-monitoring.update-qty-ok');
    Route::post('production-monitoring/{id}/save-ng', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'saveNg'])->name('production-monitoring.save-ng');
    Route::post('production-monitoring/{id}/save-downtime', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'saveDowntime'])->name('production-monitoring.save-downtime');
    Route::get('production-monitoring/{id}/check-mqtt-ng-signal', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'checkMqttNgSignal'])->name('production-monitoring.check-mqtt-ng-signal');
    Route::get('production-monitoring/{id}/check-mqtt-downtime-signal', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'checkMqttDowntimeSignal'])->name('production-monitoring.check-mqtt-downtime-signal');
    Route::get('production-monitoring/{id}/check-mqtt-status-signal', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'checkMqttStatusSignal'])->name('production-monitoring.check-mqtt-status-signal');
    Route::get('production-monitoring/{id}/get-running-time', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'getRunningTime'])->name('production-monitoring.get-running-time');
    Route::get('production-monitoring/{id}/get-current-status', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'getCurrentStatus'])->name('production-monitoring.get-current-status');
    Route::post('production-monitoring/{id}/send-mqtt-signal', [\App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class, 'sendMqttSignal'])->name('production-monitoring.send-mqtt-signal');
    Route::resource('production-monitoring', \App\Modules\Production\Http\Controllers\ProductionProcess\ProductionMonitoringController::class);
});
