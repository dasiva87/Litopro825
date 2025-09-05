<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class CustomItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calcular total automáticamente
            $item->total_price = $item->quantity * $item->unit_price;
        });
    }

    // Relaciones polimórficas
    public function documentItems(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'itemable');
    }

    // Métodos de acceso rápido
    public function getFormattedUnitPriceAttribute(): string
    {
        return '$' . number_format($this->unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return '$' . number_format($this->total_price, 2);
    }

    // Método para validar que los datos están completos
    public function isValid(): bool
    {
        return !empty($this->description) && 
               $this->quantity > 0 && 
               $this->unit_price >= 0;
    }

    // Método para actualizar cálculos manualmente si es necesario
    public function recalculate(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
    }
}