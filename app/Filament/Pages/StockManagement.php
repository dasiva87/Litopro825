<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Models\Product;
use App\Models\Paper;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Services\StockAlertService;
use App\Services\StockPredictionService;
use App\Services\StockReportService;
use App\Services\StockNotificationService;
use App\Filament\Widgets\StockKpisWidget;
use App\Filament\Widgets\StockPredictionsWidget;
use App\Filament\Widgets\RecentMovementsWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\StockLevelTrackingWidget;
use App\Filament\Widgets\StockTrendsChartWidget;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use UnitEnum;

class StockManagement extends Page
{
    protected string $view = 'filament.pages.stock-management';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Gestión de Stock';

    protected static ?string $title = 'Dashboard de Gestión de Stock';

    protected static ?int $navigationSort = 3;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::INVENTORY;

    protected ?string $pollingInterval = '30s';

    protected StockAlertService $alertService;
    protected StockPredictionService $predictionService;
    protected StockReportService $reportService;
    protected StockNotificationService $notificationService;

    public function boot(): void
    {
        $this->alertService = app(StockAlertService::class);
        $this->predictionService = app(StockPredictionService::class);
        $this->reportService = app(StockReportService::class);
        $this->notificationService = app(StockNotificationService::class);
    }

    #[Computed]
    public function stockKpis(): array
    {
        $companyId = auth()->user()->company_id;

        $totalProducts = Product::where('company_id', $companyId)->where('active', true)->count();
        $totalPapers = Paper::where('company_id', $companyId)->where('is_active', true)->count();

        $lowStockItems = Product::where('company_id', $companyId)
            ->where('active', true)
            ->lowStock()
            ->count() +
            Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->lowStock()
            ->count();

        $outOfStockItems = Product::where('company_id', $companyId)
            ->where('active', true)
            ->outOfStock()
            ->count() +
            Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->outOfStock()
            ->count();

        $criticalAlerts = StockAlert::where('company_id', $companyId)
            ->critical()
            ->active()
            ->count();

        return [
            'total_items' => $totalProducts + $totalPapers,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'critical_alerts' => $criticalAlerts,
            'stock_coverage_days' => $this->calculateStockCoverageDays(),
        ];
    }

    #[Computed]
    public function stockTrends(): array
    {
        $companyId = auth()->user()->company_id;
        $last30Days = now()->subDays(30);

        // Movimientos de los últimos 30 días agrupados por día
        $movements = StockMovement::where('company_id', $companyId)
            ->where('created_at', '>=', $last30Days)
            ->selectRaw('DATE(created_at) as date, type, SUM(quantity) as total_quantity')
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        $trends = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayMovements = $movements->get($date, collect());

            $trends[] = [
                'date' => $date,
                'in' => $dayMovements->where('type', 'in')->sum('total_quantity'),
                'out' => $dayMovements->where('type', 'out')->sum('total_quantity'),
            ];
        }

