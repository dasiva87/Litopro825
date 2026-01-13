<?php

use App\Http\Controllers\CollectionAccountPdfController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CompleteProfileController;
use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\ProductionOrderPdfController;
use App\Http\Controllers\StripeSubscriptionController;
use App\Http\Controllers\SuperAdmin\ImpersonateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas de registro público - Redirige a Filament
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('/register', function () {
        return redirect()->route('filament.admin.auth.register');
    })->name('register');
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
    if (! app()->environment('production')) {
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
// NOTA: La ruta /empresa/{slug} ahora es manejada por Filament Page (CompanyProfile)
// Route::middleware('throttle:60,1')->group(function () {
//     Route::get('/empresa/{slug}', [CompanyProfileController::class, 'show'])
//         ->name('company.profile');
//     Route::get('/empresa/{slug}/seguidores', [CompanyProfileController::class, 'followers'])
//         ->name('company.followers');
//     Route::get('/empresa/{slug}/siguiendo', [CompanyProfileController::class, 'following'])
//         ->name('company.following');
// });

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}/pdf', [DocumentPdfController::class, 'show'])
        ->name('documents.pdf');
    Route::get('/documents/{document}/download', [DocumentPdfController::class, 'download'])
        ->name('documents.pdf.download');

    // Rutas para PDFs de cuentas de cobro
    Route::get('/collection-accounts/{collectionAccount}/pdf', [CollectionAccountPdfController::class, 'show'])
        ->name('collection-accounts.pdf');
    Route::get('/collection-accounts/{collectionAccount}/download', [CollectionAccountPdfController::class, 'download'])
        ->name('collection-accounts.pdf.download');

    // Rutas para PDFs de órdenes de producción
    Route::get('/production-orders/{productionOrder}/pdf', [ProductionOrderPdfController::class, 'show'])
        ->name('production-orders.pdf');
    Route::get('/production-orders/{productionOrder}/download', [ProductionOrderPdfController::class, 'download'])
        ->name('production-orders.pdf.download');

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
// Usar POST por seguridad (CSRF protection)
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('simple.logout');

// Ruta GET que redirige a POST (compatibilidad)
Route::get('/logout', function () {
    return view('logout-form');
})->name('simple.logout.form');

// ============================================
// RUTA TEMPORAL PARA LIMPIAR DATOS DE PRUEBA
// ============================================
// IMPORTANTE: Eliminar esta ruta después de limpiar producción
Route::get('/admin/clean-test-data-temp', function() {
    // Solo en producción
    if (app()->environment() !== 'production') {
        abort(403, 'Solo disponible en producción');
    }

    // Verificar token de seguridad
    if (request()->query('token') !== 'GrafiRed2026Clean') {
        abort(403, 'Token inválido');
    }

    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Eliminar datos de prueba en orden correcto
        \App\Models\CollectionAccount::truncate();
        DB::table('document_item_production_order')->delete();
        \App\Models\ProductionOrder::query()->delete();
        DB::table('purchase_order_items')->delete();
        \App\Models\PurchaseOrder::query()->delete();
        DB::table('document_items')->delete();
        \App\Models\Document::query()->delete();
        \App\Models\User::whereNotNull('company_id')->delete();
        \App\Models\Company::query()->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Ejecutar seeder de producción
        Artisan::call('db:seed', [
            '--class' => 'MinimalProductionSeeder',
            '--force' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Datos de prueba eliminados y seeder ejecutado correctamente',
            'data' => [
                'companies_count' => \App\Models\Company::count(),
                'users_count' => \App\Models\User::count(),
                'plans_count' => \App\Models\Plan::count(),
                'roles_count' => \Spatie\Permission\Models\Role::count(),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => app()->environment('local') ? $e->getTraceAsString() : null
        ], 500);
    }
})->name('admin.clean-test-data-temp');
