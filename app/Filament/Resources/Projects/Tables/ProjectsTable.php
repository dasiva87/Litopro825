<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('contact.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date()
                    ->sortable(),

                TextColumn::make('estimated_end_date')
                    ->label('Fin Est.')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('budget')
                    ->label('Presupuesto')
                    ->money('COP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_docs_count')
                    ->label('Docs')
                    ->state(fn ($record) =>
                        ($record->documents_count ?? 0) +
                        ($record->purchase_orders_count ?? 0) +
                        ($record->production_orders_count ?? 0) +
                        ($record->collection_accounts_count ?? 0)
                    )
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ProjectStatus::class)
                    ->multiple(),

                SelectFilter::make('contact_id')
                    ->label('Cliente')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->preload(),

                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn ($record) => $record->status === ProjectStatus::DRAFT),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'contact',
                'createdBy',
            ])->withCount([
                'documents',
                'purchaseOrders',
                'productionOrders',
                'collectionAccounts',
            ]));
    }
}
