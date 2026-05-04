<?php

use App\Http\Controllers\WahaController;
use Illuminate\Support\Facades\Route;

Route::get('/waha/status', [WahaController::class, 'status']);
Route::post('/waha/send-text', [WahaController::class, 'sendText']);
