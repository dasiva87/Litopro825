<?php

namespace App\Filament\Resources\SimpleItems\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use App\Models\Paper;
use App\Models\PrintingMachine;

class SimpleItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Layout de 2 columnas principal
                Grid::make(2)
                    ->schema([
                        // COLUMNA IZQUIERDA - Información del Producto
                        Section::make('📝 Información del Producto')
                            ->schema([
                                Textarea::make('description')
                                    ->label('Descripción')
                                    ->required()
                                    ->rows(2)
                                    ->placeholder('Ej: Volantes promocionales full color...'),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->suffix('uds'),

                                        TextInput::make('sobrante_papel')
                                            ->label('Sobrante')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->suffix('uds')
                                            ->helperText('Desperdicios (si >100 se cobra)'),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('horizontal_size')
                                            ->label('Ancho')
                                            ->numeric()
                                            ->required()
                                            ->suffix('cm')
                                            ->step(0.1),

                                        TextInput::make('vertical_size')
                                            ->label('Alto')
                                            ->numeric()
                                            ->required()
                                            ->suffix('cm')
                                            ->step(0.1),

                                        Placeholder::make('area_calculation')
                                            ->label('Área')
                                            ->content(function ($get) {
                                                $h = $get('horizontal_size');
                                                $v = $get('vertical_size');
                                                return $h && $v ? number_format($h * $v, 2) . ' cm²' : '-';
                                            }),
                                    ]),
                            ])
                            ->columnSpan(1),

                        // COLUMNA DERECHA - Configuración de Impresión
                        Section::make('🖨️ Configuración de Impresión')
                            ->schema([
                                Select::make('paper_id')
                                    ->label('Papel')
                                    ->options(function () {
                                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                                        if (!$company) {
                                            return [];
                                        }

                                        if ($company->isLitografia()) {
                                            $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                                                ->where('is_active', true)
                                                ->whereNotNull('approved_at')
                                                ->pluck('supplier_company_id')
                                                ->toArray();

                                            $papers = Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                                                $query->forTenant($currentCompanyId)
                                                      ->orWhereIn('company_id', $supplierCompanyIds);
                                            })
                                            ->where('is_active', true)
                                            ->with('company')
                                            ->get()
                                            ->mapWithKeys(function ($paper) use ($currentCompanyId) {
                                                $origin = $paper->company_id === $currentCompanyId ? '✓' : '📦';
                                                $label = "$origin {$paper->code} - {$paper->name} ({$paper->width}x{$paper->height}cm)";
                                                return [$paper->id => $label];
                                            });

                                            return $papers->toArray();
                                        } else {
                                            return Paper::where('company_id', $currentCompanyId)
                                                ->where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(function ($paper) {
                                                    return [$paper->id => "{$paper->code} - {$paper->name} ({$paper->width}x{$paper->height}cm)"];
                                                })
                                                ->toArray();
                                        }
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Select::make('printing_machine_id')
                                    ->label('Máquina')
                                    ->relationship('printingMachine', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) =>
                                        $record->name . ' - ' . ucfirst($record->type) .
                                        ' (Max: ' . $record->max_colors . ' tintas)'
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('ink_front_count')
                                            ->label('Tintas Tiro')
                                            ->numeric()
                                            ->required()
                                            ->default(4)
                                            ->minValue(0)
                                            ->maxValue(8),

                                        TextInput::make('ink_back_count')
                                            ->label('Tintas Retiro')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(8),

                                        Toggle::make('front_back_plate')
                                            ->label('Misma Plancha')
                                            ->inline(false),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),

                // Sección de costos - ancho completo pero más compacta
                Section::make('💰 Costos y Márgenes')
                    ->collapsed()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('design_value')
                                    ->label('Diseño')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('transport_value')
                                    ->label('Transporte')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('rifle_value')
                                    ->label('Rifle/Doblez')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('profit_percentage')
                                    ->label('Ganancia')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(30)
                                    ->minValue(0)
                                    ->maxValue(100),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('cutting_cost')
                                    ->label('Corte (0 = automático)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('mounting_cost')
                                    ->label('Montaje (0 = automático)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),
                            ]),
                    ]),

                // Sección de resultados - solo visible en edición
                Section::make('📊 Resultados del Cálculo')
                    ->collapsed()
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Opciones de Montaje
                                Placeholder::make('mounting_options')
                                    ->label('Opciones de Montaje')
                                    ->content(function ($record) {
                                        if (!$record) return null;

                                        $simpleItem = $record;
                                        if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                            $simpleItem = $record->itemable;
                                        }

                                        if (!$simpleItem || !method_exists($simpleItem, 'getMountingOptions')) {
                                            return 'No disponible';
                                        }

                                        $options = $simpleItem->getMountingOptions();
                                        if (empty($options)) return 'Sin opciones';

                                        $content = '<div class="space-y-2">';
                                        foreach ($options as $index => $option) {
                                            $isSelected = $index === 0;
                                            $bgColor = $isSelected ? 'bg-green-50 border-green-300' : 'bg-gray-50 border-gray-200';

                                            $content .= "<div class='p-2 {$bgColor} rounded border'>";
                                            $content .= "<div class='flex justify-between items-center'>";
                                            $content .= "<div class='text-sm'>";
                                            $content .= "<span class='font-medium'>" . ucfirst($option->orientation) . "</span>";
                                            if ($isSelected) $content .= " <span class='text-green-600'>✓</span>";
                                            $content .= "<div class='text-xs text-gray-600'>";
                                            $content .= "{$option->cutsPerSheet} cortes | {$option->sheetsNeeded} pliegos | ";
                                            $content .= number_format($option->utilizationPercentage, 1) . "% aprovech.";
                                            $content .= "</div></div>";
                                            $content .= "<span class='font-bold text-sm'>$" . number_format($option->paperCost, 0) . "</span>";
                                            $content .= "</div></div>";
                                        }
                                        $content .= '</div>';

                                        return $content;
                                    })
                                    ->html(),

                                // Resumen Financiero
                                Placeholder::make('pricing_summary')
                                    ->label('Resumen Financiero')
                                    ->content(function ($record) {
                                        if (!$record) return null;

                                        $simpleItem = $record;
                                        if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                            $simpleItem = $record->itemable;
                                        }

                                        if (!$simpleItem || !isset($simpleItem->final_price)) {
                                            return 'No disponible';
                                        }

                                        $unitPrice = $simpleItem->final_price / max($simpleItem->quantity, 1);
                                        $profitAmount = ($simpleItem->final_price ?? 0) - ($simpleItem->total_cost ?? 0);

                                        $content = '<div class="space-y-1.5">';
                                        $content .= '<div class="flex justify-between text-sm">';
                                        $content .= '<span class="text-gray-600">Subtotal</span>';
                                        $content .= '<span>$' . number_format($simpleItem->total_cost ?? 0, 0) . '</span>';
                                        $content .= '</div>';

                                        $content .= '<div class="flex justify-between text-sm text-green-600">';
                                        $content .= '<span>Ganancia (' . ($simpleItem->profit_percentage ?? 0) . '%)</span>';
                                        $content .= '<span>+$' . number_format($profitAmount, 0) . '</span>';
                                        $content .= '</div>';

                                        $content .= '<div class="flex justify-between font-bold text-base border-t pt-1.5 mt-1">';
                                        $content .= '<span>TOTAL</span>';
                                        $content .= '<span class="text-blue-600">$' . number_format($simpleItem->final_price ?? 0, 0) . '</span>';
                                        $content .= '</div>';

                                        $content .= '<div class="text-center text-xs text-gray-500 mt-1">';
                                        $content .= 'Unitario: <strong>$' . number_format($unitPrice, 2) . '</strong>';
                                        $content .= '</div>';
                                        $content .= '</div>';

                                        return $content;
                                    })
                                    ->html(),
                            ]),

                        // Desglose de costos - ancho completo
                        Placeholder::make('detailed_breakdown')
                            ->label('Desglose Detallado')
                            ->content(function ($record) {
                                if (!$record) return null;

                                $simpleItem = $record;
                                if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                    $simpleItem = $record->itemable;
                                }

                                if (!$simpleItem || !method_exists($simpleItem, 'getDetailedCostBreakdown')) {
                                    return 'No disponible';
                                }

                                $breakdown = $simpleItem->getDetailedCostBreakdown();
                                if (empty($breakdown)) return 'Sin desglose';

                                $content = '<div class="grid grid-cols-2 gap-2">';
                                foreach ($breakdown as $key => $detail) {
                                    $cost = str_replace(['$', ','], '', $detail['cost']);
                                    if ($cost > 0) {
                                        $content .= '<div class="flex justify-between text-sm py-1 border-b border-gray-100">';
                                        $content .= '<span class="text-gray-700">' . $detail['description'] . '</span>';
                                        $content .= '<span class="font-medium">' . $detail['cost'] . '</span>';
                                        $content .= '</div>';
                                    }
                                }
                                $content .= '</div>';

                                return $content;
                            })
                            ->html()
                            ->columnSpanFull(),

                        // Validaciones técnicas
                        Placeholder::make('technical_validations')
                            ->label('Validaciones Técnicas')
                            ->content(function ($record) {
                                if (!$record) return null;

                                $simpleItem = $record;
                                if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                    $simpleItem = $record->itemable;
                                }

                                if (!$simpleItem || !method_exists($simpleItem, 'validateTechnicalViability')) {
                                    return 'No disponible';
                                }

                                $validations = $simpleItem->validateTechnicalViability();

                                if (empty($validations)) {
                                    return '<div class="flex items-center text-green-600 text-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Todas las validaciones OK</span>
                                    </div>';
                                }

                                $content = '<div class="space-y-1">';
                                foreach ($validations as $validation) {
                                    $isError = $validation['type'] === 'error';
                                    $color = $isError ? 'red' : 'yellow';
                                    $content .= '<div class="flex items-start text-' . $color . '-600 text-sm">';
                                    $content .= '<span class="mr-1">⚠️</span>';
                                    $content .= '<span>' . $validation['message'] . '</span>';
                                    $content .= '</div>';
                                }
                                $content .= '</div>';

                                return $content;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
