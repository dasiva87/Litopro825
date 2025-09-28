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
                Section::make('Información Básica')
                    ->schema([
                        Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Describe detalladamente el trabajo a realizar...'),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->suffix('unidades')
                                    ->placeholder('1000'),

                                TextInput::make('sobrante_papel')
                                    ->label('Sobrante de Papel')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('unidades')
                                    ->placeholder('50')
                                    ->helperText('Cantidad adicional para desperdicios y pruebas. Si es mayor a 100, se cobra en la impresión.'),
                            ]),
                    ]),
                    
                Section::make('Dimensiones del Producto')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('horizontal_size')
                                    ->label('Tamaño Horizontal')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->minValue(0)
                                    ->step(0.1)
                                    ->placeholder('21.0'),
                                    
                                TextInput::make('vertical_size')
                                    ->label('Tamaño Vertical')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->minValue(0)
                                    ->step(0.1)
                                    ->placeholder('29.7'),
                                    
                                Placeholder::make('area_calculation')
                                    ->label('Área Total')
                                    ->content(function ($get) {
                                        return $get('horizontal_size') && $get('vertical_size') ? 
                                            number_format($get('horizontal_size') * $get('vertical_size'), 2) . ' cm²' : 
                                            '- cm²';
                                    }),
                            ]),
                    ]),
                    
                Section::make('Papel y Máquina')
                    ->schema([
                        Grid::make(2)
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
                                            // Para litografías: papeles propios + de proveedores aprobados
                                            $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                                                ->where('is_active', true)
                                                ->whereNotNull('approved_at')
                                                ->pluck('supplier_company_id')
                                                ->toArray();

                                            $papers = Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                                                $query->where('company_id', $currentCompanyId) // Propios
                                                      ->orWhereIn('company_id', $supplierCompanyIds); // De proveedores aprobados
                                            })
                                            ->where('is_active', true)
                                            ->with('company')
                                            ->get()
                                            ->mapWithKeys(function ($paper) use ($currentCompanyId) {
                                                $origin = $paper->company_id === $currentCompanyId ? 'Propio' : $paper->company->name;
                                                $label = $paper->code . ' - ' . $paper->name .
                                                        ' (' . $paper->width . 'x' . $paper->height . 'cm) - ' . $origin;
                                                return [$paper->id => $label];
                                            });

                                            return $papers->toArray();
                                        } else {
                                            // Para papelerías: solo papeles propios
                                            return Paper::where('company_id', $currentCompanyId)
                                                ->where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(function ($paper) {
                                                    $label = $paper->code . ' - ' . $paper->name .
                                                            ' (' . $paper->width . 'x' . $paper->height . 'cm)';
                                                    return [$paper->id => $label];
                                                })
                                                ->toArray();
                                        }
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                    
                                Select::make('printing_machine_id')
                                    ->label('Máquina de Impresión')
                                    ->relationship('printingMachine', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => 
                                        $record->name . ' - ' . ucfirst($record->type) .
                                        ' (Max: ' . $record->max_colors . ' colores)'
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),
                    
                Section::make('Información de Tintas')
                    ->schema([
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
                                    ->label('Tiro y Retiro Plancha'),
                            ]),
                    ]),
                    
                Section::make('Costos Adicionales')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('design_value')
                                    ->label('Valor Diseño')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01),

                                TextInput::make('transport_value')
                                    ->label('Valor Transporte')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01),

                                TextInput::make('rifle_value')
                                    ->label('Valor Rifle/Doblez')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01),

                                TextInput::make('cutting_cost')
                                    ->label('Costo de Corte')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Especifica el costo de corte o deja en 0 para cálculo automático'),

                                TextInput::make('mounting_cost')
                                    ->label('Costo de Montaje')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Especifica el costo de montaje o deja en 0 para cálculo automático'),

                                TextInput::make('profit_percentage')
                                    ->label('Porcentaje de Ganancia')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(30)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1),
                            ]),
                    ]),
                    
                Section::make('Opciones de Montaje')
                    ->description('Diferentes formas de aprovechar el papel')
                    ->schema([
                        Placeholder::make('mounting_options')
                            ->label('Montajes Disponibles')
                            ->content(function ($record) {
                                if (!$record) return 'Guarda el item para ver las opciones de montaje';
                                
                                // Si el record es un DocumentItem, acceder al SimpleItem a través de itemable
                                $simpleItem = $record;
                                if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                    $simpleItem = $record->itemable;
                                }
                                
                                if (!$simpleItem || !method_exists($simpleItem, 'getMountingOptions')) {
                                    return 'No se pueden calcular opciones de montaje para este tipo de item';
                                }
                                
                                $options = $simpleItem->getMountingOptions();
                                if (empty($options)) return 'No se pudieron calcular opciones de montaje';
                                
                                $content = '<div class="space-y-3">';
                                foreach ($options as $index => $option) {
                                    $isSelected = $index === 0 ? ' (SELECCIONADO)' : '';
                                    $badgeColor = $index === 0 ? 'success' : 'gray';
                                    
                                    $content .= '<div class="p-3 bg-gray-50 rounded-lg border">';
                                    $content .= '<div class="flex justify-between items-start">';
                                    $content .= '<div>';
                                    $content .= '<h4 class="font-semibold text-sm">' . ucfirst($option->orientation) . $isSelected . '</h4>';
                                    $content .= '<div class="text-xs text-gray-600 mt-1">';
                                    $content .= '<span class="mr-4">' . $option->cutsPerSheet . ' cortes/pliego</span>';
                                    $content .= '<span class="mr-4">' . $option->sheetsNeeded . ' pliegos</span>';
                                    $content .= '<span>' . number_format($option->utilizationPercentage, 1) . '% aprovechamiento</span>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                    $content .= '<div class="text-right">';
                                    $content .= '<span class="font-bold">$' . number_format($option->paperCost, 0) . '</span>';
                                    $content .= '<div class="text-xs text-gray-500">papel</div>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                }
                                $content .= '</div>';
                                
                                return $content;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('Desglose Detallado de Costos')
                    ->description('Cálculo completo con todos los componentes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('detailed_breakdown')
                                    ->label('Componentes de Costo')
                                    ->content(function ($record) {
                                        if (!$record) return 'Guarda el item para ver el desglose detallado';
                                        
                                        // Si el record es un DocumentItem, acceder al SimpleItem a través de itemable
                                        $simpleItem = $record;
                                        if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                            $simpleItem = $record->itemable;
                                        }
                                        
                                        if (!$simpleItem || !method_exists($simpleItem, 'getDetailedCostBreakdown')) {
                                            return 'No se puede calcular el desglose para este tipo de item';
                                        }
                                        
                                        $breakdown = $simpleItem->getDetailedCostBreakdown();
                                        if (empty($breakdown)) return 'No se pudo calcular el desglose';
                                        
                                        $content = '<div class="space-y-2">';
                                        foreach ($breakdown as $key => $detail) {
                                            if (str_replace(['$', ','], '', $detail['cost']) > 0) {
                                                $content .= '<div class="flex justify-between py-1 border-b border-gray-100">';
                                                $content .= '<div>';
                                                $content .= '<span class="font-medium">' . $detail['description'] . '</span>';
                                                $content .= '<div class="text-xs text-gray-500">' . $detail['detail'] . '</div>';
                                                $content .= '</div>';
                                                $content .= '<span class="font-semibold">' . $detail['cost'] . '</span>';
                                                $content .= '</div>';
                                            }
                                        }
                                        $content .= '</div>';
                                        
                                        return $content;
                                    })
                                    ->html(),
                                    
                                Placeholder::make('pricing_summary')
                                    ->label('Resumen Financiero')
                                    ->content(function ($record) {
                                        if (!$record) return 'Guarda el item para ver el resumen';
                                        
                                        // Si el record es un DocumentItem, acceder al SimpleItem a través de itemable
                                        $simpleItem = $record;
                                        if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                            $simpleItem = $record->itemable;
                                        }
                                        
                                        if (!$simpleItem || !isset($simpleItem->final_price)) {
                                            return 'No se puede calcular el resumen para este tipo de item';
                                        }
                                        
                                        $unitPrice = $simpleItem->final_price / max($simpleItem->quantity, 1);
                                        $profitAmount = ($simpleItem->final_price ?? 0) - ($simpleItem->total_cost ?? 0);
                                        
                                        $content = '<div class="space-y-3">';
                                        
                                        // Subtotal
                                        $content .= '<div class="flex justify-between">';
                                        $content .= '<span>Subtotal (sin ganancia)</span>';
                                        $content .= '<span>$' . number_format($simpleItem->total_cost ?? 0, 2) . '</span>';
                                        $content .= '</div>';
                                        
                                        // Ganancia
                                        $content .= '<div class="flex justify-between text-green-600">';
                                        $content .= '<span>Ganancia (' . ($simpleItem->profit_percentage ?? 0) . '%)</span>';
                                        $content .= '<span>+$' . number_format($profitAmount, 2) . '</span>';
                                        $content .= '</div>';
                                        
                                        // Total
                                        $content .= '<div class="flex justify-between font-bold text-lg border-t pt-2">';
                                        $content .= '<span>PRECIO FINAL</span>';
                                        $content .= '<span class="text-blue-600">$' . number_format($simpleItem->final_price ?? 0, 2) . '</span>';
                                        $content .= '</div>';
                                        
                                        // Precio unitario
                                        $content .= '<div class="text-center text-sm text-gray-600 mt-2">';
                                        $content .= 'Precio por unidad: <strong>$' . number_format($unitPrice, 4) . '</strong>';
                                        $content .= '</div>';
                                        
                                        $content .= '</div>';
                                        
                                        return $content;
                                    })
                                    ->html(),
                            ]),
                    ]),
                    
                Section::make('Validaciones Técnicas')
                    ->description('Verificaciones de viabilidad')
                    ->schema([
                        Placeholder::make('technical_validations')
                            ->label('Estado de Validaciones')
                            ->content(function ($record) {
                                if (!$record) return 'Guarda el item para ver las validaciones';
                                
                                // Si el record es un DocumentItem, acceder al SimpleItem a través de itemable
                                $simpleItem = $record;
                                if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                    $simpleItem = $record->itemable;
                                }
                                
                                if (!$simpleItem || !method_exists($simpleItem, 'validateTechnicalViability')) {
                                    return 'No se pueden realizar validaciones para este tipo de item';
                                }
                                
                                $validations = $simpleItem->validateTechnicalViability();
                                
                                if (empty($validations)) {
                                    return '<div class="flex items-center text-green-600">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="font-medium">Todas las validaciones pasaron correctamente</span>
                                    </div>';
                                }
                                
                                $content = '<div class="space-y-2">';
                                foreach ($validations as $validation) {
                                    $isError = $validation['type'] === 'error';
                                    $color = $isError ? 'red' : 'yellow';
                                    $icon = $isError ? 
                                        '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>' :
                                        '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                                        
                                    $content .= '<div class="flex items-start text-' . $color . '-600">';
                                    $content .= $icon;
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
