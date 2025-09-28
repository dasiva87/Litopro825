<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface;
use App\Filament\Resources\Documents\RelationManagers\Traits\CalculatesFinishings;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use App\Models\Document;
use App\Models\SimpleItem;
use App\Models\Finishing;
use Filament\Forms\Components;

class SimpleItemQuickHandler implements QuickActionHandlerInterface
{
    use CalculatesFinishings;

    private $calculationContext;

    public function getFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Item Sencillo Rápido')
                ->description('Crea un item sencillo con parámetros optimizados')
                ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema)->getComponents()),

            // Sección de Acabados para Item Sencillo Rápido
            \Filament\Schemas\Components\Section::make('🎨 Acabados Opcionales')
                ->description('Agrega acabados adicionales que se calcularán automáticamente')
                ->schema([
                    Components\Repeater::make('finishings')
                        ->label('Acabados')
                        ->schema([
                            Components\Select::make('finishing_id')
                                ->label('Acabado')
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
                        ->addActionLabel('+ Agregar Acabado')
                ]),
        ];
    }

    public function handleCreate(array $data, Document $document): void
    {
        // Extraer datos del SimpleItem del formulario
        $simpleItemData = array_filter($data, function ($key) {
            return !in_array($key, ['finishings']);
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

        // Procesar acabados si existen
        $finishingsData = $data['finishings'] ?? [];
        if (!empty($finishingsData)) {
            foreach ($finishingsData as $finishingData) {
                if (isset($finishingData['finishing_id']) && isset($finishingData['calculated_cost'])) {
                    $finishing = Finishing::find($finishingData['finishing_id']);

                    if ($finishing) {
                        // Crear el acabado relacionado
                        $documentItem->finishings()->create([
                            'finishing_id' => $finishing->id,
                            'quantity' => $finishingData['quantity'] ?? 1,
                            'is_double_sided' => false, // Para SimpleItems no aplica
                            'unit_price' => ($finishingData['calculated_cost'] ?? 0) / ($finishingData['quantity'] ?? 1),
                            'total_price' => $finishingData['calculated_cost'] ?? 0,
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
        return 'Item Sencillo Rápido';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-bolt';
    }

    public function getColor(): string
    {
        return 'success';
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
            ->where('company_id', auth()->user()->company_id)
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
                'width' => $width > 0 ? $width : null,
                'height' => $height > 0 ? $height : null,
            ]);

            return '$'.number_format($cost, 2);

        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    }
}