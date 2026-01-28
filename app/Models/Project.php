<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Project extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'contact_id',
        'name',
        'code',
        'description',
        'status',
        'start_date',
        'estimated_end_date',
        'actual_end_date',
        'budget',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'estimated_end_date' => 'date',
        'actual_end_date' => 'date',
        'budget' => 'decimal:2',
        'status' => ProjectStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (!$project->code) {
                $project->code = static::generateCode();
            }
            if (!$project->created_by && auth()->check()) {
                $project->created_by = auth()->id();
            }
        });
    }

    // Relationships

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function collectionAccounts(): HasMany
    {
        return $this->hasMany(CollectionAccount::class);
    }

    // Accessors

    public function getClientNameAttribute(): ?string
    {
        return $this->contact?->name;
    }

    public function getTotalDocumentsAttribute(): int
    {
        return $this->documents()->count();
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->documents()->sum('total') ?? 0;
    }

    public function getCompletionPercentageAttribute(): int
    {
        $hasQuotes = $this->documents()->exists();
        $hasPurchaseOrders = $this->purchaseOrders()->exists();
        $hasProductionOrders = $this->productionOrders()->exists();
        $hasCollectionAccounts = $this->collectionAccounts()->exists();

        if (!$hasQuotes) {
            return 0;
        }

        $completedSteps = 1; // Has quotes = 25%

        if ($hasPurchaseOrders) {
            $completedSteps++;
        }
        if ($hasProductionOrders) {
            $completedSteps++;
        }
        if ($hasCollectionAccounts) {
            // Check if any is paid
            $hasPaid = $this->collectionAccounts()
                ->where('status', 'paid')
                ->exists();
            if ($hasPaid) {
                return 100;
            }
            $completedSteps++;
        }

        return (int) (($completedSteps / 4) * 100);
    }

    // Code Generation

    public static function generateCode(): string
    {
        $companyId = auth()->user()?->company_id ?? config('app.current_tenant_id') ?? 1;
        $year = now()->format('Y');

        $lastProject = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastProject && preg_match('/(\d+)$/', $lastProject->code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return 'PROY-'.$year.'-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    // Timeline

    public function getTimeline(): Collection
    {
        $timeline = collect();

        // Add documents
        foreach ($this->documents()->with(['documentType', 'contact'])->get() as $doc) {
            $timeline->push([
                'date' => $doc->date,
                'type' => 'document',
                'icon' => 'heroicon-o-document-text',
                'title' => ($doc->documentType?->name ?? 'Documento').' '.$doc->document_number,
                'status' => $doc->status,
                'description' => $doc->contact?->name ?? 'Sin cliente',
                'amount' => $doc->total,
                'model' => $doc,
            ]);
        }

        // Add purchase orders
        foreach ($this->purchaseOrders()->with('supplier')->get() as $order) {
            $timeline->push([
                'date' => $order->order_date,
                'type' => 'purchase_order',
                'icon' => 'heroicon-o-clipboard-document-list',
                'title' => 'Orden de Pedido '.$order->order_number,
                'status' => $order->status->value ?? $order->status,
                'description' => 'Proveedor: '.($order->supplier?->name ?? 'N/A'),
                'amount' => $order->total_amount,
                'model' => $order,
            ]);
        }

        // Add production orders
        foreach ($this->productionOrders()->with('supplier')->get() as $order) {
            $timeline->push([
                'date' => $order->scheduled_date ?? $order->created_at,
                'type' => 'production_order',
                'icon' => 'heroicon-o-cog-6-tooth',
                'title' => 'Orden de Producción '.$order->production_number,
                'status' => $order->status->value ?? $order->status,
                'description' => 'Proveedor: '.($order->supplier?->name ?? 'N/A'),
                'amount' => null,
                'model' => $order,
            ]);
        }

        // Add collection accounts
        foreach ($this->collectionAccounts()->with('contact')->get() as $account) {
            $timeline->push([
                'date' => $account->issue_date,
                'type' => 'collection_account',
                'icon' => 'heroicon-o-banknotes',
                'title' => 'Cuenta de Cobro '.$account->account_number,
                'status' => $account->status->value ?? $account->status,
                'description' => 'Cliente: '.($account->contact?->name ?? 'N/A'),
                'amount' => $account->total_amount,
                'model' => $account,
            ]);
        }

        return $timeline->sortByDesc('date');
    }
}
