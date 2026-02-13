<?php

namespace App\Models;

use App\Enums\FinishingMeasurementUnit;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Finishing extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'code',
        'name',
        'description',
        'unit_price',
        'measurement_unit',
        'is_own_provider',
        'active',
        'is_public',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'measurement_unit' => FinishingMeasurementUnit::class,
        'is_own_provider' => 'boolean',
        'active' => 'boolean',
        'is_public' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($finishing) {
            // Generar código único si no tiene
            if (empty($finishing->code)) {
                $finishing->code = static::generateUniqueCode($finishing->company_id);
            }

            // Si es acabado propio y no tiene supplier_id, asignar la empresa como proveedor
            if ($finishing->is_own_provider && empty($finishing->supplier_id)) {
                $finishing->supplier_id = static::getSelfContactId($finishing->company_id);
            }
        });

        static::updating(function ($finishing) {
            // Si cambia a acabado propio, asignar la empresa como proveedor
            if ($finishing->is_own_provider && $finishing->isDirty('is_own_provider')) {
                $finishing->supplier_id = static::getSelfContactId($finishing->company_id);
            }

            // Si cambia de propio a externo y el supplier es la empresa propia, limpiar supplier_id
            if (!$finishing->is_own_provider && $finishing->isDirty('is_own_provider')) {
                $selfContactId = static::getSelfContactId($finishing->company_id);
                if ($finishing->supplier_id === $selfContactId) {
                    $finishing->supplier_id = null;
                }
            }
        });
    }

    /**
     * Obtener o crear el contacto autorreferencial de la empresa
     */
    protected static function getSelfContactId(int $companyId): ?int
    {
        // Buscar contacto autorreferencial existente
        $company = \App\Models\Company::find($companyId);
        if (!$company) {
            return null;
        }

        $selfContactName = $company->name . ' (Producción Propia)';

        // Buscar contacto existente con búsqueda exacta
        $selfContact = \App\Models\Contact::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('name', $selfContactName)
            ->first();

        // Si no existe, crearlo
        if (!$selfContact) {
            // Generar email seguro usando Str::slug para evitar caracteres inválidos
            $slug = Str::slug($company->name, '');
            $email = 'produccion@' . ($slug ?: 'empresa') . '.internal';

            $selfContact = \App\Models\Contact::create([
                'company_id' => $companyId,
                'type' => 'supplier', // Es un proveedor interno
                'name' => $selfContactName,
                'email' => $email,
                'is_local' => true,
            ]);
        }

        return $selfContact->id;
    }

    /**
     * Generar código único para el acabado
     */
    protected static function generateUniqueCode(int $companyId): string
    {
        $prefix = 'ACB';
        $lastFinishing = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('code', 'LIKE', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC')
            ->first();

        if ($lastFinishing && preg_match('/^' . $prefix . '(\d+)$/', $lastFinishing->code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relación con proveedor
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    /**
     * Relación con rangos de precios (solo para measurement_unit = 'rango')
     */
    public function ranges(): HasMany
    {
        return $this->hasMany(FinishingRange::class);
    }

    /**
     * Relación con DigitalItems a través de tabla pivote
     */
    public function digitalItems(): BelongsToMany
    {
        return $this->belongsToMany(DigitalItem::class, 'digital_item_finishing')
            ->withPivot(['quantity', 'width', 'height', 'calculated_cost'])
            ->withTimestamps();
    }

    /**
     * Relación con SimpleItems a través de tabla pivote
     */
    public function simpleItems(): BelongsToMany
    {
        return $this->belongsToMany(SimpleItem::class, 'simple_item_finishing')
            ->withPivot(['quantity', 'width', 'height', 'calculated_cost', 'is_default', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Relación con Products a través de tabla pivote
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_finishing')
            ->withPivot(['quantity', 'width', 'height', 'calculated_cost'])
            ->withTimestamps();
    }

    /**
     * Scope para acabados activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope por tipo de medida
     */
    public function scopeByMeasurementUnit($query, FinishingMeasurementUnit $unit)
    {
        return $query->where('measurement_unit', $unit);
    }

    /**
     * Verificar si necesita rangos
     */
    public function needsRanges(): bool
    {
        return $this->measurement_unit === FinishingMeasurementUnit::RANGO;
    }

    /**
     * Verificar si necesita dimensiones
     */
    public function needsDimensions(): bool
    {
        return $this->measurement_unit === FinishingMeasurementUnit::TAMAÑO;
    }

    /**
     * Obtener el nombre del proveedor
     */
    public function getProviderNameAttribute(): string
    {
        return $this->is_own_provider ? 'Propio' : 'Tercero';
    }
}
