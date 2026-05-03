<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::post('/export', [ExportController::class, 'link']);
Route::get('/export/{token}', [ExportController::class, 'download']);

Route::get('/users', [UserController::class, 'index']);
Route::get('/fetch-all', [UserController::class, 'fetchAll']);
Route::get('/fetchAll', [UserController::class, 'fetchAll']);

Route::get('/expense', [ExpenseController::class, 'index']);
Route::post('/expense', [ExpenseController::class, 'store']);
Route::patch('/expense/{expense}', [ExpenseController::class, 'update']);
Route::delete('/expense/{expense}', [ExpenseController::class, 'destroy']);

Route::get('/income', [IncomeController::class, 'index']);
Route::post('/income', [IncomeController::class, 'store']);
Route::patch('/income/{income}', [IncomeController::class, 'update']);
Route::delete('/income/{income}', [IncomeController::class, 'destroy']);

Route::get('/subs', [SubscriptionController::class, 'index']);
Route::post('/subs', [SubscriptionController::class, 'store']);
Route::patch('/subs/{subscription}', [SubscriptionController::class, 'update']);
Route::delete('/subs/{subscription}', [SubscriptionController::class, 'destroy']);

Route::post('/receipt/analyze', [ReceiptController::class, 'analyze']);
Route::get('/receipt/file/{path}', [ReceiptController::class, 'file'])->where('path', '.*');
