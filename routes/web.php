<?php

use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CompleteProfileController;
use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\PayUWebhookController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SimpleRegistrationController;
use App\Http\Controllers\SuperAdmin\ImpersonateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas de registro público
Route::middleware('guest')->group(function () {
    Route::get('/register', [SimpleRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [SimpleRegistrationController::class, 'store'])->name('register.store');

    // Registro completo (mantener para casos especiales)
    Route::get('/register-full', [RegistrationController::class, 'create'])->name('register.full');
    Route::post('/register-full', [RegistrationController::class, 'store'])->name('register.full.store');

    // AJAX endpoints para ubicaciones
    Route::post('/get-states', [RegistrationController::class, 'getStates'])->name('get-states');
    Route::post('/get-cities', [RegistrationController::class, 'getCities'])->name('get-cities');
});

// Rutas de respuesta PayU para registro
Route::get('/registration/payment-response', [RegistrationController::class, 'paymentResponse'])
    ->name('registration.payment-response');
Route::get('/registration/success', [RegistrationController::class, 'success'])
    ->name('registration.success');
Route::get('/registration/pending', [RegistrationController::class, 'pending'])
    ->name('registration.pending');
Route::get('/registration/failed', [RegistrationController::class, 'failed'])
    ->name('registration.failed');

// Rutas para completar perfil de empresa (requiere autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/complete-profile', [CompleteProfileController::class, 'show'])->name('complete-profile');
    Route::post('/complete-profile', [CompleteProfileController::class, 'update'])->name('complete-profile.update');
    Route::get('/complete-profile/skip', [CompleteProfileController::class, 'skip'])->name('complete-profile.skip');

    // AJAX endpoints para ubicaciones en completar perfil
    Route::post('/complete-profile/states', [CompleteProfileController::class, 'getStates'])->name('complete-profile.states');
    Route::post('/complete-profile/cities', [CompleteProfileController::class, 'getCities'])->name('complete-profile.cities');
});

// Rutas públicas de perfiles de empresa
Route::get('/empresa/{slug}', [CompanyProfileController::class, 'show'])
    ->name('company.profile');
Route::get('/empresa/{slug}/seguidores', [CompanyProfileController::class, 'followers'])
    ->name('company.followers');
Route::get('/empresa/{slug}/siguiendo', [CompanyProfileController::class, 'following'])
    ->name('company.following');

// PayU Webhooks (sin middleware de autenticación)
Route::post('/payu/webhook', [PayUWebhookController::class, 'handle'])
    ->name('payu.webhook');

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}/pdf', [DocumentPdfController::class, 'show'])
        ->name('documents.pdf');
    Route::get('/documents/{document}/download', [DocumentPdfController::class, 'download'])
        ->name('documents.pdf.download');

    // Rutas de impersonación
    Route::prefix('super-admin')->middleware(['role:Super Admin'])->group(function () {
        Route::post('/impersonate/{user}', [ImpersonateController::class, 'impersonate'])
            ->name('superadmin.impersonate');
        Route::post('/leave-impersonation', [ImpersonateController::class, 'leaveImpersonation'])
            ->name('superadmin.leave-impersonation');
    });
});