        return $trends;
    }

    #[Computed]
    public function stockPredictions(): array
    {
        return $this->predictionService->getReorderAlerts(
            auth()->user()->company_id,
            30 // 30 días de predicción
        );
    }

    #[Computed]
    public function recentMovements(): array
    {
        return StockMovement::where('company_id', auth()->user()->company_id)
            ->with(['stockable', 'user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'item_name' => $movement->stockable->name,
                    'item_type' => $movement->stockable_type === Product::class ? 'Producto' : 'Papel',
                    'type' => $movement->type,
                    'type_label' => $movement->type === 'in' ? 'Entrada' : 'Salida',
                    'quantity' => $movement->quantity,
                    'reason' => $movement->reason,
                    'user_name' => $movement->user->name ?? 'Sistema',
                    'created_at' => $movement->created_at->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    #[Computed]
    public function criticalAlerts(): array
    {
        return $this->alertService->getAlertsSummary();
    }

    protected function calculateStockCoverageDays(): int
    {
        $companyId = auth()->user()->company_id;
        $last30Days = now()->subDays(30);

        // Calcular consumo promedio diario
        $totalConsumption = StockMovement::where('company_id', $companyId)
            ->where('type', 'out')
            ->where('created_at', '>=', $last30Days)
            ->sum('quantity');

        $avgDailyConsumption = $totalConsumption / 30;

        if ($avgDailyConsumption <= 0) {
            return 999; // Stock infinito si no hay consumo
        }

        // Calcular stock total actual
        $totalStock = Product::where('company_id', $companyId)
            ->where('active', true)
            ->sum('stock') +
            Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->sum('stock');

        return (int) ($totalStock / $avgDailyConsumption);
    }

    public function refreshData(): void
    {
        // Refrescar alertas
        $this->alertService->evaluateAllAlerts(auth()->user()->company_id);

        // Limpiar computed properties cache
        unset($this->stockKpis);
        unset($this->stockTrends);
        unset($this->stockPredictions);
        unset($this->recentMovements);
        unset($this->criticalAlerts);

        $this->js('$refresh');
    }

    public function generateReport(string $format = 'json'): void
    {
        try {
            $report = $this->reportService->generateInventoryReport(auth()->user()->company_id);
            $fileName = "stock_report_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            $content = $this->reportService->exportReport($report, $format);

            // Crear directorio si no existe
            $reportsPath = storage_path('app/reports');
            if (!file_exists($reportsPath)) {
                mkdir($reportsPath, 0755, true);
            }

            // Guardar archivo
            file_put_contents("{$reportsPath}/{$fileName}", $content);

            // Notificar al usuario
            $this->dispatch('report-generated', [
                'message' => "Reporte generado exitosamente: {$fileName}",
                'file' => $fileName
            ]);

        } catch (\Exception $e) {
            $this->dispatch('report-error', [
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ]);
        }
    }

    #[Computed]
    public function liveNotifications(): array
    {
        $stats = $this->notificationService->getNotificationStats(
            auth()->user()->company_id,
            7
        );

        // Obtener notificaciones recientes del usuario actual
        $userNotifications = auth()->user()
            ->notifications()
            ->whereIn('type', [
                'App\Notifications\StockAlertNotification',
                'App\Notifications\DepletionPredictionNotification'
            ])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'stock_alert',
                    'title' => $data['title'] ?? 'Alerta de Stock',
                    'message' => $data['message'] ?? '',
                    'is_read' => !is_null($notification->read_at),
                    'created_at' => $notification->created_at->format('d/m/Y H:i'),
                    'action_url' => $data['action_url'] ?? null,
                    'priority' => $data['severity'] ?? 'medium',
                ];
            })
            ->toArray();

        return [
            'stats' => $stats,
            'recent' => $userNotifications,
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ];
    }

    public function markNotificationAsRead(string $notificationId): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);

        // Refrescar notificaciones
        unset($this->liveNotifications);
    }

    public function markAllNotificationsAsRead(): void
    {
        auth()->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        // Refrescar notificaciones
        unset($this->liveNotifications);

        $this->dispatch('notifications-updated', [
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }

    public function sendTestNotification(): void
    {
        try {
            $this->notificationService->sendTestNotification(auth()->user());

            $this->dispatch('notification-sent', [
                'message' => 'Notificación de prueba enviada exitosamente'
            ]);

            // Refrescar notificaciones después de un momento
            $this->js('setTimeout(() => $wire.dispatch("refresh-notifications"), 2000)');

        } catch (\Exception $e) {
            $this->dispatch('notification-error', [
                'message' => 'Error al enviar notificación: ' . $e->getMessage()
            ]);
        }
    }

    #[Computed]
    public function recentReports(): array
    {
        $reportsPath = storage_path('app/reports');
        if (!is_dir($reportsPath)) {
            return [];
        }

        $files = glob($reportsPath . '/stock_report_*.{json,csv,html}', GLOB_BRACE);

        return collect($files)
            ->sortByDesc('mtime')
            ->take(5)
            ->map(function ($file) {
                $fileName = basename($file);
                $fileTime = filemtime($file);
                $fileSize = filesize($file);

                return [
                    'name' => $fileName,
                    'created_at' => date('d/m/Y H:i', $fileTime),
                    'size' => $this->formatFileSize($fileSize),
                    'format' => pathinfo($file, PATHINFO_EXTENSION),
                    'path' => $file,
                ];
            })
            ->values()
            ->toArray();
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Widgets are now manually rendered in the Blade template
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Actualizar Datos')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn() => $this->refreshData()),

            \Filament\Actions\Action::make('generate_json')
                ->label('Generar JSON')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn() => $this->generateReport('json')),

            \Filament\Actions\Action::make('generate_csv')
                ->label('Generar CSV')
                ->icon('heroicon-o-table-cells')
                ->color('warning')
                ->action(fn() => $this->generateReport('csv')),
        ];
    }
}
