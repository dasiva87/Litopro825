<?php

namespace App\Filament\Resources\MagazineItems\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Models\SimpleItem;
use App\Models\MagazinePage;
use App\Models\Finishing;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;

class MagazineItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Textarea::make('description')
                            ->label('Descripción de la Revista')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Describe la revista: temática, características especiales, etc.'),
                            
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1)
                            ->suffix('revistas')
                            ->placeholder('100'),
                    ]),
                    
                Section::make('Dimensiones Revista Cerrada')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('closed_width')
                                    ->label('Ancho Cerrado')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->minValue(0)
                                    ->placeholder('21')
                                    ->helperText('Ancho de la revista cuando está cerrada'),
                                    
                                TextInput::make('closed_height')
                                    ->label('Alto Cerrado')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->minValue(0)
                                    ->placeholder('29.7')
                                    ->helperText('Alto de la revista cuando está cerrada'),
                            ]),
                    ]),
                    
                Section::make('Encuadernación')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('binding_type')
                                    ->label('Tipo de Encuadernación')
                                    ->required()
                                    ->options([
                                        'grapado' => 'Grapado',
                                        'plegado' => 'Plegado',
                                        'anillado' => 'Anillado',
                                        'cosido' => 'Cosido',
                                        'caballete' => 'Caballete',
                                        'lomo' => 'Lomo',
                                        'espiral' => 'Espiral',
                                        'wire_o' => 'Wire-O',
                                        'hotmelt' => 'Hot Melt',
                                    ])
                                    ->default('grapado')
                                    ->searchable()
                                    ->helperText('Seleccione el método de encuadernación'),
                                    
                                Select::make('binding_side')
                                    ->label('Lado de Encuadernación')
                                    ->required()
                                    ->options([
                                        'izquierda' => 'Izquierda',
                                        'derecha' => 'Derecha',
                                        'arriba' => 'Arriba',
                                        'abajo' => 'Abajo',
                                    ])
                                    ->default('izquierda')
                                    ->helperText('Posición donde se realizará la encuadernación'),
                            ]),
                    ]),
                    
                Section::make('Páginas de la Revista')
                    ->schema([
                        Placeholder::make('existing_pages_table')
                            ->label('Páginas Actuales')
                            ->content(function ($record) {
                                if (!$record) {
                                    return '📄 No hay páginas agregadas. Use el botón "Agregar Página" para crear la primera página.';
                                }

                                $pages = $record->getPagesTableData();
                                
                                if (empty($pages)) {
                                    return '📄 No hay páginas agregadas. Use el botón "Agregar Página" para crear la primera página.';
                                }

                                $content = '<div class="overflow-x-auto">';
                                $content .= '<table class="min-w-full divide-y divide-gray-200">';
                                $content .= '<thead class="bg-gray-50">';
                                $content .= '<tr>';
                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Orden</th>';
                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>';
                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>';
                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>';
                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>';
                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>';
                                $content .= '</tr>';
                                $content .= '</thead>';
                                $content .= '<tbody class="bg-white divide-y divide-gray-200">';

                                foreach ($pages as $page) {
                                    $content .= '<tr>';
                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">' . $page['order'] . '</td>';
                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">';
                                    $content .= '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">' . $page['type'] . '</span>';
                                    $content .= '</td>';
                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">' . $page['quantity'] . ' págs</td>';
                                    $content .= '<td class="px-3 py-2 text-sm text-gray-900">' . substr($page['description'], 0, 50) . '...</td>';
                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">$' . $page['unit_price'] . '</td>';
                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">$' . $page['total_cost'] . '</td>';
                                    $content .= '</tr>';
                                }

                                $content .= '</tbody>';
                                $content .= '</table>';
                                $content .= '</div>';

                                // Mostrar totales
                                $totalPages = collect($pages)->sum('quantity');
                                $totalCost = collect($pages)->sum(function ($page) {
                                    return floatval(str_replace(',', '', $page['total_cost']));
                                });

                                $content .= '<div class="mt-4 p-3 bg-blue-50 rounded-lg">';
                                $content .= '<div class="flex justify-between">';
                                $content .= '<span class="font-medium">Total: ' . $totalPages . ' páginas</span>';
                                $content .= '<span class="font-bold">Costo total páginas: $' . number_format($totalCost, 2) . '</span>';
                                $content .= '</div>';
                                $content .= '</div>';

                                return $content;
                            })
                            ->html()
                            ->columnSpanFull(),
                            
                        Placeholder::make('add_pages_info')
                            ->content(function ($record) {
                                if (!$record) {
                                    $content = '<div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">';
                                    $content .= '<div class="flex items-start">';
                                    $content .= '<div class="flex-shrink-0">';
                                    $content .= '<svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">';
                                    $content .= '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>';
                                    $content .= '</svg>';
                                    $content .= '</div>';
                                    $content .= '<div class="ml-3">';
                                    $content .= '<h3 class="text-sm font-medium text-amber-800">Gestión de Páginas</h3>';
                                    $content .= '<div class="mt-2 text-sm text-amber-700">';
                                    $content .= '<p>Para agregar páginas a la revista, primero debe <strong>guardar la revista</strong>.</p>';
                                    $content .= '<p class="mt-1">Después podrá:</p>';
                                    $content .= '<ul class="mt-1 list-disc list-inside">';
                                    $content .= '<li>Usar el botón "➕ Agregar Página"</li>';
                                    $content .= '<li>Crear SimpleItems completos para cada página</li>';
                                    $content .= '<li>Ver cálculos automáticos en tiempo real</li>';
                                    $content .= '</ul>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                    
                                    return $content;
                                }
                                
                                return null; // No mostrar nada si ya existe la revista
                            })
                            ->html()
                            ->columnSpanFull(),
                            
                        Actions::make([
                            Action::make('add_page')
                                ->label('➕ Agregar Página')
                                ->color('primary')
                                ->icon('heroicon-o-plus-circle')
                                ->modalHeading('Crear Nueva Página para la Revista')
                                ->modalWidth('7xl')
                                ->form([
                                    Section::make('Información de la Página')
                                        ->schema([
                                            Select::make('page_type')
                                                ->label('Tipo de Página')
                                                ->required()
                                                ->options([
                                                    'portada' => 'Portada',
                                                    'contraportada' => 'Contraportada',
                                                    'interior' => 'Interior',
                                                    'inserto' => 'Inserto',
                                                    'separador' => 'Separador',
                                                    'anexo' => 'Anexo',
                                                ])
                                                ->default('interior')
                                                ->columnSpan(1),
                                                
                                            TextInput::make('page_quantity')
                                                ->label('Cantidad de Páginas')
                                                ->numeric()
                                                ->required()
                                                ->default(1)
                                                ->minValue(1)
                                                ->placeholder('1')
                                                ->columnSpan(1),
                                                
                                            Textarea::make('description')
                                                ->label('Descripción del Contenido')
                                                ->required()
                                                ->rows(3)
                                                ->columnSpanFull()
                                                ->placeholder('Describe el contenido de esta página...'),
                                                
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('quantity')
                                                        ->label('Cantidad de Impresión')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(100)
                                                        ->minValue(1)
                                                        ->placeholder('100')
                                                        ->helperText('Cantidad de ejemplares a imprimir'),
                                                        
                                                    TextInput::make('profit_percentage')
                                                        ->label('Margen de Ganancia')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(25)
                                                        ->minValue(0)
                                                        ->maxValue(500)
                                                        ->suffix('%')
                                                        ->placeholder('25'),
                                                ])
                                                ->columnSpanFull(),
                                        ]),
                                        
                                    Section::make('Dimensiones')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('horizontal_size')
                                                        ->label('Ancho')
                                                        ->numeric()
                                                        ->required()
                                                        ->suffix('cm')
                                                        ->minValue(0)
                                                        ->placeholder('21'),
                                                        
                                                    TextInput::make('vertical_size')
                                                        ->label('Alto')
                                                        ->numeric()
                                                        ->required()
                                                        ->suffix('cm')
                                                        ->minValue(0)
                                                        ->placeholder('29.7'),
                                                ]),
                                        ]),
                                        
                                    Section::make('Materiales')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    Select::make('paper_id')
                                                        ->label('Papel')
                                                        ->options(function () {
                                                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                                                            $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                                                            if ($company && $company->isLitografia()) {
                                                                // Para litografías: incluir papeles propios + de proveedores aprobados
                                                                $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                                                                    ->where('is_active', true)
                                                                    ->whereNotNull('approved_at')
                                                                    ->pluck('supplier_company_id')
                                                                    ->toArray();

                                                                $papers = Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                                                                    $query->where('company_id', $currentCompanyId)
                                                                          ->orWhereIn('company_id', $supplierCompanyIds);
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
                                                                return Paper::query()
                                                                    ->where('company_id', $currentCompanyId)
                                                                    ->where('is_active', true)
                                                                    ->get()
                                                                    ->mapWithKeys(function ($paper) {
                                                                        $label = $paper->full_name ?: ($paper->code . ' - ' . $paper->name);
                                                                        return [$paper->id => $label];
                                                                    })
                                                                    ->toArray();
                                                            }
                                                        })
                                                        ->required()
                                                        ->searchable()
                                                        ->placeholder('Seleccionar papel'),
                                                        
                                                    Select::make('printing_machine_id')
                                                        ->label('Máquina de Impresión')
                                                        ->options(function () {
                                                            $companyId = auth()->user()->company_id ?? 1;
                                                            return PrintingMachine::query()
                                                                ->where('company_id', $companyId)
                                                                ->get()
                                                                ->mapWithKeys(function ($machine) {
                                                                    $label = $machine->name . ' - ' . ucfirst($machine->type);
                                                                    return [$machine->id => $label];
                                                                })
                                                                ->toArray();
                                                        })
                                                        ->required()
                                                        ->searchable()
                                                        ->placeholder('Seleccionar máquina'),
                                                ]),
                                        ]),
                                        
                                    Section::make('Configuración de Tintas')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make('ink_front_count')
                                                        ->label('Tintas Frente')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(4)
                                                        ->minValue(0)
                                                        ->maxValue(8)
                                                        ->placeholder('4'),
                                                        
                                                    TextInput::make('ink_back_count')
                                                        ->label('Tintas Reverso')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(0)
                                                        ->minValue(0)
                                                        ->maxValue(8)
                                                        ->placeholder('0'),
                                                        
                                                    Select::make('front_back_plate')
                                                        ->label('Tiro y Retiro Plancha')
                                                        ->boolean()
                                                        ->default(false)
                                                        ->helperText('¿Se imprime frente y reverso con la misma plancha?'),
                                                ]),
                                        ]),
                                ])
                                ->action(function (array $data, $record) {
                                    // Extraer datos específicos de la página
                                    $pageType = $data['page_type'] ?? 'interior';
                                    $pageQuantity = $data['page_quantity'] ?? 1;
                                    
                                    // Preparar datos del SimpleItem (sin campos de página)
                                    $simpleItemData = $data;
                                    unset($simpleItemData['page_type'], $simpleItemData['page_quantity']);
                                    
                                    // Crear el SimpleItem
                                    $simpleItem = SimpleItem::create(array_merge($simpleItemData, [
                                        'company_id' => auth()->user()->company_id,
                                        'user_id' => auth()->id(),
                                        'profit_percentage' => $data['profit_percentage'] ?? 25,
                                    ]));

                                    // Crear la página de revista
                                    MagazinePage::create([
                                        'magazine_item_id' => $record->id,
                                        'simple_item_id' => $simpleItem->id,
                                        'page_type' => $pageType,
                                        'page_order' => $record->getNextPageOrder(),
                                        'page_quantity' => $pageQuantity,
                                    ]);

                                    // Recalcular precios de la revista
                                    $record->calculateAll();
                                    $record->save();

                                    // Notificación de éxito
                                    Notification::make()
                                        ->title('Página agregada correctamente')
                                        ->body("La página '{$pageType}' se ha creado y agregado a la revista.")
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn ($record) => $record !== null),
                        ])
                        ->alignEnd()
                        ->columnSpanFull()
                        ->visible(fn ($record) => $record !== null),
                    ]),
                    
                Section::make('Acabados')
                    ->schema([
                        CheckboxList::make('selected_finishings')
                            ->label('Acabados Disponibles')
                            ->options(
                                Finishing::query()
                                    ->where('active', true)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->descriptions(
                                Finishing::query()
                                    ->where('active', true)
                                    ->pluck('description', 'id')
                                    ->toArray()
                            )
                            ->default(function ($record) {
                                return $record && $record->finishings ? $record->finishings->pluck('id')->toArray() : [];
                            })
                            ->columns(2)
                            ->columnSpanFull()
                            ->helperText('Seleccione los acabados que requiere la revista'),
                    ]),
                    
                Section::make('Costos Adicionales')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('design_value')
                                    ->label('Valor Diseño')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->placeholder('0'),
                                    
                                TextInput::make('transport_value')
                                    ->label('Valor Transporte')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->placeholder('0'),
                                    
                                TextInput::make('profit_percentage')
                                    ->label('Porcentaje de Ganancia')
                                    ->numeric()
                                    ->default(25)
                                    ->minValue(0)
                                    ->maxValue(500)
                                    ->suffix('%')
                                    ->placeholder('25'),
                            ]),
                    ]),
                    
                Section::make('Resumen de Costos')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('pages_total_cost')
                                    ->label('Costo Total de Páginas')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->pages_total_cost ?? 0, 2);
                                    }),
                                    
                                Placeholder::make('binding_cost')
                                    ->label('Costo Encuadernación')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->binding_cost ?? 0, 2);
                                    }),
                                    
                                Placeholder::make('assembly_cost')
                                    ->label('Costo Armado')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->assembly_cost ?? 0, 2);
                                    }),
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('finishing_cost')
                                    ->label('Costo Acabados')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->finishing_cost ?? 0, 2);
                                    }),
                                    
                                Placeholder::make('total_cost')
                                    ->label('Costo Total')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->total_cost ?? 0, 2);
                                    }),
                                    
                                Placeholder::make('final_price')
                                    ->label('Precio Final')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->final_price ?? 0, 2);
                                    }),
                            ]),
                            
                        Placeholder::make('unit_price')
                            ->label('Precio Unitario')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->quantity) return '$0.00';
                                $unitPrice = $record->final_price / $record->quantity;
                                return '$' . number_format($unitPrice, 2) . ' / revista';
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Configuración de Páginas')
                    ->description('Define las páginas que tendrá la revista durante la creación')
                    ->schema([
                        Repeater::make('pages')
                            ->label('Páginas de la Revista')
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('page_type')
                                        ->label('Tipo de Página')
                                        ->required()
                                        ->options([
                                            'portada' => '📖 Portada',
                                            'contraportada' => '📗 Contraportada',
                                            'interior' => '📄 Interior',
                                            'inserto' => '📋 Inserto',
                                            'separador' => '📑 Separador',
                                            'anexo' => '📎 Anexo',
                                        ])
                                        ->default('interior'),

                                    TextInput::make('page_quantity')
                                        ->label('Cantidad de Páginas')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(1)
                                        ->suffix('páginas'),

                                    TextInput::make('page_order')
                                        ->label('Orden')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(1),
                                ]),

                                Textarea::make('description')
                                    ->label('Descripción del Contenido')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->placeholder('Describe el contenido de esta página...'),

                                Grid::make(2)->schema([
                                    Select::make('paper_id')
                                        ->label('Papel')
                                        ->options(function () {
                                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                                            $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                                            if ($company && $company->isLitografia()) {
                                                // Para litografías: incluir papeles propios + de proveedores aprobados
                                                $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                                                    ->where('is_active', true)
                                                    ->whereNotNull('approved_at')
                                                    ->pluck('supplier_company_id')
                                                    ->toArray();

                                                $papers = Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                                                    $query->where('company_id', $currentCompanyId)
                                                          ->orWhereIn('company_id', $supplierCompanyIds);
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
                                                return Paper::query()
                                                    ->where('company_id', $currentCompanyId)
                                                    ->where('is_active', true)
                                                    ->get()
                                                    ->mapWithKeys(function ($paper) {
                                                        $label = $paper->full_name ?: ($paper->code . ' - ' . $paper->name);
                                                        return [$paper->id => $label];
                                                    })
                                                    ->toArray();
                                            }
                                        })
                                        ->required()
                                        ->searchable(),

                                    Select::make('printing_machine_id')
                                        ->label('Máquina de Impresión')
                                        ->options(function () {
                                            $companyId = auth()->user()->company_id ?? 1;
                                            return PrintingMachine::query()
                                                ->where('company_id', $companyId)
                                                ->where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(function ($machine) {
                                                    $label = $machine->name . ' - ' . ucfirst($machine->type);
                                                    return [$machine->id => $label];
                                                })
                                                ->toArray();
                                        })
                                        ->required()
                                        ->searchable(),
                                ]),

                                Grid::make(4)->schema([
                                    TextInput::make('horizontal_size')
                                        ->label('Ancho')
                                        ->numeric()
                                        ->required()
                                        ->suffix('cm')
                                        ->minValue(0)
                                        ->step(0.1)
                                        ->placeholder('21.0'),

                                    TextInput::make('vertical_size')
                                        ->label('Alto')
                                        ->numeric()
                                        ->required()
                                        ->suffix('cm')
                                        ->minValue(0)
                                        ->step(0.1)
                                        ->placeholder('29.7'),

                                    TextInput::make('ink_front_count')
                                        ->label('Tintas Frente')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(0)
                                        ->maxValue(6),

                                    TextInput::make('ink_back_count')
                                        ->label('Tintas Dorso')
                                        ->numeric()
                                        ->required()
                                        ->default(0)
                                        ->minValue(0)
                                        ->maxValue(6),
                                ]),

                                Grid::make(3)->schema([
                                    TextInput::make('design_value')
                                        ->label('Valor Diseño')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('$')
                                        ->minValue(0),

                                    TextInput::make('transport_value')
                                        ->label('Valor Transporte')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('$')
                                        ->minValue(0),

                                    TextInput::make('profit_percentage')
                                        ->label('% Ganancia')
                                        ->numeric()
                                        ->default(25)
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(100),
                                ]),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(20)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['page_type'] ?
                                '📄 ' . ucfirst($state['page_type']) . ' - ' . ($state['page_quantity'] ?? 1) . ' pág.' :
                                'Nueva Página'
                            )
                            ->visible(fn ($record) => $record === null) // Solo mostrar al crear
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record === null) // Solo mostrar al crear
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Notas Adicionales')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Notas adicionales sobre la revista...'),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
}