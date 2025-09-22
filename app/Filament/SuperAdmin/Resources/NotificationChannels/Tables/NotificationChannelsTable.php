<?php

namespace App\Filament\SuperAdmin\Resources\NotificationChannels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type'),
                TextColumn::make('status'),
                TextColumn::make('default_template')
                    ->searchable(),
                TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('supports_realtime')
                    ->boolean(),
                IconColumn::make('supports_bulk')
                    ->boolean(),
                TextColumn::make('total_sent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_delivered')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_failed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
