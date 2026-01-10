<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface;
use App\Filament\Resources\Documents\RelationManagers\Traits\CalculatesFinishings;
use App\Models\Document;
use App\Models\DigitalItem;
use App\Models\Finishing;
use Filament\Forms\Components;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;

class DigitalItemQuickHandler implements QuickActionHandlerInterface
{
    use CalculatesFinishings;

    private $calculationContext;

    public function getFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Agregar Impresión Digital')
                ->description('Selecciona una impresión digital existente y especifica parámetros')
                ->schema([
                    Select::make('digital_item_id')
                        ->label('Impresión Digital')
                        ->options(function () {
                            return $this->getDigitalItemOptions();
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $item = DigitalItem::find($state);
                                if ($item) {
                                    $set('item_description', $item->description);
                                    $set('pricing_type', $item->pricing_type);
                                    $set('sale_price', $item->sale_price);
                                }
                            }
                        }),

                    Grid::make(3)
                        ->schema([
                            Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->suffix('unidades')
                                ->live(),

                            Components\TextInput::make('width')
                                ->label('Ancho (cm)')
                                ->numeric()
                                ->visible(fn ($get) => $get('pricing_type') === 'size')
                                ->required(fn ($get) => $get('pricing_type') === 'size')
                                ->live(),

                            Components\TextInput::make('height')
                                ->label('Alto (cm)')
                                ->numeric()
                                ->visible(fn ($get) => $get('pricing_type') === 'size')
                                ->required(fn ($get) => $get('pricing_type') === 'size')
                                ->live(),
                        ]),

                    Components\Placeholder::make('digital_calc')
                        ->content(function ($get) {
                            return $this->getCalculationSummary($get);
                        })
                        ->html()
                        ->columnSpanFull()
                        ->visible(fn ($get) => filled($get('digital_item_id'))),

                    // Sección de Acabados para Item Digital Rápido
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
                                        ->live()
                                        ->searchable()
                                        ->afterStateUpdated(function ($set, $get, $state) {
                                            if ($this->calculationContext) {
                                                $this->calculationContext->calculateFinishingCost($set, $get);
                                            }
                                        }),

                                    Grid::make(3)
                                        ->schema([
                                            Components\TextInput::make('quantity')
                                                ->label('Cantidad')
                                                ->numeric()
                                                ->default(1)
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($set, $get, $state) {
                                                    if ($this->calculationContext) {
                                                        $this->calculationContext->calculateFinishingCost($set, $get);
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
                                                        $this->calculationContext->calculateFinishingCost($set, $get);
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
                                                        $this->calculationContext->calculateFinishingCost($set, $get);
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
                                ->addActionLabel('+ Agregar Acabado')
                        ]),

                    Components\Hidden::make('item_description'),
                    Components\Hidden::make('pricing_type'),
                    Components\Hidden::make('sale_price'),
                ]),
        ];
    }

    public function handleCreate(array $data, Document $document): void
    {
        $digitalItem = DigitalItem::find($data['digital_item_id']);

        if (!$digitalItem) {
            throw new \Exception('Item digital no encontrado');
        }

        // Preparar parámetros para cálculo
        $params = ['quantity' => $data['quantity'] ?? 1];
        if ($digitalItem->pricing_type === 'size') {
            $params['width'] = $data['width'] ?? 0;
            $params['height'] = $data['height'] ?? 0;
        }

        // Validar parámetros
        $errors = $digitalItem->validateParameters($params);

        if (!empty($errors)) {
            throw new \Exception('Parámetros inválidos: '.implode(', ', $errors));
        }

        // Calcular precio base del item
        $baseTotalPrice = $digitalItem->calculateTotalPrice($params);

        // Procesar acabados si existen
        $finishingsCost = 0;
        $finishingsData = $data['finishings_data'] ?? [];

        if (!empty($finishingsData)) {
            foreach ($finishingsData as $finishingData) {
                if (isset($finishingData['finishing_id']) && isset($finishingData['calculated_cost'])) {
                    $finishingsCost += (float) $finishingData['calculated_cost'];
                }
            }
        }

        // Precio total incluyendo acabados
        $totalPrice = $baseTotalPrice + $finishingsCost;
        $unitPrice = $totalPrice / $params['quantity'];

        // Crear configuración del item
        $itemConfig = [
            'pricing_type' => $digitalItem->pricing_type,
            'sale_price' => $digitalItem->sale_price,
        ];

        // Preparar datos del DocumentItem
        $documentItemData = [
            'itemable_type' => 'App\\Models\\DigitalItem',
            'itemable_id' => $digitalItem->id,
            'description' => '', // Se generará automáticamente
            'quantity' => $params['quantity'],
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'item_type' => 'digital',
            'item_config' => json_encode($itemConfig),
        ];

        if ($digitalItem->pricing_type === 'size') {
            $itemConfig['width'] = $params['width'];
            $itemConfig['height'] = $params['height'];
            $documentItemData['width'] = $params['width'];
            $documentItemData['height'] = $params['height'];
        }

        // Crear el DocumentItem asociado
        $documentItem = $document->items()->create($documentItemData);

        // Procesar acabados después de crear el DocumentItem
        if (!empty($finishingsData)) {
            foreach ($finishingsData as $finishingData) {
                if (isset($finishingData['finishing_id']) && isset($finishingData['calculated_cost'])) {
                    $finishing = Finishing::find($finishingData['finishing_id']);

                    if ($finishing) {
                        // Crear el acabado relacionado con parámetros
                        $finishingParams = [
                            'quantity' => $finishingData['quantity'] ?? 1,
                            'is_double_sided' => false,
                            'unit_price' => ($finishingData['calculated_cost'] ?? 0) / ($finishingData['quantity'] ?? 1),
                            'total_price' => $finishingData['calculated_cost'] ?? 0,
                        ];

                        if (isset($finishingData['width'])) {
                            $finishingParams['width'] = $finishingData['width'];
                        }
                        if (isset($finishingData['height'])) {
                            $finishingParams['height'] = $finishingData['height'];
                        }

                        // Attach finishing a DigitalItem usando tabla pivot (Arquitectura 1)
                        $digitalItem->finishings()->attach($finishingData['finishing_id'], [
                            'quantity' => $finishingParams['quantity'],
                            'width' => $finishingParams['width'] ?? null,
                            'height' => $finishingParams['height'] ?? null,
                            'calculated_cost' => $finishingParams['total_price'],
                        ]);
                    }
                }
            }
        }

        // Recalcular totales del documento
        $document->recalculateTotals();
    }

    public function getLabel(): string
    {
        return 'Impresión Digital';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-computer-desktop';
    }

    public function getColor(): string
    {
        return 'primary';
    }

    public function getModalWidth(): string
    {
        return '5xl';
    }

    public function getSuccessNotificationTitle(): string
    {
        return 'Item digital agregado correctamente';
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

    private function getDigitalItemOptions(): array
    {
        return DigitalItem::where('active', true)
            ->forCurrentTenant()
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->id => $item->description.' ('.$item->pricing_type_name.')',
                ];
            })
            ->toArray();
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

    private function getCalculationSummary($get): string
    {
        $itemId = $get('digital_item_id');
        $quantity = $get('quantity') ?? 1;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;

        if (!$itemId) {
            return '📋 Selecciona un item digital para ver el cálculo';
        }

        $item = DigitalItem::find($itemId);
        if (!$item) {
            return '❌ Item digital no encontrado';
        }

        $params = ['quantity' => $quantity];

        if ($item->pricing_type === 'size') {
            $params['width'] = $width;
            $params['height'] = $height;
        }

        $errors = $item->validateParameters($params);

        $content = '<div class="space-y-2">';
        $content .= '<div><strong>📋 Item:</strong> '.$item->description.'</div>';
        $content .= '<div><strong>📐 Tipo:</strong> '.$item->pricing_type_name.'</div>';
        $content .= '<div><strong>💰 Precio de Venta:</strong> '.$item->formatted_sale_price.'</div>';

        if (!empty($errors)) {
            $content .= '<div class="text-red-600 mt-2">';
            foreach ($errors as $error) {
                $content .= '<div>❌ '.$error.'</div>';
            }
            $content .= '</div>';
        } else {
            $totalPrice = $item->calculateTotalPrice($params);
            $unitPrice = $totalPrice / $quantity;

            if ($item->pricing_type === 'size' && $width > 0 && $height > 0) {
                $area = ($width / 100) * ($height / 100); // Convertir a m²
                $content .= '<div><strong>📏 Área:</strong> '.number_format($area, 4).' m²</div>';
            }

            $content .= '<div class="mt-2 p-2 bg-blue-50 rounded">';
            $content .= '<div><strong>💵 Precio por unidad:</strong> $'.number_format($unitPrice, 2).'</div>';
            $content .= '<div><strong>💵 Total:</strong> $'.number_format($totalPrice, 2).'</div>';
            $content .= '</div>';

            $content .= '<div class="text-green-600"><strong>✅ Cálculo válido</strong></div>';
        }

        $content .= '</div>';

        return $content;
    }

    private function getFinishingCostDisplay($get): string
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 0;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;

        if (!$finishingId || $quantity <= 0) {
            return '$0.00';
        }

        try {
            $finishing = Finishing::find($finishingId);
            if (!$finishing) {
                return 'Acabado no encontrado';
            }

            $calculator = app(\App\Services\FinishingCalculatorService::class);
            $cost = $calculator->calculateCost($finishing, [
                'quantity' => $quantity,
                'width' => $width,
                'height' => $height,
            ]);

            return '$'.number_format($cost, 2);

        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    }
}