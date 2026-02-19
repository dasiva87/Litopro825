<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface;
use App\Filament\Resources\Documents\RelationManagers\Traits\CalculatesFinishings;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use App\Models\Document;
use App\Models\Finishing;
use App\Models\SimpleItem;
use Filament\Forms\Components;

class SimpleItemQuickHandler implements QuickActionHandlerInterface
{
    use CalculatesFinishings;

    private $calculationContext;

    public function getFormSchema(): array
    {
        return [
            // Grid de 12 columnas para el layout de 2 columnas (7 + 5)
            \Filament\Schemas\Components\Grid::make(12)
                ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema)->getComponents()),

            // Grid de 12 columnas para Acabados (izquierda) y Resumen (derecha)
            \Filament\Schemas\Components\Grid::make(12)
                ->schema([
                    // Columna izquierda - Acabados (7/12)
                    \Filament\Schemas\Components\Grid::make(1)
                        ->columnSpan(7)
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Acabados Opcionales')
                                ->icon('heroicon-o-sparkles')
                                ->compact()
                                ->schema([
                                    Components\Repeater::make('finishings_data')
                                        ->label('')
                                        ->defaultItems(0)
                                        ->live()
                                        ->afterStateUpdated(function ($livewire) {
                                            // Forzar actualización del componente completo
                                            $livewire->dispatch('$refresh');
                                        })
                                        ->schema([
                                            Components\Select::make('finishing_id')
                                                ->label('Acabado')
                                                ->helperText('El proveedor se asigna desde el catálogo')
                                                ->options(function () {
                                                    return Finishing::where('active', true)
                                                        ->forCurrentTenant()
                                                        ->get()
                                                        ->mapWithKeys(fn ($f) => [$f->id => "{$f->name} ({$f->measurement_unit->label()})"])
                                                        ->toArray();
                                                })
                                                ->required()
                                                ->searchable()
                                                ->live(onBlur: false)
                                                ->afterStateUpdated(function ($set, $get, $state, $livewire) {
                                                    // Calcular costo directamente
                                                    $cost = self::calculateFinishingCostStatic(
                                                        $state,
                                                        $get('quantity') ?? 1,
                                                        $get('width'),
                                                        $get('height')
                                                    );
                                                    $set('calculated_cost', $cost);
                                                    // Forzar actualización
                                                    $livewire->dispatch('$refresh');
                                                }),

                                            \Filament\Schemas\Components\Grid::make(3)
                                                ->schema([
                                                    Components\TextInput::make('quantity')
                                                        ->label('Cantidad')
                                                        ->numeric()
                                                        ->default(1)
                                                        ->required()
                                                        ->live(onBlur: false)
                                                        ->afterStateUpdated(function ($set, $get, $state, $livewire) {
                                                            $cost = self::calculateFinishingCostStatic(
                                                                $get('finishing_id'),
                                                                $state ?? 1,
                                                                $get('width'),
                                                                $get('height')
                                                            );
                                                            $set('calculated_cost', $cost);
                                                            $livewire->dispatch('$refresh');
                                                        }),

                                                    Components\TextInput::make('width')
                                                        ->label('Ancho (cm)')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->live(onBlur: false)
                                                        ->visible(function ($get) {
                                                            $finishingId = $get('finishing_id');
                                                            if (! $finishingId) {
                                                                return false;
                                                            }
                                                            $finishing = Finishing::find($finishingId);

                                                            return $finishing && $finishing->measurement_unit === \App\Enums\FinishingMeasurementUnit::TAMAÑO;
                                                        })
                                                        ->afterStateUpdated(function ($set, $get, $state, $livewire) {
                                                            $cost = self::calculateFinishingCostStatic(
                                                                $get('finishing_id'),
                                                                $get('quantity') ?? 1,
                                                                $state,
                                                                $get('height')
                                                            );
                                                            $set('calculated_cost', $cost);
                                                            $livewire->dispatch('$refresh');
                                                        }),

                                                    Components\TextInput::make('height')
                                                        ->label('Alto (cm)')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->live(onBlur: false)
                                                        ->visible(function ($get) {
                                                            $finishingId = $get('finishing_id');
                                                            if (! $finishingId) {
                                                                return false;
                                                            }
                                                            $finishing = Finishing::find($finishingId);

                                                            return $finishing && $finishing->measurement_unit === \App\Enums\FinishingMeasurementUnit::TAMAÑO;
                                                        })
                                                        ->afterStateUpdated(function ($set, $get, $state, $livewire) {
                                                            $cost = self::calculateFinishingCostStatic(
                                                                $get('finishing_id'),
                                                                $get('quantity') ?? 1,
                                                                $get('width'),
                                                                $state
                                                            );
                                                            $set('calculated_cost', $cost);
                                                            $livewire->dispatch('$refresh');
                                                        }),
                                                ]),

                                            Components\Placeholder::make('calculated_cost_display')
                                                ->label('Costo Calculado')
                                                ->live()
                                                ->content(function ($get) {
                                                    $finishingId = $get('finishing_id');
                                                    $quantity = $get('quantity') ?? 0;

                                                    if (! $finishingId || $quantity <= 0) {
                                                        return '$0.00';
                                                    }

                                                    $cost = self::calculateFinishingCostStatic(
                                                        $finishingId,
                                                        $quantity,
                                                        $get('width'),
                                                        $get('height')
                                                    );

                                                    return '$'.number_format($cost, 2);
                                                })
                                                ->columnSpanFull(),

                                            Components\Hidden::make('calculated_cost'),
                                        ])
                                        ->collapsible()
                                        ->addActionLabel('+ Agregar Acabado'),
                                ]),
                        ]),

