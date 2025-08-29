<?php

namespace App\Filament\Widgets;

use App\Models\Deadline;
use App\Models\Document;
use App\Models\PaperOrder;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class DeadlinesWidget extends Widget
{
    protected string $view = 'filament.widgets.deadlines';
    
    protected static ?int $sort = 11;
    
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
    
    public function getUpcomingDeadlines(): Collection
    {
        // Get deadlines from the Deadline model
        $modelDeadlines = Deadline::where('company_id', auth()->user()->company_id)
            ->pending()
            ->where('deadline_date', '>=', now())
            ->where('deadline_date', '<=', now()->addDays(15))
            ->orderBy('deadline_date')
            ->with('deadlinable')
            ->get();
        
        // Get document-based deadlines
        $documentDeadlines = $this->getDocumentDeadlines();
        
        // Get paper order deadlines
        $paperOrderDeadlines = $this->getPaperOrderDeadlines();
        
        // Combine and sort all deadlines
        $allDeadlines = collect()
            ->merge($modelDeadlines->map([$this, 'formatModelDeadline']))
            ->merge($documentDeadlines)
            ->merge($paperOrderDeadlines)
            ->sortBy('deadline_date')
            ->take(10);
            
        return $allDeadlines;
    }
    
    public function getOverdueDeadlines(): Collection
    {
        $modelDeadlines = Deadline::where('company_id', auth()->user()->company_id)
            ->where('status', 'pending')
            ->where('deadline_date', '<', now())
            ->orderBy('deadline_date', 'desc')
            ->with('deadlinable')
            ->limit(5)
            ->get();
            
        return $modelDeadlines->map([$this, 'formatModelDeadline']);
    }
    
    private function getDocumentDeadlines(): Collection
    {
        return Document::where('company_id', auth()->user()->company_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(15))
            ->with(['contact', 'documentType'])
            ->get()
            ->map(function ($document) {
                return [
                    'id' => 'doc_' . $document->id,
                    'title' => "Entrega: {$document->document_number}",
                    'description' => "Cliente: {$document->contact->name}",
                    'deadline_date' => $document->due_date,
                    'type' => 'document_delivery',
                    'type_label' => 'Entrega de Documento',
                    'priority' => $this->calculateDocumentPriority($document),
                    'priority_color' => $this->getPriorityColor($this->calculateDocumentPriority($document)),
                    'status' => 'pending',
                    'days_until' => now()->diffInDays($document->due_date, false),
                    'related_model' => 'Document',
                    'related_id' => $document->id,
                    'url' => route('filament.admin.resources.documents.view', $document),
                ];
            });
    }
    
    private function getPaperOrderDeadlines(): Collection
    {
        return PaperOrder::where('company_id', auth()->user()->company_id)
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->whereNotNull('requested_delivery_date')
            ->where('requested_delivery_date', '>=', now())
            ->where('requested_delivery_date', '<=', now()->addDays(15))
            ->with('supplier')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'order_' . $order->id,
                    'title' => "Pedido: {$order->order_number}",
                    'description' => "Proveedor: {$order->supplier->name}",
                    'deadline_date' => $order->requested_delivery_date,
                    'type' => 'material_order',
                    'type_label' => 'Entrega de Pedido',
                    'priority' => $order->priority,
                    'priority_color' => $this->getPriorityColor($order->priority),
                    'status' => 'pending',
                    'days_until' => now()->diffInDays($order->requested_delivery_date, false),
                    'related_model' => 'PaperOrder',
                    'related_id' => $order->id,
                    'url' => '#', // TODO: Add paper orders resource URL when implemented
                ];
            });
    }
    
    public function formatModelDeadline(Deadline $deadline): array
    {
        return [
            'id' => 'deadline_' . $deadline->id,
            'title' => $deadline->title,
            'description' => $deadline->description ?? '',
            'deadline_date' => $deadline->deadline_date,
            'type' => $deadline->deadline_type,
            'type_label' => $deadline->getDeadlineTypeLabel(),
            'priority' => $deadline->priority,
            'priority_color' => $deadline->getPriorityColor(),
            'status' => $deadline->status,
            'days_until' => $deadline->getDaysUntilDeadline(),
            'related_model' => $deadline->deadlinable_type,
            'related_id' => $deadline->deadlinable_id,
            'url' => $this->getDeadlineUrl($deadline),
        ];
    }
    
    private function calculateDocumentPriority(Document $document): string
    {
        $daysUntil = now()->diffInDays($document->due_date, false);
        
        if ($daysUntil <= 1) {
            return 'urgent';
        } elseif ($daysUntil <= 3) {
            return 'high';
        } elseif ($daysUntil <= 7) {
            return 'normal';
        }
        
        return 'low';
    }
    
    private function getPriorityColor(string $priority): string
    {
        return match($priority) {
            'low' => 'success',
            'normal' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary',
        };
    }
    
    private function getDeadlineUrl(Deadline $deadline): string
    {
        // Return URL based on the related model type
        if ($deadline->deadlinable_type === 'App\Models\Document') {
            return route('filament.admin.resources.documents.view', $deadline->deadlinable_id);
        }
        
        // Default fallback
        return '#';
    }
    
    public function getDeadlineStats(): array
    {
        $upcoming = $this->getUpcomingDeadlines()->count();
        $overdue = $this->getOverdueDeadlines()->count();
        $urgent = $this->getUpcomingDeadlines()->where('priority', 'urgent')->count();
        
        return [
            'upcoming' => $upcoming,
            'overdue' => $overdue,
            'urgent' => $urgent,
            'total' => $upcoming + $overdue,
        ];
    }
    
    public function getViewData(): array
    {
        return [
            'upcomingDeadlines' => $this->getUpcomingDeadlines(),
            'overdueDeadlines' => $this->getOverdueDeadlines(),
            'stats' => $this->getDeadlineStats(),
        ];
    }
}