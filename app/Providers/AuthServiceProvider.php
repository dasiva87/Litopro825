<?php

namespace App\Providers;

use App\Models\CollectionAccount;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Finishing;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\SimpleItem;
use App\Models\SupplierRequest;
use App\Models\User;
use App\Policies\CollectionAccountPolicy;
use App\Policies\ContactPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\FinishingPolicy;
use App\Policies\PaperPolicy;
use App\Policies\PrintingMachinePolicy;
use App\Policies\ProductionOrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProjectPolicy;
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
        Project::class => ProjectPolicy::class,
        Contact::class => ContactPolicy::class,
        Product::class => ProductPolicy::class,
        SimpleItem::class => SimpleItemPolicy::class,

        // Orders & Accounting
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        ProductionOrder::class => ProductionOrderPolicy::class,
        CollectionAccount::class => CollectionAccountPolicy::class,
        SupplierRequest::class => SupplierRequestPolicy::class,

        // Configuration & Resources
        Paper::class => PaperPolicy::class,
        PrintingMachine::class => PrintingMachinePolicy::class,
        Finishing::class => FinishingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
