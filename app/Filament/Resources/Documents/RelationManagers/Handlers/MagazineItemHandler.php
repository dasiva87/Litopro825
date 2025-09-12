<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\MagazineItem;
use App\Models\SimpleItem;
use App\Models\MagazinePage;
use App\Models\Paper;
use App\Models\PrintingMachine;

class MagazineItemHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            $this->makeSection('Editar Revista', 'Modificar los detalles de la revista y recalcular automáticamente')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción de la Revista')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                        
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('quantity', 'Cantidad')
                            ->suffix('revistas')
                            ->minValue(1),
                            
                        $this->makeTextInput('profit_percentage', 'Porcentaje de Ganancia')
                            ->suffix('%')
                            ->default(25)
                            ->minValue(0)
                            ->maxValue(500),
                    ]),
                    
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('closed_width', 'Ancho Cerrado')
                            ->suffix('cm')
                            ->minValue(0),
                            
                        $this->makeTextInput('closed_height', 'Alto Cerrado')
                            ->suffix('cm')
                            ->minValue(0),
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
                        ]),
                        
                        $this->makeSelect('binding_side', 'Lado de Encuadernación', [
                            'izquierda' => 'Izquierda',
                            'derecha' => 'Derecha',
                            'arriba' => 'Arriba',
                            'abajo' => 'Abajo',
                        ]),
                    ]),
                    
                    $this->makeGrid(3)->schema([
                        $this->makeTextInput('design_value', 'Valor Diseño')
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0),
                            
                        $this->makeTextInput('transport_value', 'Valor Transporte')
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0),
                            
                        Components\Placeholder::make('pages_count')
                            ->label('Total Páginas')
                            ->content(function ($record) {
                                if (!$record || !$record->itemable) {
                                    return '0 páginas';
                                }
                                $totalPages = $record->itemable->pages->sum('page_quantity');
                                return $totalPages . ' páginas';
                            }),
                    ]),
                    
                    Components\Textarea::make('notes')
                        ->label('Notas Adicionales')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
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
                        ->default(100)
                        ->minValue(1)
                        ->suffix('revistas'),
                ]),
                
                $this->makeSection('Dimensiones y Encuadernación')->schema([
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('closed_width', 'Ancho Cerrado')
                            ->suffix('cm')
                            ->default(21)
                            ->minValue(0),
                            
                        $this->makeTextInput('closed_height', 'Alto Cerrado')
                            ->suffix('cm')
                            ->default(29.7)
                            ->minValue(0),
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
                        ])->default('grapado'),
                        
                        $this->makeSelect('binding_side', 'Lado de Encuadernación', [
                            'izquierda' => 'Izquierda',
                            'derecha' => 'Derecha',
                            'arriba' => 'Arriba',
                            'abajo' => 'Abajo',
                        ])->default('izquierda'),
                    ]),
                ]),
                
                $this->makeSection('Configuración de Páginas')->schema([
                    Components\Repeater::make('pages_configuration')
                        ->label('Páginas de la Revista')
                        ->schema($this->getPageConfigurationSchema())
                        ->defaultItems(1)
                        ->addActionLabel('Agregar Página')
                        ->collapsible()
                        ->cloneable()
                        ->columnSpanFull(),
                ]),
            ]);
    }
    
    private function getPageConfigurationSchema(): array
    {
        return [
            $this->makeGrid(3)->schema([
                $this->makeSelect('page_type', 'Tipo de Página', [
                    'portada' => 'Portada',
                    'contraportada' => 'Contraportada', 
                    'interior' => 'Interior',
                    'inserto' => 'Inserto',
                    'separador' => 'Separador',
                    'anexo' => 'Anexo',
                ])->default('interior'),
                
                $this->makeTextInput('page_quantity', 'Cantidad de Páginas')
                    ->default(1)
                    ->minValue(1),
                    
                $this->makeTextInput('profit_percentage', 'Margen Ganancia')
                    ->suffix('%')
                    ->default(25)
                    ->minValue(0),
            ]),
            
            Components\Textarea::make('page_description')
                ->label('Descripción del Contenido')
                ->required()
                ->rows(2)
                ->columnSpanFull(),
            
            $this->makeSection('Materiales y Configuración')->schema([
                $this->makeGrid(2)->schema([
                    Components\Select::make('paper_id')
                        ->label('Papel')
                        ->options(function () {
                            $companyId = auth()->user()->company_id ?? 1;
                            return Paper::query()
                                ->where('company_id', $companyId)
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->required()
                        ->searchable(),
                        
                    Components\Select::make('printing_machine_id')
                        ->label('Máquina de Impresión')
                        ->options(function () {
                            $companyId = auth()->user()->company_id ?? 1;
                            return PrintingMachine::query()
                                ->where('company_id', $companyId)
                                ->selectRaw("CONCAT(name, ' - ', type) as label, id")
                                ->pluck('label', 'id')
                                ->toArray();
                        })
                        ->required()
                        ->searchable(),
                ]),
                
                $this->makeGrid(3)->schema([
                    $this->makeTextInput('ink_front_count', 'Tintas Frente')
                        ->default(4)
                        ->minValue(0)
                        ->maxValue(8),
                        
                    $this->makeTextInput('ink_back_count', 'Tintas Reverso')
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(8),
                        
                    Components\Toggle::make('front_back_plate')
                        ->label('Tiro y Retiro')
                        ->default(false),
                ]),
            ])
            ->collapsible()
            ->collapsed(),
        ];
    }
}