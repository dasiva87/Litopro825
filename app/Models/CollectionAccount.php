<?php

namespace App\Models;

use App\Enums\CollectionAccountStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Notifications\CollectionAccountSent;
use App\Notifications\CollectionAccountStatusChanged;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CollectionAccount extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'company_id',
        'project_id',
        'client_company_id',
        'contact_id',
        'account_number',
        'status',
        'issue_date',
        'due_date',
        'paid_date',
        'total_amount',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'email_sent_at',
        'email_sent_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'status' => CollectionAccountStatus::class,
        'email_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CollectionAccount $account) {
            if (! $account->company_id) {
                $account->company_id = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : null);
            }

            if (! $account->account_number) {
                $account->account_number = static::generateAccountNumber();
            }

            if (! $account->created_by) {
                $account->created_by = auth()->id();
            }
        });

        static::created(function (CollectionAccount $account) {
            // Crear registro de historial inicial
            $account->statusHistories()->create([
                'from_status' => null,
                'to_status' => $account->status,
                'user_id' => auth()->id(),
            ]);

            // ❌ DESACTIVADO: Notificaciones automáticas
            // No se envían notificaciones ni emails al crear cuentas de cobro
        });

        static::updating(function (CollectionAccount $account) {
            // Detectar cambios de estado
            if ($account->isDirty('status')) {
                $oldStatus = $account->getOriginal('status');
                $newStatus = $account->status;

                // Crear registro de historial después de actualizar
                static::updated(function (CollectionAccount $updatedAccount) use ($oldStatus, $newStatus) {
                    $updatedAccount->statusHistories()->create([
                        'from_status' => $oldStatus,
                        'to_status' => $newStatus,
                        'user_id' => auth()->id(),
                    ]);

                    // ❌ DESACTIVADO: Notificaciones y emails automáticos
                    // No se envían notificaciones al cambiar de estado
                });
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function clientCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'client_company_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function emailSentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email_sent_by');
    }

    public function documentItems(): BelongsToMany
    {
        return $this->belongsToMany(DocumentItem::class, 'document_item_collection_account')
            ->withPivot([
                'quantity_ordered',
                'unit_price',
                'total_price',
                'status',
                'notes',
            ])
            ->withTimestamps();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CollectionAccountStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Obtener todas las cotizaciones (documents) únicas relacionadas con esta cuenta
     */
    public function documents()
    {
        return Document::whereIn('id',
            $this->documentItems()->pluck('document_id')->unique()
        )->get();
    }

    public function recalculateTotal(): void
    {
        $total = DB::table('document_item_collection_account')
            ->where('collection_account_id', $this->id)
            ->sum('total_price');

        $this->total_amount = $total ?? 0;
        $this->save();
    }

    public static function generateAccountNumber(): string
    {
        $companyId = TenantContext::id() ?? 1;

        // Obtener el último número para este tipo de cuenta (sin filtrar por año)
        $lastAccount = static::forTenant($companyId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastAccount) {
            // Extraer el número del último documento
            preg_match('/(\d+)$/', $lastAccount->account_number, $matches);
            $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        }

        return str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function changeStatus(CollectionAccountStatus $newStatus, ?string $notes = null): bool
    {
        // Permitir cualquier cambio de estado sin restricciones
        $this->status = $newStatus;

        // Si se cambia a pagada, registrar fecha de pago
        if ($newStatus === CollectionAccountStatus::PAID && ! $this->paid_date) {
            $this->paid_date = now();
        }

        $this->save();

        return true;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Get the client name (from Company or Contact)
     */
    public function getClientNameAttribute(): string
    {
        if ($this->contact_id && $this->contact) {
            return $this->contact->name;
        }

        if ($this->client_company_id && $this->clientCompany) {
            return $this->clientCompany->name;
        }

        return 'Sin cliente';
    }

    /**
     * Get the client email (from Company or Contact)
     */
    public function getClientEmailAttribute(): ?string
    {
        if ($this->contact_id && $this->contact) {
            return $this->contact->email;
        }

        if ($this->client_company_id && $this->clientCompany) {
            return $this->clientCompany->email;
        }

        return null;
    }
}
