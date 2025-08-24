<?php

namespace App\Filament\Resources\Users\Tables;

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

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),
                    
                TextColumn::make('company.name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->colors([
                        'primary' => 'Super Admin',
                        'success' => 'Company Admin',
                        'warning' => 'Manager',
                        'secondary' => 'Salesperson',
                        'info' => 'Operator',
                    ])
                    ->separator(', '),
                    
                TextColumn::make('position')
                    ->label('Cargo')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->icon('heroicon-m-phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                BooleanColumn::make('is_active')
                    ->label('Activo')
                    ->sortable(),
                    
                TextColumn::make('last_login_at')
                    ->label('Último Acceso')
                    ->dateTime('d/M/Y H:i')
                    ->sortable()
                    ->placeholder('Nunca'),
                    
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->dateTime('d/M/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Empresa')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),
                    
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->native(false),
                    
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