<?php

namespace App\Filament\Resources\TalonarioItems\RelationManagers;

use App\Models\SimpleItem;
use App\Models\TalonarioSheet;
use App\Models\Paper;
use App\Models\PrintingMachine;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class TalonarioSheetsRelationManager extends RelationManager
{
    protected static string $relationship = 'sheets';
    protected static ?string $recordTitleAttribute = 'sheet_type';
    protected static ?string $title = 'Hojas del Talonario';
    protected static ?string $modelLabel = 'Hoja';
    protected static ?string $pluralModelLabel = 'Hojas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sheet_order')
                    ->label('#')
                    ->sortable(),
                    
                TextColumn::make('sheet_type')
                    ->label('Tipo de Hoja')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'original' => 'Original',
                        'copia_1' => '1ª Copia',
                        'copia_2' => '2ª Copia',
                        'copia_3' => '3ª Copia',
                        default => ucfirst($state)
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'original' => 'primary',
                        'copia_1' => 'success',
                        'copia_2' => 'warning',
                        'copia_3' => 'danger',
                        default => 'secondary'
                    }),
                    
                TextColumn::make('paper_color')
                    ->label('Color')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'blanco' => '🤍 Blanco',
                        'amarillo' => '💛 Amarillo',
                        'rosado' => '💗 Rosado',
                        'azul' => '💙 Azul',
                        'verde' => '💚 Verde',
                        'naranja' => '🧡 Naranja',
                        default => ucfirst($state)
                    }),
                    
                TextColumn::make('simpleItem.description')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                    
                TextColumn::make('simpleItem.final_price')
                    ->label('Precio Total')
                    ->money('COP')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar Hoja')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Crear Nueva Hoja para el Talonario')
                    ->modalWidth('7xl')
                    ->form([
                        Section::make('Información de la Hoja')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('sheet_type')
                                            ->label('Tipo de Hoja')
                                            ->required()
                                            ->options([
                                                'original' => 'Original',
                                                'copia_1' => '1ª Copia',
                                                'copia_2' => '2ª Copia',
                                                'copia_3' => '3ª Copia'
                                            ])
                                            ->default('original'),
                                            
                                        Select::make('paper_color')
                                            ->label('Color del Papel')
                                            ->required()
                                            ->options([
                                                'blanco' => '🤍 Blanco',
                                                'amarillo' => '💛 Amarillo',
                                                'rosado' => '💗 Rosado',
                                                'azul' => '💙 Azul',
                                                'verde' => '💚 Verde',
                                                'naranja' => '🧡 Naranja'
                                            ])
                                            ->default('blanco'),
                                            
                                        TextInput::make('sheet_order')
                                            ->label('Orden')
                                            ->numeric()
                                            ->required()
                                            ->default(fn () => $this->getOwnerRecord()->sheets()->count() + 1)
                                            ->minValue(1)
                                            ->helperText('Orden de la hoja en el talonario'),
                                    ]),
                                    
                                Textarea::make('sheet_notes')
                                    ->label('Descripción del Contenido')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->placeholder('Describe el contenido de esta hoja...'),
                            ]),
                            
                        Section::make('Materiales')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('paper_id')
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
                                            ->default(1)
                                            ->minValue(0)
                                            ->maxValue(8)
                                            ->placeholder('1')
                                            ->helperText('Talonarios normalmente usan 1 tinta (negro)'),
                                            
                                        TextInput::make('ink_back_count')
                                            ->label('Tintas Reverso')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(8)
                                            ->placeholder('0'),
                                            
                                        Select::make('front_back_plate')
                                            ->label('Placa Frente y Reverso')
                                            ->options([
                                                0 => 'No - Placas separadas',
                                                1 => 'Sí - Misma placa'
                                            ])
                                            ->default(0)
                                            ->required()
                                            ->helperText('Para talonarios normalmente es "No"'),
                                    ]),
                            ]),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        // Extraer datos específicos de la hoja
                        $sheetData = [
                            'sheet_type' => $data['sheet_type'] ?? 'original',
                            'paper_color' => $data['paper_color'] ?? 'blanco',
                            'sheet_order' => $data['sheet_order'] ?? 1,
                            'sheet_notes' => $data['sheet_notes'] ?? '',
                        ];
                        
                        // Preparar datos del SimpleItem
                        $record = $this->getOwnerRecord();
                        $totalNumbers = ($record->numero_final - $record->numero_inicial) + 1;
                        $correctQuantity = $totalNumbers * $record->quantity;
                        
                        $simpleItemData = array_diff_key($data, array_flip(['sheet_type', 'paper_color', 'sheet_order', 'sheet_notes']));
                        $simpleItemData = array_merge($simpleItemData, [
                            'description' => $data['sheet_notes'],
                            'quantity' => $correctQuantity,
                            'horizontal_size' => $record->ancho,
                            'vertical_size' => $record->alto,
                            'profit_percentage' => 0, // Sin ganancia doble
                            'front_back_plate' => (bool)($simpleItemData['front_back_plate'] ?? false),
                        ]);
                        
                        // Crear el SimpleItem
                        $simpleItem = SimpleItem::create($simpleItemData);
                        
                        // Preparar datos finales para TalonarioSheet
                        return array_merge($sheetData, [
                            'simple_item_id' => $simpleItem->id,
                        ]);
                    })
                    ->after(function ($record) {
                        // Recalcular precios del talonario
                        $this->getOwnerRecord()->calculateAll();
                        $this->getOwnerRecord()->save();
                        
                        Notification::make()
                            ->title('Hoja agregada correctamente')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        // Recalcular precios después de eliminar
                        $this->getOwnerRecord()->calculateAll();
                        $this->getOwnerRecord()->save();
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('sheet_order');
    }
}