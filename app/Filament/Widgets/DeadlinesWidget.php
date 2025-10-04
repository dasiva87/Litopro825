<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeadlinesWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Entregas hoy
        $todayCount = Document::forCurrentTenant()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', today())
            ->count();

        // Próximos 3 días
        $next3Days = Document::forCurrentTenant()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [today()->addDay(), today()->addDays(3)])
            ->count();

        // Próxima semana
        $nextWeek = Document::forCurrentTenant()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [today()->addDays(4), today()->addDays(7)])
            ->count();

        // Vencidos
        $overdue = Document::forCurrentTenant()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->count();

        return [
            Stat::make('📅 Entregas Hoy', number_format($todayCount))
                ->description($todayCount > 0 ? 'Requieren atención urgente' : 'Sin entregas')
                ->descriptionIcon($todayCount > 0 ? 'heroicon-m-clock' : 'heroicon-m-check')
                ->color($todayCount > 0 ? 'danger' : 'success')
                ->chart($this->getTodayTrend()),

            Stat::make('⏰ Próximos 3 Días', number_format($next3Days))
                ->description('Entregar esta semana')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($next3Days > 0 ? 'warning' : 'success'),

            Stat::make('📆 Próxima Semana', number_format($nextWeek))
                ->description('Planificar producción')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('🚨 Vencidos', number_format($overdue))
                ->description($overdue > 0 ? 'Acción inmediata' : 'Todo al día')
                ->descriptionIcon($overdue > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdue > 0 ? 'danger' : 'success'),
        ];
    }

    private function getTodayTrend(): array
    {
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $count = Document::forCurrentTenant()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', $date)
                ->count();

            $trend[] = $count;
        }

        return $trend;
    }
}