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

        $selfContact = \App\Models\Contact::where('company_id', $companyId)
            ->where('name', 'LIKE', $company->name . ' (Producción Propia)')
            ->first();

        // Si no existe, crearlo
        if (!$selfContact) {
            $selfContact = \App\Models\Contact::create([
                'company_id' => $companyId,
                'name' => $company->name . ' (Producción Propia)',
                'email' => 'produccion@' . strtolower(str_replace(' ', '', $company->name)) . '.com',
            ]);
        }

        return $selfContact->id;
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
