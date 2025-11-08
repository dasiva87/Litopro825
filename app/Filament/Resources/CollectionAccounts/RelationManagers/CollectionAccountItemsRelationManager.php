<?php

namespace App\Filament\Resources\CollectionAccounts\RelationManagers;

use App\Filament\Resources\CollectionAccounts\Handlers\CustomItemQuickHandler;
use App\Models\Document;
use App\Models\DocumentItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CollectionAccountItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'documentItems';

    protected static ?string $title = 'Items de la Cuenta de Cobro';

    protected static ?string $modelLabel = 'Item';

    protected static ?string $pluralModelLabel = 'Items';

    protected static ?string $recordTitleAttribute = 'description';

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
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('itemable_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'App\Models\SimpleItem' => 'Impresión',
                        'App\Models\Product' => 'Producto',
                        'App\Models\DigitalItem' => 'Digital',
                        'App\Models\TalonarioItem' => 'Talonario',
                        'App\Models\MagazineItem' => 'Revista',
                        'App\Models\CustomItem' => 'Personalizado',
                        default => 'Otro'
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'App\Models\SimpleItem' => 'info',
                        'App\Models\Product' => 'success',
                        'App\Models\DigitalItem' => 'warning',
                        'App\Models\TalonarioItem' => 'purple',
                        'App\Models\MagazineItem' => 'primary',
                        'App\Models\CustomItem' => 'secondary',
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
                        'billed' => 'Facturado',
                        'cancelled' => 'Cancelado',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'billed' => 'success',
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
                    ->visible(function () {
                        // Verificar si estamos en modo edición usando la clase de la página
                        $pageClass = $this->getPageClass();
                        $isEditPage = $pageClass === \App\Filament\Resources\CollectionAccounts\Pages\EditCollectionAccount::class;

                        return $isEditPage;
                    })
                    ->modalHeading('Buscar y Agregar Items desde Cotizaciones')
                    ->modalDescription('Busca cotizaciones aprobadas y selecciona items para agregar a esta cuenta de cobro')
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
                                    ->with('itemable')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        $type = match ($item->itemable_type) {
                                            'App\Models\SimpleItem' => '📄 Impresión',
                                            'App\Models\Product' => '📦 Producto',
                                            'App\Models\DigitalItem' => '💻 Digital',
                                            'App\Models\TalonarioItem' => '📋 Talonario',
                                            'App\Models\MagazineItem' => '📖 Revista',
                                            'App\Models\CustomItem' => '✏️ Personalizado',
                                            default => '📋 Otro'
                                        };

                                        $price = number_format($item->total_price ?? 0, 0);
                                        $label = "{$type} - {$item->description} (Total: \${price})";

                                        return [$item->id => $label];
                                    });
                            })
                            ->visible(fn (callable $get) => (bool) $get('document_id'))
                            ->required()
                            ->columns(1),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $collectionAccount = $livewire->ownerRecord;

                        foreach ($data['item_ids'] as $itemId) {
                            $item = DocumentItem::find($itemId);

                            if (!$item) {
                                continue;
                            }

                            // Verificar si ya está en esta cuenta
                            $alreadyAttached = $collectionAccount->documentItems()
                                ->where('document_item_id', $itemId)
                                ->exists();

                            if ($alreadyAttached) {
                                continue;
                            }

                            // Calcular precios - usar el precio final del item
                            $unitPrice = $item->unit_price ?? 0;
                            $totalPrice = $item->total_price ?? 0;
                            $quantity = $item->quantity ?? 1;

                            $collectionAccount->documentItems()->attach($item->id, [
                                'quantity_ordered' => $quantity,
                                'unit_price' => $unitPrice,
                                'total_price' => $totalPrice,
                                'status' => 'pending',
                            ]);
                        }

                        $collectionAccount->recalculateTotal();

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
                        $isEditPage = $pageClass === \App\Filament\Resources\CollectionAccounts\Pages\EditCollectionAccount::class;

                        return $isEditPage;
                    })
                    ->modalWidth((new CustomItemQuickHandler)->getModalWidth())
                    ->form((new CustomItemQuickHandler)->getFormSchema())
                    ->action(function (array $data, RelationManager $livewire) {
                        $collectionAccount = $livewire->ownerRecord;
                        (new CustomItemQuickHandler)->handleCreate($data, $collectionAccount);

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
                    ->modalHeading('Quitar Item de la Cuenta')
                    ->modalDescription('¿Estás seguro de quitar este item de la cuenta de cobro?')
                    ->after(function ($record, RelationManager $livewire) {
                        $collectionAccount = $livewire->ownerRecord;
                        $collectionAccount->recalculateTotal();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->after(function ($records, RelationManager $livewire) {
                            $collectionAccount = $livewire->ownerRecord;
                            $collectionAccount->recalculateTotal();
                        }),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['itemable', 'document.contact']);
            });
    }
}
