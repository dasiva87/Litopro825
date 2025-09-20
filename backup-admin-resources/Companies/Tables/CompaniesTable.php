<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre de la Empresa')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->icon('heroicon-m-phone'),

                TextColumn::make('subscription_plan')
                    ->label('Plan')
                    ->badge()
                    ->colors([
                        'secondary' => 'free',
                        'primary' => 'basic',
                        'success' => 'premium',
                        'warning' => 'enterprise',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'free' => 'Gratuito',
                        'basic' => 'Básico',
                        'premium' => 'Premium',
                        'enterprise' => 'Empresarial',
                        default => $state,
                    }),

                TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('city.name')
                    ->label('Ciudad')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('state.name')
                    ->label('Departamento')
                    ->toggleable(isToggledHiddenByDefault: true),

                BooleanColumn::make('is_active')
                    ->label('Activo')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d/M/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('subscription_plan')
                    ->label('Plan de Suscripción')
                    ->options([
                        'free' => 'Gratuito',
                        'basic' => 'Básico',
                        'premium' => 'Premium',
                        'enterprise' => 'Empresarial',
                    ])
                    ->multiple(),

                TernaryFilter::make('is_active')
                    ->label('Estado de la Empresa')
                    ->boolean()
                    ->trueLabel('Solo empresas activas')
                    ->falseLabel('Solo empresas inactivas')
                    ->native(false),

                SelectFilter::make('country_id')
                    ->label('País')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload(),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->searchOnBlur()
            ->striped();
    }
}