                    // Columna derecha - Resumen de precios (5/12)
                    \Filament\Schemas\Components\Grid::make(1)
                        ->columnSpan(5)
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Resumen de Precios')
                                ->icon('heroicon-o-calculator')
                                ->compact()
                                ->schema([
                                    Components\Placeholder::make('price_preview')
                                        ->label('')
                                        ->live()
                                        ->content(function ($get) {
                                            // Log para debug
                                            $finishingsData = $get('finishings_data') ?? [];
                                            \Log::info('price_preview evaluado', [
                                                'finishings_count' => count($finishingsData),
                                                'finishings_data' => $finishingsData,
                                            ]);

                                            return $this->getPricePreview($get);
                                        })
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ];
    }

    public function handleCreate(array $data, Document $document): void
    {
        // Extraer datos del SimpleItem del formulario
        $simpleItemData = array_filter($data, function ($key) {
            return ! in_array($key, ['finishings_data']);
        }, ARRAY_FILTER_USE_KEY);

        // Crear el SimpleItem
        $simpleItem = SimpleItem::create($simpleItemData);

        // Crear el DocumentItem asociado
        $documentItem = $document->items()->create([
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem->id,
            'description' => 'SimpleItem: '.$simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
            'total_price' => $simpleItem->final_price,
            'item_type' => 'simple',
        ]);

        // Procesar acabados si existen - Guardar en simple_item_finishing (Arquitectura 1)
        $finishingsData = $data['finishings_data'] ?? [];
        if (! empty($finishingsData)) {
            foreach ($finishingsData as $finishingData) {
                if (isset($finishingData['finishing_id'])) {
                    // Attach finishing a SimpleItem usando tabla pivot
                    $simpleItem->finishings()->attach($finishingData['finishing_id'], [
                        'quantity' => $finishingData['quantity'] ?? 1,
                        'width' => $finishingData['width'] ?? null,
                        'height' => $finishingData['height'] ?? null,
                        'calculated_cost' => $finishingData['calculated_cost'] ?? 0,
                        'is_default' => false,
                        'sort_order' => 0,
                    ]);
                }
            }
        }

        // Recalcular totales del documento
        $document->recalculateTotals();
    }

