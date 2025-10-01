<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PurchaseOrdersOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return [];
        }

        $companyId = $user->company_id;

        // Estadísticas básicas
        $totalOrders = PurchaseOrder::where('company_id', $companyId)->count();
        $pendingOrders = PurchaseOrder::where('company_id', $companyId)
            ->whereIn('status', ['draft', 'sent', 'confirmed', 'partially_received'])
            ->count();
        $completedOrders = PurchaseOrder::where('company_id', $companyId)
            ->where('status', 'completed')
            ->count();

        // Valor total pendiente
        $pendingValue = PurchaseOrder::where('company_id', $companyId)
            ->whereIn('status', ['draft', 'sent', 'confirmed', 'partially_received'])
            ->sum('total_amount');

        // Órdenes este mes
        $thisMonthOrders = PurchaseOrder::where('company_id', $companyId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Comparación con mes anterior
        $lastMonthOrders = PurchaseOrder::where('company_id', $companyId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $monthlyChange = $lastMonthOrders > 0
            ? (($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100
            : ($thisMonthOrders > 0 ? 100 : 0);

        // Proveedores únicos
        $uniqueSuppliers = PurchaseOrder::where('company_id', $companyId)
            ->distinct('supplier_company_id')
            ->count();

        return [
            Stat::make('Total de Órdenes', $totalOrders)
                ->description('Órdenes históricas')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Órdenes Pendientes', $pendingOrders)
                ->description('Requieren seguimiento')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 10 ? 'warning' : 'success'),

            Stat::make('Valor Pendiente', '$' . number_format($pendingValue, 0))
                ->description('En órdenes activas')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Este Mes', $thisMonthOrders)
                ->description($monthlyChange >= 0 ? "+{$monthlyChange}%" : "{$monthlyChange}%")
                ->descriptionIcon($monthlyChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyChange >= 0 ? 'success' : 'danger'),

            Stat::make('Órdenes Completadas', $completedOrders)
                ->description('Finalizadas exitosamente')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Proveedores Activos', $uniqueSuppliers)
                ->description('Con órdenes registradas')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->company_id !== null;
    }
}