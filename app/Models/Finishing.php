<?php

namespace App\Models;

use App\Enums\FinishingMeasurementUnit;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Finishing extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'unit_price',
        'measurement_unit',
        'is_own_provider',
        'active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'measurement_unit' => FinishingMeasurementUnit::class,
        'is_own_provider' => 'boolean',
        'active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($finishing) {
            if (empty($finishing->code)) {
                $finishing->code = 'FIN-' . strtoupper(Str::random(6));
            }
        });
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
