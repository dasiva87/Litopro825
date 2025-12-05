<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Paper;
use App\Models\StockMovement;
use App\Models\PurchaseOrder;
use App\Models\Contact;
use App\Services\TenantContext;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class QuickActionsWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.widgets.quick-actions-widget';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'lg' => 2,
    ];

    protected static bool $isLazy = false;

    public function stockEntryAction(): Action
    {
        return Action::make('stock_entry')
                ->label('Realizar Entrada de Stock')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('item_type')
                        ->label('Tipo de Item')
                        ->options([
                            'product' => 'Producto',
                            'paper' => 'Papel',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('item_id', null)),

                    Select::make('item_id')
                        ->label('Item')
                        ->options(function (callable $get) {
                            $type = $get('item_type');
                            if (!$type) {
                                return [];
                            }

                            $companyId = TenantContext::id();

                            if ($type === 'product') {
                                return Product::where('company_id', $companyId)
                                    ->where('active', true)
                                    ->pluck('name', 'id');
                            } else {
                                return Paper::where('company_id', $companyId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            }
                        })
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            if (!$state) return;

                            $type = $get('item_type');
                            $companyId = TenantContext::id();

                            if ($type === 'product') {
                                $item = Product::where('company_id', $companyId)->find($state);
                            } else {
                                $item = Paper::where('company_id', $companyId)->find($state);
                            }

                            if ($item) {
                                $set('current_stock', $item->stock ?? 0);
                            }
                        }),

                    TextInput::make('current_stock')
                        ->label('Stock Actual')
                        ->disabled()
                        ->dehydrated(false)
                        ->suffix('unidades'),

                    TextInput::make('quantity')
                        ->label('Cantidad a Ingresar')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->suffix('unidades')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $current = (int) ($get('current_stock') ?? 0);
                            $quantity = (int) ($state ?? 0);
                            $set('new_stock', $current + $quantity);
                        }),

                    TextInput::make('new_stock')
                        ->label('Stock Resultante')
                        ->disabled()
                        ->dehydrated(false)
                        ->suffix('unidades'),

                    Select::make('reason')
                        ->label('Razón de Entrada')
                        ->options([
                            'purchase' => 'Compra',
                            'return' => 'Devolución',
                            'adjustment' => 'Ajuste de Inventario',
                            'transfer' => 'Transferencia',
                        ])
                        ->required()
                        ->default('purchase'),

                    TextInput::make('reference')
                        ->label('Referencia/Número de Documento')
                        ->placeholder('Ej: PO-123, OC-456')
                        ->maxLength(100),

                    Textarea::make('notes')
                        ->label('Notas')
                        ->placeholder('Información adicional sobre esta entrada...')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        DB::beginTransaction();

                        $companyId = TenantContext::id();
                        $itemType = $data['item_type'];
                        $itemId = $data['item_id'];

                        // Obtener el item
                        if ($itemType === 'product') {
                            $item = Product::where('company_id', $companyId)->findOrFail($itemId);
                            $stockableType = Product::class;
                        } else {
                            $item = Paper::where('company_id', $companyId)->findOrFail($itemId);
                            $stockableType = Paper::class;
                        }

                        // Calcular nuevo stock
                        $stockBefore = $item->stock ?? 0;
                        $newStock = $stockBefore + $data['quantity'];

                        // Actualizar stock del item
                        $item->update(['stock' => $newStock]);

                        // Registrar movimiento
                        StockMovement::create([
                            'company_id' => $companyId,
                            'stockable_type' => $stockableType,
                            'stockable_id' => $itemId,
                            'type' => 'in',
                            'quantity' => $data['quantity'],
                            'stock_before' => $stockBefore,
                            'stock_after' => $newStock,
                            'reason' => $data['reason'],
                            'reference' => $data['reference'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'user_id' => auth()->id(),
                        ]);

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('Entrada de Stock Registrada')
                            ->body("Se ingresaron {$data['quantity']} unidades de {$item->name}. Stock actual: {$newStock}")
                            ->send();

                        // Refresh page
                        $this->dispatch('$refresh');

                    } catch (\Exception $e) {
                        DB::rollBack();

                        Notification::make()
                            ->danger()
                            ->title('Error al Registrar Entrada')
                            ->body('Ocurrió un error: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->modalHeading('Registrar Entrada de Stock')
                ->modalSubmitActionLabel('Registrar Entrada')
                ->modalWidth('2xl');
    }

    public function viewCriticalAction(): Action
    {
        return Action::make('view_critical')
                ->label('Ver Items Críticos')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url(function () {
                    return route('filament.admin.resources.products.index', [
                        'tableFilters' => [
                            'stock_status' => ['value' => 'out'],
                        ],
                    ]);
                })
                ->openUrlInNewTab();
    }

    public function generatePurchaseOrderAction(): Action
    {
        return Action::make('generate_purchase_order')
                ->label('Generar Orden de Compra')
                ->icon('heroicon-o-shopping-cart')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generar Orden de Compra Automática')
                ->modalDescription('Se generará una orden de compra con todos los productos que tienen stock bajo o sin stock.')
                ->form([
                    Select::make('supplier_id')
                        ->label('Proveedor')
                        ->options(function () {
                            $companyId = TenantContext::id();
                            return Contact::where('company_id', $companyId)
                                ->where('type', 'supplier')
                                ->where('is_active', true)
                                ->pluck('business_name', 'id');
                        })
                        ->searchable()
                        ->required()
                        ->placeholder('Seleccione un proveedor'),

                    Textarea::make('notes')
                        ->label('Notas de la Orden')
                        ->placeholder('Notas adicionales para la orden de compra...')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        DB::beginTransaction();

                        $companyId = TenantContext::id();

                        // Obtener productos con stock bajo
                        $lowStockProducts = Product::where('company_id', $companyId)
                            ->where('active', true)
                            ->lowStock()
                            ->get();

                        $outOfStockProducts = Product::where('company_id', $companyId)
                            ->where('active', true)
                            ->outOfStock()
                            ->get();

                        $criticalProducts = $lowStockProducts->merge($outOfStockProducts)->unique('id');

                        if ($criticalProducts->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Sin Items Críticos')
                                ->body('No hay productos con stock bajo o sin stock en este momento.')
                                ->send();
                            return;
                        }

                        // Construir lista detallada de productos para las notas
                        $productsList = "PRODUCTOS CON STOCK CRÍTICO:\n\n";
                        $totalAmount = 0;

                        foreach ($criticalProducts as $product) {
                            $quantity = max($product->min_stock * 2, 100); // Duplicar mínimo o 100 unidades
                            $unitPrice = $product->cost_price ?? 0;
                            $subtotal = $quantity * $unitPrice;
                            $totalAmount += $subtotal;

                            $status = $product->stock <= 0 ? 'SIN STOCK' : 'STOCK BAJO';
                            $productsList .= sprintf(
                                "- %s [%s]\n  Stock actual: %d, Mínimo: %d\n  Sugerido: %d unidades @ $%s = $%s\n\n",
                                $product->name,
                                $status,
                                $product->stock ?? 0,
                                $product->min_stock ?? 0,
                                $quantity,
                                number_format($unitPrice, 2),
                                number_format($subtotal, 2)
                            );
                        }

                        $productsList .= sprintf(
                            "TOTAL ESTIMADO: $%s\n\n%s",
                            number_format($totalAmount, 2),
                            $data['notes'] ?? ''
                        );

                        // Crear orden de compra
                        $purchaseOrder = PurchaseOrder::create([
                            'company_id' => $companyId,
                            'supplier_id' => $data['supplier_id'],
                            'order_date' => now(),
                            'status' => 'pending',
                            'total_amount' => $totalAmount,
                            'notes' => $productsList,
                            'created_by' => auth()->id(),
                        ]);

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('Orden de Compra Generada')
                            ->body("Se creó la orden {$purchaseOrder->order_number} con {$criticalProducts->count()} productos críticos. Total estimado: $" . number_format($totalAmount, 2))
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Ver Orden')
                                    ->url(route('filament.admin.resources.purchase-orders.edit', ['record' => $purchaseOrder->id]))
                                    ->button(),
                            ])
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();

                        Notification::make()
                            ->danger()
                            ->title('Error al Generar Orden')
                            ->body('Ocurrió un error: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->modalSubmitActionLabel('Generar Orden');
    }

    public function downloadReportAction(): Action
    {
        return Action::make('download_report')
                ->label('Descargar Reporte')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->form([
                    Select::make('report_type')
                        ->label('Tipo de Reporte')
                        ->options([
                            'stock_summary' => 'Resumen de Stock',
                            'low_stock' => 'Productos con Stock Bajo',
                            'movements' => 'Movimientos de Stock',
                            'alerts' => 'Alertas Activas',
                        ])
                        ->required()
                        ->default('stock_summary'),

                    Select::make('format')
                        ->label('Formato')
                        ->options([
                            'csv' => 'CSV (Excel)',
                            'pdf' => 'PDF',
                        ])
                        ->required()
                        ->default('csv'),

                    Select::make('date_range')
                        ->label('Rango de Fechas')
                        ->options([
                            '7' => 'Últimos 7 días',
                            '30' => 'Últimos 30 días',
                            '90' => 'Últimos 90 días',
                            'all' => 'Todo el historial',
                        ])
                        ->required()
                        ->default('30')
                        ->visible(fn (callable $get) => in_array($get('report_type'), ['movements', 'alerts'])),
                ])
                ->action(function (array $data) {
                    $reportType = $data['report_type'];
                    $format = $data['format'];
                    $dateRange = $data['date_range'] ?? 'all';

                    if ($format === 'csv') {
                        return $this->downloadCSVReport($reportType, $dateRange);
                    } else {
                        Notification::make()
                            ->info()
                            ->title('Generación de PDF')
                            ->body('La generación de reportes en PDF estará disponible próximamente.')
                            ->send();
                    }
                })
                ->modalHeading('Descargar Reporte de Stock')
                ->modalSubmitActionLabel('Descargar')
                ->modalWidth('lg');
    }

    protected function downloadCSVReport(string $reportType, string $dateRange)
    {
        $companyId = TenantContext::id();
        $csvData = [];

        // Determinar fecha de inicio según rango
        $startDate = match($dateRange) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            default => null,
        };

        switch ($reportType) {
            case 'stock_summary':
                $csvData[] = ['Código', 'Nombre', 'Stock Actual', 'Stock Mínimo', 'Estado', 'Valor Inventario'];

                $products = Product::where('company_id', $companyId)
                    ->where('active', true)
                    ->orderBy('name')
                    ->get();

                foreach ($products as $product) {
                    $status = match(true) {
                        $product->stock <= 0 => 'Sin Stock',
                        $product->stock <= $product->min_stock => 'Stock Bajo',
                        default => 'Normal'
                    };

                    $csvData[] = [
                        $product->code ?? 'N/A',
                        $product->name,
                        $product->stock ?? 0,
                        $product->min_stock ?? 0,
                        $status,
                        number_format(($product->stock ?? 0) * ($product->cost_price ?? 0), 2),
                    ];
                }
                break;

            case 'low_stock':
                $csvData[] = ['Código', 'Nombre', 'Stock Actual', 'Stock Mínimo', 'Diferencia', 'Sugerido Comprar'];

                $products = Product::where('company_id', $companyId)
                    ->where('active', true)
                    ->lowStock()
                    ->orderBy('stock')
                    ->get();

                foreach ($products as $product) {
                    $difference = ($product->min_stock ?? 0) - ($product->stock ?? 0);
                    $suggested = max($difference, ($product->min_stock ?? 0) * 2);

                    $csvData[] = [
                        $product->code ?? 'N/A',
                        $product->name,
                        $product->stock ?? 0,
                        $product->min_stock ?? 0,
                        $difference,
                        $suggested,
                    ];
                }
                break;

            case 'movements':
                $csvData[] = ['Fecha', 'Tipo', 'Item', 'Cantidad', 'Stock Anterior', 'Stock Resultante', 'Razón', 'Usuario'];

                $query = StockMovement::where('company_id', $companyId)
                    ->with(['stockable', 'user'])
                    ->orderByDesc('created_at');

                if ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                }

                $movements = $query->get();

                foreach ($movements as $movement) {
                    $csvData[] = [
                        $movement->created_at->format('d/m/Y H:i'),
                        $movement->type === 'in' ? 'Entrada' : 'Salida',
                        $movement->stockable->name ?? 'N/A',
                        $movement->quantity,
                        $movement->stock_before ?? 0,
                        $movement->stock_after ?? 0,
                        match($movement->reason) {
                            'purchase' => 'Compra',
                            'sale' => 'Venta',
                            'return' => 'Devolución',
                            'adjustment' => 'Ajuste',
                            default => ucfirst($movement->reason ?? 'N/A'),
                        },
                        $movement->user->name ?? 'Sistema',
                    ];
                }
                break;

            case 'alerts':
                $csvData[] = ['Fecha Activación', 'Item', 'Tipo', 'Severidad', 'Stock Actual', 'Stock Mínimo', 'Estado'];

                $query = \App\Models\StockAlert::where('company_id', $companyId)
                    ->with(['stockable'])
                    ->orderByDesc('triggered_at');

                if ($startDate) {
                    $query->where('triggered_at', '>=', $startDate);
                }

                $alerts = $query->get();

                foreach ($alerts as $alert) {
                    $csvData[] = [
                        $alert->triggered_at->format('d/m/Y H:i'),
                        $alert->stockable->name ?? 'N/A',
                        $alert->type_label,
                        $alert->severity_label,
                        $alert->current_stock,
                        $alert->min_stock ?? 0,
                        $alert->status_label,
                    ];
                }
                break;
        }

        // Generar archivo CSV
        $filename = 'reporte_' . $reportType . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $csvContent = '';

        foreach ($csvData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
