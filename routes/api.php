<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WithdrawalController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/paystack/webhook', PaystackWebhookController::class)
    ->name('api.paystack.webhook');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('api.withdrawals.index');
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('api.withdrawals.store');
    Route::get('/withdrawals/history', [WithdrawalController::class, 'history'])->name('api.withdrawals.history');
});

Route::get('/products', [ProductController::class, 'apiIndex']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::put('/products/{product}', [ProductController::class, 'update']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);
