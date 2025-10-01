<?php

use Illuminate\Support\Facades\Route;

// Debug route to check tenant context (only in non-production)
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
        ]);
    })->middleware(['web', 'auth']);
}