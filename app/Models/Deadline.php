<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Deadline extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'user_id',
        'deadlinable_type',
        'deadlinable_id',
        'title',
        'description',
        'deadline_date',
        'deadline_type',
        'priority',
        'status',
        'reminder_sent',
        'reminder_date',
        'metadata',
    ];

    protected $casts = [
        'deadline_date' => 'datetime',
        'reminder_date' => 'datetime',
        'reminder_sent' => 'boolean',
        'metadata' => 'array',
    ];

    // Deadline types
    const TYPE_DOCUMENT_DELIVERY = 'document_delivery';
    const TYPE_QUOTATION_EXPIRY = 'quotation_expiry';
    const TYPE_PRODUCTION_DEADLINE = 'production_deadline';
    const TYPE_PAYMENT_DUE = 'payment_due';
    const TYPE_MATERIAL_ORDER = 'material_order';
    const TYPE_EQUIPMENT_MAINTENANCE = 'equipment_maintenance';
    const TYPE_CLIENT_FOLLOWUP = 'client_followup';
    const TYPE_CONTRACT_RENEWAL = 'contract_renewal';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    // Priorities
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deadlinable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('deadline_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
                    ->orWhere(function ($q) {
                        $q->where('status', self::STATUS_PENDING)
                          ->where('deadline_date', '<', now());
                    });
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('deadline_date', '>=', now())
                    ->where('deadline_date', '<=', now()->addDays($days))
                    ->orderBy('deadline_date');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    public function scopeNeedsReminder($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('reminder_sent', false)
                    ->where('reminder_date', '<=', now());
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->deadline_date->isPast();
    }

    public function getDaysUntilDeadline(): int
    {
        return $this->deadline_date->diffInDays(now(), false);
    }

    public function getDeadlineTypeLabel(): string
    {
        return match($this->deadline_type) {
            self::TYPE_DOCUMENT_DELIVERY => 'Entrega de Documento',
            self::TYPE_QUOTATION_EXPIRY => 'Vencimiento de Cotización',
            self::TYPE_PRODUCTION_DEADLINE => 'Fecha Límite de Producción',
            self::TYPE_PAYMENT_DUE => 'Vencimiento de Pago',
            self::TYPE_MATERIAL_ORDER => 'Pedido de Material',
            self::TYPE_EQUIPMENT_MAINTENANCE => 'Mantenimiento de Equipo',
            self::TYPE_CLIENT_FOLLOWUP => 'Seguimiento de Cliente',
            self::TYPE_CONTRACT_RENEWAL => 'Renovación de Contrato',
            default => 'Vencimiento',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_OVERDUE => 'Vencido',
            self::STATUS_CANCELLED => 'Cancelado',
            default => 'Desconocido',
        };
    }

    public function getStatusColor(): string
    {
        if ($this->isOverdue() && $this->status === self::STATUS_PENDING) {
            return 'danger';
        }

        return match($this->status) {
            self::STATUS_PENDING => $this->getPriorityColor(),
            self::STATUS_COMPLETED => 'success',
            self::STATUS_OVERDUE => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'info',
            self::PRIORITY_NORMAL => 'secondary',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger',
            default => 'secondary',
        };
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function markAsOverdue(): void
    {
        $this->update(['status' => self::STATUS_OVERDUE]);
    }

    public function sendReminder(): void
    {
        $this->update([
            'reminder_sent' => true,
            'reminder_date' => now(),
        ]);

        // TODO: Implement actual reminder notification
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($deadline) {
            // Set reminder date if not provided (1 day before deadline)
            if (!$deadline->reminder_date) {
                $deadline->reminder_date = $deadline->deadline_date->subDay();
            }
        });

        static::saving(function ($deadline) {
            // Auto-update status to overdue if past deadline
            if ($deadline->status === self::STATUS_PENDING && $deadline->deadline_date->isPast()) {
                $deadline->status = self::STATUS_OVERDUE;
            }
        });
    }

    public static function getDeadlineTypes(): array
    {
        return [
            self::TYPE_DOCUMENT_DELIVERY => 'Entrega de Documento',
            self::TYPE_QUOTATION_EXPIRY => 'Vencimiento de Cotización',
            self::TYPE_PRODUCTION_DEADLINE => 'Fecha Límite de Producción',
            self::TYPE_PAYMENT_DUE => 'Vencimiento de Pago',
            self::TYPE_MATERIAL_ORDER => 'Pedido de Material',
            self::TYPE_EQUIPMENT_MAINTENANCE => 'Mantenimiento de Equipo',
            self::TYPE_CLIENT_FOLLOWUP => 'Seguimiento de Cliente',
            self::TYPE_CONTRACT_RENEWAL => 'Renovación de Contrato',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_OVERDUE => 'Vencido',
            self::STATUS_CANCELLED => 'Cancelado',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Baja',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'Alta',
            self::PRIORITY_URGENT => 'Urgente',
        ];
    }
}