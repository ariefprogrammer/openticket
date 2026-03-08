<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentCallbackController;

Route::post('/doku/callback', [PaymentCallbackController::class, 'handle'])->name('doku.callback');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
