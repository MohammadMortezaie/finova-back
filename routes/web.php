<?php

use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\StripeUpgradeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/export/{token}', [ExportController::class, 'download']);
Route::get('/receipt/file/{path}', [ReceiptController::class, 'file'])->where('path', '.*');
Route::get('/upgrade', [StripeUpgradeController::class, 'show'])->name('upgrade.show');
Route::post('/upgrade/checkout', [StripeUpgradeController::class, 'checkout'])->name('upgrade.checkout');
Route::get('/upgrade/success', [StripeUpgradeController::class, 'success'])->name('upgrade.success');
