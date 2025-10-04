<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Services\StockMovementService;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),
                    
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->description ? 
                            $record->name . "\n\n" . $record->description : 
                            $record->name;
                    }),
                    
                TextColumn::make('supplier_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($record) => $record->is_own_product ? 'success' : 'info')
                    ->toggleable(),
                    
                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->placeholder('Producto propio')
                    ->toggleable(),

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
                        return '🏪 ' . ($record->company->name ?? 'N/A');
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
                    
                TextColumn::make('purchase_price')
                    ->label('Precio Compra')
                    ->money('COP')
                    ->sortable()
                    ->toggleable()
                    ->visible(function ($record) {
                        // Solo mostrar precio de compra para productos propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                    
                TextColumn::make('sale_price')
                    ->label('Precio Venta')
                    ->money('COP')
                    ->sortable()
                    ->fontFamily('mono')
                    ->color('success'),
                    
                TextColumn::make('profit_margin')
                    ->label('Margen')
                    ->getStateUsing(fn ($record) => number_format($record->getProfitMargin(), 1) . '%')
                    ->badge()
                    ->color(function ($record) {
                        $margin = $record->getProfitMargin();
                        if ($margin >= 50) return 'success';
                        if ($margin >= 25) return 'warning';
                        return 'danger';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("((sale_price - purchase_price) / NULLIF(purchase_price, 0) * 100) {$direction}");
                    })
                    ->toggleable()
                    ->visible(function ($record) {
                        // Solo mostrar margen para productos propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                    
                TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->suffix(' uds')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->stock_status_color),
                    
                TextColumn::make('stock_status_label')
                    ->label('Estado Stock')
                    ->badge()
                    ->color(fn ($record) => $record->stock_status_color)
                    ->toggleable(),
                    
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                
                Tables\Filters\SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activos',
                        0 => 'Inactivos',
                    ]),
                    
                Tables\Filters\SelectFilter::make('is_own_product')
                    ->label('Tipo de Producto')
                    ->options([
                        1 => 'Productos Propios',
                        0 => 'Productos de Terceros',
                    ]),

                Tables\Filters\SelectFilter::make('company_id')
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
                    
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Estado del Stock')
                    ->options([
                        'sin_stock' => 'Sin Stock',
                        'stock_bajo' => 'Stock Bajo',
                        'stock_normal' => 'Stock Normal',
                    ])
                    ->query(function ($query, $data) {
                        if (!$data['value']) return $query;
                        
                        return match($data['value']) {
                            'sin_stock' => $query->where('stock', 0),
                            'stock_bajo' => $query->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0),
                            'stock_normal' => $query->whereColumn('stock', '>', 'min_stock'),
                            default => $query,
                        };
                    }),
                    
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock Bajo')
                    ->query(fn ($query) => $query->whereColumn('stock', '<=', 'min_stock'))
                    ->toggle(),
            ])
            ->actions([
                Action::make('register_inbound')
                    ->label('Entrada Stock')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->suffix('uds')
                            ->live()
                            ->helperText(fn ($record) => "Stock actual: {$record->stock} uds"),

                        Select::make('reason')
                            ->label('Motivo')
                            ->required()
                            ->options([
                                'purchase' => 'Compra',
                                'return' => 'Devolución',
                                'adjustment' => 'Ajuste de inventario',
                                'production' => 'Producción',
                                'initial_stock' => 'Stock inicial',
                            ])
                            ->default('purchase'),

                        TextInput::make('unit_cost')
                            ->label('Costo Unitario (opcional)')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->helperText('Dejar en blanco si no aplica'),

                        TextInput::make('batch_number')
                            ->label('Número de Lote (opcional)')
                            ->maxLength(255)
                            ->placeholder('Ej: LOTE-2025-001'),

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
                                ->body("Se agregaron {$data['quantity']} unidades. Nuevo stock: {$movement->new_stock}")
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
                        // Solo para productos propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),

                EditAction::make()
                    ->visible(function ($record) {
                        // Solo permitir editar productos propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                DeleteAction::make()
                    ->visible(function ($record) {
                        // Solo permitir eliminar productos propios
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                ForceDeleteAction::make()
                    ->visible(function ($record) {
                        if (!$record || !isset($record->company_id)) {
                            return false;
                        }
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        return $record->company_id === $currentCompanyId;
                    }),
                RestoreAction::make()
                    ->visible(function ($record) {
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
                            // Solo permitir eliminar productos propios
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
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('No hay productos registrados')
            ->emptyStateDescription('Comienza agregando tu primer producto al inventario.')
;
    }
}