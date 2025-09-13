<?php

namespace App\Filament\Pages;

use BackedEnum;
use App\Filament\Widgets\ActiveDocumentsWidget;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\DeadlinesWidget;
use App\Filament\Widgets\PaperCalculatorWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\SocialFeedWidget;
use App\Filament\Widgets\StockAlertsWidget;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Product;
use Filament\Pages\Page;

class LitoproDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';
    
    protected string $view = 'filament.pages.litopro-dashboard';
    
    protected static ?string $title = 'Dashboard - LitoPro';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $slug = 'dashboard';
    
    protected static bool $shouldRegisterNavigation = true;
    
    public function getMainWidgets(): array
    {
        return [
            QuickActionsWidget::class,
            ActiveDocumentsWidget::class,
            SocialFeedWidget::class,
        ];
    }
    
    public function getSidebarWidgets(): array
    {
        return [
            StockAlertsWidget::class,
            DeadlinesWidget::class,
            PaperCalculatorWidget::class,
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
    
    // Métodos para obtener datos dinámicos usados en la vista
    public function getActiveQuotations(): int
    {
        return Document::where('company_id', auth()->user()->company_id)
            ->whereHas('documentType', function ($query) {
                $query->where('code', 'QUOTE');
            })
            ->whereIn('status', ['sent', 'approved'])
            ->count();
    }
    
    public function getProductionOrders(): int
    {
        return Document::where('company_id', auth()->user()->company_id)
            ->whereIn('status', ['in_production', 'approved'])
            ->count();
    }
    
    public function getMonthlyRevenue(): float
    {
        $revenue = Document::where('company_id', auth()->user()->company_id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['completed'])
            ->sum('total');
            
        return (float) $revenue / 1000000; // Convertir a millones
    }
    
    public function getActiveClients(): int
    {
        return Contact::where('company_id', auth()->user()->company_id)
            ->where('type', 'customer')
            ->whereHas('documents', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })
            ->count();
    }
    
    public function getCriticalStock(): int
    {
        return Product::where('company_id', auth()->user()->company_id)
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();
    }
    
    public function getRecentActivity()
    {
        return Document::where('company_id', auth()->user()->company_id)
            ->with(['contact'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->document_number,
                    'title' => $document->reference ?? 'Documento #' . $document->document_number,
                    'client' => $document->contact->name ?? 'Cliente N/A',
                    'status' => $document->status,
                    'status_label' => $this->getStatusLabel($document->status),
                    'status_color' => $this->getStatusColor($document->status),
                    'total' => $document->total,
                    'created_at' => $document->created_at,
                    'time_diff' => $document->created_at->diffForHumans()
                ];
            });
    }

    protected function getStatusLabel($status): string
    {
        return match($status) {
            'draft' => 'BORRADOR',
            'sent' => 'ENVIADA',
            'approved' => 'APROBADA',
            'in_production' => 'PRODUCCIÓN',
            'completed' => 'COMPLETADA',
            'cancelled' => 'CANCELADA',
            default => 'PENDIENTE'
        };
    }

    protected function getStatusColor($status): string
    {
        return match($status) {
            'draft' => 'gray',
            'sent' => 'yellow',
            'approved' => 'green',
            'in_production' => 'orange',
            'completed' => 'blue',
            'cancelled' => 'red',
            default => 'gray'
        };
    }
}