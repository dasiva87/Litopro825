<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySettings extends Model
{
    protected $fillable = [
        'company_id',
        'measurement_system',
        'quote_number_start',
        'order_number_start',
        'print_order_number_start',
        'profit_margin_percentage',
        'waste_percentage',
        'default_design_price',
        'default_transport_price',
        'default_cutting_price',
        'tax_rate',
        'currency',
        'timezone',
    ];

    protected $casts = [
        'profit_margin_percentage' => 'decimal:2',
        'waste_percentage' => 'decimal:2',
        'default_design_price' => 'decimal:2',
        'default_transport_price' => 'decimal:2',
        'default_cutting_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'quote_number_start' => 'integer',
        'order_number_start' => 'integer',
        'print_order_number_start' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Métodos de negocio específicos para el sector litográfico
    public function getNextQuoteNumber(): int
    {
        // Aquí se implementaría la lógica para obtener el próximo número de cotización
        return $this->quote_number_start;
    }

    public function getNextOrderNumber(): int
    {
        return $this->order_number_start;
    }

    public function getNextPrintOrderNumber(): int
    {
        return $this->print_order_number_start;
    }

    public function calculatePriceWithMargin(float $cost): float
    {
        return $cost * (1 + ($this->profit_margin_percentage / 100));
    }

    public function calculateWithWaste(float $quantity): float
    {
        return $quantity * (1 + ($this->waste_percentage / 100));
    }
}