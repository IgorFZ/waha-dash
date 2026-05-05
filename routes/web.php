<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
Route::get('/contacts/import/preview', [ContactController::class, 'importPreview'])->name('contacts.import.preview');
Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import');
Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

Route::resource('message-templates', MessageTemplateController::class)
    ->only(['index', 'store', 'update', 'destroy']);

Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/onboarding/session', [OnboardingController::class, 'store'])->name('onboarding.session.store');
Route::get('/onboarding/qr', [OnboardingController::class, 'qr'])->name('onboarding.qr');
Route::get('/onboarding/status', [OnboardingController::class, 'status'])->name('onboarding.status');
Route::post('/onboarding/qr/refresh', [OnboardingController::class, 'refreshQr'])->name('onboarding.qr.refresh');
