<?php

namespace App\Filament\Resources\Finishings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class FinishingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('measurement_unit')
                    ->label('Unidad de Medida')
                    ->formatStateUsing(fn($state) => $state->label())
                    ->badge()
                    ->color(fn($state) => match($state->value) {
                        'millar' => 'info',
                        'rango' => 'warning', 
                        'unidad' => 'success',
                        'tamaño' => 'primary',
                        'por_numero' => 'indigo',
                        'por_talonario' => 'purple',
                    }),
                TextColumn::make('unit_price')
                    ->label('Precio')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->default(fn ($record) => $record->is_own_provider ? 'Propio' : 'Sin asignar')
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        $record->is_own_provider => 'success',
                        $record->supplier_id !== null => 'info',
                        default => 'gray'
                    })
                    ->icon(fn ($record) => match(true) {
                        $record->is_own_provider => 'heroicon-o-home',
                        $record->supplier_id !== null => 'heroicon-o-building-office',
                        default => 'heroicon-o-question-mark-circle'
                    }),
                TextColumn::make('company.name')
                    ->label('Origen')
                    ->getStateUsing(function ($record) {
                        if (!$record || !isset($record->company_id)) {
                            return '❓ Desconocido';
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        if ($record->company_id === $currentCompanyId) {
                            return '🏢 Propio';
                        }
                        return '✨ ' . ($record->company->name ?? 'N/A');
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record || !isset($record->company_id)) {
                            return 'warning';
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId ? 'success' : 'info';
                    })
                    ->visible(function () {
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        return $company && $company->isLitografia();
                    }),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->tooltip(fn (bool $state): string => $state ? 'Visible para clientes' : 'Solo uso interno'),
                TextColumn::make('ranges_count')
                    ->label('Rangos')
                    ->counts('ranges')
                    ->visible(fn($record) => $record?->measurement_unit === \App\Enums\FinishingMeasurementUnit::RANGO),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
