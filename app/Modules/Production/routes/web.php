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

    // PD Master Data
    Route::resource('bom', \App\Modules\Production\Http\Controllers\PdMasterData\BomController::class);
    Route::resource('master-process', \App\Modules\Production\Http\Controllers\PdMasterData\MasterProcessController::class);
    Route::resource('setting-process', \App\Modules\Production\Http\Controllers\PdMasterData\SettingProcessController::class)->names('setting-process');
});
