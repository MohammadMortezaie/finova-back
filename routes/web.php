<?php

use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\PasswordResetController;
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
Route::get('/stripe/portal', [StripeUpgradeController::class, 'portal'])->name('stripe.portal');
Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])->name('password.forgot');
Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->name('password.email');
Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset.form');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');
Route::get('/reset-password/success', [PasswordResetController::class, 'success'])->name('password.reset.success');
