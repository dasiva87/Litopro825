<?php

namespace App\Filament\Resources\CollectionAccounts\Tables;

use App\Enums\CollectionAccountStatus;
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

class CollectionAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_type')
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

                        return $record->company_id === $currentCompanyId ? 'heroicon-o-paper-airplane' : 'heroicon-o-inbox-arrow-down';
                    }),

                TextColumn::make('account_number')
                    ->label('Número de Cuenta')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('clientCompany.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->label('Fecha de Emisión')
                    ->date()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && $record->status !== CollectionAccountStatus::PAID ? 'danger' : null),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('documentItems')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->date()
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

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(fn () => collect(CollectionAccountStatus::cases())
                        ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                    )
                    ->multiple()
                    ->native(false),

                SelectFilter::make('client_company_id')
                    ->label('Cliente')
                    ->relationship('clientCompany', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('account_type')
                    ->label('Tipo de Cuenta')
                    ->options([
                        'sent' => 'Enviadas',
                        'received' => 'Recibidas',
                    ])
                    ->query(function ($query, array $data) {
                        $currentCompanyId = auth()->user()->company_id;

                        if ($data['value'] === 'sent') {
                            return $query->where('company_id', $currentCompanyId);
                        } elseif ($data['value'] === 'received') {
                            return $query->where('client_company_id', $currentCompanyId);
                        }

                        return $query;
                    })
                    ->native(false),

                \Filament\Tables\Filters\Filter::make('overdue')
                    ->label('Vencidas')
                    ->query(fn ($query) => $query
                        ->where('due_date', '<', now())
                        ->whereNotIn('status', [CollectionAccountStatus::PAID->value, CollectionAccountStatus::CANCELLED->value])
                    )
                    ->toggle(),

                \Filament\Tables\Filters\Filter::make('due_soon')
                    ->label('Por Vencer (7 días)')
                    ->query(fn ($query) => $query
                        ->whereBetween('due_date', [now(), now()->addDays(7)])
                        ->whereNotIn('status', [CollectionAccountStatus::PAID->value, CollectionAccountStatus::CANCELLED->value])
                    )
                    ->toggle(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye'),

                Action::make('view_pdf')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->tooltip('Ver PDF')
                    ->url(fn ($record) => route('collection-accounts.pdf', $record))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