    /**
     * Manejar la actualización de un SimpleItem existente
     */
    public function handleUpdate(array $data, \App\Models\DocumentItem $documentItem): void
    {
        $simpleItem = $documentItem->itemable;

        if (! $simpleItem instanceof SimpleItem) {
            throw new \Exception('Error: El item relacionado no es un SimpleItem válido');
        }

        // Filtrar solo los campos que pertenecen al SimpleItem (excluir finishings_data)
        $simpleItemData = array_filter($data, function ($key) {
            return ! in_array($key, ['finishings_data', 'item_type', 'itemable_type', 'itemable_id']);
        }, ARRAY_FILTER_USE_KEY);

        // Actualizar el SimpleItem
        $simpleItem->fill($simpleItemData);

        // Recalcular automáticamente
        if (method_exists($simpleItem, 'calculateAll')) {
            $simpleItem->calculateAll();
        }
        $simpleItem->save();

        // Sincronizar acabados en tabla pivot
        $finishingsData = $data['finishings_data'] ?? [];

        // Primero, detach todos los acabados existentes
        $simpleItem->finishings()->detach();

        // Luego, attach los nuevos acabados
        if (! empty($finishingsData)) {
            foreach ($finishingsData as $finishingData) {
                if (isset($finishingData['finishing_id'])) {
                    $simpleItem->finishings()->attach($finishingData['finishing_id'], [
                        'quantity' => $finishingData['quantity'] ?? 1,
                        'width' => $finishingData['width'] ?? null,
                        'height' => $finishingData['height'] ?? null,
                        'calculated_cost' => $finishingData['calculated_cost'] ?? 0,
                        'is_default' => $finishingData['is_default'] ?? false,
                        'sort_order' => 0,
                    ]);
                }
            }
        }

        // Actualizar también el DocumentItem con los nuevos valores
        $documentItem->update([
            'description' => 'SimpleItem: '.$simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
            'total_price' => $simpleItem->final_price,
        ]);

        // Recalcular totales del documento
        $documentItem->document->recalculateTotals();
    }

    /**
     * Prellenar datos del formulario para edición
     */
    public function fillFormData(\App\Models\DocumentItem $documentItem): array
    {
        $simpleItem = $documentItem->itemable;

        if (! $simpleItem instanceof SimpleItem) {
            return [];
        }

        // Cargar todos los datos del SimpleItem
        $data = $simpleItem->toArray();

        // Cargar acabados existentes desde tabla pivot
        $finishingsData = [];
        $existingFinishings = $simpleItem->finishings()->get();

        foreach ($existingFinishings as $finishing) {
            $finishingsData[] = [
                'finishing_id' => $finishing->id,
                'quantity' => $finishing->pivot->quantity ?? 1,
                'width' => $finishing->pivot->width,
                'height' => $finishing->pivot->height,
                'calculated_cost' => $finishing->pivot->calculated_cost,
                'is_default' => $finishing->pivot->is_default ?? false,
            ];
        }

        $data['finishings_data'] = $finishingsData;

        return $data;
    }

    /**
     * Calcular costo del acabado de forma estática (sin depender de contexto)
     */
    public static function calculateFinishingCostStatic($finishingId, $quantity, $width = null, $height = null): float
    {
        \Log::info('calculateFinishingCostStatic', compact('finishingId', 'quantity', 'width', 'height'));

        if (! $finishingId || $quantity <= 0) {
            return 0;
        }

        try {
            $finishing = Finishing::find($finishingId);
            if (! $finishing) {
                return 0;
            }

            $calculator = app(\App\Services\FinishingCalculatorService::class);
            $cost = $calculator->calculateCost($finishing, [
                'quantity' => (int) $quantity,
                'width' => $width ? (float) $width : null,
                'height' => $height ? (float) $height : null,
            ]);

            \Log::info('Finishing cost calculated', ['cost' => $cost, 'unit' => $finishing->measurement_unit->value]);

            return $cost;
        } catch (\Exception $e) {
            \Log::error('Error calculating finishing cost: '.$e->getMessage());

            return 0;
        }
    }

    public function getLabel(): string
    {
        return 'Sencillo';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-bolt';
    }

