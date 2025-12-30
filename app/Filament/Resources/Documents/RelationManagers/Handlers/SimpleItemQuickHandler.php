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
            // Resumen de cálculo reactivo para creación - AL INICIO para que siempre esté visible
            \Filament\Schemas\Components\Section::make('💰 Resumen de Precios')
                ->description('Vista previa del cálculo en tiempo real')
                ->schema([
                    Components\Placeholder::make('price_preview')
                        ->label('')
                        ->live()
                        ->content(function ($get) {
                            return $this->getPricePreview($get);
                        })
                        ->html()
                        ->columnSpanFull(),
                ])
                ->collapsed(false)
                ->collapsible(),

            ...SimpleItemForm::configure(new \Filament\Schemas\Schema)->getComponents(),

            // Sección de Acabados
            \Filament\Schemas\Components\Section::make('🎨 Acabados Opcionales')
                ->description('Agrega acabados adicionales que se calcularán automáticamente')
                ->schema([
                    Components\Repeater::make('finishings_data')
                        ->label('Acabados')
                        ->defaultItems(0)
                        ->schema([
                            Components\Select::make('finishing_id')
                                ->label('Acabado')
                                ->helperText('⚠️ El proveedor se asigna desde el catálogo de Acabados')
                                ->options(function () {
                                    return $this->getFinishingOptions();
                                })
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($set, $get, $state) {
                                    if ($this->calculationContext) {
                                        $this->calculationContext->calculateSimpleFinishingCost($set, $get);
                                    }
                                }),

                            \Filament\Schemas\Components\Grid::make(3)
                                ->schema([
                                    Components\TextInput::make('quantity')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($set, $get, $state) {
                                            if ($this->calculationContext) {
                                                $this->calculationContext->calculateSimpleFinishingCost($set, $get);
                                            }
                                        }),

                                    Components\TextInput::make('width')
                                        ->label('Ancho (cm)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->live()
                                        ->visible(fn ($get) => $this->shouldShowSizeFields($get('finishing_id')))
                                        ->afterStateUpdated(function ($set, $get, $state) {
                                            if ($this->calculationContext) {
                                                $this->calculationContext->calculateSimpleFinishingCost($set, $get);
                                            }
                                        }),

                                    Components\TextInput::make('height')
                                        ->label('Alto (cm)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->live()
                                        ->visible(fn ($get) => $this->shouldShowSizeFields($get('finishing_id')))
                                        ->afterStateUpdated(function ($set, $get, $state) {
                                            if ($this->calculationContext) {
                                                $this->calculationContext->calculateSimpleFinishingCost($set, $get);
                                            }
                                        }),
                                ]),

                            Components\Placeholder::make('calculated_cost_display')
                                ->label('Costo Calculado')
                                ->content(function ($get) {
                                    return $this->getFinishingCostDisplay($get);
                                })
                                ->columnSpanFull(),

                            Components\Hidden::make('calculated_cost'),
                        ])
                        ->collapsible()
                        ->addActionLabel('+ Agregar Acabado'),
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
                    $finishing->id => $finishing->code.' - '.$finishing->name.' ('.$finishing->measurement_unit->label().')',
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
            // Debug info
            $quantity = $get('quantity') ?? 0;
            $horizontalSize = $get('horizontal_size') ?? 0;
            $verticalSize = $get('vertical_size') ?? 0;
            $paperId = $get('paper_id');
            $machineId = $get('printing_machine_id');

            // Mostrar debug si no hay datos
            if (! $quantity && ! $horizontalSize && ! $verticalSize && ! $paperId && ! $machineId) {
                return '<div class="p-4 bg-blue-50 rounded text-center">
                    <div class="text-sm text-blue-700">👋 Complete los campos del formulario</div>
                    <div class="text-xs text-blue-600 mt-1">El cálculo aparecerá automáticamente aquí</div>
                </div>';
            }

            // Crear un SimpleItem temporal con los datos del formulario
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

            // Cargar relaciones necesarias
            if ($tempItem->paper_id) {
                $tempItem->setRelation('paper', \App\Models\Paper::find($tempItem->paper_id));
            }

            if ($tempItem->printing_machine_id) {
                $tempItem->setRelation('printingMachine', \App\Models\PrintingMachine::find($tempItem->printing_machine_id));
            }

            // Validar datos mínimos
            if (! $tempItem->paper || ! $tempItem->printingMachine || ! $tempItem->quantity || ! $tempItem->horizontal_size || ! $tempItem->vertical_size) {
                return '<div class="p-4 bg-gray-50 rounded text-center text-gray-500">
                    <div class="text-sm">Complete todos los campos requeridos para ver el cálculo</div>
                    <div class="text-xs mt-1">Se requiere: cantidad, tamaño, papel y máquina</div>
                </div>';
            }

            // Calcular usando el servicio
            $calculator = new \App\Services\SimpleItemCalculatorService;
            $pricingResult = $calculator->calculateFinalPricingNew($tempItem);

            if (! $pricingResult) {
                return '<div class="p-4 bg-yellow-50 rounded text-center">
                    <div class="text-sm text-yellow-700">No se pudo calcular el precio</div>
                    <div class="text-xs text-yellow-600 mt-1">Verifique que los datos sean válidos</div>
                </div>';
            }

            // Calcular acabados si existen
            $finishingsTotal = 0;
            $finishingsData = $get('finishings_data') ?? [];
            if (! empty($finishingsData)) {
                $finishingCalculator = app(\App\Services\FinishingCalculatorService::class);
                foreach ($finishingsData as $finishingData) {
                    if (! empty($finishingData['finishing_id'])) {
                        $finishing = Finishing::find($finishingData['finishing_id']);
                        if ($finishing) {
                            $params = [
                                'quantity' => $finishingData['quantity'] ?? $tempItem->quantity,
                                'width' => $finishingData['width'] ?? null,
                                'height' => $finishingData['height'] ?? null,
                            ];
                            $finishingsTotal += $finishingCalculator->calculateCost($finishing, $params);
                        }
                    }
                }
            }

            // Construir HTML de resumen
            $finalPriceWithFinishings = $pricingResult->finalPrice + $finishingsTotal;
            $unitPrice = $finalPriceWithFinishings / $tempItem->quantity;

            $content = '<div class="space-y-3">';

            // Información del montaje
            $content .= '<div class="p-3 bg-blue-50 rounded border border-blue-200">';
            $content .= '<div class="text-xs font-medium text-blue-700 mb-1">MONTAJE</div>';
            $content .= '<div class="grid grid-cols-3 gap-2 text-xs">';
            $content .= '<div><span class="text-gray-600">Copias/montaje:</span> <strong>'.$pricingResult->mountingOption->cutsPerSheet.'</strong></div>';
            $content .= '<div><span class="text-gray-600">Pliegos:</span> <strong>'.$pricingResult->mountingOption->sheetsNeeded.'</strong></div>';
            $content .= '<div><span class="text-gray-600">Aprovech:</span> <strong>'.number_format($pricingResult->mountingOption->utilizationPercentage, 1).'%</strong></div>';
            $content .= '</div>';
            $content .= '</div>';

            // Desglose de costos
            $content .= '<div class="space-y-1">';
            $content .= '<div class="flex justify-between text-sm">';
            $content .= '<span class="text-gray-600">Papel</span>';
            $content .= '<span>$'.number_format($pricingResult->mountingOption->paperCost, 0).'</span>';
            $content .= '</div>';
            $content .= '<div class="flex justify-between text-sm">';
            $content .= '<span class="text-gray-600">Impresión</span>';
            $content .= '<span>$'.number_format($pricingResult->printingCalculation->printingCost, 0).'</span>';
            $content .= '</div>';
            $content .= '<div class="flex justify-between text-sm">';
            $content .= '<span class="text-gray-600">Otros costos</span>';
            $content .= '<span>$'.number_format($pricingResult->additionalCosts->getTotalCost(), 0).'</span>';
            $content .= '</div>';

            if ($finishingsTotal > 0) {
                $content .= '<div class="flex justify-between text-sm text-purple-600">';
                $content .= '<span>Acabados</span>';
                $content .= '<span>+$'.number_format($finishingsTotal, 0).'</span>';
                $content .= '</div>';
            }

            $content .= '<div class="flex justify-between text-sm text-green-600 border-t pt-1">';
            $content .= '<span>Ganancia ('.$tempItem->profit_percentage.'%)</span>';
            $content .= '<span>+$'.number_format($pricingResult->profitAmount, 0).'</span>';
            $content .= '</div>';
            $content .= '</div>';

            // Total
            $content .= '<div class="flex justify-between items-center font-bold text-lg border-t-2 pt-2 mt-2">';
            $content .= '<span>PRECIO TOTAL</span>';
            $content .= '<span class="text-blue-600">$'.number_format($finalPriceWithFinishings, 0).'</span>';
            $content .= '</div>';

            $content .= '<div class="text-center text-xs text-gray-500 mt-1">';
            $content .= 'Precio unitario: <strong>$'.number_format($unitPrice, 2).'</strong>';
            $content .= '</div>';

            $content .= '</div>';

            return $content;

        } catch (\Exception $e) {
            return '<div class="p-4 bg-red-50 rounded">
                <div class="text-sm text-red-700">Error al calcular</div>
                <div class="text-xs text-red-600 mt-1">'.$e->getMessage().'</div>
            </div>';
        }
    }
}
