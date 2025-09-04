<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalonarioSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'talonario_item_id',
        'simple_item_id',
        'sheet_type',
        'sheet_order',
        'paper_color',
        'sheet_notes',
    ];

    protected $casts = [
        'talonario_item_id' => 'integer',
        'simple_item_id' => 'integer',
        'sheet_order' => 'integer',
    ];

    // Relaciones
    public function talonarioItem(): BelongsTo
    {
        return $this->belongsTo(TalonarioItem::class);
    }

    public function simpleItem(): BelongsTo
    {
        return $this->belongsTo(SimpleItem::class);
    }

    // Accessors
    public function getSheetTypeNameAttribute(): string
    {
        return match($this->sheet_type) {
            'original' => 'Original',
            'copia_1' => '1ª Copia',
            'copia_2' => '2ª Copia', 
            'copia_3' => '3ª Copia',
            default => ucfirst($this->sheet_type)
        };
    }

    public function getPaperColorNameAttribute(): string
    {
        return match($this->paper_color) {
            'blanco' => 'Blanco',
            'amarillo' => 'Amarillo',
            'rosado' => 'Rosado',
            'azul' => 'Azul',
            'verde' => 'Verde',
            'naranja' => 'Naranja',
            default => ucfirst($this->paper_color)
        };
    }

    public function getFullDescriptionAttribute(): string
    {
        return $this->sheet_type_name . ' (' . $this->paper_color_name . ')';
    }

    public function getTotalCostAttribute(): float
    {
        return $this->simpleItem ? $this->simpleItem->final_price : 0;
    }

    // Validaciones específicas
    public function validateSheet(): array
    {
        $errors = [];
        $warnings = [];

        // Validar que tenga SimpleItem asociado
        if (!$this->simpleItem) {
            $errors[] = "La hoja '{$this->sheet_type_name}' no tiene un SimpleItem asociado";
        }

        // Validar orden
        if ($this->sheet_order <= 0) {
            $errors[] = "El orden de la hoja debe ser mayor a 0";
        }

        // Validar tipo vs orden (recomendaciones)
        if ($this->sheet_type === 'original' && $this->sheet_order !== 1) {
            $warnings[] = "Se recomienda que la hoja original sea la primera (orden 1)";
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'isValid' => empty($errors)
        ];
    }

    // Scopes útiles
    public function scopeOriginal($query)
    {
        return $query->where('sheet_type', 'original');
    }

    public function scopeCopies($query)
    {
        return $query->whereIn('sheet_type', ['copia_1', 'copia_2', 'copia_3']);
    }

    public function scopeByColor($query, string $color)
    {
        return $query->where('paper_color', $color);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sheet_order');
    }
}