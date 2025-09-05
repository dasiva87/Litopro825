<?php

use App\Http\Controllers\DocumentPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}/pdf', [DocumentPdfController::class, 'show'])
        ->name('documents.pdf');
    Route::get('/documents/{document}/download', [DocumentPdfController::class, 'download'])
        ->name('documents.pdf.download');
});
