<?php

use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\CompanyProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas públicas de perfiles de empresa
Route::get('/empresa/{slug}', [CompanyProfileController::class, 'show'])
    ->name('company.profile');
Route::get('/empresa/{slug}/seguidores', [CompanyProfileController::class, 'followers'])
    ->name('company.followers');
Route::get('/empresa/{slug}/siguiendo', [CompanyProfileController::class, 'following'])
    ->name('company.following');

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}/pdf', [DocumentPdfController::class, 'show'])
        ->name('documents.pdf');
    Route::get('/documents/{document}/download', [DocumentPdfController::class, 'download'])
        ->name('documents.pdf.download');
});
