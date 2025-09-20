<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class Document extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'contact_id',
        'document_type_id',
        'document_number',
        'reference',
        'date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'tax_percentage',
        'total',
        'notes',
        'internal_notes',
        'valid_until',
        'version',
        'parent_document_id',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'total' => 'decimal:2',
        'version' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generar número de documento
        static::creating(function ($document) {
            if (empty($document->document_number)) {
                $document->document_number = $document->generateDocumentNumber();
            }
        });
    }

    // Relaciones principales
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    // Relación con items del documento
    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class, 'document_id');
    }

    // Relaciones de versionado
    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function childVersions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $typeCode)
    {
        return $query->whereHas('documentType', function ($q) use ($typeCode) {
            $q->where('code', $typeCode);
        });
    }

    public function scopeQuotes($query)
    {
        return $query->byType(DocumentType::QUOTE);
    }

    public function scopeOrders($query)
    {
        return $query->byType(DocumentType::ORDER);
    }

    public function scopeInvoices($query)
    {
        return $query->byType(DocumentType::INVOICE);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'rejected']);
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('valid_until', '<=', now()->addDays($days))
                    ->where('status', 'sent');
    }

    // Métodos de estado
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isInProduction(): bool
    {
        return $this->status === 'in_production';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canSend(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    public function canApprove(): bool
    {
        return $this->status === 'sent';
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    // Métodos de tipo de documento
    public function isQuote(): bool
    {
        return $this->documentType->code === DocumentType::QUOTE;
    }

    public function isOrder(): bool
    {
        return $this->documentType->code === DocumentType::ORDER;
    }

    public function isInvoice(): bool
    {
        return $this->documentType->code === DocumentType::INVOICE;
    }

    // Métodos de cálculo
    public function calculateTotals(): void
    {
        // Calcular subtotal basado en los items polimórficos
        $subtotal = 0;
        foreach ($this->items as $item) {
            $itemTotal = 0;
            
            if ($item->itemable_type === 'App\\Models\\Product') {
                // Para productos, usar total_price del DocumentItem
                $itemTotal = $item->total_price ?? 0;
            } elseif ($item->itemable_type === 'App\\Models\\DigitalItem') {
                // Para items digitales, usar total_price del DocumentItem (ya calculado)
                $itemTotal = $item->total_price ?? 0;
            } elseif ($item->itemable && isset($item->itemable->final_price)) {
                // Para SimpleItems y otros, usar final_price del item relacionado
                $itemTotal = $item->itemable->final_price;
            } else {
                // Fallback: usar total_price del DocumentItem
                $itemTotal = $item->total_price ?? 0;
            }
            
            $subtotal += $itemTotal;
        }
        $this->subtotal = $subtotal;
        
        // Calcular descuento
        if ($this->discount_percentage > 0) {
            $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        }
        
        $subtotalAfterDiscount = $this->subtotal - $this->discount_amount;
        
        // Calcular impuestos
        if ($this->tax_percentage > 0) {
            $this->tax_amount = $subtotalAfterDiscount * ($this->tax_percentage / 100);
        }
        
        $this->total = $subtotalAfterDiscount + $this->tax_amount;
        $this->save();
    }
    
    public function recalculateTotals(): void
    {
        $this->load('items.itemable');
        $this->calculateTotals();
    }

    // Generación de número de documento
    public function generateDocumentNumber(): string
    {
        $prefix = $this->getDocumentPrefix();
        $year = now()->year;
        $companyId = $this->company_id ?? auth()->user()->company_id;
        
        // Obtener el último número para este tipo de documento
        $lastDocument = self::where('company_id', $companyId)
            ->where('document_type_id', $this->document_type_id)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = 1;
        if ($lastDocument) {
            // Extraer el número del último documento
            preg_match('/(\d+)$/', $lastDocument->document_number, $matches);
            $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        }
        
        return $prefix . '-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    private function getDocumentPrefix(): string
    {
        return match($this->documentType?->code ?? DocumentType::QUOTE) {
            DocumentType::QUOTE => 'COT',
            DocumentType::ORDER => 'ORD',
            DocumentType::INVOICE => 'FAC',
            DocumentType::PAPER => 'PAP',
            DocumentType::PURCHASE => 'COM',
            DocumentType::DELIVERY => 'REM',
            default => 'DOC',
        };
    }

    // Métodos de transición de estado
    public function markAsSent(): bool
    {
        if (!$this->canSend()) {
            return false;
        }
        
        $this->status = 'sent';
        return $this->save();
    }

    public function markAsApproved(): bool
    {
        if (!$this->canApprove()) {
            return false;
        }
        
        $this->status = 'approved';
        return $this->save();
    }

    public function markAsRejected(string $reason = null): bool
    {
        if (!$this->canApprove()) {
            return false;
        }
        
        $this->status = 'rejected';
        if ($reason) {
            $this->internal_notes = ($this->internal_notes ? $this->internal_notes . "\n\n" : '') . 
                                   "Rechazado: " . $reason;
        }
        return $this->save();
    }

    // Crear nueva versión
    public function createNewVersion(): Document
    {
        $newDocument = $this->replicate();
        $newDocument->version = $this->version + 1;
        $newDocument->parent_document_id = $this->id;
        $newDocument->status = 'draft';
        $newDocument->document_number = null; // Se generará automáticamente
        $newDocument->save();

        // Duplicar items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->document_id = $newDocument->id;
            $newItem->save();

            // Duplicar acabados del item si existen
            foreach ($item->finishings as $finishing) {
                $newFinishing = $finishing->replicate();
                $newFinishing->document_item_id = $newItem->id;
                $newFinishing->save();
            }
        }

        return $newDocument;
    }

    // Constantes de estado
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_PRODUCTION = 'in_production';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
}