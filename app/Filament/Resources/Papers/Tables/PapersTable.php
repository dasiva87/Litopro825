<?php

namespace App\Filament\Resources\Papers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Services\StockMovementService;
use Illuminate\Database\Eloquent\Builder;

class PapersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                    
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                TextColumn::make('weight')
                    ->label('Gramaje')
                    ->sortable()
                    ->suffix(' gr/m²')
                    ->alignCenter(),
                    
                TextColumn::make('dimensions')
                    ->label('Dimensiones')
                    ->getStateUsing(fn ($record) => "{$record->width} × {$record->height} cm")
                    ->alignCenter(),
                    
                TextColumn::make('area')
                    ->label('Área')
                    ->getStateUsing(fn ($record) => number_format($record->area, 2) . ' cm²')
                    ->alignCenter()
                    ->toggleable(),
                    
                TextColumn::make('cost_per_sheet')
                    ->label('Costo/Pliego')
                    ->money('COP')
                    ->sortable()
                    ->visible(function ($record) {
                        // Solo mostrar costo para papeles propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                    
                TextColumn::make('price')
                    ->label('Precio/Pliego')
                    ->money('COP')
                    ->sortable(),

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
                        return '📄 ' . ($record->company->name ?? 'N/A');
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
                    
                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->suffix(' pliegos')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 10 => 'warning',
                        $state <= 50 => 'primary',
                        default => 'success',
                    }),
                    
                TextColumn::make('is_own')
                    ->label('Propiedad')
                    ->formatStateUsing(fn (bool $state, $record): string => 
                        $state ? 'Propio' : ($record->supplier ? $record->supplier->name : 'Sin proveedor')
                    )
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'secondary'),
                    
                TextColumn::make('margin')
                    ->label('Margen')
                    ->getStateUsing(fn ($record) => $record->margin)
                    ->suffix('%')
                    ->sortable(false)
                    ->toggleable()
                    ->color(fn (float $state): string => match (true) {
                        $state < 10 => 'danger',
                        $state < 25 => 'warning',
                        default => 'success',
                    })
                    ->visible(function ($record) {
                        // Solo mostrar margen para papeles propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                    
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_own')
                    ->label('Propiedad')
                    ->placeholder('Todos')
                    ->trueLabel('Propios')
                    ->falseLabel('De Proveedores'),
                    
                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'name')
                    ->preload()
                    ->searchable(),

                SelectFilter::make('company_id')
                    ->label('Papelería')
                    ->options(function () {
                        // Solo mostrar este filtro para litografías
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                        if (!$company || !$company->isLitografia()) {
                            return [];
                        }

                        // Obtener papelerías proveedoras aprobadas + propia empresa
                        $supplierCompanies = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                            ->where('is_active', true)
                            ->whereNotNull('approved_at') // Solo relaciones aprobadas
                            ->with('supplierCompany')
                            ->get()
                            ->pluck('supplierCompany.name', 'supplier_company_id')
                            ->toArray();

                        // Agregar la empresa propia
                        $supplierCompanies[$currentCompanyId] = 'Propios';

                        return $supplierCompanies;
                    })
                    ->visible(function () {
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        return $company && $company->isLitografia();
                    }),
                    
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                    
                Filter::make('weight_range')
                    ->label('Rango de Gramaje')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('weight_from')
                                    ->label('Desde')
                                    ->numeric()
                                    ->suffix('gr/m²'),
                                TextInput::make('weight_to')
                                    ->label('Hasta')
                                    ->numeric()
                                    ->suffix('gr/m²'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['weight_from'],
                                fn (Builder $query, $weight): Builder => $query->where('weight', '>=', $weight),
                            )
                            ->when(
                                $data['weight_to'],
                                fn (Builder $query, $weight): Builder => $query->where('weight', '<=', $weight),
                            );
                    }),
                    
                Filter::make('stock_status')
                    ->label('Estado de Stock')
                    ->form([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'out' => 'Sin stock',
                                'low' => 'Stock bajo (≤ 10)',
                                'medium' => 'Stock medio (11-50)',
                                'high' => 'Stock alto (> 50)',
                            ])
                            ->placeholder('Seleccionar estado')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['status'], function (Builder $query, string $status) {
                            return match ($status) {
                                'out' => $query->where('stock', '<=', 0),
                                'low' => $query->where('stock', '>', 0)->where('stock', '<=', 10),
                                'medium' => $query->where('stock', '>', 10)->where('stock', '<=', 50),
                                'high' => $query->where('stock', '>', 50),
                                default => $query,
                            };
                        });
                    }),
                    
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('register_inbound')
                    ->label('Entrada Stock')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Cantidad de Pliegos')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->suffix('pliegos')
                            ->live()
                            ->helperText(fn ($record) => "Stock actual: {$record->stock} pliegos"),

                        Select::make('reason')
                            ->label('Motivo')
                            ->required()
                            ->options([
                                'purchase' => 'Compra',
                                'return' => 'Devolución',
                                'adjustment' => 'Ajuste de inventario',
                                'initial_stock' => 'Stock inicial',
                            ])
                            ->default('purchase'),

                        TextInput::make('unit_cost')
                            ->label('Costo por Pliego (opcional)')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->helperText('Dejar en blanco si no aplica'),

                        TextInput::make('batch_number')
                            ->label('Número de Lote (opcional)')
                            ->maxLength(255)
                            ->placeholder('Ej: PAPEL-2025-001'),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Descripción del movimiento...'),
                    ])
                    ->action(function ($record, array $data) {
                        $stockService = app(StockMovementService::class);

                        try {
                            $movement = $stockService->recordInbound(
                                stockable: $record,
                                reason: $data['reason'],
                                quantity: (int) $data['quantity'],
                                unitCost: !empty($data['unit_cost']) ? (float) $data['unit_cost'] : null,
                                batchNumber: $data['batch_number'] ?? null,
                                notes: $data['notes'] ?? null
                            );

                            Notification::make()
                                ->success()
                                ->title('Entrada de stock registrada')
                                ->body("Se agregaron {$data['quantity']} pliegos. Nuevo stock: {$movement->new_stock}")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error al registrar entrada')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(function ($record) {
                        // Solo para papeles propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),

                ViewAction::make()
                    ->visible(function ($record) {
                        // Solo permitir ver papeles propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                EditAction::make()
                    ->visible(function ($record) {
                        // Solo permitir editar papeles propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                DeleteAction::make()
                    ->visible(function ($record) {
                        // Solo permitir eliminar papeles propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            // Solo permitir eliminar papeles propios
                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                            $records->each(function ($record) use ($currentCompanyId) {
                                if ($record->company_id === $currentCompanyId) {
                                    $record->delete();
                                }
                            });
                        }),
                    ForceDeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                            $records->each(function ($record) use ($currentCompanyId) {
                                if ($record->company_id === $currentCompanyId) {
                                    $record->forceDelete();
                                }
                            });
                        }),
                    RestoreBulkAction::make()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                            $records->each(function ($record) use ($currentCompanyId) {
                                if ($record->company_id === $currentCompanyId) {
                                    $record->restore();
                                }
                            });
                        }),

                    BulkAction::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                            $records->each(function ($record) use ($currentCompanyId) {
                                // Solo permitir modificar papeles propios
                                if ($record->company_id === $currentCompanyId) {
                                    $record->update(['is_active' => !$record->is_active]);
                                }
                            });
                        }),

                    BulkAction::make('update_stock')
                        ->label('Actualizar Stock')
                        ->icon('heroicon-o-archive-box')
                        ->form([
                            TextInput::make('stock_adjustment')
                                ->label('Ajuste de Stock')
                                ->numeric()
                                ->required()
                                ->helperText('Ingresa un número positivo para agregar o negativo para reducir'),
                        ])
                        ->action(function ($records, array $data) {
                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                            $records->each(function ($record) use ($data, $currentCompanyId) {
                                // Solo permitir modificar stock de papeles propios
                                if ($record->company_id === $currentCompanyId) {
                                    $newStock = max(0, $record->stock + $data['stock_adjustment']);
                                    $record->update(['stock' => $newStock]);
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('code')
            ->recordUrl(null);
    }
}