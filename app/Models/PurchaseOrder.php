<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Notifications\PurchaseOrderCreated;
use App\Notifications\PurchaseOrderStatusChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PurchaseOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'supplier_company_id',
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
            // Enviar notificación al proveedor cuando se crea la orden
            if ($order->status === 'sent' && $order->supplierCompany && $order->supplierCompany->email) {
                Notification::route('mail', $order->supplierCompany->email)
                    ->notify(new PurchaseOrderCreated($order));
            }

            // Notificar a los usuarios de la empresa creadora
            $companyUsers = User::where('company_id', $order->company_id)->get();
            Notification::send($companyUsers, new PurchaseOrderCreated($order));
        });

        static::updating(function (PurchaseOrder $order) {
            // Detectar cambios de estado
            if ($order->isDirty('status')) {
                $oldStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                // Programar notificación después de actualizar
                static::updated(function (PurchaseOrder $updatedOrder) use ($oldStatus, $newStatus) {
                    // Notificar a usuarios de la empresa
                    $companyUsers = User::where('company_id', $updatedOrder->company_id)->get();
                    Notification::send($companyUsers, new PurchaseOrderStatusChanged($updatedOrder, $oldStatus, $newStatus));

                    // Si el estado cambia a confirmado o completado, notificar también al proveedor
                    if (in_array($newStatus, ['confirmed', 'completed']) && $updatedOrder->supplierCompany && $updatedOrder->supplierCompany->email) {
                        Notification::route('mail', $updatedOrder->supplierCompany->email)
                            ->notify(new PurchaseOrderStatusChanged($updatedOrder, $oldStatus, $newStatus));
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
                    ])
                    ->withTimestamps();
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
        $companyId = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : 1);
        $year = now()->year;
        $month = now()->format('m');
        $prefix = "OP-{$year}{$month}-";

        // Buscar el número más alto en el mes (no solo el último por ID)
        $maxOrderNumber = static::where('company_id', $companyId)
            ->where('order_number', 'LIKE', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(order_number, -4) AS UNSIGNED) DESC')
            ->value('order_number');

        $sequence = $maxOrderNumber ? (int) substr($maxOrderNumber, -4) + 1 : 1;

        // Generar número con retry en caso de colisión (máximo 10 intentos)
        $attempts = 0;
        do {
            $orderNumber = $prefix.str_pad($sequence + $attempts, 4, '0', STR_PAD_LEFT);

            $exists = static::where('company_id', $companyId)
                ->where('order_number', $orderNumber)
                ->exists();

            if (!$exists) {
                return $orderNumber;
            }

            $attempts++;
        } while ($attempts < 10);

        // Si después de 10 intentos aún hay colisión, usar timestamp
        return $prefix.substr(time(), -4);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'sent' => 'Enviada',
            'confirmed' => 'Confirmada',
            'partially_received' => 'Parcialmente Recibida',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => 'Desconocido'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'sent' => 'warning',
            'confirmed' => 'info',
            'partially_received' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray'
        };
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'confirmed', 'partially_received']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'confirmed']);
    }
}
