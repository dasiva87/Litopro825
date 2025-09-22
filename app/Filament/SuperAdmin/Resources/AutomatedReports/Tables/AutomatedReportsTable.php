<?php

namespace App\Filament\SuperAdmin\Resources\AutomatedReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AutomatedReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('status'),
                TextColumn::make('report_type')
                    ->searchable(),
                TextColumn::make('frequency'),
                TextColumn::make('custom_cron')
                    ->searchable(),
                TextColumn::make('day_of_month')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('day_of_week')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('time_of_day')
                    ->time()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('format'),
                IconColumn::make('include_charts')
                    ->boolean(),
                IconColumn::make('include_raw_data')
                    ->boolean(),
                TextColumn::make('template')
                    ->searchable(),
                TextColumn::make('retention_days')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('archive_reports')
                    ->boolean(),
                IconColumn::make('send_only_on_changes')
                    ->boolean(),
                TextColumn::make('last_run_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('next_run_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('execution_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_status'),
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
