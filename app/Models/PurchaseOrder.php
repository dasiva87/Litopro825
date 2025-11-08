<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Notifications\PurchaseOrderCreated;
use App\Notifications\PurchaseOrderStatusChanged;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PurchaseOrder extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'company_id',
        'supplier_company_id',
        'supplier_id',
        'order_number',
        'status',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'total_amount',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'status' => OrderStatus::class,
    ];

    protected static function booted(): void
    {

        static::creating(function (PurchaseOrder $order) {
            if (! $order->company_id) {
                $order->company_id = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : null);
            }

            if (! $order->order_number) {
                $order->order_number = static::generateOrderNumber();
            }

            if (! $order->created_by) {
                $order->created_by = auth()->id();
            }
        });

        static::created(function (PurchaseOrder $order) {
            // Crear registro de historial inicial
            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => $order->status,
                'user_id' => auth()->id(),
            ]);

            // Enviar notificación al proveedor cuando se crea la orden con estado 'sent'
            if ($order->status === OrderStatus::SENT && $order->supplierCompany && $order->supplierCompany->email) {
                Notification::route('mail', $order->supplierCompany->email)
                    ->notify(new PurchaseOrderCreated($order->id));
            }

            // Notificar a los usuarios de la empresa creadora
            $companyUsers = User::forTenant($order->company_id)->get();
            Notification::send($companyUsers, new PurchaseOrderCreated($order->id));
        });

        static::updating(function (PurchaseOrder $order) {
            // Detectar cambios de estado
            if ($order->isDirty('status')) {
                $oldStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                // Programar notificación después de actualizar
                static::updated(function (PurchaseOrder $updatedOrder) use ($oldStatus, $newStatus) {
                    // Crear registro de historial
                    $updatedOrder->statusHistories()->create([
                        'from_status' => $oldStatus,
                        'to_status' => $newStatus,
                        'user_id' => auth()->id(),
                    ]);

                    // Notificar a usuarios de la empresa que envía
                    $companyUsers = User::where('company_id', $updatedOrder->company_id)->get();
                    Notification::send($companyUsers, new PurchaseOrderStatusChanged(
                        $updatedOrder->id,
                        $oldStatus instanceof OrderStatus ? $oldStatus->value : $oldStatus,
                        $newStatus instanceof OrderStatus ? $newStatus->value : $newStatus
                    ));

                    // Si el estado cambia a 'sent', notificar a usuarios del proveedor
                    if ($newStatus === OrderStatus::SENT && $updatedOrder->supplierCompany) {
                        // Notificar a usuarios del proveedor (notificación en app + email)
                        $supplierUsers = User::where('company_id', $updatedOrder->supplier_company_id)->get();
                        if ($supplierUsers->isNotEmpty()) {
                            Notification::send($supplierUsers, new PurchaseOrderCreated($updatedOrder->id));
                        }

                        // Email adicional al email general del proveedor si existe
                        if ($updatedOrder->supplierCompany->email) {
                            Notification::route('mail', $updatedOrder->supplierCompany->email)
                                ->notify(new PurchaseOrderCreated($updatedOrder->id));
                        }
                    }

                    // Si el estado cambia a 'confirmed' o 'received', notificar a la empresa que envió
                    if (in_array($newStatus, [OrderStatus::CONFIRMED, OrderStatus::RECEIVED])) {
                        // Notificar por email a la empresa cliente
                        $clientCompany = $updatedOrder->company;
                        if ($clientCompany && $clientCompany->email) {
                            Notification::route('mail', $clientCompany->email)
                                ->notify(new PurchaseOrderStatusChanged(
                                    $updatedOrder->id,
                                    $oldStatus instanceof OrderStatus ? $oldStatus->value : $oldStatus,
                                    $newStatus instanceof OrderStatus ? $newStatus->value : $newStatus
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

    public function supplierCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
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
        return $this->belongsToMany(DocumentItem::class, 'document_item_purchase_order')
            ->withPivot([
                'quantity_ordered',
                'unit_price',
                'total_price',
                'status',
                'notes',
                'paper_id',
                'paper_description',
                'sheets_quantity',
            ])
            ->withTimestamps();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Relación directa con la tabla pivot (para mostrar cada fila por separado)
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Obtener todas las cotizaciones (documents) únicas relacionadas con esta orden
     */
    public function documents()
    {
        return Document::whereIn('id',
            $this->documentItems()->pluck('document_id')->unique()
        )->get();
    }

    public function recalculateTotal(): void
    {
        $total = DB::table('document_item_purchase_order')
            ->where('purchase_order_id', $this->id)
            ->sum('total_price');

        $this->total_amount = $total ?? 0;
        $this->save();
    }

    public static function generateOrderNumber(): string
    {
        $companyId = TenantContext::id() ?? 1;

        // Obtener el último número para este tipo de orden (sin filtrar por año)
        $lastOrder = static::forTenant($companyId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastOrder) {
            // Extraer el número del último documento
            preg_match('/(\d+)$/', $lastOrder->order_number, $matches);
            $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        }

        return str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function changeStatus(OrderStatus $newStatus, ?string $notes = null): bool
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;
        $this->save();

        // El historial se crea automáticamente en el observer

        return true;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [OrderStatus::DRAFT, OrderStatus::SENT, OrderStatus::CONFIRMED]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === OrderStatus::DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [OrderStatus::DRAFT, OrderStatus::SENT, OrderStatus::CONFIRMED]);
    }

    /**
     * Get the supplier name (from Company or Contact)
     */
    public function getSupplierNameAttribute(): string
    {
        if ($this->supplier_id && $this->supplier) {
            return $this->supplier->name;
        }

        if ($this->supplier_company_id && $this->supplierCompany) {
            return $this->supplierCompany->name;
        }

        return 'Sin proveedor';
    }

    /**
     * Get the supplier email (from Company or Contact)
     */
    public function getSupplierEmailAttribute(): ?string
    {
        if ($this->supplier_id && $this->supplier) {
            return $this->supplier->email;
        }

        if ($this->supplier_company_id && $this->supplierCompany) {
            return $this->supplierCompany->email;
        }

        return null;
    }
}
