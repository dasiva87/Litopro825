<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre del Rol')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label('Permisos Asignados')
                    ->counts('permissions')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('users_count')
                    ->label('Usuarios con este Rol')
                    ->getStateUsing(function ($record) {
                        // Obtener el tenant_id actual
                        $tenantId = config('app.current_tenant_id') ?? auth()->user()->company_id;

                        // Contar solo usuarios de la empresa actual
                        return $record->users()
                            ->where('company_id', $tenantId)
                            ->count();
                    })
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('permissions.name')
                    ->label('Permisos Principales')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn($record) => auth()->user()->can('update', $record)),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn($record) => auth()->user()->can('delete', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Rol')
                    ->modalDescription('¿Estás seguro de que quieres eliminar este rol? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('No hay roles disponibles')
            ->emptyStateDescription('Crea un nuevo rol para empezar a gestionar permisos.')
            ->striped();
    }
}