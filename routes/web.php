<?php

use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/export/{token}', [ExportController::class, 'download']);
Route::get('/receipt/file/{path}', [ReceiptController::class, 'file'])->where('path', '.*');
