<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->sortable(),

                TextColumn::make('contact.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'secondary',
                        'sent' => 'primary',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'in_production' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'sent' => 'Enviado',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'in_production' => 'En Producción',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),

                TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label('Válida Hasta')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),

                TextColumn::make('version')
                    ->label('Versión')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'sent' => 'Enviado',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'in_production' => 'En Producción',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->multiple(),

                SelectFilter::make('document_type_id')
                    ->label('Tipo')
                    ->relationship('documentType', 'name'),

                Filter::make('expired')
                    ->label('Vencidas')
                    ->query(fn (Builder $query): Builder => $query->where('valid_until', '<', now()))
                    ->toggle(),

                Filter::make('expiring_soon')
                    ->label('Por Vencer (7 días)')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon())
                    ->toggle(),

                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => $record->canEdit()),

                    Action::make('send')
                        ->label('Enviar')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->visible(fn ($record) => $record->canSend())
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->markAsSent()),

                    Action::make('approve')
                        ->label('Aprobar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->canApprove())
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->markAsApproved()),

                    Action::make('reject')
                        ->label('Rechazar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->canApprove())
                        ->form([
                            Textarea::make('reason')
                                ->label('Motivo de Rechazo')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->markAsRejected($data['reason']);
                        }),

                    Action::make('new_version')
                        ->label('Nueva Versión')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $newDocument = $record->createNewVersion();

                            return redirect()->route('filament.admin.resources.documents.edit', $newDocument);
                        }),

                    Action::make('create_collection_account')
                        ->label('Crear Cuenta de Cobro')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'approved')
                        ->form([
                            \Filament\Schemas\Components\Section::make('Seleccionar Items para Cuenta de Cobro')
                                ->description('Selecciona los items que deseas incluir en la cuenta de cobro')
                                ->schema([
                                    \Filament\Forms\Components\CheckboxList::make('selected_items')
                                        ->label('Items Disponibles')
                                        ->options(function ($record) {
                                            return $record->items
                                                ->mapWithKeys(function ($item) {
                                                    $total = number_format($item->total_price ?? 0, 0);
                                                    $label = "{$item->description} - Total: \${total}";

                                                    return [$item->id => $label];
                                                });
                                        })
                                        ->required()
                                        ->minItems(1)
                                        ->columns(1),

                                    \Filament\Forms\Components\Select::make('client_company_id')
                                        ->label('Cliente')
                                        ->relationship('contact.company', 'name')
                                        ->default(fn ($record) => $record->contact->company_id ?? null)
                                        ->required(),

                                    \Filament\Forms\Components\DatePicker::make('due_date')
                                        ->label('Fecha de Vencimiento')
                                        ->default(now()->addDays(30))
                                        ->required(),

                                    \Filament\Forms\Components\Textarea::make('notes')
                                        ->label('Notas adicionales')
                                        ->placeholder('Notas para la cuenta de cobro...')
                                        ->rows(3),
                                ]),
                        ])
                        ->action(function ($record, array $data) {
                            $collectionAccount = \App\Models\CollectionAccount::create([
                                'company_id' => auth()->user()->company_id,
                                'client_company_id' => $data['client_company_id'],
                                'issue_date' => now(),
                                'due_date' => $data['due_date'],
                                'notes' => $data['notes'] ?? null,
                                'status' => \App\Enums\CollectionAccountStatus::DRAFT,
                            ]);

                            // Agregar items seleccionados
                            $selectedItems = \App\Models\DocumentItem::whereIn('id', $data['selected_items'])->get();

                            foreach ($selectedItems as $item) {
                                $collectionAccount->documentItems()->attach($item->id, [
                                    'quantity_ordered' => $item->quantity ?? 1,
                                    'unit_price' => $item->unit_price ?? 0,
                                    'total_price' => $item->total_price ?? 0,
                                    'status' => 'pending',
                                ]);
                            }

                            $collectionAccount->recalculateTotal();

                            \Filament\Notifications\Notification::make()
                                ->title('Cuenta de cobro creada exitosamente')
                                ->success()
                                ->body("Cuenta {$collectionAccount->account_number} creada con {$selectedItems->count()} items")
                                ->send();

                            return redirect()->route('filament.admin.resources.collection-accounts.view', $collectionAccount);
                        }),

                    Action::make('create_purchase_orders')
                        ->label('Crear Órdenes de Pedido')
                        ->icon('heroicon-o-shopping-cart')
                        ->color('info')
                        ->visible(fn ($record) => $record->canCreateOrders())
                        ->form([
                            \Filament\Schemas\Components\Section::make('Seleccionar Items para Orden de Pedido')
                                ->description('Selecciona los items que deseas incluir en las órdenes de pedido. Se agruparán automáticamente por proveedor.')
                                ->schema([
                                    \Filament\Forms\Components\CheckboxList::make('selected_items')
                                        ->label('Items Disponibles')
                                        ->options(function ($record) {
                                            return $record->getAvailableItemsForOrder()
                                                ->mapWithKeys(function ($item) {
                                                    $description = $item->description;

                                                    // Información específica según tipo de item
                                                    if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
                                                        $simpleItem = $item->itemable;
                                                        $paper = $simpleItem->paper;
                                                        $description .= $paper ?
                                                            " - {$paper->name} ({$simpleItem->mounting_quantity} pliegos - {$simpleItem->horizontal_size}x{$simpleItem->vertical_size}cm)" :
                                                            ' - Papel no definido';
                                                    } elseif ($item->itemable_type === 'App\Models\MagazineItem' && $item->itemable) {
                                                        $magazine = $item->itemable;
                                                        $totalSheets = $magazine->total_sheets;
                                                        $description .= " - Revista ({$totalSheets} pliegos totales - {$magazine->closed_width}x{$magazine->closed_height}cm cerrado)";
                                                    } elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
                                                        $product = $item->itemable;
                                                        $description .= " - {$product->name} ({$item->quantity} unidades)";
                                                    }

                                                    return [$item->id => $description];
                                                });
                                        })
                                        ->descriptions(function ($record) {
                                            return $record->getAvailableItemsForOrder()
                                                ->mapWithKeys(function ($item) {
                                                    $info = "Cantidad: {$item->quantity}";

                                                    if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
                                                        $simpleItem = $item->itemable;
                                                        $paper = $simpleItem->paper;
                                                        $supplier = $paper && $paper->company ? $paper->company->name : 'Proveedor no definido';
                                                        $info .= " | Proveedor: {$supplier}";
                                                        if ($paper) {
                                                            $cost = ($simpleItem->mounting_quantity ?? 0) * $paper->cost_per_sheet;
                                                            $info .= ' | Costo estimado: $'.number_format($cost, 2);
                                                        }
                                                    } elseif ($item->itemable_type === 'App\Models\MagazineItem' && $item->itemable) {
                                                        $magazine = $item->itemable;
                                                        $supplierId = $magazine->getMainPaperSupplier();
                                                        $supplier = $supplierId ? \App\Models\Contact::find($supplierId)?->name : 'Múltiples proveedores';
                                                        $info .= " | Proveedor principal: {$supplier}";
                                                        $papersUsed = $magazine->getPapersUsed();
                                                        $totalCost = 0;
                                                        foreach ($papersUsed as $paperData) {
                                                            $totalCost += $paperData['total_sheets'] * ($paperData['paper']->cost_per_sheet ?? 0);
                                                        }
                                                        $info .= ' | Costo estimado: $'.number_format($totalCost, 2);
                                                    } elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
                                                        $product = $item->itemable;
                                                        $supplier = $product->company ? $product->company->name : 'Proveedor no definido';
                                                        $info .= " | Proveedor: {$supplier}";
                                                        $cost = $item->quantity * ($product->purchase_price ?? $product->sale_price);
                                                        $info .= ' | Costo estimado: $'.number_format($cost, 2);
                                                    }

                                                    return [$item->id => $info];
                                                });
                                        })
                                        ->required()
                                        ->minItems(1)
                                        ->columns(1),

                                    \Filament\Forms\Components\Textarea::make('notes')
                                        ->label('Notas adicionales')
                                        ->placeholder('Notas que se incluirán en todas las órdenes generadas...')
                                        ->rows(3),
                                ]),
                        ])
                        ->action(function ($record, array $data) {
                            // Crear las órdenes de pedido
                            $selectedItems = \App\Models\DocumentItem::whereIn('id', $data['selected_items'])
                                ->with(['itemable'])
                                ->get();

                            // Cargar relaciones específicas según el tipo
                            $selectedItems->load([
                                'itemable' => function ($morphTo) {
                                    $morphTo->morphWith([
                                        'App\Models\SimpleItem' => ['paper.company', 'company'],
                                        'App\Models\Product' => ['company'],
                                        'App\Models\DigitalItem' => ['company'],
                                        'App\Models\TalonarioItem' => ['sheets.simpleItem.paper.company', 'company'],
                                        'App\Models\MagazineItem' => ['pages.simpleItem.paper.company', 'company'],
                                        'App\Models\CustomItem' => ['company'],
                                        'App\Models\Paper' => ['company'],
                                    ]);
                                },
                            ]);

                            // Agrupar por proveedor y tipo
                            $groupedItems = $selectedItems->groupBy(function ($item) {
                                // Determinar el tipo de orden basado en el tipo de item
                                $orderType = 'producto'; // Default
                                $supplierId = 0; // Default

                                // Items que van como 'papel'
                                if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable && $item->itemable->paper) {
                                    $orderType = 'papel';
                                    $supplierId = $item->itemable->paper->company_id;
                                } elseif ($item->itemable_type === 'App\Models\TalonarioItem' && $item->itemable) {
                                    // Para talonarios, obtener el proveedor principal de papel
                                    $supplierId = $item->itemable->getMainPaperSupplier();
                                    if ($supplierId) {
                                        $orderType = 'papel';
                                    }
                                } elseif ($item->itemable_type === 'App\Models\MagazineItem' && $item->itemable) {
                                    // Para revistas, obtener el proveedor principal de papel
                                    $supplierId = $item->itemable->getMainPaperSupplier();
                                    if ($supplierId) {
                                        $orderType = 'papel';
                                    }
                                } elseif ($item->itemable_type === 'App\Models\Paper' && $item->itemable) {
                                    $orderType = 'papel';
                                    $supplierId = $item->itemable->company_id;
                                }
                                // Items que van como 'producto'
                                elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
                                    $orderType = 'producto';
                                    $supplierId = $item->itemable->company_id;
                                } elseif ($item->itemable_type === 'App\Models\DigitalItem' && $item->itemable) {
                                    $orderType = 'producto';
                                    $supplierId = $item->itemable->company_id;
                                } elseif ($item->itemable_type === 'App\Models\CustomItem' && $item->itemable) {
                                    $orderType = 'producto';
                                    $supplierId = $item->itemable->company_id;
                                }

                                return $orderType.'_'.$supplierId;
                            });

                            $ordersCreated = 0;
                            foreach ($groupedItems as $groupKey => $items) {
                                [$type, $supplierId] = explode('_', $groupKey);

                                // Crear la orden (sin document_id ni order_type que ya no existen)
                                $order = \App\Models\PurchaseOrder::create([
                                    'company_id' => auth()->user()->company_id,
                                    'supplier_company_id' => $supplierId,
                                    'order_date' => now(),
                                    'expected_delivery_date' => now()->addDays(7),
                                    'status' => 'draft',
                                    'notes' => $data['notes'] ?? null,
                                ]);

                                // Agregar items usando la relación many-to-many
                                foreach ($items as $item) {
                                    if ($item->itemable_type === 'App\Models\MagazineItem' && $item->itemable) {
                                        // Para revistas, crear UNA FILA POR CADA TIPO DE PAPEL
                                        $magazine = $item->itemable;
                                        $papersUsed = $magazine->getPapersUsed();

                                        foreach ($papersUsed as $paperId => $paperData) {
                                            $paper = $paperData['paper'];
                                            $sheets = $paperData['total_sheets'];
                                            $unitPrice = $paper->cost_per_sheet ?? 0;
                                            $totalPrice = $sheets * $unitPrice;

                                            // Crear descripción específica del papel
                                            $paperDescription = "{$paper->name} - Revista: {$magazine->description}";

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

                                            // Attach con información específica del papel
                                            $order->documentItems()->attach($item->id, [
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
                                        }

                                        // Actualizar order_status del item
                                        $item->updateOrderStatus();

                                    } elseif ($item->itemable_type === 'App\Models\TalonarioItem' && $item->itemable) {
                                        // Para talonarios, crear UNA FILA POR CADA TIPO DE PAPEL
                                        $talonario = $item->itemable;
                                        $papersUsed = $talonario->getPapersUsed();

                                        foreach ($papersUsed as $paperId => $paperData) {
                                            $paper = $paperData['paper'];
                                            $sheets = $paperData['total_sheets'];
                                            $unitPrice = $paper->cost_per_sheet ?? 0;
                                            $totalPrice = $sheets * $unitPrice;

                                            // Crear descripción específica del papel con hojas que lo usan
                                            $sheetsUsing = implode(', ', $paperData['sheets_using']);
                                            $paperDescription = "{$paper->name} - Talonario: {$talonario->description} ({$sheetsUsing})";

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

                                            // Attach con información específica del papel
                                            $order->documentItems()->attach($item->id, [
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
                                        }

                                        // Actualizar order_status del item
                                        $item->updateOrderStatus();

                                    } else {
                                        // Para SimpleItem, Product, etc. - una sola fila
                                        $unitPrice = 0;
                                        $totalPrice = 0;
                                        $sheets = 0;
                                        $paperId = null;
                                        $cutWidth = null;
                                        $cutHeight = null;

                                        if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
                                            $paper = $item->itemable->paper;
                                            $sheets = $item->itemable->mounting_quantity ?? 0;
                                            $unitPrice = $paper ? ($paper->cost_per_sheet ?? 0) : 0;
                                            $totalPrice = $sheets * $unitPrice;
                                            $paperId = $paper?->id;
                                            $cutWidth = $item->itemable->horizontal_size;
                                            $cutHeight = $item->itemable->vertical_size;
                                        } elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
                                            $unitPrice = $item->itemable->sale_price ?? 0;
                                            $totalPrice = $item->quantity * $unitPrice;
                                            // Product no tiene cut_width/cut_height (producto terminado)
                                        } else {
                                            $unitPrice = $item->unit_price ?? 0;
                                            $totalPrice = $item->quantity * $unitPrice;
                                        }

                                        // Attach item a la orden con pivot data
                                        $order->documentItems()->attach($item->id, [
                                            'paper_id' => $paperId,
                                            'quantity_ordered' => $item->quantity,
                                            'sheets_quantity' => $sheets,
                                            'cut_width' => $cutWidth,
                                            'cut_height' => $cutHeight,
                                            'unit_price' => $unitPrice,
                                            'total_price' => $totalPrice,
                                            'status' => 'pending',
                                        ]);

                                        // Actualizar order_status del item
                                        $item->updateOrderStatus();
                                    }
                                }

                                // Recalcular total
                                $order->recalculateTotal();
                                $ordersCreated++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Órdenes de Pedido Creadas')
                                ->body("Se crearon {$ordersCreated} órdenes de pedido exitosamente.")
                                ->success()
                                ->send();
                        })
                        ->modalWidth('7xl'),

                    Action::make('create_production_order')
                        ->label('Crear Órdenes de Producción')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'approved')
                        ->form([
                            \Filament\Schemas\Components\Section::make('Seleccionar Items para Producción')
                                ->description('Selecciona los items. Se crearán órdenes automáticamente agrupadas por proveedor (impresión + acabados).')
                                ->schema([
                                    \Filament\Forms\Components\CheckboxList::make('selected_items')
                                        ->label('Items Disponibles')
                                        ->options(function ($record) {
                                            return $record->items
                                                ->where('itemable_type', 'App\Models\SimpleItem')
                                                ->mapWithKeys(function ($item) {
                                                    if (!$item->itemable) {
                                                        return [$item->id => "⚠️ {$item->description} (Sin datos de producción)"];
                                                    }

                                                    $type = '📄 Impresión';
                                                    $quantity = number_format($item->quantity ?? 0, 0);
                                                    $size = "{$item->itemable->horizontal_size}x{$item->itemable->vertical_size} cm";
                                                    $paper = $item->itemable->paper?->name ?? 'Sin papel';
                                                    $tintas = "F:{$item->itemable->ink_front_count}/V:{$item->itemable->ink_back_count}";

                                                    // Contar acabados
                                                    $finishingsCount = $item->finishings()->count();
                                                    $finishingsText = $finishingsCount > 0 ? " | 🎯 {$finishingsCount} acabado(s)" : '';

                                                    $label = "{$type} {$item->description}\n";
                                                    $label .= "   📦 Cantidad: {$quantity} | 📏 Tamaño: {$size}\n";
                                                    $label .= "   🎨 Papel: {$paper} | 🖨️ Tintas: {$tintas}{$finishingsText}";

                                                    return [$item->id => $label];
                                                });
                                        })
                                        ->required()
                                        ->minItems(1)
                                        ->columns(1)
                                        ->bulkToggleable(),

                                    \Filament\Forms\Components\Placeholder::make('orders_preview')
                                        ->label('Vista Previa de Órdenes')
                                        ->content(function (callable $get) {
                                            $selectedIds = $get('selected_items');
                                            if (!$selectedIds || count($selectedIds) === 0) {
                                                return 'Selecciona items para ver cuántas órdenes se crearán...';
                                            }

                                            $selectedItems = \App\Models\DocumentItem::whereIn('id', $selectedIds)
                                                ->with(['finishings.supplier', 'itemable'])
                                                ->get();

                                            $groupingService = new \App\Services\ProductionOrderGroupingService();
                                            $summary = $groupingService->getOrdersSummary($selectedItems);

                                            if (count($summary) === 0) {
                                                return '⚠️ Los items seleccionados no tienen acabados con proveedores asignados.';
                                            }

                                            $text = "✅ Se crearán " . count($summary) . " orden(es) de producción:\n\n";
                                            foreach ($summary as $order) {
                                                $text .= "📦 {$order['supplier_name']}: {$order['total_processes']} proceso(s)\n";
                                                if ($order['printing_count'] > 0) {
                                                    $text .= "   - 🖨️ {$order['printing_count']} impresión(es)\n";
                                                }
                                                if ($order['finishing_count'] > 0) {
                                                    $text .= "   - 🎯 {$order['finishing_count']} acabado(s)\n";
                                                }
                                            }

                                            return new \Illuminate\Support\HtmlString('<pre style="white-space: pre-wrap;">' . $text . '</pre>');
                                        })
                                        ->visible(fn (callable $get) => !empty($get('selected_items'))),

                                    \Filament\Forms\Components\DatePicker::make('scheduled_date')
                                        ->label('Fecha Programada (Opcional)')
                                        ->helperText('Fecha estimada para realizar la producción')
                                        ->native(false)
                                        ->default(now()->addDays(7)),

                                    \Filament\Forms\Components\Textarea::make('notes')
                                        ->label('Notas adicionales')
                                        ->placeholder('Notas sobre las órdenes de producción...')
                                        ->rows(3),
                                ]),
                        ])
                        ->action(function ($record, array $data) {
                            $selectedItems = \App\Models\DocumentItem::whereIn('id', $data['selected_items'])
                                ->with(['itemable', 'finishings.supplier'])
                                ->get();

                            $groupingService = new \App\Services\ProductionOrderGroupingService();
                            $grouped = $groupingService->groupBySupplier($selectedItems);

                            // Validar que haya al menos un proveedor agrupado
                            if (count($grouped) === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No se pueden crear órdenes de producción')
                                    ->danger()
                                    ->body('Los items seleccionados no tienen acabados con proveedores asignados. Por favor, asigna proveedores a los acabados de los items antes de crear órdenes de producción.')
                                    ->send();
                                return;
                            }

                            $createdOrders = [];
                            $totalProcesses = 0;
                            $errors = [];

                            foreach ($grouped as $supplierId => $processes) {
                                // Verificar que el supplierId sea válido (es un Contact)
                                $supplier = \App\Models\Contact::find($supplierId);

                                if (!$supplier) {
                                    $errors[] = "Proveedor con ID {$supplierId} no encontrado";
                                    continue;
                                }

                                // Crear orden para este proveedor (Contact)
                                $productionOrder = \App\Models\ProductionOrder::create([
                                    'company_id' => auth()->user()->company_id,
                                    'supplier_id' => $supplierId, // Contact ID
                                    'supplier_company_id' => null, // No es empresa del sistema
                                    'scheduled_date' => $data['scheduled_date'] ?? null,
                                    'notes' => $data['notes'] ?? null,
                                    'status' => \App\Enums\ProductionStatus::DRAFT,
                                ]);

                                // Agregar procesos de impresión
                                foreach ($processes['printing'] as $process) {
                                    if ($productionOrder->addItem(
                                        $process['document_item'],
                                        $process['quantity'],
                                        'printing',
                                        null,
                                        $process['process_description']
                                    )) {
                                        $totalProcesses++;
                                    }
                                }

                                // Agregar procesos de acabados
                                foreach ($processes['finishings'] as $process) {
                                    if ($productionOrder->addItem(
                                        $process['document_item'],
                                        $process['quantity'],
                                        'finishing',
                                        $process['finishing_name'],
                                        $process['process_description']
                                    )) {
                                        $totalProcesses++;
                                    }
                                }

                                $productionOrder->recalculateMetrics();
                                $createdOrders[] = $productionOrder;
                            }

                            // Notificación
                            if (count($createdOrders) > 0) {
                                $message = "Se crearon " . count($createdOrders) . " orden(es) con {$totalProcesses} proceso(s) total";
                                if (count($errors) > 0) {
                                    $message .= "\n\nAdvertencias:\n" . implode("\n", $errors);
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Órdenes de producción creadas exitosamente')
                                    ->success()
                                    ->body($message)
                                    ->send();

                                // Redirect to production orders list
                                return redirect()->route('filament.admin.resources.production-orders.index');
                            } else {
                                $errorMessage = 'Los items seleccionados no tienen acabados con proveedores asignados';
                                if (count($errors) > 0) {
                                    $errorMessage = implode("\n", $errors);
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('No se pudieron crear las órdenes')
                                    ->danger()
                                    ->body($errorMessage)
                                    ->send();
                            }
                        })
                        ->modalWidth('7xl'),

                    DeleteAction::make()
                        ->visible(fn ($record) => $record->isDraft()),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),

                    BulkAction::make('mark_as_sent')
                        ->label('Marcar como Enviado')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->canSend() && $record->markAsSent());
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
