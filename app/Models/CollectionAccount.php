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
        'client_company_id',
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
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'status' => CollectionAccountStatus::class,
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

            // Enviar notificación al cliente cuando se crea con estado 'sent'
            if ($account->status === CollectionAccountStatus::SENT && $account->clientCompany && $account->clientCompany->email) {
                Notification::route('mail', $account->clientCompany->email)
                    ->notify(new CollectionAccountSent($account->id));
            }

            // Notificar a los usuarios de la empresa creadora
            $companyUsers = User::forTenant($account->company_id)->get();
            if ($companyUsers->isNotEmpty()) {
                Notification::send($companyUsers, new CollectionAccountSent($account->id));
            }
        });

        static::updating(function (CollectionAccount $account) {
            // Detectar cambios de estado
            if ($account->isDirty('status')) {
                $oldStatus = $account->getOriginal('status');
                $newStatus = $account->status;

                // Crear registro de historial y notificar después de actualizar
                static::updated(function (CollectionAccount $updatedAccount) use ($oldStatus, $newStatus) {
                    $updatedAccount->statusHistories()->create([
                        'from_status' => $oldStatus,
                        'to_status' => $newStatus,
                        'user_id' => auth()->id(),
                    ]);

                    // Notificar a usuarios de la empresa emisora
                    $companyUsers = User::where('company_id', $updatedAccount->company_id)->get();
                    if ($companyUsers->isNotEmpty()) {
                        Notification::send($companyUsers, new CollectionAccountStatusChanged(
                            $updatedAccount->id,
                            $oldStatus instanceof CollectionAccountStatus ? $oldStatus->value : $oldStatus,
                            $newStatus instanceof CollectionAccountStatus ? $newStatus->value : $newStatus
                        ));
                    }

                    // Si el estado cambia a 'sent', notificar a usuarios del cliente
                    if ($newStatus === CollectionAccountStatus::SENT && $updatedAccount->clientCompany) {
                        // Notificar a usuarios del cliente (notificación en app + email)
                        $clientUsers = User::where('company_id', $updatedAccount->client_company_id)->get();
                        if ($clientUsers->isNotEmpty()) {
                            Notification::send($clientUsers, new CollectionAccountSent($updatedAccount->id));
                        }

                        // Email adicional al email general del cliente si existe
                        if ($updatedAccount->clientCompany->email) {
                            Notification::route('mail', $updatedAccount->clientCompany->email)
                                ->notify(new CollectionAccountSent($updatedAccount->id));
                        }
                    }

                    // Si el estado cambia a 'approved' o 'paid', notificar a la empresa emisora
                    if (in_array($newStatus, [CollectionAccountStatus::APPROVED, CollectionAccountStatus::PAID])) {
                        // Notificar por email a la empresa emisora
                        $emitterCompany = $updatedAccount->company;
                        if ($emitterCompany && $emitterCompany->email) {
                            Notification::route('mail', $emitterCompany->email)
                                ->notify(new CollectionAccountStatusChanged(
                                    $updatedAccount->id,
                                    $oldStatus instanceof CollectionAccountStatus ? $oldStatus->value : $oldStatus,
                                    $newStatus instanceof CollectionAccountStatus ? $newStatus->value : $newStatus
                                ));
                        }
                    }
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
        $year = now()->year;
        $prefix = "COB-{$year}-";

        // Buscar el número más alto en el año
        $maxAccountNumber = static::forTenant($companyId)
            ->where('account_number', 'LIKE', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(account_number, -4) AS UNSIGNED) DESC')
            ->value('account_number');

        $sequence = $maxAccountNumber ? (int) substr($maxAccountNumber, -4) + 1 : 1;

        // Generar número con retry en caso de colisión (máximo 10 intentos)
        $attempts = 0;
        do {
            $accountNumber = $prefix.str_pad($sequence + $attempts, 4, '0', STR_PAD_LEFT);

            $exists = static::forTenant($companyId)
                ->where('account_number', $accountNumber)
                ->exists();

            if (! $exists) {
                return $accountNumber;
            }

            $attempts++;
        } while ($attempts < 10);

        // Si después de 10 intentos aún hay colisión, usar timestamp
        return $prefix.substr(time(), -4);
    }

    public function changeStatus(CollectionAccountStatus $newStatus, ?string $notes = null): bool
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            return false;
        }

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
}
