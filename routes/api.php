<?php

use App\Http\Controllers\Api\CompanyFollowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas para seguimiento de empresas
Route::middleware(['auth:web'])->group(function () {
    Route::post('/companies/{company}/follow', [CompanyFollowController::class, 'toggle'])
        ->name('api.companies.follow.toggle');

    Route::get('/companies/{company}/follow-status', [CompanyFollowController::class, 'check'])
        ->name('api.companies.follow.check');

    Route::get('/companies/suggestions', [CompanyFollowController::class, 'suggestions'])
        ->name('api.companies.suggestions');
});

// Rutas para sistema social
Route::middleware(['auth:web'])->prefix('social')->group(function () {
    Route::get('/feed', [\App\Http\Controllers\Api\SocialController::class, 'getFeedPosts'])
        ->name('api.social.feed');

    Route::post('/posts', [\App\Http\Controllers\Api\SocialController::class, 'createPost'])
        ->name('api.social.posts.create');

    Route::post('/posts/{post}/like', [\App\Http\Controllers\Api\SocialController::class, 'toggleLike'])
        ->name('api.social.posts.like');

    Route::post('/posts/{post}/comments', [\App\Http\Controllers\Api\SocialController::class, 'addComment'])
        ->name('api.social.posts.comments.create');

    Route::get('/posts/{post}/comments', [\App\Http\Controllers\Api\SocialController::class, 'getComments'])
        ->name('api.social.posts.comments.index');
});