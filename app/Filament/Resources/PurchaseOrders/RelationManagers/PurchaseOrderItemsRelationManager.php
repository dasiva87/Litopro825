<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use App\Filament\Resources\PurchaseOrders\Handlers\CustomItemQuickHandler;
use App\Models\Document;
use App\Models\DocumentItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrderItems';

    protected static ?string $title = 'Items de la Orden';

    protected static ?string $modelLabel = 'Item';

    protected static ?string $pluralModelLabel = 'Items';

    protected static ?string $recordTitleAttribute = 'paper_description';

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
            ->modifyQueryUsing(fn ($query) => $query->with(['documentItem', 'paper']))
            ->columns([
                Tables\Columns\TextColumn::make('quantity_display')
                    ->label('Cantidad')
                    ->state(function ($record) {
                        // Para productos y otros items sin papel, mostrar quantity_ordered
                        if ($record->documentItem && in_array($record->documentItem->itemable_type, [
                            'App\Models\Product',
                            'App\Models\DigitalItem',
                            'App\Models\CustomItem',
                        ])) {
                            return number_format($record->quantity_ordered ?? 0, 0) . ' uds';
                        }
                        // Para papeles, mostrar sheets_quantity (pliegos)
                        return number_format($record->sheets_quantity ?? 0, 0) . ' pliegos';
                    })
                    ->alignCenter()
                    ->badge()
                    ->color(function ($record) {
                        if ($record->documentItem && in_array($record->documentItem->itemable_type, [
                            'App\Models\Product',
                            'App\Models\DigitalItem',
                            'App\Models\CustomItem',
                        ])) {
                            return 'info';
                        }
                        return 'warning';
                    }),

                Tables\Columns\TextColumn::make('paper_name')
                    ->label('Descripción')
                    ->searchable(['paper_description'])
                    ->description(function ($record) {
                        // Mostrar cantidad de revistas si es MagazineItem
                        if ($record->documentItem && $record->documentItem->itemable_type === 'App\Models\MagazineItem') {
                            return "Cantidad de revistas: " . number_format($record->quantity_ordered, 0);
                        }
                        // Mostrar información adicional para productos
                        if ($record->documentItem && $record->documentItem->itemable_type === 'App\Models\Product') {
                            return "Producto de catálogo";
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('cut_size')
                    ->label('Tamaño Corte')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-scissors')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Valor Unitario')
                    ->money('COP')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Valor Total')
                    ->money('COP')
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'received' => 'Recibido',
                        'cancelled' => 'Cancelado',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray'
                    })
                    ->alignCenter(),
            ])
            ->headerActions([
                Action::make('add_items')
                    ->label('Agregar Items')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(function () {
                        // Verificar si estamos en modo edición usando la clase de la página
                        $pageClass = $this->getPageClass();
                        $isEditPage = $pageClass === \App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder::class;

                        return $isEditPage;
                    })
                    ->modalHeading('Buscar y Agregar Items desde Cotizaciones')
                    ->modalDescription('Busca cotizaciones aprobadas y selecciona items para agregar a esta orden')
                    ->modalWidth('7xl')
                    ->modalSubmitActionLabel('Agregar Seleccionados')
                    ->form([
                        Components\Select::make('document_id')
                            ->label('Buscar Cotización')
                            ->placeholder('Selecciona una cotización aprobada...')
                            ->options(function () {
                                return Document::where('company_id', auth()->user()->company_id)
                                    ->where('status', 'approved')
                                    ->with(['contact', 'clientCompany'])
                                    ->orderBy('created_at', 'desc')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($doc) {
                                        $clientName = $doc->clientCompany->name ?? $doc->contact->name ?? 'Sin cliente';
                                        return [
                                            $doc->id => "{$doc->document_number} - {$clientName} ({$doc->created_at->format('d/m/Y')})"
                                        ];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('item_ids', [])),

                        Components\CheckboxList::make('item_ids')
                            ->label('Items Disponibles')
                            ->options(function (callable $get) {
                                $documentId = $get('document_id');
                                if (!$documentId) {
                                    return [];
                                }

                                return DocumentItem::where('document_id', $documentId)
                                    ->availableForOrders()
                                    ->with('itemable')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        $type = match ($item->itemable_type) {
                                            'App\Models\SimpleItem' => '📄 Papel',
                                            'App\Models\Product' => '📦 Producto',
                                            'App\Models\DigitalItem' => '💻 Digital',
                                            default => '📋 Otro'
                                        };

                                        $label = "{$type} - {$item->description} (Cant: {$item->quantity})";

                                        return [$item->id => $label];
                                    });
                            })
                            ->visible(fn (callable $get) => (bool) $get('document_id'))
                            ->required()
                            ->columns(1),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $purchaseOrder = $livewire->ownerRecord;

                        foreach ($data['item_ids'] as $itemId) {
                            $item = DocumentItem::find($itemId);

                            if (!$item || $item->isInPurchaseOrder($purchaseOrder)) {
                                continue;
                            }

                            // Calcular datos según tipo de item
                            $unitPrice = $this->calculateUnitPrice($item);
                            $totalPrice = $this->calculateTotalPrice($item);
                            $sheets = 0;
                            $paperId = null;
                            $paperDescription = null;
                            $cutWidth = null;
                            $cutHeight = null;

                            // Obtener datos específicos según el tipo
                            if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
                                // SimpleItem: Agregar como una sola fila
                                $paperId = $item->itemable->paper_id;
                                $paperDescription = $item->itemable->paper?->name;
                                $sheets = $item->itemable->paper_sheets_needed ?? 0;
                                $cutWidth = $item->itemable->horizontal_size;
                                $cutHeight = $item->itemable->vertical_size;

                                $purchaseOrder->documentItems()->attach($item->id, [
                                    'paper_id' => $paperId,
                                    'paper_description' => $paperDescription,
                                    'quantity_ordered' => $item->quantity,
                                    'sheets_quantity' => $sheets,
                                    'cut_width' => $cutWidth,
                                    'cut_height' => $cutHeight,
                                    'unit_price' => $unitPrice,
                                    'total_price' => $totalPrice,
                                    'status' => 'pending',
                                ]);

                            } elseif ($item->itemable_type === 'App\Models\MagazineItem' && $item->itemable) {
                                // MagazineItem: Crear una fila por cada tipo de papel
                                $magazine = $item->itemable;
                                $papersUsed = $magazine->getPapersUsed();

                                foreach ($papersUsed as $paperId => $paperData) {
                                    $paper = $paperData['paper'];
                                    $sheets = $paperData['total_sheets'];
                                    $unitPrice = $paper->price ?? $paper->cost_per_sheet ?? 0;
                                    $totalPrice = $sheets * $unitPrice;

                                    // Obtener tamaño de corte de la primera página que usa este papel
                                    $cutWidth = null;
                                    $cutHeight = null;
                                    foreach ($magazine->pages as $page) {
                                        if ($page->simpleItem && $page->simpleItem->paper_id == $paperId) {
                                            $cutWidth = $page->simpleItem->horizontal_size;
                                            $cutHeight = $page->simpleItem->vertical_size;
                                            break;
                                        }
                                    }

                                    $purchaseOrder->documentItems()->attach($item->id, [
                                        'paper_id' => $paperId,
                                        'paper_description' => "{$paper->name} - Revista: {$magazine->description}",
                                        'quantity_ordered' => $item->quantity,
                                        'sheets_quantity' => $sheets,
                                        'cut_width' => $cutWidth,
                                        'cut_height' => $cutHeight,
                                        'unit_price' => $unitPrice,
                                        'total_price' => $totalPrice,
                                        'status' => 'pending',
                                    ]);
                                }

                            } elseif ($item->itemable_type === 'App\Models\TalonarioItem' && $item->itemable) {
                                // TalonarioItem: Crear una fila por cada tipo de papel
                                $talonario = $item->itemable;
                                $papersUsed = $talonario->getPapersUsed();

                                foreach ($papersUsed as $paperId => $paperData) {
                                    $paper = $paperData['paper'];
                                    $sheets = $paperData['total_sheets'];
                                    $unitPrice = $paper->price ?? $paper->cost_per_sheet ?? 0;
                                    $totalPrice = $sheets * $unitPrice;

                                    // Obtener tamaño de corte de la primera hoja que usa este papel
                                    $cutWidth = null;
                                    $cutHeight = null;
                                    foreach ($talonario->sheets as $sheet) {
                                        if ($sheet->simpleItem && $sheet->simpleItem->paper_id == $paperId) {
                                            $cutWidth = $sheet->simpleItem->horizontal_size;
                                            $cutHeight = $sheet->simpleItem->vertical_size;
                                            break;
                                        }
                                    }

                                    $sheetsUsing = implode(', ', $paperData['sheets_using']);
                                    $purchaseOrder->documentItems()->attach($item->id, [
                                        'paper_id' => $paperId,
                                        'paper_description' => "{$paper->name} - Talonario: {$talonario->description} ({$sheetsUsing})",
                                        'quantity_ordered' => $item->quantity,
                                        'sheets_quantity' => $sheets,
                                        'cut_width' => $cutWidth,
                                        'cut_height' => $cutHeight,
                                        'unit_price' => $unitPrice,
                                        'total_price' => $totalPrice,
                                        'status' => 'pending',
                                    ]);
                                }

                            } elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
                                // Product: Una sola fila sin papel ni tamaño de corte
                                $paperDescription = $item->itemable->name;

                                $purchaseOrder->documentItems()->attach($item->id, [
                                    'paper_id' => null,
                                    'paper_description' => $paperDescription,
                                    'quantity_ordered' => $item->quantity,
                                    'sheets_quantity' => 0,
                                    'cut_width' => null,
                                    'cut_height' => null,
                                    'unit_price' => $unitPrice,
                                    'total_price' => $totalPrice,
                                    'status' => 'pending',
                                ]);

                            } else {
                                // Otros tipos: Sin datos específicos
                                $purchaseOrder->documentItems()->attach($item->id, [
                                    'paper_id' => null,
                                    'paper_description' => $item->description,
                                    'quantity_ordered' => $item->quantity,
                                    'sheets_quantity' => 0,
                                    'cut_width' => null,
                                    'cut_height' => null,
                                    'unit_price' => $unitPrice,
                                    'total_price' => $totalPrice,
                                    'status' => 'pending',
                                ]);
                            }

                            $item->updateOrderStatus();
                        }

                        $purchaseOrder->recalculateTotal();

                        \Filament\Notifications\Notification::make()
                            ->title('Items agregados exitosamente')
                            ->success()
                            ->send();
                    }),

                // Item Personalizado Rápido
                Action::make('quick_custom_item')
                    ->label((new CustomItemQuickHandler)->getLabel())
                    ->icon((new CustomItemQuickHandler)->getIcon())
                    ->color((new CustomItemQuickHandler)->getColor())
                    ->visible(function () {
                        // Verificar si estamos en modo edición usando la clase de la página
                        $pageClass = $this->getPageClass();
                        $isEditPage = $pageClass === \App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder::class;

                        return $isEditPage;
                    })
                    ->modalWidth((new CustomItemQuickHandler)->getModalWidth())
                    ->form((new CustomItemQuickHandler)->getFormSchema())
                    ->action(function (array $data, RelationManager $livewire) {
                        $purchaseOrder = $livewire->ownerRecord;
                        (new CustomItemQuickHandler)->handleCreate($data, $purchaseOrder);

                        \Filament\Notifications\Notification::make()
                            ->title((new CustomItemQuickHandler)->getSuccessNotificationTitle())
                            ->success()
                            ->send();

                        $livewire->dispatch('$refresh');
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->visible(function ($record) {
                        // Solo permitir editar items personalizados (CustomItem)
                        return $record->documentItem
                            && $record->documentItem->itemable_type === 'App\Models\CustomItem';
                    })
                    ->modalWidth('3xl')
                    ->form([
                        Components\Textarea::make('paper_description')
                            ->label('Descripción')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),

                        Components\TextInput::make('quantity_ordered')
                            ->label('Cantidad')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('unidades')
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $unitPrice = $get('unit_price') ?? 0;
                                $total = $state * $unitPrice;
                                $set('total_price', number_format($total, 2, '.', ''));
                            }),

                        Components\TextInput::make('unit_price')
                            ->label('Precio Unitario')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $quantity = $get('quantity_ordered') ?? 1;
                                $total = $quantity * $state;
                                $set('total_price', number_format($total, 2, '.', ''));
                            }),

                        Components\TextInput::make('total_price')
                            ->label('Precio Total')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->fillForm(function ($record) {
                        // Obtener descripción: primero de paper_description, luego del CustomItem
                        $description = $record->paper_description;

                        if (empty($description) && $record->documentItem && $record->documentItem->itemable) {
                            $description = $record->documentItem->itemable->description;
                        }

                        return [
                            'paper_description' => $description,
                            'quantity_ordered' => $record->quantity_ordered,
                            'unit_price' => $record->unit_price,
                            'total_price' => $record->total_price,
                            'notes' => $record->notes,
                        ];
                    })
                    ->action(function ($record, array $data, RelationManager $livewire) {
                        $record->update([
                            'paper_description' => $data['paper_description'],
                            'quantity_ordered' => $data['quantity_ordered'],
                            'unit_price' => $data['unit_price'],
                            'total_price' => $data['quantity_ordered'] * $data['unit_price'],
                            'notes' => $data['notes'] ?? null,
                        ]);

                        // Actualizar también el CustomItem y DocumentItem
                        if ($record->documentItem && $record->documentItem->itemable) {
                            $record->documentItem->itemable->update([
                                'description' => $data['paper_description'],
                                'quantity' => $data['quantity_ordered'],
                                'unit_price' => $data['unit_price'],
                                'notes' => $data['notes'] ?? null,
                            ]);

                            $record->documentItem->update([
                                'description' => 'Personalizado: ' . $data['paper_description'],
                                'quantity' => $data['quantity_ordered'],
                                'unit_price' => $data['unit_price'],
                                'total_price' => $data['quantity_ordered'] * $data['unit_price'],
                            ]);
                        }

                        $purchaseOrder = $livewire->ownerRecord;
                        $purchaseOrder->recalculateTotal();

                        \Filament\Notifications\Notification::make()
                            ->title('Item actualizado correctamente')
                            ->success()
                            ->send();
                    }),

                DetachAction::make()
                    ->label('Quitar')
                    ->modalHeading('Quitar Item de la Orden')
                    ->modalDescription('¿Estás seguro de quitar este item de la orden?')
                    ->after(function ($record, RelationManager $livewire) {
                        $purchaseOrder = $livewire->ownerRecord;
                        $purchaseOrder->recalculateTotal();

                        if ($record->documentItem) {
                            $record->documentItem->updateOrderStatus();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->after(function ($records, RelationManager $livewire) {
                            $purchaseOrder = $livewire->ownerRecord;
                            $purchaseOrder->recalculateTotal();

                            foreach ($records as $record) {
                                if ($record->documentItem) {
                                    $record->documentItem->updateOrderStatus();
                                }
                            }
                        }),
                ]),
            ]);
    }

    private function calculateUnitPrice(DocumentItem $item): float
    {
        if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
            $paper = $item->itemable->paper;
            return $paper ? ($paper->price ?? $paper->cost_per_sheet ?? 0) : 0;
        } elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
            // Para órdenes de compra usar purchase_price (costo), no sale_price (venta)
            return $item->itemable->purchase_price ?? $item->itemable->sale_price ?? 0;
        }

        return $item->unit_price ?? 0;
    }

    private function calculateTotalPrice(DocumentItem $item): float
    {
        if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
            $sheets = $item->itemable->paper_sheets_needed ?? 0;
            $unitPrice = $this->calculateUnitPrice($item);
            return $sheets * $unitPrice;
        } elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
            $unitPrice = $this->calculateUnitPrice($item);
            return $item->quantity * $unitPrice;
        }

        return 0;
    }

}
