<?php

use App\Http\Controllers\Api\ExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/export/{token}', [ExportController::class, 'download']);
