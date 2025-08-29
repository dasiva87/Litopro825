<?php

namespace App\Filament\Pages;

use BackedEnum;
use App\Filament\Widgets\ActiveDocumentsWidget;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\DeadlinesWidget;
use App\Filament\Widgets\PaperCalculatorWidget;
use App\Filament\Widgets\SocialFeedWidget;
use App\Filament\Widgets\StockAlertsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class LitoproDashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard - LitoPro';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';
    
    protected static ?int $navigationSort = 1;
    
    public function getWidgets(): array
    {
        return [
            // Panel Central - Estadísticas, Documentos y Red Social
            DashboardStatsWidget::class,
            ActiveDocumentsWidget::class,
            SocialFeedWidget::class,
            
            // Sidebar Derecho - Herramientas Especializadas  
            StockAlertsWidget::class,
            DeadlinesWidget::class,
            PaperCalculatorWidget::class,
        ];
    }
    
    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            ActiveDocumentsWidget::class,
        ];
    }
    
    public function getTitle(): string
    {
        $company = auth()->user()->company;
        $greeting = $this->getGreeting();
        
        return "{$greeting}, " . auth()->user()->name . " - {$company->name}";
    }
    
    private function getGreeting(): string
    {
        $hour = now()->hour;
        
        if ($hour < 12) {
            return '🌅 Buenos días';
        } elseif ($hour < 18) {
            return '☀️ Buenas tardes';
        } else {
            return '🌙 Buenas noches';
        }
    }
    
    public function getSubheading(): ?string
    {
        $company = auth()->user()->company;
        
        return "📍 {$company->city}, {$company->state} • " .
               "📊 Dashboard de Litografía • " .
               "📅 " . now()->locale('es')->isoFormat('dddd, MMMM Do YYYY');
    }
}