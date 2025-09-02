<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CuttingCalculatorService;

class DocumentItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id',
        'itemable_type',
        'itemable_id',
        'printing_machine_id',
        'paper_id',
        'description',
        'quantity',
        'width',
        'height',
        'pages',
        'colors_front',
        'colors_back',
        'paper_cut_width',
        'paper_cut_height',
        'orientation',
        'cuts_per_sheet',
        'sheets_needed',
        'unit_copies',
        'paper_cost',
        'printing_cost',
        'cutting_cost',
        'design_cost',
        'transport_cost',
        'other_costs',
        'unit_price',
        'total_price',
        'profit_margin',
        'item_type',
        'item_config',
        'is_template',
        'template_name',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'pages' => 'integer',
        'colors_front' => 'integer',
        'colors_back' => 'integer',
        'paper_cut_width' => 'decimal:2',
        'paper_cut_height' => 'decimal:2',
        'cuts_per_sheet' => 'integer',
        'sheets_needed' => 'integer',
        'unit_copies' => 'integer',
        'paper_cost' => 'decimal:2',
        'printing_cost' => 'decimal:2',
        'cutting_cost' => 'decimal:2',
        'design_cost' => 'decimal:2',
        'transport_cost' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'item_config' => 'array',
        'is_template' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-calcular totales cuando se actualiza
        static::saving(function ($item) {
            $item->calculateTotals();
        });
    }

    // Relaciones
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function printingMachine(): BelongsTo
    {
        return $this->belongsTo(PrintingMachine::class);
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function finishings(): HasMany
    {
        return $this->hasMany(DocumentItemFinishing::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeSimple($query)
    {
        return $query->byType('simple');
    }

    public function scopeTalonario($query)
    {
        return $query->byType('talonario');
    }

    public function scopeMagazine($query)
    {
        return $query->byType('magazine');
    }

    public function scopeDigital($query)
    {
        return $query->byType('digital');
    }

    // Métodos de tipo
    public function isSimple(): bool
    {
        return $this->item_type === 'simple';
    }

    public function isTalonario(): bool
    {
        return $this->item_type === 'talonario';
    }

    public function isMagazine(): bool
    {
        return $this->item_type === 'magazine';
    }

    public function isDigital(): bool
    {
        return $this->item_type === 'digital';
    }

    public function isCustom(): bool
    {
        return $this->item_type === 'custom';
    }

    public function isProduct(): bool
    {
        return $this->item_type === 'product';
    }

    // Métodos de cálculo usando la calculadora de cortes existente
    public function calculateCuttingOptimization(): array
    {
        if (!$this->paper || !$this->width || !$this->height) {
            return [];
        }

        $calculator = new CuttingCalculatorService();
        
        try {
            return $calculator->calculateCuts(
                paperWidth: $this->paper->width,
                paperHeight: $this->paper->height,
                cutWidth: $this->width,
                cutHeight: $this->height,
                desiredCuts: (int) $this->quantity,
                orientation: $this->orientation
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    public function updateFromCuttingCalculation(array $calculation): void
    {
        if (empty($calculation)) {
            return;
        }

        $this->cuts_per_sheet = $calculation['cutsPerSheet'] ?? 1;
        $this->sheets_needed = $calculation['sheetsNeeded'] ?? 1;
        
        // Actualizar dimensiones de corte si están en el cálculo
        if (isset($calculation['arrangeResult'])) {
            $this->paper_cut_width = $this->width;
            $this->paper_cut_height = $this->height;
        }
    }

    // Cálculo de costos
    public function calculatePaperCost(): float
    {
        if (!$this->paper || !$this->sheets_needed) {
            return 0;
        }

        return $this->sheets_needed * $this->paper->cost_per_sheet;
    }

    public function calculatePrintingCost(): float
    {
        if (!$this->printingMachine) {
            return 0;
        }

        $impressions = $this->sheets_needed;
        $colors = $this->colors_front + $this->colors_back;
        
        $cost = ($impressions * $colors * $this->printingMachine->cost_per_impression) + 
                $this->printingMachine->setup_cost;
                
        return $cost;
    }

    public function calculateBaseCosts(): void
    {
        $this->paper_cost = $this->calculatePaperCost();
        $this->printing_cost = $this->calculatePrintingCost();
        
        // Calcular costo de corte (básico por ahora)
        if ($this->cuts_per_sheet > 1) {
            $this->cutting_cost = $this->sheets_needed * 0.5; // $0.5 por pliego cortado
        }
    }

    public function calculateTotals(): void
    {
        // Para productos, no recalcular - los precios ya vienen definidos
        if ($this->itemable_type === 'App\\Models\\Product') {
            // Solo validar que los precios estén correctos si hay un producto relacionado
            if ($this->itemable) {
                $product = $this->itemable;
                // Si los precios no están definidos, calcularlos del producto
                if ($this->unit_price == 0) {
                    $this->unit_price = $product->sale_price;
                }
                if ($this->total_price == 0) {
                    $this->total_price = $product->calculateTotalPrice($this->quantity);
                }
            }
            return;
        }
        
        // Para items digitales, manejar cálculo completo incluyendo acabados
        if ($this->itemable_type === 'App\\Models\\DigitalItem') {
            if ($this->itemable) {
                // Si los precios ya están definidos (del formulario), incluir acabados
                if ($this->unit_price > 0 && $this->total_price > 0) {
                    // Agregar costo de acabados al precio total
                    $finishingsCost = $this->itemable->calculateFinishingsCost();
                    if ($finishingsCost > 0) {
                        $baseTotal = $this->total_price;
                        $this->total_price = $baseTotal + $finishingsCost;
                        $this->unit_price = $this->total_price / $this->quantity;
                    }
                    return;
                }
                
                // Si no están definidos y hay un DigitalItem relacionado, calcular desde cero
                if ($this->item_config) {
                    $config = json_decode($this->item_config, true);
                    
                    // Preparar parámetros para el cálculo
                    $params = ['quantity' => $this->quantity];
                    if ($config['pricing_type'] === 'size' && isset($config['width']) && isset($config['height'])) {
                        $params['width'] = $config['width'];
                        $params['height'] = $config['height'];
                    }
                    
                    // Calcular precio base + acabados
                    $baseTotalPrice = $this->itemable->calculateTotalPrice($params);
                    $finishingsCost = $this->itemable->calculateFinishingsCost();
                    $totalPrice = $baseTotalPrice + $finishingsCost;
                    
                    $this->unit_price = $totalPrice / $this->quantity;
                    $this->total_price = $totalPrice;
                }
            }
            return;
        }

        // Para otros tipos de items, usar el cálculo original
        // Calcular costos base si no están definidos
        if ($this->paper_cost == 0 && $this->paper) {
            $this->calculateBaseCosts();
        }

        // Sumar todos los costos
        $totalCosts = $this->paper_cost + 
                     $this->printing_cost + 
                     $this->cutting_cost + 
                     $this->design_cost + 
                     $this->transport_cost + 
                     $this->other_costs;

        // Aplicar margen de ganancia
        if ($this->profit_margin > 0) {
            $totalCosts = $totalCosts * (1 + ($this->profit_margin / 100));
        }

        // Calcular precio unitario y total
        $this->unit_price = $this->quantity > 0 ? $totalCosts / $this->quantity : $totalCosts;
        $this->total_price = $totalCosts;
    }

    /**
     * Obtener el precio total incluyendo acabados (para DigitalItem)
     */
    public function getTotalPriceWithFinishings(): float
    {
        $basePrice = $this->total_price ?? 0;
        
        // Solo para DigitalItems
        if ($this->itemable_type === 'App\\Models\\DigitalItem' && $this->itemable) {
            $finishingsCost = $this->itemable->calculateFinishingsCost();
            return $basePrice + $finishingsCost;
        }
        
        return $basePrice;
    }

    /**
     * Obtener el precio unitario incluyendo acabados (para DigitalItem)
     */
    public function getUnitPriceWithFinishings(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        
        return $this->getTotalPriceWithFinishings() / $this->quantity;
    }

    // Métodos específicos por tipo de item
    public function getConfigValue(string $key, $default = null)
    {
        return $this->item_config[$key] ?? $default;
    }

    public function setConfigValue(string $key, $value): void
    {
        $config = $this->item_config ?? [];
        $config[$key] = $value;
        $this->item_config = $config;
    }

    // Configuración específica para talonarios
    public function getTalonarioConfig(): array
    {
        return array_merge([
            'numeracion_inicial' => 1,
            'numeracion_final' => 100,
            'copias_por_talonario' => 1,
            'papel_carbon' => false,
        ], $this->item_config ?? []);
    }

    public function setTalonarioConfig(array $config): void
    {
        $this->item_config = array_merge($this->getTalonarioConfig(), $config);
        
        // Recalcular unit_copies basado en configuración
        $this->unit_copies = $config['copias_por_talonario'] ?? 1;
    }

    // Configuración específica para revistas
    public function getMagazineConfig(): array
    {
        return array_merge([
            'tipo_encuadernacion' => 'grapa',
            'cubierta_diferente' => false,
            'papel_interior_id' => null,
            'papel_cubierta_id' => null,
        ], $this->item_config ?? []);
    }

    public function setMagazineConfig(array $config): void
    {
        $this->item_config = array_merge($this->getMagazineConfig(), $config);
        
        // Las revistas deben tener páginas múltiplos de 4
        if ($this->pages % 4 !== 0) {
            $this->pages = ceil($this->pages / 4) * 4;
        }
    }

    // Configuración específica para digital
    public function getDigitalConfig(): array
    {
        return array_merge([
            'material' => 'vinilo',
            'acabado' => 'mate',
            'instalacion_incluida' => false,
            'unidad_medida' => 'm2',
        ], $this->item_config ?? []);
    }

    public function setDigitalConfig(array $config): void
    {
        $this->item_config = array_merge($this->getDigitalConfig(), $config);
    }

    // Crear template desde este item
    public function saveAsTemplate(string $name): self
    {
        $template = $this->replicate();
        $template->is_template = true;
        $template->template_name = $name;
        $template->document_id = null; // No asociar a documento específico
        $template->save();

        return $template;
    }

    // Crear item desde template
    public static function createFromTemplate(int $templateId, int $documentId): self
    {
        $template = self::templates()->findOrFail($templateId);
        
        $item = $template->replicate();
        $item->is_template = false;
        $item->template_name = null;
        $item->document_id = $documentId;
        $item->save();

        return $item;
    }

    // Método helper para calcular y actualizar precios automáticamente
    public function calculateAndUpdatePrices(): bool
    {
        if ($this->unit_price > 0 && $this->total_price > 0) {
            return true; // Ya tiene precios, no necesita cálculo
        }

        try {
            if ($this->itemable_type === 'App\\Models\\SimpleItem' && $this->itemable) {
                $calculator = new \App\Services\SimpleItemCalculatorService();
                $pricing = $calculator->calculateFinalPricing($this->itemable);
                
                $this->update([
                    'unit_price' => $pricing->unitPrice,
                    'total_price' => $pricing->finalPrice,
                ]);
                
                return true;
                
            } elseif ($this->itemable_type === 'App\\Models\\Product' && $this->itemable) {
                $product = $this->itemable;
                $unitPrice = $product->sale_price;
                $totalPrice = $unitPrice * $this->quantity;
                
                $this->update([
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
                
                return true;
                
            } elseif ($this->itemable_type === 'App\\Models\\DigitalItem' && $this->itemable) {
                // Para DigitalItems, el precio ya está calculado y guardado en el DocumentItem
                // Pero podríamos recalcular si tenemos parámetros adicionales en metadata
                $digitalItem = $this->itemable;
                
                // Si hay metadata con parámetros específicos, usar esos para recalcular
                if ($this->item_config && is_array($this->item_config)) {
                    $calculator = new \App\Services\DigitalItemCalculatorService();
                    $totalPrice = $calculator->calculateTotalPrice($digitalItem, $this->item_config);
                    $unitPrice = $totalPrice / ($this->item_config['quantity'] ?? 1);
                    
                    $this->update([
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);
                } else {
                    // Si no hay configuración específica, usar precio base * cantidad
                    $unitPrice = $digitalItem->unit_value;
                    $totalPrice = $unitPrice * $this->quantity;
                    
                    $this->update([
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);
                }
                
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Error calculating item prices: ' . $e->getMessage(), [
                'document_item_id' => $this->id,
                'itemable_type' => $this->itemable_type,
                'itemable_id' => $this->itemable_id
            ]);
        }

        return false;
    }

    // Método estático para corregir todos los items con precios 0
    public static function fixZeroPrices(): int
    {
        $itemsFixed = 0;
        $zeroItems = self::where('unit_price', 0)->orWhere('total_price', 0)->get();
        
        foreach ($zeroItems as $item) {
            if ($item->calculateAndUpdatePrices()) {
                $itemsFixed++;
            }
        }

        return $itemsFixed;
    }

    // Constantes
    const TYPE_SIMPLE = 'simple';
    const TYPE_TALONARIO = 'talonario';
    const TYPE_MAGAZINE = 'magazine';
    const TYPE_DIGITAL = 'digital';
    const TYPE_CUSTOM = 'custom';
    const TYPE_PRODUCT = 'product';

    const ORIENTATION_HORIZONTAL = 'horizontal';
    const ORIENTATION_VERTICAL = 'vertical';
    const ORIENTATION_MAXIMUM = 'maximum';
}