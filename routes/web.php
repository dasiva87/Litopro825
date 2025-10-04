<?php

use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CompleteProfileController;
use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SimpleRegistrationController;
use App\Http\Controllers\StripeSubscriptionController;
use App\Http\Controllers\SuperAdmin\ImpersonateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas de registro público con rate limiting
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('/register', [SimpleRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [SimpleRegistrationController::class, 'store'])->name('register.store');

    // Registro completo (mantener para casos especiales)
    Route::get('/register-full', [RegistrationController::class, 'create'])->name('register.full');
    Route::post('/register-full', [RegistrationController::class, 'store'])->name('register.full.store');

    // AJAX endpoints para ubicaciones
    Route::post('/get-states', [RegistrationController::class, 'getStates'])->name('get-states');
    Route::post('/get-cities', [RegistrationController::class, 'getCities'])->name('get-cities');
});


// Rutas para completar perfil de empresa (requiere autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/complete-profile', [CompleteProfileController::class, 'show'])->name('complete-profile');
    Route::post('/complete-profile', [CompleteProfileController::class, 'update'])->name('complete-profile.update');

    // Rutas para PDFs de órdenes de pedido
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('/{purchaseOrder}/pdf', [\App\Http\Controllers\PurchaseOrderController::class, 'viewPdf'])
            ->name('pdf');
        Route::get('/{purchaseOrder}/download', [\App\Http\Controllers\PurchaseOrderController::class, 'downloadPdf'])
            ->name('download');
        Route::post('/{purchaseOrder}/email', [\App\Http\Controllers\PurchaseOrderController::class, 'emailPdf'])
            ->name('email');

        // FLUJO 1: Desde Purchase Order → Buscar Cotizaciones → Seleccionar Items
        Route::get('/search-documents', [\App\Http\Controllers\PurchaseOrderController::class, 'searchDocuments'])
            ->name('search-documents');
        Route::get('/document-items/{documentId}', [\App\Http\Controllers\PurchaseOrderController::class, 'getDocumentItems'])
            ->name('document-items');
        Route::post('/{purchaseOrder}/add-items', [\App\Http\Controllers\PurchaseOrderController::class, 'addItemsToOrder'])
            ->name('add-items');
    });

    // FLUJO 2: Desde Document Item → Seleccionar Órdenes Abiertas
    Route::prefix('document-items')->name('document-items.')->group(function () {
        Route::get('/open-orders', [\App\Http\Controllers\DocumentItemController::class, 'getOpenOrders'])
            ->name('open-orders');
        Route::post('/{documentItem}/add-to-orders', [\App\Http\Controllers\DocumentItemController::class, 'addToOrders'])
            ->name('add-to-orders');
    });
    Route::get('/complete-profile/skip', [CompleteProfileController::class, 'skip'])->name('complete-profile.skip');

    // AJAX endpoints para ubicaciones en completar perfil
    Route::post('/complete-profile/states', [CompleteProfileController::class, 'getStates'])->name('complete-profile.states');
    Route::post('/complete-profile/cities', [CompleteProfileController::class, 'getCities'])->name('complete-profile.cities');

    // Debug route for tenant context (non-production only)
    if (!app()->environment('production')) {
        Route::get('/debug/tenant-context', function () {
            return response()->json([
                'authenticated' => auth()->check(),
                'user' => auth()->check() ? [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'company_id' => auth()->user()->company_id,
                    'company_name' => auth()->user()->company ? auth()->user()->company->name : null,
                ] : null,
                'tenant_config' => config('app.current_tenant_id'),
                'session_tenant' => session('current_tenant_id'),
                'purchase_orders_visible' => auth()->check() ? \App\Models\PurchaseOrder::count() : 0,
                'all_purchase_orders' => \App\Models\PurchaseOrder::withoutGlobalScopes()->count(),
                'timestamp' => now()->toISOString(),
            ]);
        })->middleware(['web', 'auth']);
    }
});

// Rutas públicas de perfiles de empresa con rate limiting
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/empresa/{slug}', [CompanyProfileController::class, 'show'])
        ->name('company.profile');
    Route::get('/empresa/{slug}/seguidores', [CompanyProfileController::class, 'followers'])
        ->name('company.followers');
    Route::get('/empresa/{slug}/siguiendo', [CompanyProfileController::class, 'following'])
        ->name('company.following');
});


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

    // Rutas de suscripciones Stripe
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/pricing', [StripeSubscriptionController::class, 'pricing'])->name('pricing');
        Route::post('/subscribe/{plan}', [StripeSubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::get('/success', [StripeSubscriptionController::class, 'success'])->name('success');
        Route::get('/manage', [StripeSubscriptionController::class, 'manage'])->name('manage');
        Route::post('/change-plan/{plan}', [StripeSubscriptionController::class, 'changePlan'])->name('change-plan');
        Route::post('/cancel', [StripeSubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/resume', [StripeSubscriptionController::class, 'resume'])->name('resume');
        Route::get('/invoice/{invoice}', [StripeSubscriptionController::class, 'downloadInvoice'])->name('invoice.download');
        Route::get('/billing-portal', [StripeSubscriptionController::class, 'billingPortal'])->name('billing-portal');
    });
});

// Rutas públicas de suscripciones (sin auth)
Route::get('/pricing', [StripeSubscriptionController::class, 'pricing'])->name('pricing');

// Ruta de logout simple para páginas públicas
Route::get('/logout', function() {
    Auth::logout();
    return redirect('/');
})->name('simple.logout');
