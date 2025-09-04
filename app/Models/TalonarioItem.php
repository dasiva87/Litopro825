<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;
use App\Services\TalonarioCalculatorService;

class TalonarioItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'description',
        'quantity',
        'numero_inicial',
        'numero_final',
        'numeros_por_talonario',
        'prefijo',
        'ancho',
        'alto',
        'sheets_total_cost',
        'finishing_cost',
        'design_value',
        'transport_value',
        'profit_percentage',
        'total_cost',
        'final_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'numero_inicial' => 'integer',
        'numero_final' => 'integer',
        'numeros_por_talonario' => 'integer',
        'ancho' => 'decimal:2',
        'alto' => 'decimal:2',
        'sheets_total_cost' => 'decimal:2',
        'finishing_cost' => 'decimal:2',
        'design_value' => 'decimal:2',
        'transport_value' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->calculateAll();
        });
    }

    // Relaciones polimórficas
    public function documentItems(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'itemable');
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(TalonarioSheet::class);
    }

    public function simpleItems()
    {
        return $this->hasManyThrough(
            SimpleItem::class,
            TalonarioSheet::class,
            'talonario_item_id',
            'id',
            'id',
            'simple_item_id'
        );
    }

    public function finishings(): BelongsToMany
    {
        return $this->belongsToMany(Finishing::class, 'talonario_finishings')
            ->withPivot([
                'quantity',
                'unit_cost',
                'total_cost',
                'finishing_options',
                'notes'
            ])
            ->withTimestamps();
    }

    // Métodos de cálculo automático
    public function calculateSheetsTotal(): float
    {
        return $this->sheets->sum(function ($sheet) {
            return $sheet->simpleItem ? $sheet->simpleItem->final_price : 0;
        });
    }

    public function calculateFinishingCost(): float
    {
        return $this->finishings->sum('pivot.total_cost');
    }

    public function calculateTotalCost(): float
    {
        return ($this->sheets_total_cost ?? 0) +
               ($this->finishing_cost ?? 0) +
               ($this->design_value ?? 0) +
               ($this->transport_value ?? 0);
    }

    public function calculateFinalPrice(): float
    {
        $totalCost = $this->total_cost ?? 0;
        
        if (($this->profit_percentage ?? 0) > 0) {
            $totalCost = $totalCost * (1 + ($this->profit_percentage / 100));
        }

        return $totalCost;
    }

    public function calculateAll(): void
    {
        try {
            $calculator = new TalonarioCalculatorService();
            $pricingResult = $calculator->calculateFinalPricing($this);
            
            $this->sheets_total_cost = $pricingResult->sheetsCost;
            $this->finishing_cost = $pricingResult->finishingCost;
            $this->total_cost = $pricingResult->totalCost;
            $this->final_price = $pricingResult->finalPrice;
            
        } catch (\Exception $e) {
            // Fallback al sistema de cálculo local
            $this->calculateAllLegacy();
        }
    }

    private function calculateAllLegacy(): void
    {
        $this->sheets_total_cost = $this->calculateSheetsTotal();
        $this->finishing_cost = $this->calculateFinishingCost();
        $this->total_cost = $this->calculateTotalCost();
        $this->final_price = $this->calculateFinalPrice();
    }

    // Métodos de validación
    public function validateTechnicalViability(): array
    {
        $errors = [];
        $warnings = [];

        // Validar dimensiones
        if ($this->ancho <= 0 || $this->alto <= 0) {
            $errors[] = 'Las dimensiones del talonario deben ser mayores a 0';
        }

        // Validar rango de numeración
        if ($this->numero_final <= $this->numero_inicial) {
            $errors[] = 'El número final debe ser mayor al número inicial';
        }

        // Validar cantidad
        if ($this->quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0';
        }

        // Validar números por talonario
        if ($this->numeros_por_talonario <= 0 || $this->numeros_por_talonario > 100) {
            $warnings[] = 'Se recomienda entre 1 y 100 números por talonario';
        }

        // Validar hojas
        if ($this->sheets->count() === 0) {
            $errors[] = 'El talonario debe tener al menos una hoja';
        }

        // Validar hoja original
        $hasOriginal = $this->sheets->where('sheet_type', 'original')->count() > 0;
        if (!$hasOriginal) {
            $warnings[] = 'Se recomienda agregar una hoja original';
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'isValid' => empty($errors)
        ];
    }

    // Accessors útiles
    public function getTotalNumbersAttribute(): int
    {
        return ($this->numero_final - $this->numero_inicial) + 1;
    }

    public function getTotalTalonariosAttribute(): int
    {
        return ceil($this->total_numbers / $this->numeros_por_talonario);
    }

    public function getNumberingRangeAttribute(): string
    {
        return "Del {$this->prefijo}{$this->numero_inicial} al {$this->prefijo}{$this->numero_final}";
    }

    public function getSheetsPerTalonarioAttribute(): int
    {
        return $this->sheets->count();
    }

    public function getClosedAreaAttribute(): float
    {
        return $this->ancho * $this->alto;
    }

    // Métodos helper para gestión de hojas
    public function getNextSheetOrder(): int
    {
        return $this->sheets()->max('sheet_order') + 1;
    }

    public function hasSheetType(string $sheetType): bool
    {
        return $this->sheets()->where('sheet_type', $sheetType)->exists();
    }

    public function getSheetsTableData(): array
    {
        return $this->sheets()->with('simpleItem')->orderBy('sheet_order')->get()->map(function ($sheet) {
            return [
                'id' => $sheet->id,
                'type' => $sheet->sheet_type_name,
                'order' => $sheet->sheet_order,
                'color' => $sheet->paper_color,
                'description' => $sheet->simpleItem ? $sheet->simpleItem->description : 'Sin SimpleItem',
                'unit_price' => $sheet->simpleItem ? number_format($sheet->simpleItem->final_price / $sheet->simpleItem->quantity, 2) : '0.00',
                'total_cost' => $sheet->simpleItem ? number_format($sheet->simpleItem->final_price, 2) : '0.00',
                'simple_item_id' => $sheet->simple_item_id,
            ];
        })->toArray();
    }

    // Método para obtener el desglose detallado de costos
    public function getDetailedCostBreakdown(): array
    {
        try {
            $calculator = new TalonarioCalculatorService();
            return $calculator->getDetailedBreakdown($this);
        } catch (\Exception $e) {
            return [
                'sheets_cost' => $this->sheets_total_cost,
                'finishing_cost' => $this->finishing_cost,
                'design_value' => $this->design_value,
                'transport_value' => $this->transport_value,
                'subtotal' => $this->total_cost,
                'profit_percentage' => $this->profit_percentage,
                'profit_amount' => $this->final_price - $this->total_cost,
                'final_price' => $this->final_price,
            ];
        }
    }
}