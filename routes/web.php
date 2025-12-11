<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;


Route::prefix('erp')->group(function () {
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'form'])->name('customers.create');
    Route::get('customers/{id}/edit', [CustomerController::class, 'form'])->name('customers.edit');
    Route::post('customers/save/{id?}', [CustomerController::class, 'save'])->name('customers.save');
    Route::delete('customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
});
