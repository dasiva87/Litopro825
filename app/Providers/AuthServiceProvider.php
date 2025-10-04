<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\Document;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SimpleItem;
use App\Models\SupplierRequest;
use App\Models\User;
use App\Policies\ContactPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\RolePolicy;
use App\Policies\SimpleItemPolicy;
use App\Policies\SupplierRequestPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // User & Role Management
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,

        // Core Business Models
        Document::class => DocumentPolicy::class,
        Contact::class => ContactPolicy::class,
        Product::class => ProductPolicy::class,
        SimpleItem::class => SimpleItemPolicy::class,

        // Purchase Orders
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        SupplierRequest::class => SupplierRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
