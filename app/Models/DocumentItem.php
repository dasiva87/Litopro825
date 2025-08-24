<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CuttingCalculatorService;

class DocumentItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id',
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