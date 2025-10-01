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
    protected static string $relationship = 'documentItems';

    protected static ?string $title = 'Items de la Orden';

    protected static ?string $modelLabel = 'Item';

    protected static ?string $pluralModelLabel = 'Items';

    protected static ?string $recordTitleAttribute = 'description';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('itemable_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'App\Models\SimpleItem' => 'Papel',
                        'App\Models\Product' => 'Producto',
                        'App\Models\DigitalItem' => 'Digital',
                        default => 'Otro'
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'App\Models\SimpleItem' => 'info',
                        'App\Models\Product' => 'success',
                        'App\Models\DigitalItem' => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('pivot.quantity_ordered')
                    ->label('Cantidad')
                    ->numeric(decimalPlaces: 0),

                Tables\Columns\TextColumn::make('pivot.unit_price')
                    ->label('Precio Unit.')
                    ->money('COP'),

                Tables\Columns\TextColumn::make('pivot.total_price')
                    ->label('Total')
                    ->money('COP'),

                Tables\Columns\TextColumn::make('pivot.status')
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
                    }),

                Tables\Columns\TextColumn::make('document.document_number')
                    ->label('Cotización')
                    ->searchable()
                    ->url(fn ($record) => $record->document ?
                        route('filament.admin.resources.documents.view', $record->document) : null)
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('add_items')
                    ->label('Agregar Items')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
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
                                    ->orderBy('created_at', 'desc')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($doc) {
                                        return [
                                            $doc->id => "{$doc->document_number} - {$doc->contact->name} ({$doc->created_at->format('d/m/Y')})"
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

                            // Calcular precios según tipo de item
                            $unitPrice = $this->calculateUnitPrice($item);
                            $totalPrice = $this->calculateTotalPrice($item);

                            $purchaseOrder->documentItems()->attach($item->id, [
                                'quantity_ordered' => $item->quantity,
                                'unit_price' => $unitPrice,
                                'total_price' => $totalPrice,
                                'status' => 'pending',
                            ]);

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
                DetachAction::make()
                    ->label('Quitar')
                    ->modalHeading('Quitar Item de la Orden')
                    ->modalDescription('¿Estás seguro de quitar este item de la orden?')
                    ->after(function ($record, RelationManager $livewire) {
                        $purchaseOrder = $livewire->ownerRecord;
                        $purchaseOrder->recalculateTotal();
                        $record->updateOrderStatus();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->after(function ($records, RelationManager $livewire) {
                            $purchaseOrder = $livewire->ownerRecord;
                            $purchaseOrder->recalculateTotal();

                            foreach ($records as $record) {
                                $record->updateOrderStatus();
                            }
                        }),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['itemable', 'document.contact']);
            });
    }

    private function calculateUnitPrice(DocumentItem $item): float
    {
        if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
            $paper = $item->itemable->paper;
            return $paper ? ($paper->cost_per_sheet ?? 0) : 0;
        } elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
            return $item->itemable->sale_price ?? 0;
        }

        return $item->unit_price ?? 0;
    }

    private function calculateTotalPrice(DocumentItem $item): float
    {
        if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
            $sheets = $item->itemable->total_sheets ?? 0;
            return $sheets * $this->calculateUnitPrice($item);
        }

        return $item->quantity * $this->calculateUnitPrice($item);
    }
}