    public function getColor(): string
    {
        return 'primary';
    }

    public function getModalWidth(): string
    {
        return '7xl';
    }

    public function getSuccessNotificationTitle(): string
    {
        return 'Item sencillo agregado correctamente';
    }

    public function isVisible(): bool
    {
        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

        return $company && $company->isLitografia();
    }

    public function setCalculationContext($context): void
    {
        $this->calculationContext = $context;
    }

    private function getFinishingOptions(): array
    {
        return Finishing::where('active', true)
            ->forCurrentTenant()
            ->get()
            ->mapWithKeys(function ($finishing) {
                return [
                    $finishing->id => $finishing->name.' ('.$finishing->measurement_unit->label().')',
                ];
            })
            ->toArray();
    }

    private function getFinishingCostDisplay($get): string
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 0;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;

        if (! $finishingId || $quantity <= 0) {
            return '$0.00';
        }

        try {
            $finishing = Finishing::find($finishingId);
            if (! $finishing) {
                return 'Acabado no encontrado';
            }

            $calculator = app(\App\Services\FinishingCalculatorService::class);
            $cost = $calculator->calculateCost($finishing, [
                'quantity' => $quantity,
                'width' => $width > 0 ? $width : null,
                'height' => $height > 0 ? $height : null,
            ]);

            return '$'.number_format($cost, 2);

        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    /**
     * Generar vista previa del precio total usando $get (reactivo)
     */
    private function getPricePreview($get): string
    {
        try {
            $quantity = $get('quantity') ?? 0;
            $horizontalSize = $get('horizontal_size') ?? 0;
            $verticalSize = $get('vertical_size') ?? 0;
            $paperId = $get('paper_id');
            $machineId = $get('printing_machine_id');

            // Estado inicial
            if (! $quantity && ! $horizontalSize && ! $verticalSize && ! $paperId && ! $machineId) {
                return '
                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; padding: 32px 20px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 8px; opacity: 0.5;">💰</div>
                        <div style="color: #64748b; font-size: 13px; font-weight: 500;">Complete los campos para ver el precio</div>
                    </div>
                ';
            }

            // Crear SimpleItem temporal
            $tempItem = new SimpleItem([
                'quantity' => $quantity,
                'horizontal_size' => $get('horizontal_size') ?? 0,
                'vertical_size' => $get('vertical_size') ?? 0,
                'paper_id' => $paperId,
                'printing_machine_id' => $machineId,
                'ink_front_count' => $get('ink_front_count') ?? 0,
                'ink_back_count' => $get('ink_back_count') ?? 0,
                'front_back_plate' => $get('front_back_plate') ?? false,
                'sobrante_papel' => $get('sobrante_papel') ?? 0,
                'design_value' => $get('design_value') ?? 0,
                'transport_value' => $get('transport_value') ?? 0,
                'rifle_value' => $get('rifle_value') ?? 0,
                'cutting_cost' => $get('cutting_cost') ?? 0,
                'mounting_cost' => $get('mounting_cost') ?? 0,
                'profit_percentage' => $get('profit_percentage') ?? 25,
                'mounting_type' => $get('mounting_type') ?? 'automatic',
                'custom_paper_width' => $get('custom_paper_width'),
                'custom_paper_height' => $get('custom_paper_height'),
            ]);

            // Cargar relaciones (sin TenantScope para permitir papeles de proveedores)
            if ($tempItem->paper_id) {
                $paper = \App\Models\Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                    ->find($tempItem->paper_id);
                $tempItem->setRelation('paper', $paper);
            }
            if ($tempItem->printing_machine_id) {
                $machine = \App\Models\PrintingMachine::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                    ->find($tempItem->printing_machine_id);
                $tempItem->setRelation('printingMachine', $machine);
            }

            // Validar datos mínimos
            if (! $tempItem->paper || ! $tempItem->printingMachine || ! $tempItem->quantity || ! $tempItem->horizontal_size || ! $tempItem->vertical_size) {
                return '
                    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 20px; text-align: center;">
                        <div style="font-size: 32px; margin-bottom: 6px;">⚠️</div>
                        <div style="color: #92400e; font-size: 12px; font-weight: 500;">Faltan datos requeridos</div>
                        <div style="color: #a16207; font-size: 11px; margin-top: 4px;">Cantidad, tamaño, papel y máquina</div>
                    </div>
                ';
            }

            // Validar montaje manual
            if ($tempItem->mounting_type === 'custom' && (! $tempItem->custom_paper_width || ! $tempItem->custom_paper_height)) {
                return '
                    <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 12px; padding: 20px; text-align: center;">
                        <div style="font-size: 32px; margin-bottom: 6px;">✏️</div>
                        <div style="color: #1d4ed8; font-size: 12px; font-weight: 500;">Montaje Manual</div>
                        <div style="color: #3b82f6; font-size: 11px; margin-top: 4px;">Ingresa dimensiones de hoja personalizada</div>
                    </div>
                ';
            }

            // Calcular
            $calculator = new \App\Services\SimpleItemCalculatorService;
            $pricingResult = $calculator->calculateFinalPricingNew($tempItem);

            if (! $pricingResult) {
                return '
                    <div style="background: #fef2f2; border-radius: 12px; padding: 20px; text-align: center;">
                        <div style="color: #dc2626; font-size: 13px;">No se pudo calcular el precio</div>
                    </div>
                ';
            }

            // Calcular acabados
            $finishingsTotal = 0;
            $finishingsCount = 0;
            $finishingsData = $get('finishings_data') ?? [];
            if (! empty($finishingsData)) {
                $finishingCalculator = app(\App\Services\FinishingCalculatorService::class);
                foreach ($finishingsData as $finishingData) {
                    if (! empty($finishingData['finishing_id'])) {
                        $finishing = Finishing::find($finishingData['finishing_id']);
                        if ($finishing) {
                            $finishingsCount++;
                            $finishingsTotal += $finishingCalculator->calculateCost($finishing, [
                                'quantity' => $finishingData['quantity'] ?? $tempItem->quantity,
                                'width' => $finishingData['width'] ?? null,
                                'height' => $finishingData['height'] ?? null,
                            ]);
                        }
                    }
                }
            }

            // Calcular totales
            $finalPriceWithFinishings = $pricingResult->finalPrice + $finishingsTotal;
            $unitPrice = $finalPriceWithFinishings / $tempItem->quantity;
            $subtotal = $pricingResult->subtotal;

            // ═══════════════════════════════════════════════════════════
            // CONSTRUIR HTML CON DISEÑO MEJORADO
            // ═══════════════════════════════════════════════════════════

            $html = '<div style="font-family: system-ui, -apple-system, sans-serif;">';

            // Header con precio total destacado
            $html .= '
                <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 16px 20px; margin-bottom: 16px; text-align: center; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);">
                    <div style="color: rgba(255,255,255,0.85); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Precio Total</div>
                    <div style="color: white; font-size: 36px; font-weight: 800; line-height: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">$'.number_format($finalPriceWithFinishings, 0).'</div>
                    <div style="color: rgba(255,255,255,0.9); font-size: 12px; margin-top: 6px;">
                        <span style="background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 20px;">Unitario: $'.number_format($unitPrice, 0).'</span>
                    </div>
                </div>
            ';

            // Tarjetas de métricas
            $html .= '
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px;">
                    <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 8px; padding: 10px; text-align: center; border: 1px solid #bfdbfe;">
                        <div style="font-size: 20px; font-weight: 700; color: #1e40af;">'.$pricingResult->mountingOption->cutsPerSheet.'</div>
                        <div style="font-size: 9px; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.3px;">Copias/Pliego</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 8px; padding: 10px; text-align: center; border: 1px solid #bbf7d0;">
                        <div style="font-size: 20px; font-weight: 700; color: #166534;">'.$pricingResult->mountingOption->sheetsNeeded.'</div>
                        <div style="font-size: 9px; color: #22c55e; text-transform: uppercase; letter-spacing: 0.3px;">Pliegos</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #fefce8 0%, #fef08a 100%); border-radius: 8px; padding: 10px; text-align: center; border: 1px solid #fde047;">
                        <div style="font-size: 20px; font-weight: 700; color: #a16207;">'.number_format($pricingResult->mountingOption->utilizationPercentage, 0).'%</div>
                        <div style="font-size: 9px; color: #ca8a04; text-transform: uppercase; letter-spacing: 0.3px;">Aprovech.</div>
                    </div>
                </div>
            ';

            // Desglose de costos
            $html .= '<div style="background: #f8fafc; border-radius: 10px; padding: 14px; margin-bottom: 12px;">';
            $html .= '<div style="font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; font-weight: 600;">Desglose de Costos</div>';

            // ── 1. PAPEL ──
            $paperCost = $pricingResult->mountingOption->paperCost;
            if ($paperCost > 0) {
                $paperSheets = $pricingResult->mountingOption->paperSheetsNeeded ?? $pricingResult->mountingOption->sheetsNeeded;
                $paperUnitPrice = $paper->price ?? $paper->cost_per_sheet ?? 0;
                $html .= '
                    <div style="padding: 6px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #475569; font-size: 12px;">📄 Papel</span>
                            <span style="color: #1e293b; font-size: 12px; font-weight: 600;">$'.number_format($paperCost, 0).'</span>
                        </div>
                        <div style="color: #94a3b8; font-size: 10px; margin-top: 2px; padding-left: 20px;">'.$paperSheets.' pliegos × $'.number_format($paperUnitPrice, 0).'/pliego</div>
                    </div>
                ';
            }

            // ── 2. IMPRESIÓN ──
            $printingCost = $pricingResult->printingCalculation->printingCost;
            if ($printingCost > 0) {
                $millaresRaw = $pricingResult->printingCalculation->millaresRaw;
                $millaresFinal = $pricingResult->printingCalculation->millaresFinal;
                $totalColors = $pricingResult->printingCalculation->totalColors;
                $costPerMillar = $machine->cost_per_impression ?? 0;
                $setupCost = $pricingResult->printingCalculation->setupCost;

                // Calcular millar base (redondeado, antes de multiplicar por tintas)
                $millarBase = $millaresFinal / $totalColors;

                $html .= '
                    <div style="padding: 6px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #475569; font-size: 12px;">🖨️ Impresión</span>
                            <span style="color: #1e293b; font-size: 12px; font-weight: 600;">$'.number_format($printingCost, 0).'</span>
                        </div>
                        <div style="color: #94a3b8; font-size: 10px; margin-top: 2px; padding-left: 20px;">'.number_format($millarBase, 0).' millar × '.$totalColors.' tintas = '.$millaresFinal.' mill.</div>
                        <div style="color: #94a3b8; font-size: 10px; padding-left: 20px;">'.$millaresFinal.' mill. × $'.number_format($costPerMillar, 0).'/millar</div>';

                if ($setupCost > 0) {
                    $html .= '<div style="color: #94a3b8; font-size: 10px; padding-left: 20px;">Alistamiento: $'.number_format($setupCost, 0).'</div>';
                }

                $html .= '</div>';
            }

            // ── 3. CTP ──
            $ctpCost = $pricingResult->additionalCosts->ctpCost;
            if ($ctpCost > 0) {
                $totalColors = $pricingResult->printingCalculation->totalColors;
                $ctpPerPlate = $machine->costo_ctp ?? 0;
                $html .= '
                    <div style="padding: 6px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #475569; font-size: 12px;">🔲 Planchas CTP</span>
                            <span style="color: #1e293b; font-size: 12px; font-weight: 600;">$'.number_format($ctpCost, 0).'</span>
                        </div>
                        <div style="color: #94a3b8; font-size: 10px; margin-top: 2px; padding-left: 20px;">'.$totalColors.' planchas × $'.number_format($ctpPerPlate, 0).'/plancha</div>
                    </div>
                ';
            }

            // ── 4. OTROS COSTOS (solo valores ingresados por el usuario) ──
            $otherCosts = $pricingResult->additionalCosts;
            $otherTotal = $otherCosts->cuttingCost + $otherCosts->mountingCost + $otherCosts->designCost + $otherCosts->transportCost + $otherCosts->rifleCost;
            if ($otherTotal > 0) {
                $html .= '
                    <div style="padding: 6px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #475569; font-size: 12px;">📦 Otros costos</span>
                            <span style="color: #1e293b; font-size: 12px; font-weight: 600;">$'.number_format($otherTotal, 0).'</span>
                        </div>';

                if ($otherCosts->cuttingCost > 0) {
                    $html .= '<div style="display: flex; justify-content: space-between; color: #94a3b8; font-size: 10px; padding-left: 20px; margin-top: 2px;">
                        <span>Corte</span>
                        <span>$'.number_format($otherCosts->cuttingCost, 0).'</span>
                    </div>';
                }
                if ($otherCosts->mountingCost > 0) {
                    $html .= '<div style="display: flex; justify-content: space-between; color: #94a3b8; font-size: 10px; padding-left: 20px;">
                        <span>Montaje</span>
                        <span>$'.number_format($otherCosts->mountingCost, 0).'</span>
                    </div>';
                }
                if ($otherCosts->designCost > 0) {
                    $html .= '<div style="display: flex; justify-content: space-between; color: #94a3b8; font-size: 10px; padding-left: 20px;">
                        <span>Diseño</span>
                        <span>$'.number_format($otherCosts->designCost, 0).'</span>
                    </div>';
                }
                if ($otherCosts->transportCost > 0) {
                    $html .= '<div style="display: flex; justify-content: space-between; color: #94a3b8; font-size: 10px; padding-left: 20px;">
                        <span>Transporte</span>
                        <span>$'.number_format($otherCosts->transportCost, 0).'</span>
                    </div>';
                }
                if ($otherCosts->rifleCost > 0) {
                    $html .= '<div style="display: flex; justify-content: space-between; color: #94a3b8; font-size: 10px; padding-left: 20px;">
                        <span>Rifle/Doblez</span>
                        <span>$'.number_format($otherCosts->rifleCost, 0).'</span>
                    </div>';
                }

                $html .= '</div>';
            }

            // Acabados si existen
            if ($finishingsTotal > 0) {
                $html .= '
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #e2e8f0;">
                        <span style="color: #7c3aed; font-size: 12px;">🎨 Acabados ('.$finishingsCount.')</span>
                        <span style="color: #7c3aed; font-size: 12px; font-weight: 600;">+$'.number_format($finishingsTotal, 0).'</span>
                    </div>
                ';
            }

            // Subtotal
            $html .= '
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; margin-top: 4px;">
                    <span style="color: #64748b; font-size: 11px; font-weight: 500;">SUBTOTAL</span>
                    <span style="color: #475569; font-size: 13px; font-weight: 600;">$'.number_format($subtotal + $finishingsTotal, 0).'</span>
                </div>
            ';

            $html .= '</div>'; // Fin desglose

            // Ganancia
            $html .= '
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 10px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #86efac;">
                    <div>
                        <div style="color: #166534; font-size: 12px; font-weight: 600;">Ganancia</div>
                        <div style="color: #22c55e; font-size: 10px;">'.$tempItem->profit_percentage.'% de margen</div>
                    </div>
                    <div style="color: #166534; font-size: 18px; font-weight: 700;">+$'.number_format($pricingResult->profitAmount, 0).'</div>
                </div>
            ';

            $html .= '</div>'; // Fin container

            return $html;

        } catch (\Exception $e) {
            return '
                <div style="background: #fef2f2; border-radius: 12px; padding: 20px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 8px;">❌</div>
                    <div style="color: #dc2626; font-size: 13px; font-weight: 500;">Error al calcular</div>
                    <div style="color: #f87171; font-size: 11px; margin-top: 4px;">'.$e->getMessage().'</div>
                </div>
            ';
        }
    }
}
