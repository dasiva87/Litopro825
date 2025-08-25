<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Models\SimpleItem;
use App\Models\DocumentItem;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Wizard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items de la Cotización';

    protected static ?string $modelLabel = 'Item';

    protected static ?string $pluralModelLabel = 'Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Tipo de Item')
                        ->schema([
                            Select::make('item_type')
                                ->label('Tipo de Item')
                                ->options([
                                    'simple' => 'Item Sencillo (montaje, papel, máquina, tintas)',
                                    'talonario' => 'Talonario',
                                    'magazine' => 'Revista',
                                    'digital' => 'Digital',
                                    'custom' => 'Personalizado',
                                    'product' => 'Producto (desde inventario)',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    // Limpiar datos cuando se cambia el tipo
                                    $set('itemable_type', null);
                                    $set('itemable_id', null);
                                }),
                        ]),
                        
                    Wizard\Step::make('Detalles del Item')
                        ->schema(function ($get) {
                            $itemType = $get('item_type');
                            
                            if ($itemType === 'simple') {
                                return [
                                    Forms\Components\Hidden::make('itemable_type')
                                        ->default('App\\Models\\SimpleItem'),
                                        
                                    // Incluir formulario de SimpleItem inline
                                    Forms\Components\Group::make()
                                        ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema())->getComponents())
                                        ->columnSpanFull(),
                                ];
                            }
                            
                            // Para otros tipos de item, mostrar mensaje temporal
                            return [
                                Forms\Components\Placeholder::make('not_implemented')
                                    ->content('Este tipo de item aún no está implementado.')
                                    ->columnSpanFull(),
                            ];
                        })
                        ->visible(fn ($get) => filled($get('item_type'))),
                ])
                ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('itemable_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'App\\Models\\SimpleItem' => 'Sencillo',
                        'App\\Models\\TalonarioItem' => 'Talonario',
                        'App\\Models\\MagazineItem' => 'Revista',
                        'App\\Models\\DigitalItem' => 'Digital',
                        default => 'Otro'
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'App\\Models\\SimpleItem' => 'success',
                        'App\\Models\\TalonarioItem' => 'warning',
                        'App\\Models\\MagazineItem' => 'info',
                        'App\\Models\\DigitalItem' => 'primary',
                        default => 'gray'
                    }),
                    
                TextColumn::make('itemable.description')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),
                    
                TextColumn::make('itemable.quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->suffix(' uds'),
                    
                TextColumn::make('dimensions')
                    ->label('Dimensiones')
                    ->getStateUsing(function ($record) {
                        if ($record->itemable_type === 'App\\Models\\SimpleItem' && $record->itemable) {
                            return $record->itemable->horizontal_size . ' × ' . $record->itemable->vertical_size . ' cm';
                        }
                        return '-';
                    })
                    ->toggleable(),
                    
                TextColumn::make('itemable.final_price')
                    ->label('Precio')
                    ->money('COP')
                    ->sortable(),
                    
                TextColumn::make('unit_price_display')
                    ->label('Precio Unitario')
                    ->getStateUsing(function ($record) {
                        if ($record->itemable && $record->itemable->quantity > 0) {
                            return $record->itemable->final_price / $record->itemable->quantity;
                        }
                        return 0;
                    })
                    ->money('COP')
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('itemable_type')
                    ->label('Tipo de Item')
                    ->options([
                        'App\\Models\\SimpleItem' => 'Sencillo',
                        'App\\Models\\TalonarioItem' => 'Talonario',
                        'App\\Models\\MagazineItem' => 'Revista',
                        'App\\Models\\DigitalItem' => 'Digital',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar Item')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Primero crear el SimpleItem si es de tipo simple
                        if ($data['item_type'] === 'simple' && $data['itemable_type'] === 'App\\Models\\SimpleItem') {
                            // Extraer datos del SimpleItem del formulario anidado
                            $simpleItemData = array_filter($data, function($key) {
                                return !in_array($key, ['item_type', 'itemable_type', 'itemable_id']);
                            }, ARRAY_FILTER_USE_KEY);
                            
                            // Crear el SimpleItem
                            $simpleItem = SimpleItem::create($simpleItemData);
                            
                            // Configurar la relación polimórfica
                            $data['itemable_type'] = 'App\\Models\\SimpleItem';
                            $data['itemable_id'] = $simpleItem->id;
                            
                            // Configurar datos para DocumentItem
                            $data = [
                                'itemable_type' => 'App\\Models\\SimpleItem',
                                'itemable_id' => $simpleItem->id,
                                'description' => 'SimpleItem: ' . $simpleItem->description,
                                'quantity' => $simpleItem->quantity,
                                'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
                                'total_price' => $simpleItem->final_price
                            ];
                        }
                        
                        return $data;
                    })
                    ->after(function () {
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                    }),
                    
                Action::make('quick_simple_item')
                    ->label('Item Sencillo Rápido')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->form([
                        \Filament\Schemas\Components\Section::make('Item Sencillo Rápido')
                            ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema())->getComponents())
                    ])
                    ->action(function (array $data) {
                        // Crear el SimpleItem
                        $simpleItem = SimpleItem::create($data);
                        
                        // Crear el DocumentItem asociado con todos los campos requeridos
                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\SimpleItem',
                            'itemable_id' => $simpleItem->id,
                            'description' => 'SimpleItem: ' . $simpleItem->description,
                            'quantity' => $simpleItem->quantity,
                            'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
                            'total_price' => $simpleItem->final_price
                        ]);
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                        
                        // Refrescar la tabla
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('7xl'),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => $record->itemable_type === 'App\\Models\\SimpleItem')
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        // Cargar datos del SimpleItem para edición
                        if ($record->itemable) {
                            return array_merge($data, $record->itemable->toArray(), [
                                'item_type' => 'simple',
                                'itemable_type' => $record->itemable_type,
                                'itemable_id' => $record->itemable_id,
                            ]);
                        }
                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        // Actualizar el SimpleItem
                        if ($record->itemable && $record->itemable_type === 'App\\Models\\SimpleItem') {
                            $simpleItemData = array_filter($data, function($key) {
                                return !in_array($key, ['item_type', 'itemable_type', 'itemable_id']);
                            }, ARRAY_FILTER_USE_KEY);
                            
                            $record->itemable->update($simpleItemData);
                            
                            // Recalcular totales del documento
                            $this->getOwnerRecord()->recalculateTotals();
                        }
                        
                        return $data;
                    }),
                    
                DeleteAction::make()
                    ->after(function ($record) {
                        // Eliminar el item relacionado también
                        if ($record->itemable) {
                            $record->itemable->delete();
                        }
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records) {
                            // Eliminar los items relacionados también
                            foreach ($records as $record) {
                                if ($record->itemable) {
                                    $record->itemable->delete();
                                }
                            }
                            
                            // Recalcular totales del documento
                            $this->getOwnerRecord()->recalculateTotals();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}