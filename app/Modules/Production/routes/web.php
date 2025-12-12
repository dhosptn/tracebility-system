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
});
