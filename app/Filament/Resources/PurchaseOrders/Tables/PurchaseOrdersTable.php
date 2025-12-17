<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Enums\OrderStatus;
use App\Services\PurchaseOrderPdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_type')
                    ->label('Tipo')
                    ->state(function ($record) {
                        $currentCompanyId = auth()->user()->company_id;

                        return $record->company_id === $currentCompanyId ? 'Enviada' : 'Recibida';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $currentCompanyId = auth()->user()->company_id;

                        return $record->company_id === $currentCompanyId ? 'info' : 'success';
                    })
                    ->icon(function ($record) {
                        $currentCompanyId = auth()->user()->company_id;

                        return $record->company_id === $currentCompanyId ? 'heroicon-o-arrow-up-tray' : 'heroicon-o-arrow-down-tray';
                    }),

                TextColumn::make('order_number')
                    ->label('Número de Orden')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('supplierCompany.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

                TextColumn::make('email_sent_at')
                    ->label('Email')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Enviado' : 'Pendiente')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                    ->tooltip(fn ($record) => $record->email_sent_at
                        ? "Enviado: {$record->email_sent_at->format('d/m/Y H:i')}"
                        : 'Email no enviado')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('order_date')
                    ->label('Fecha de Orden')
                    ->date()
                    ->sortable(),

                TextColumn::make('expected_delivery_date')
                    ->label('Entrega Esperada')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('documentItems')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('items_details')
                    ->label('Detalle de Items')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        // Usar documentItems con la relación many-to-many
                        $items = $record->documentItems;

                        if ($items->isEmpty()) {
                            return '<span class="text-gray-400 italic">Sin items</span>';
                        }

                        $details = [];
                        foreach ($items as $item) {
                            $description = '';
                            $unitPrice = number_format($item->pivot->unit_price ?? 0, 2);
                            $totalPrice = number_format($item->pivot->total_price ?? 0, 2);
                            $quantity = number_format($item->pivot->quantity_ordered ?? 0, 0);

                            // Determinar tipo según itemable_type
                            $isPaper = $item->itemable_type === 'App\Models\SimpleItem';
                            $typeBadge = $isPaper
                                ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mr-1">📄 Papel</span>'
                                : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mr-1">📦 Producto</span>';

                            if ($isPaper && $item->itemable) {
                                $paper = $item->itemable->paper;
                                $description = $paper ? $paper->name : 'Papel';
                                $sheets = $item->itemable->total_sheets ?? 0;
                                if ($sheets) {
                                    $description .= " ({$sheets} pliegos)";
                                }
                                $cutSize = "{$item->itemable->horizontal_size}x{$item->itemable->vertical_size}cm";
                                $description .= " - {$cutSize}";
                            } elseif ($item->itemable) {
                                $description = $item->itemable->name ?? 'Producto';
                                if (isset($item->itemable->code) && $item->itemable->code) {
                                    $description .= " (Cód: {$item->itemable->code})";
                                }
                            } else {
                                $description = $item->description ?? 'Item';
                            }

                            $details[] = "<div class='mb-2 p-2 bg-gray-50 rounded text-xs border-l-3 border-l-blue-500'>".
                                        "<div class='flex items-center mb-1'>{$typeBadge}<span class='font-medium text-gray-900'>{$description}</span></div>".
                                        "<div class='text-gray-600 grid grid-cols-3 gap-2'>".
                                        "<span>🔢 Cant: <strong>{$quantity}</strong></span>".
                                        "<span>💰 Unit: <strong>\${$unitPrice}</strong></span>".
                                        "<span>💵 Total: <strong class='text-green-600'>\${$totalPrice}</strong></span>".
                                        '</div>'.
                                        '</div>';
                        }

                        return '<div class="space-y-1 max-w-sm">'.implode('', $details).'</div>';
                    })
                    ->wrap()
                    ->width('300px')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('createdBy.name')
                    ->label('Creado por')
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
                    ->options(OrderStatus::class),

                SelectFilter::make('supplier_company_id')
                    ->label('Proveedor')
                    ->relationship('supplierCompany', 'name')
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye'),

                Action::make('change_status')
                    ->label('')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->tooltip('Cambiar Estado')
                    ->visible(fn ($record) => $record && ! in_array($record->status, [OrderStatus::RECEIVED, OrderStatus::CANCELLED]))
                    ->fillForm(fn ($record): array => [
                        'current_status' => $record->status->getLabel(),
                    ])
                    ->form(fn ($record): array => [
                        \Filament\Forms\Components\Placeholder::make('current_status')
                            ->label('Estado Actual')
                            ->content(fn ($record) => $record->status->getLabel()),

                        Select::make('new_status')
                            ->label('Nuevo Estado')
                            ->options(fn ($record) => collect($record->status->getNextStatuses())->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()]))
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->placeholder('Opcional: Agrega notas sobre este cambio de estado'),
                    ])
                    ->modalHeading(fn ($record) => "Cambiar Estado - Orden #{$record->order_number}")
                    ->modalWidth('md')
                    ->action(function ($record, array $data) {
                        if (!$record) {
                            return;
                        }

                        $newStatus = OrderStatus::from($data['new_status']);
                        $oldStatus = $record->status;

                        if ($record->changeStatus($newStatus, $data['notes'] ?? null)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Estado actualizado')
                                ->body("Orden cambiada de {$oldStatus->getLabel()} a {$newStatus->getLabel()}")
                                ->success()
                                ->send();

                            // Si se cambió a 'sent', notificar
                            if ($newStatus === OrderStatus::SENT) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Notificación enviada')
                                    ->body('Se ha enviado una notificación al proveedor')
                                    ->info()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body("No se puede cambiar de {$oldStatus->getLabel()} a {$newStatus->getLabel()}")
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('download_pdf')
                    ->label('')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($record) {
                        $pdfService = new PurchaseOrderPdfService;

                        return $pdfService->downloadPdf($record);
                    }),

                Action::make('view_pdf')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(function ($record) {
                        return route('purchase-orders.pdf', $record->id);
                    })
                    ->openUrlInNewTab(),

                Action::make('send_email')
                    ->label('')
                    ->icon('heroicon-o-envelope')
                    ->color(fn ($record) => $record->email_sent_at ? 'success' : 'warning')
                    ->tooltip(fn ($record) => $record->email_sent_at
                        ? 'Reenviar Email (enviado ' . $record->email_sent_at->diffForHumans() . ')'
                        : 'Enviar Email al Proveedor')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->email_sent_at
                        ? 'Reenviar Orden por Email'
                        : 'Enviar Orden por Email')
                    ->modalDescription(function ($record) {
                        $supplierName = $record->supplierCompany->name
                            ?? $record->supplier->name
                            ?? 'Sin proveedor';

                        $description = "Orden #{$record->order_number} para {$supplierName}";

                        if ($record->email_sent_at) {
                            $description .= "\n\n⚠️ Esta orden ya fue enviada el {$record->email_sent_at->format('d/m/Y H:i')}";
                        }

                        return $description;
                    })
                    ->modalIcon('heroicon-o-envelope')
                    ->action(function ($record) {
                        // Validar que tenga items
                        if ($record->purchaseOrderItems->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('No se puede enviar')
                                ->body('La orden no tiene items.')
                                ->send();
                            return;
                        }

                        // Validar que tenga total
                        if ($record->total_amount <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('No se puede enviar')
                                ->body('La orden tiene un total de $0.')
                                ->send();
                            return;
                        }

                        // Obtener email del proveedor
                        $supplierEmail = $record->supplierCompany->email
                            ?? $record->supplier->email;

                        if (!$supplierEmail) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('No se puede enviar')
                                ->body('El proveedor no tiene email configurado.')
                                ->send();
                            return;
                        }

                        try {
                            // Enviar notificación con PDF
                            \Illuminate\Support\Facades\Notification::route('mail', $supplierEmail)
                                ->notify(new \App\Notifications\PurchaseOrderCreated($record->id));

                            // Actualizar registro de envío
                            $record->update([
                                'email_sent_at' => now(),
                                'email_sent_by' => auth()->id(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Email enviado')
                                ->body("Orden enviada exitosamente a {$supplierEmail}")
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Error al enviar email')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil'),

                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function ($query) {
                $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

                if (! $companyId) {
                    throw new \Exception('No company context found - security violation prevented');
                }

                // Mostrar órdenes creadas por la empresa O órdenes recibidas como proveedor
                return $query->where(function ($q) use ($companyId) {
                        $q->where('purchase_orders.company_id', $companyId)
                            ->orWhere('purchase_orders.supplier_company_id', $companyId);
                    })
                    ->with([
                        'documentItems.itemable',
                        'supplierCompany',
                        'createdBy',
                    ]);
            })
            ->defaultSort('created_at', 'desc');
    }
}
