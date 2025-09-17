<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\MagazineItem;

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

                    Components\TextInput::make('quantity')
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
                    Grid::make(2)->schema([
                        Components\TextInput::make('closed_width')
                            ->label('Ancho Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->minValue(0)
                            ->placeholder('21'),

                        Components\TextInput::make('closed_height')
                            ->label('Alto Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->minValue(0)
                            ->placeholder('29.7'),
                    ]),
                ]),

            Section::make('Encuadernación')
                ->schema([
                    Grid::make(2)->schema([
                        Components\Select::make('binding_type')
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

                        Components\Select::make('binding_side')
                            ->label('Lado de Encuadernación')
                            ->required()
                            ->options([
                                'arriba' => 'Arriba',
                                'izquierda' => 'Izquierda',
                                'derecha' => 'Derecha',
                                'abajo' => 'Abajo',
                            ])
                            ->default('izquierda')
                            ->helperText('Lado donde se aplicará la encuadernación'),
                    ]),
                ]),

            Section::make('Costos Adicionales')
                ->schema([
                    Grid::make(3)->schema([
                        Components\TextInput::make('design_value')
                            ->label('Valor Diseño')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0)
                            ->placeholder('0'),

                        Components\TextInput::make('transport_value')
                            ->label('Valor Transporte')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0)
                            ->placeholder('0'),

                        Components\TextInput::make('profit_percentage')
                            ->label('Porcentaje de Ganancia')
                            ->numeric()
                            ->default(25)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('25'),
                    ]),
                ]),

            Section::make('Notas')
                ->schema([
                    Components\Textarea::make('notes')
                        ->label('Notas Adicionales')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Información adicional sobre la revista...'),
                ]),
        ];
    }

    public function fillForm($record): array
    {
        return [
            'description' => $record->itemable->description,
            'quantity' => $record->itemable->quantity,
            'closed_width' => $record->itemable->closed_width,
            'closed_height' => $record->itemable->closed_height,
            'binding_type' => $record->itemable->binding_type,
            'binding_side' => $record->itemable->binding_side,
            'design_value' => $record->itemable->design_value,
            'transport_value' => $record->itemable->transport_value,
            'profit_percentage' => $record->itemable->profit_percentage,
            'notes' => $record->itemable->notes,
        ];
    }

    public function handleUpdate($record, array $data): void
    {
        $record->itemable->update($data);
        $record->itemable->calculateAll();
        $record->update([
            'unit_price' => $record->itemable->final_price / $record->itemable->quantity,
            'total_price' => $record->itemable->final_price,
        ]);
    }

    public function getWizardStep(): Step
    {
        return Step::make('Configuración de Revista')
            ->schema([
                Components\Textarea::make('description')
                    ->label('Descripción de la Revista')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Describe la revista: temática, características especiales, etc.'),

                Grid::make(2)->schema([
                    Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->required()
                        ->default(100)
                        ->minValue(1)
                        ->suffix('revistas'),

                    Components\Select::make('binding_type')
                        ->label('Tipo de Encuadernación')
                        ->required()
                        ->options([
                            'grapado' => 'Grapado',
                            'plegado' => 'Plegado',
                            'anillado' => 'Anillado',
                            'cosido' => 'Cosido',
                        ])
                        ->default('grapado'),
                ]),

                Grid::make(2)->schema([
                    Components\TextInput::make('closed_width')
                        ->label('Ancho Cerrado')
                        ->numeric()
                        ->required()
                        ->suffix('cm')
                        ->default(21),

                    Components\TextInput::make('closed_height')
                        ->label('Alto Cerrado')
                        ->numeric()
                        ->required()
                        ->suffix('cm')
                        ->default(29.7),
                ]),
            ]);
    }

    public function handleCreate(array $data): void
    {
        $magazine = MagazineItem::create(array_merge($data, [
            'company_id' => auth()->user()->company_id,
        ]));

        $this->record->documentItems()->create([
            'itemable_type' => MagazineItem::class,
            'itemable_id' => $magazine->id,
            'quantity' => $magazine->quantity,
            'unit_price' => $magazine->final_price / $magazine->quantity,
            'total_price' => $magazine->final_price,
            'order' => $this->record->documentItems()->max('order') + 1,
        ]);
    }
}