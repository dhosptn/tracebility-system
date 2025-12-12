<?php

use Illuminate\Support\Facades\Route;
use App\Modules\MasterData\Http\Controllers\ItemCategoryController;
use App\Modules\MasterData\Http\Controllers\UnitController; // Note: Check namespace equality
use App\Modules\MasterData\Http\Controllers\ItemMasterController;

Route::prefix('master-data')->middleware(['auth'])->group(function () {

  // Item Master
  Route::get('/item-master/data', [ItemMasterController::class, 'getDataTable'])->name('itemmaster.data');
  Route::get('/item-master/export', [ItemMasterController::class, 'export'])->name('itemmaster.export'); // Assuming export exists or will exist
  Route::resource('item-master', ItemMasterController::class)->names('itemmaster');
  Route::get('/item-master/create', [ItemMasterController::class, 'create'])->name('itemmaster.create');
  Route::post('/item-master', [ItemMasterController::class, 'store'])->name('itemmaster.store');
  Route::get('/item-master/{id}/edit', [ItemMasterController::class, 'edit'])->name('itemmaster.edit');
  Route::put('/item-master/{id}', [ItemMasterController::class, 'update'])->name('itemmaster.update');
  Route::delete('/item-master/{id}', [ItemMasterController::class, 'destroy'])->name('itemmaster.destroy');

  // Item Categories
  Route::get('/item-categories/data', [ItemCategoryController::class, 'data'])->name('itemcategory.data');
  Route::resource('item-categories', ItemCategoryController::class)->names('itemcategory');

  // Units
  Route::prefix('units')->name('master-data.unit.')->group(function () {
    Route::get('/', [UnitController::class, 'index'])->name('index');
    Route::get('/data', [UnitController::class, 'getData'])->name('data');
    Route::get('/create', [UnitController::class, 'create'])->name('create');
    Route::post('/', [UnitController::class, 'store'])->name('store');
    Route::get('/{unit}/edit', [UnitController::class, 'edit'])->name('edit');
    Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
    Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
  });
});
