<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'collectionAccounts';

    protected static ?string $title = 'Cuentas de Cobro';

    protected static ?string $recordTitleAttribute = 'account_number';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact.name')
                    ->label('Cliente')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('issue_date')
                    ->label('Emisión')
                    ->date()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date()
                    ->sortable(),

                TextColumn::make('paid_date')
                    ->label('Pagada')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('COP'),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.collection-accounts.view', $record)),
            ])
            ->defaultSort('issue_date', 'desc');
    }
}
