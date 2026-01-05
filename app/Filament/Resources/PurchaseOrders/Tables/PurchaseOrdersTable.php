<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Enums\OrderStatus;
use App\Services\PurchaseOrderPdfService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

                TextColumn::make('order_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('supplier_name')
                    ->label('Proveedor')
                    ->getStateUsing(fn ($record) => $record->supplierCompany?->name ?? $record->supplier?->name ?? 'Sin proveedor')
                    ->badge()
                    ->color('primary')
                    ->tooltip(fn ($record) => $record->supplierCompany?->email ?? $record->supplier?->email ?? null),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

              

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
                    ->options(OrderStatus::class)
                    ->attribute('purchase_orders.status'),

                SelectFilter::make('supplier_company_id')
                    ->label('Proveedor')
                    ->options(function () {
                        $companyId = auth()->user()->company_id;
                        // Obtener todas las empresas que son proveedores en órdenes
                        return \App\Models\Company::query()
                            ->whereIn('id', function ($query) use ($companyId) {
                                $query->select('supplier_company_id')
                                    ->from('purchase_orders')
                                    ->whereNotNull('supplier_company_id')
                                    ->whereRaw('(purchase_orders.company_id = ? OR purchase_orders.supplier_company_id = ?)', [$companyId, $companyId]);
                            })
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->attribute('purchase_orders.supplier_company_id'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make(),

                    Action::make('view_pdf')
                        ->label('Ver PDF')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(function ($record) {
                            return route('purchase-orders.pdf', $record->id);
                        })
                        ->openUrlInNewTab(),

                    Action::make('send_email')
                        ->label(fn ($record) => $record->email_sent_at ? 'Reenviar Email' : 'Enviar Email')
                        ->icon('heroicon-o-envelope')
                        ->color(fn ($record) => $record->email_sent_at ? 'success' : 'warning')
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

                                // Actualizar registro de envío y cambiar estado a "Enviada"
                                $record->update([
                                    'email_sent_at' => now(),
                                    'email_sent_by' => auth()->id(),
                                    'status' => OrderStatus::SENT,
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Email enviado')
                                    ->body("Orden enviada exitosamente a {$supplierEmail}. Estado cambiado a 'Enviada'.")
                                    ->send();

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Error al enviar email')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Action::make('change_status')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                    ->visible(fn ($record) => $record && ! in_array($record->status, [OrderStatus::COMPLETED, OrderStatus::CANCELLED]))
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

                    DeleteAction::make(),
                ]),
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

                // CRÍTICO: Remover el TenantScope global para aplicar lógica personalizada
                // El scope global solo filtra por company_id, pero necesitamos también supplier_company_id
                return $query->withoutGlobalScopes()
                    ->select('purchase_orders.*')
                    ->leftJoin('companies', 'companies.id', '=', 'purchase_orders.supplier_company_id')
                    ->whereRaw(
                        "(purchase_orders.company_id = ? OR purchase_orders.supplier_company_id = ?)",
                        [$companyId, $companyId]
                    )
                    ->with([
                        'documentItems.itemable',
                        'supplierCompany',
                        'supplier',
                        'createdBy',
                    ]);
            })
            ->defaultSort('created_at', 'desc');
    }
}
