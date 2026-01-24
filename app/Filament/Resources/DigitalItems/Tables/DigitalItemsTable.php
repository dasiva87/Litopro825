<?php

namespace App\Filament\Resources\DigitalItems\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\DigitalItem;
use Illuminate\Database\Eloquent\Builder;

class DigitalItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('pricing_type')
                    ->label('Tipo de Valoración')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unit' => 'success',
                        'size' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unit' => 'Por Unidad',
                        'size' => 'Por Tamaño',
                        default => $state,
                    }),

                TextColumn::make('sale_price')
                    ->label('Precio de Venta')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('supplier_type')
                    ->label('Tipo de Producto')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Producto Propio' => 'success',
                        'Producto de Terceros' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->default('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        return '💻 ' . ($record->company->name ?? 'N/A');
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
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->tooltip(fn (bool $state): string => $state ? 'Visible para clientes' : 'Solo uso interno'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('pricing_type')
                    ->label('Tipo de Valoración')
                    ->options(DigitalItem::getPricingTypeOptions()),

                TernaryFilter::make('is_own_product')
                    ->label('Tipo de Producto')
                    ->placeholder('Todos los productos')
                    ->trueLabel('Solo productos propios')
                    ->falseLabel('Solo productos de terceros'),

                TernaryFilter::make('active')
                    ->label('Estado')
                    ->placeholder('Todos los estados')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->actions([
                EditAction::make()
                    ->tooltip('Editar item digital'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No hay items digitales registrados')
            ->emptyStateDescription('Cree su primer item digital para comenzar a incluirlo en cotizaciones.')
            ->emptyStateIcon('heroicon-o-computer-desktop');
    }
}