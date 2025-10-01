<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Services\PurchaseOrderPdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->color(fn ($record) => $record->supplierCompany->id === auth()->user()->company_id ? 'success' : 'primary'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Borrador',
                        'sent' => 'Enviada',
                        'confirmed' => 'Confirmada',
                        'partially_received' => 'Parcialmente Recibida',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'confirmed' => 'info',
                        'partially_received' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray'
                    }),

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
                            } else if ($item->itemable) {
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
                    ->options([
                        'draft' => 'Borrador',
                        'sent' => 'Enviada',
                        'confirmed' => 'Confirmada',
                        'partially_received' => 'Parcialmente Recibida',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    ]),

                SelectFilter::make('supplier_company_id')
                    ->label('Proveedor')
                    ->relationship('supplierCompany', 'name')
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye'),

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
                    ->color('warning')
                    ->tooltip('Enviar por Email')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('email')
                            ->label('Email del Proveedor')
                            ->email()
                            ->required()
                            ->default(fn ($record) => $record->supplierCompany?->email)
                            ->helperText(fn ($record) => $record->supplierCompany?->email
                                ? 'Email configurado en el proveedor'
                                : 'El proveedor no tiene email configurado. Ingresa uno manualmente.'),
                    ])
                    ->modalHeading('Enviar Orden por Email')
                    ->modalDescription(fn ($record) => "Enviar orden #{$record->order_number} a {$record->supplierCompany?->name}")
                    ->action(function ($record, array $data) {
                        $pdfService = new PurchaseOrderPdfService;
                        $sent = $pdfService->emailPdf($record, [$data['email']]);

                        if ($sent) {
                            \Filament\Notifications\Notification::make()
                                ->title('Email enviado')
                                ->body("Orden enviada exitosamente a {$data['email']}")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al enviar')
                                ->body('No se pudo enviar el email. Revisa la configuración SMTP.')
                                ->danger()
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

                return $query->where('purchase_orders.company_id', $companyId)
                    ->with([
                        'documentItems.itemable',
                        'supplierCompany',
                        'createdBy',
                    ]);
            })
            ->defaultSort('created_at', 'desc');
    }
}
