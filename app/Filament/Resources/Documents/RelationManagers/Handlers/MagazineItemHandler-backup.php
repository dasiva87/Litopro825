<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\MagazineItem;
use App\Models\SimpleItem;
use App\Models\MagazinePage;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Finishing;

class MagazineItemHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            Section::make('Información Básica')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción de la Revista')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Describe la revista: temática, características especiales, etc.'),
                        
                    $this->makeTextInput('quantity', 'Cantidad')
                        ->numeric()
                        ->required()
                        ->default(100)
                        ->minValue(1)
                        ->suffix('revistas')
                        ->placeholder('100'),
                ]),
                
            Section::make('Dimensiones Revista Cerrada')
                ->schema([
                    Grid::make(2)->schema([
                        $this->makeTextInput('closed_width', 'Ancho Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->minValue(0)
                            ->placeholder('21')
                            ->helperText('Ancho de la revista cuando está cerrada'),
                            
                        $this->makeTextInput('closed_height', 'Alto Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->minValue(0)
                            ->placeholder('29.7')
                            ->helperText('Alto de la revista cuando está cerrada'),
                    ]),
                ]),
                
            $this->makeSection('Encuadernación')
                ->schema([
                    $this->makeGrid(2)->schema([
                        $this->makeSelect('binding_type', 'Tipo de Encuadernación', [
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
                        
                        $this->makeSelect('binding_side', 'Lado de Encuadernación', [
                            'izquierda' => 'Izquierda',
                            'derecha' => 'Derecha',
                            'arriba' => 'Arriba',
                            'abajo' => 'Abajo',
                        ])
                        ->default('izquierda')
                        ->helperText('Posición donde se realizará la encuadernación'),
                    ]),
                ]),
                
            $this->makeSection('Páginas de la Revista')
                ->schema([
                    Components\Placeholder::make('existing_pages_table')
                        ->label('Páginas Actuales')
                        ->content(function ($record) {
                            if (!$record || !$record->itemable) {
                                return '📄 No hay páginas agregadas. Use el botón "Agregar Página" para crear la primera página.';
                            }

                            $pages = $record->itemable->getPagesTableData();
                            
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
                        
                    Actions::make([
                        Action::make('add_page')
                            ->label('➕ Agregar Página')
                            ->color('primary')
                            ->icon('heroicon-o-plus-circle')
                            ->modalHeading('Crear Nueva Página para la Revista')
                            ->modalWidth('7xl')
                            ->form($this->getAddPageFormSchema())
                            ->action(function (array $data, $record) {
                                $this->handleAddPage($data, $record);
                            })
                            ->visible(fn ($record) => $record !== null),
                    ])
                    ->alignEnd()
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record !== null),
                ]),
                
            $this->makeSection('Acabados')
                ->schema([
                    Components\CheckboxList::make('selected_finishings')
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
                            return $record && $record->itemable ? $record->itemable->finishings->pluck('id')->toArray() : [];
                        })
                        ->columns(2)
                        ->columnSpanFull()
                        ->helperText('Seleccione los acabados que requiere la revista'),
                ]),
                
            $this->makeSection('Costos Adicionales')
                ->schema([
                    $this->makeGrid(3)->schema([
                        $this->makeTextInput('design_value', 'Valor Diseño')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('$')
                            ->placeholder('0'),
                            
                        $this->makeTextInput('transport_value', 'Valor Transporte')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('$')
                            ->placeholder('0'),
                            
                        $this->makeTextInput('profit_percentage', 'Porcentaje de Ganancia')
                            ->numeric()
                            ->default(25)
                            ->minValue(0)
                            ->maxValue(500)
                            ->suffix('%')
                            ->placeholder('25'),
                    ]),
                ]),
                
            $this->makeSection('Resumen de Costos')
                ->schema([
                    $this->makeGrid(3)->schema([
                        Components\Placeholder::make('pages_total_cost')
                            ->label('Costo Total de Páginas')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->itemable) return '$0.00';
                                return '$' . number_format($record->itemable->pages_total_cost ?? 0, 2);
                            }),
                            
                        Components\Placeholder::make('binding_cost')
                            ->label('Costo Encuadernación')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->itemable) return '$0.00';
                                return '$' . number_format($record->itemable->binding_cost ?? 0, 2);
                            }),
                            
                        Components\Placeholder::make('assembly_cost')
                            ->label('Costo Armado')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->itemable) return '$0.00';
                                return '$' . number_format($record->itemable->assembly_cost ?? 0, 2);
                            }),
                    ]),
                    
                    $this->makeGrid(3)->schema([
                        Components\Placeholder::make('finishing_cost')
                            ->label('Costo Acabados')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->itemable) return '$0.00';
                                return '$' . number_format($record->itemable->finishing_cost ?? 0, 2);
                            }),
                            
                        Components\Placeholder::make('total_cost')
                            ->label('Costo Total')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->itemable) return '$0.00';
                                return '$' . number_format($record->itemable->total_cost ?? 0, 2);
                            }),
                            
                        Components\Placeholder::make('final_price')
                            ->label('Precio Final')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->itemable) return '$0.00';
                                return '$' . number_format($record->itemable->final_price ?? 0, 2);
                            }),
                    ]),
                    
                    Components\Placeholder::make('unit_price')
                        ->label('Precio Unitario')
                        ->content(function ($get, $record) {
                            if (!$record || !$record->itemable || !$record->itemable->quantity) return '$0.00';
                            $unitPrice = $record->itemable->final_price / $record->itemable->quantity;
                            return '$' . number_format($unitPrice, 2) . ' / revista';
                        })
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(false),
                
            $this->makeSection('Notas Adicionales')
                ->schema([
                    Components\Textarea::make('notes')
                        ->label('Notas')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Notas adicionales sobre la revista...'),
                ])
                ->collapsible()
                ->collapsed(true),
        ];
    }
    
    public function fillForm($record): array
    {
        $magazine = $record->itemable;
        
        return [
            'description' => $magazine->description,
            'quantity' => $magazine->quantity,
            'closed_width' => $magazine->closed_width,
            'closed_height' => $magazine->closed_height,
            'binding_type' => $magazine->binding_type,
            'binding_side' => $magazine->binding_side,
            'design_value' => $magazine->design_value,
            'transport_value' => $magazine->transport_value,
            'profit_percentage' => $magazine->profit_percentage,
            'notes' => $magazine->notes,
        ];
    }
    
    private function getAddPageFormSchema(): array
    {
        return [
            $this->makeSection('Información de la Página')
                ->schema([
                    $this->makeSelect('page_type', 'Tipo de Página', [
                        'portada' => 'Portada',
                        'contraportada' => 'Contraportada',
                        'interior' => 'Interior',
                        'inserto' => 'Inserto',
                        'separador' => 'Separador',
                        'anexo' => 'Anexo',
                    ])
                    ->default('interior')
                    ->columnSpan(1),
                    
                    $this->makeTextInput('page_quantity', 'Cantidad de Páginas')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1)
                        ->placeholder('1')
                        ->columnSpan(1),
                        
                    Components\Textarea::make('description')
                        ->label('Descripción del Contenido')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Describe el contenido de esta página...'),
                        
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('quantity', 'Cantidad de Impresión')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1)
                            ->placeholder('100')
                            ->helperText('Cantidad de ejemplares a imprimir'),
                            
                        $this->makeTextInput('profit_percentage', 'Margen de Ganancia')
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
                
            $this->makeSection('Dimensiones')
                ->schema([
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('horizontal_size', 'Ancho')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->minValue(0)
                            ->placeholder('21'),
                            
                        $this->makeTextInput('vertical_size', 'Alto')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->minValue(0)
                            ->placeholder('29.7'),
                    ]),
                ]),
                
            $this->makeSection('Materiales')
                ->schema([
                    $this->makeGrid(2)->schema([
                        Components\Select::make('paper_id')
                            ->label('Papel')
                            ->options(function () {
                                $companyId = auth()->user()->company_id ?? 1;
                                return Paper::query()
                                    ->where('company_id', $companyId)
                                    ->get()
                                    ->mapWithKeys(function ($paper) {
                                        $label = $paper->full_name ?: ($paper->code . ' - ' . $paper->name);
                                        return [$paper->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Seleccionar papel'),
                            
                        Components\Select::make('printing_machine_id')
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
                
            $this->makeSection('Configuración de Tintas')
                ->schema([
                    $this->makeGrid(3)->schema([
                        $this->makeTextInput('ink_front_count', 'Tintas Frente')
                            ->numeric()
                            ->required()
                            ->default(4)
                            ->minValue(0)
                            ->maxValue(8)
                            ->placeholder('4'),
                            
                        $this->makeTextInput('ink_back_count', 'Tintas Reverso')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(8)
                            ->placeholder('0'),
                            
                        Components\Toggle::make('front_back_plate')
                            ->label('Tiro y Retiro Plancha')
                            ->default(false)
                            ->helperText('¿Se imprime frente y reverso con la misma plancha?'),
                    ]),
                ]),
        ];
    }
    
    private function handleAddPage(array $data, $record): void
    {
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
            'magazine_item_id' => $record->itemable->id,
            'simple_item_id' => $simpleItem->id,
            'page_type' => $pageType,
            'page_order' => $record->itemable->getNextPageOrder(),
            'page_quantity' => $pageQuantity,
        ]);

        // Recalcular precios de la revista
        $record->itemable->calculateAll();
        $record->itemable->save();
        
        // Actualizar DocumentItem
        $record->calculateAndUpdatePrices();

        // Notificación de éxito
        Notification::make()
            ->title('Página agregada correctamente')
            ->body("La página '{$pageType}' se ha creado y agregado a la revista.")
            ->success()
            ->send();
    }
    
    public function handleUpdate($record, array $data): void
    {
        $magazine = $record->itemable;
        
        $magazine->update([
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'closed_width' => $data['closed_width'],
            'closed_height' => $data['closed_height'],
            'binding_type' => $data['binding_type'],
            'binding_side' => $data['binding_side'],
            'design_value' => $data['design_value'] ?? 0,
            'transport_value' => $data['transport_value'] ?? 0,
            'profit_percentage' => $data['profit_percentage'] ?? 25,
            'notes' => $data['notes'],
        ]);
        
        // Actualizar acabados si existen en los datos
        if (isset($data['selected_finishings'])) {
            $magazine->finishings()->sync($data['selected_finishings']);
        }
        
        // Recalcular precios
        $magazine->calculateAll();
        $magazine->save();
        
        // Actualizar DocumentItem
        $record->calculateAndUpdatePrices();
    }
    
    public function getWizardStep(): Step
    {
        return Step::make('Revista')
            ->description('Crear una revista con múltiples páginas')
            ->icon('heroicon-o-book-open')
            ->schema([
                $this->makeSection('Información Básica')->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción de la Revista')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Describe la revista: temática, características especiales, etc.'),
                        
                    $this->makeTextInput('quantity', 'Cantidad')
                        ->numeric()
                        ->required()
                        ->default(100)
                        ->minValue(1)
                        ->suffix('revistas')
                        ->placeholder('100'),
                ]),
                
                $this->makeSection('Dimensiones y Encuadernación')->schema([
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('closed_width', 'Ancho Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->default(21)
                            ->minValue(0)
                            ->placeholder('21')
                            ->helperText('Ancho de la revista cuando está cerrada'),
                            
                        $this->makeTextInput('closed_height', 'Alto Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->default(29.7)
                            ->minValue(0)
                            ->placeholder('29.7')
                            ->helperText('Alto de la revista cuando está cerrada'),
                    ]),
                    
                    $this->makeGrid(2)->schema([
                        $this->makeSelect('binding_type', 'Tipo de Encuadernación', [
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
                        
                        $this->makeSelect('binding_side', 'Lado de Encuadernación', [
                            'izquierda' => 'Izquierda',
                            'derecha' => 'Derecha',
                            'arriba' => 'Arriba',
                            'abajo' => 'Abajo',
                        ])
                        ->default('izquierda')
                        ->helperText('Posición donde se realizará la encuadernación'),
                    ]),
                ]),
                
                $this->makeSection('Acabados')->schema([
                    Components\CheckboxList::make('selected_finishings')
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
                        ->columns(2)
                        ->columnSpanFull()
                        ->helperText('Seleccione los acabados que requiere la revista'),
                ]),
                
                $this->makeSection('Costos Adicionales')->schema([
                    $this->makeGrid(3)->schema([
                        $this->makeTextInput('design_value', 'Valor Diseño')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('$')
                            ->placeholder('0'),
                            
                        $this->makeTextInput('transport_value', 'Valor Transporte')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('$')
                            ->placeholder('0'),
                            
                        $this->makeTextInput('profit_percentage', 'Porcentaje de Ganancia')
                            ->numeric()
                            ->default(25)
                            ->minValue(0)
                            ->maxValue(500)
                            ->suffix('%')
                            ->placeholder('25'),
                    ]),
                ]),
                
                $this->makeSection('Notas Adicionales')
                    ->schema([
                        Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Notas adicionales sobre la revista...'),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
    
    public function handleCreate($document, array $data): void
    {
        // Crear el MagazineItem
        $magazine = MagazineItem::create([
            'company_id' => auth()->user()->company_id,
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'closed_width' => $data['closed_width'],
            'closed_height' => $data['closed_height'],
            'binding_type' => $data['binding_type'],
            'binding_side' => $data['binding_side'],
            'design_value' => $data['design_value'] ?? 0,
            'transport_value' => $data['transport_value'] ?? 0,
            'profit_percentage' => $data['profit_percentage'] ?? 25,
            'notes' => $data['notes'] ?? null,
        ]);
        
        // Asociar acabados si se proporcionaron
        if (isset($data['selected_finishings']) && !empty($data['selected_finishings'])) {
            $magazine->finishings()->attach($data['selected_finishings']);
        }
        
        // Calcular precios iniciales
        $magazine->calculateAll();
        $magazine->save();
        
        // Crear DocumentItem
        $documentItem = $document->items()->create([
            'itemable_type' => MagazineItem::class,
            'itemable_id' => $magazine->id,
            'quantity' => $data['quantity'],
            'unit_price' => $magazine->final_price / $data['quantity'],
            'total_price' => $magazine->final_price,
        ]);
        
        // Calcular y actualizar precios del DocumentItem
        $documentItem->calculateAndUpdatePrices();
        
        Notification::make()
            ->title('Revista creada exitosamente')
            ->body('La revista se ha agregado a la cotización. Puede agregar páginas editándola.')
            ->success()
            ->send();
    }
    
}