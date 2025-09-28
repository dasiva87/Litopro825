<?php

namespace App\Filament\Resources\SupplierRequests\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('requesterCompany.name')
                    ->label('Litografía')
                    ->searchable()
                    ->sortable()
                    ->visible(function () {
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        return $company && $company->isPapeleria();
                    }),

                TextColumn::make('supplierCompany.name')
                    ->label('Papelería')
                    ->searchable()
                    ->sortable()
                    ->visible(function () {
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        return $company && $company->isLitografia();
                    }),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->message;
                    }),

                TextColumn::make('status')
                    ->label('Estado')
                    ->getStateUsing(fn ($record) => $record->getStatusLabel())
                    ->badge()
                    ->color(fn ($record) => $record->getStatusColor()),

                TextColumn::make('created_at')
                    ->label('Solicitado')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('responded_at')
                    ->label('Respondido')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Pendiente'),

                TextColumn::make('response_message')
                    ->label('Respuesta')
                    ->limit(30)
                    ->placeholder('Sin respuesta')
                    ->tooltip(function ($record) {
                        return $record->response_message;
                    }),

                TextColumn::make('respondedByUser.name')
                    ->label('Respondido por')
                    ->placeholder('N/A'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->label('Responder')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->visible(function ($record) {
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        return $company && $company->isPapeleria();
                    }),
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->form([
                        Textarea::make('response_message')
                            ->label('Mensaje de respuesta (opcional)')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->approve(auth()->user(), $data['response_message'] ?? null);

                        Notification::make()
                            ->title('Solicitud aprobada')
                            ->body('La solicitud ha sido aprobada y se ha activado la relación de proveedor.')
                            ->success()
                            ->send();
                    })
                    ->visible(function ($record) {
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        // Permitir aprobar si está pendiente o rechazada
                        return ($record->isPending() || $record->isRejected()) && $company && $company->isPapeleria();
                    }),

                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Textarea::make('response_message')
                            ->label('Mensaje de respuesta (opcional)')
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->reject(auth()->user(), $data['response_message'] ?? null);

                        Notification::make()
                            ->title('Solicitud rechazada')
                            ->body('La solicitud ha sido rechazada y se ha desactivado la relación de proveedor.')
                            ->warning()
                            ->send();
                    })
                    ->visible(function ($record) {
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        // Permitir rechazar si está pendiente o aprobada
                        return ($record->isPending() || $record->isApproved()) && $company && $company->isPapeleria();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(function () {
                            // Solo litografías pueden eliminar sus solicitudes pendientes
                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                            $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                            return $company && $company->isLitografia();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
