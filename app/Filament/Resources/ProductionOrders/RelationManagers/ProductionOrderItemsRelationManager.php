<?php

namespace App\Filament\Resources\ProductionOrders\RelationManagers;

use App\Filament\Resources\ProductionOrders\Handlers\CustomItemQuickHandler;
use App\Models\Document;
use App\Models\DocumentItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionOrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'productionProcesses';

    protected static ?string $title = 'Procesos de Producción';

    protected static ?string $modelLabel = 'Proceso';

    protected static ?string $pluralModelLabel = 'Procesos';

    protected static ?string $recordTitleAttribute = 'process_description';

    /**
     * Get the class name of the current page
     */
    public function getPageClass(): string
    {
        return $this->pageClass ?? get_class($this->getPage());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('process_type')
                    ->label('Proceso')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'printing' => '🖨️ Impresión',
                        'finishing' => '🎯 Acabado',
                        default => 'Otro'
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'printing' => 'primary',
                        'finishing' => 'success',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('finishing_name')
                    ->label('Acabado')
                    ->placeholder('N/A')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('process_description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->process_description),

                Tables\Columns\TextColumn::make('quantity_to_produce')
                    ->label('A Producir')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' pzs'),

                Tables\Columns\TextColumn::make('sheets_needed')
                    ->label('Pliegos')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' pl')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('total_impressions')
                    ->label('Millares')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' M')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ink_front_count')
                    ->label('Tintas F')
                    ->numeric()
                    ->badge()
                    ->color('primary')
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ink_back_count')
                    ->label('Tintas R')
                    ->numeric()
                    ->badge()
                    ->color('primary')
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('produced_quantity')
                    ->label('Producido')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' pzs')
                    ->toggleable()
                    ->default(0),

                Tables\Columns\TextColumn::make('item_status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Proceso',
                        'completed' => 'Completado',
                        'paused' => 'Pausado',
                        default => $state ?? 'Pendiente'
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'paused' => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('documentItem.document.document_number')
                    ->label('Cotización')
                    ->searchable()
                    ->url(fn ($record) => $record->documentItem && $record->documentItem->document ?
                        route('filament.admin.resources.documents.view', $record->documentItem->document) : null)
                    ->openUrlInNewTab()
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('add_items')
                    ->label('Agregar Items')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->modalHeading('Agregar Items a Producción')
                    ->modalDescription('Selecciona items desde cotizaciones aprobadas para enviar a producción')
                    ->modalWidth('7xl')
                    ->modalSubmitActionLabel('Agregar a Producción')
                    ->visible(function () {
                        $pageClass = $this->getPageClass();
                        $isEditPage = $pageClass === \App\Filament\Resources\ProductionOrders\Pages\EditProductionOrder::class;
                        return $isEditPage;
                    })
                    ->form([
                        Components\Select::make('document_id')
                            ->label('Buscar Cotización')
                            ->placeholder('Selecciona una cotización aprobada...')
                            ->options(function () {
                                return Document::where('company_id', auth()->user()->company_id)
                                    ->where('status', 'approved')
                                    ->with('contact')
                                    ->orderBy('created_at', 'desc')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($doc) {
                                        $contactName = $doc->contact?->name ?? 'Sin cliente';
                                        return [
                                            $doc->id => "{$doc->document_number} - {$contactName} ({$doc->created_at->format('d/m/Y')})"
                                        ];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('item_ids', [])),

                        Components\CheckboxList::make('item_ids')
                            ->label('Items Disponibles para Producción')
                            ->helperText('Selecciona los items que deseas enviar a producción. Se agregarán con sus cantidades originales.')
                            ->options(function (callable $get) {
                                $documentId = $get('document_id');
                                if (!$documentId) {
                                    return [];
                                }

                                return DocumentItem::where('document_id', $documentId)
                                    ->whereIn('itemable_type', ['App\Models\SimpleItem', 'App\Models\DigitalItem', 'App\Models\Product'])
                                    ->with(['itemable'])
                                    ->get()
                                    ->each(function ($item) {
                                        // Cargar relaciones específicas según el tipo
                                        if ($item->itemable_type === 'App\Models\SimpleItem') {
                                            $item->itemable->load(['paper', 'printingMachine', 'finishings']);
                                        } elseif ($item->itemable_type === 'App\Models\DigitalItem') {
                                            $item->itemable->load(['finishings']);
                                        }
                                        // Products no necesitan eager loading adicional
                                    })
                                    ->filter(function ($item) {
                                        // Aplicar mismo filtro que DocumentsTable.php
                                        if ($item->itemable_type === 'App\Models\Product') {
                                            return $item->itemable && !empty($item->item_config['finishings']);
                                        }
                                        return true; // SimpleItem y DigitalItem siempre incluidos
                                    })
                                    ->mapWithKeys(function ($item) {
                                        if (!$item->itemable) {
                                            return [$item->id => "⚠️ {$item->description} (Sin datos de producción)"];
                                        }

                                        $quantity = number_format($item->quantity ?? 0, 0);

                                        // Generar etiqueta según tipo de item
                                        if ($item->itemable_type === 'App\Models\SimpleItem') {
                                            $type = '📄 Impresión';
                                            $size = "{$item->itemable->horizontal_size}x{$item->itemable->vertical_size} cm";
                                            $paper = $item->itemable->paper?->name ?? 'Sin papel';
                                            $tintas = "F:{$item->itemable->ink_front_count}/V:{$item->itemable->ink_back_count}";

                                            // Contar acabados
                                            $finishingsCount = $item->itemable->finishings->count();
                                            $finishingsText = $finishingsCount > 0 ? " | 🎯 {$finishingsCount} acabado(s)" : '';

                                            $label = "{$type} {$item->description}\n";
                                            $label .= "   📦 Cantidad: {$quantity} | 📏 Tamaño: {$size}\n";
                                            $label .= "   🎨 Papel: {$paper} | 🖨️ Tintas: {$tintas}{$finishingsText}";
                                        } elseif ($item->itemable_type === 'App\Models\DigitalItem') {
                                            $type = '📱 Digital';
                                            $proveedor = $item->itemable->is_own_product ? 'Propio' : 'Externo';

                                            // Contar acabados
                                            $finishingsCount = $item->itemable->finishings->count();
                                            $finishingsText = $finishingsCount > 0 ? " | 🎯 {$finishingsCount} acabado(s)" : '';

                                            $label = "{$type} {$item->description}\n";
                                            $label .= "   📦 Cantidad: {$quantity} | 🏭 Proveedor: {$proveedor}{$finishingsText}";
                                        } elseif ($item->itemable_type === 'App\Models\Product') {
                                            $type = '📦 Producto';

                                            // Contar acabados desde item_config
                                            $finishingsCount = count($item->item_config['finishings'] ?? []);
                                            $finishingsText = "🎯 {$finishingsCount} acabado(s)";

                                            $label = "{$type} {$item->description}\n";
                                            $label .= "   📦 Cantidad: {$quantity} | {$finishingsText}";
                                        } else {
                                            return [$item->id => "⚠️ {$item->description} (Tipo no soportado)"];
                                        }

                                        return [$item->id => $label];
                                    });
                            })
                            ->visible(fn (callable $get) => (bool) $get('document_id'))
                            ->required()
                            ->columns(1)
                            ->gridDirection('row')
                            ->bulkToggleable(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $productionOrder = $livewire->getOwnerRecord();
                        $calculator = new \App\Services\ProductionCalculatorService();
                        $groupingService = new \App\Services\ProductionOrderGroupingService();

                        $added = 0;
                        $failed = 0;
                        $errors = [];

                        foreach ($data['item_ids'] as $itemId) {
                            $documentItem = DocumentItem::with(['itemable.finishings'])->find($itemId);

                            if (!$documentItem) {
                                $failed++;
                                $errors[] = "Item ID {$itemId} no encontrado";
                                continue;
                            }

                            // Validar que se pueda producir
                            $validation = $calculator->canBeProduced($documentItem);
                            if (!$validation['valid']) {
                                $failed++;
                                $errors[] = "Item '{$documentItem->description}': " . implode(', ', $validation['errors']);
                                continue;
                            }

                            // Usar ProductionOrderGroupingService para obtener todos los procesos (impresión + acabados)
                            $grouped = $groupingService->groupBySupplier(collect([$documentItem]));
                            $currentSupplierId = $productionOrder->supplier_id;

                            // Solo procesar los procesos que corresponden a este proveedor
                            if (!isset($grouped[$currentSupplierId])) {
                                $failed++;
                                $errors[] = "Item '{$documentItem->description}' no tiene procesos para este proveedor";
                                continue;
                            }

                            $processes = $grouped[$currentSupplierId];
                            $itemAdded = false;

                            // Agregar procesos de impresión
                            foreach ($processes['printing'] as $process) {
                                if ($productionOrder->addItem(
                                    $process['document_item'],
                                    $process['quantity'],
                                    'printing',
                                    null,
                                    $process['process_description']
                                )) {
                                    $itemAdded = true;
                                }
                            }

                            // Agregar procesos de acabados
                            foreach ($processes['finishings'] as $process) {
                                if ($productionOrder->addItem(
                                    $process['document_item'],
                                    $process['quantity'],
                                    'finishing',
                                    $process['finishing_name'],
                                    $process['process_description'],
                                    $process['finishing_parameters'] ?? []
                                )) {
                                    $itemAdded = true;
                                }
                            }

                            if ($itemAdded) {
                                $added++;
                            } else {
                                $failed++;
                                $errors[] = "No se pudo agregar '{$documentItem->description}'";
                            }
                        }

                        // Mostrar notificación
                        if ($added > 0) {
                            Notification::make()
                                ->success()
                                ->title('Items agregados exitosamente')
                                ->body("{$added} item(s) agregados a producción correctamente")
                                ->send();
                        }

                        if ($failed > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Algunos items no se pudieron agregar')
                                ->body(implode("\n", array_slice($errors, 0, 3)))
                                ->send();
                        }
                    }),

                // Item Personalizado Rápido
                Action::make('quick_custom_item')
                    ->label((new CustomItemQuickHandler)->getLabel())
                    ->icon((new CustomItemQuickHandler)->getIcon())
                    ->color((new CustomItemQuickHandler)->getColor())
                    ->visible(function () {
                        $pageClass = $this->getPageClass();
                        $isEditPage = $pageClass === \App\Filament\Resources\ProductionOrders\Pages\EditProductionOrder::class;
                        return $isEditPage;
                    })
                    ->modalWidth((new CustomItemQuickHandler)->getModalWidth())
                    ->form((new CustomItemQuickHandler)->getFormSchema())
                    ->action(function (array $data, $livewire) {
                        $productionOrder = $livewire->getOwnerRecord();
                        (new CustomItemQuickHandler)->handleCreate($data, $productionOrder);

                        Notification::make()
                            ->title((new CustomItemQuickHandler)->getSuccessNotificationTitle())
                            ->success()
                            ->send();

                        $livewire->dispatch('$refresh');
                    }),
            ])
            ->actions([
                Action::make('update_status')
                    ->label('Actualizar Estado')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        Components\Select::make('item_status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'in_progress' => 'En Proceso',
                                'completed' => 'Completado',
                                'paused' => 'Pausado',
                            ])
                            ->required()
                            ->native(false),

                        Components\TextInput::make('produced_quantity')
                            ->label('Cantidad Producida')
                            ->numeric()
                            ->default(fn ($record) => $record->pivot->produced_quantity ?? 0),

                        Components\TextInput::make('rejected_quantity')
                            ->label('Cantidad Rechazada')
                            ->numeric()
                            ->default(fn ($record) => $record->pivot->rejected_quantity ?? 0),

                        Components\Textarea::make('production_notes')
                            ->label('Notas de Producción')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data, $livewire) {
                        $productionOrder = $livewire->getOwnerRecord();

                        $productionOrder->updateItemStatus($record, $data['item_status'], [
                            'produced_quantity' => $data['produced_quantity'] ?? 0,
                            'rejected_quantity' => $data['rejected_quantity'] ?? 0,
                            'production_notes' => $data['production_notes'] ?? null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Estado actualizado')
                            ->send();
                    }),

                DetachAction::make()
                    ->label('Eliminar')
                    ->visible(fn ($livewire) => $livewire->getOwnerRecord()->canBeEdited()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->visible(fn ($livewire) => $livewire->getOwnerRecord()->canBeEdited()),
                ]),
            ]);
    }
}
