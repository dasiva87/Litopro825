<?php

namespace App\Filament\Widgets;

use App\Models\DocumentType;
use Filament\Actions\Action;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-actions-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    public function getActions(): array
    {
        return [
            Action::make('new_quotation')
                ->label('Nueva Cotización')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->url(fn () => route('filament.admin.resources.documents.create-quotation')),

            Action::make('new_client')
                ->label('Nuevo Cliente')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->url(fn () => route('filament.admin.resources.contacts.create', ['type' => 'customer'])),

            Action::make('check_inventory')
                ->label('Ver Inventario')
                ->icon('heroicon-o-squares-2x2')
                ->color('info')
                ->url(fn () => route('filament.admin.resources.products.index')),

            Action::make('urgent_paper_order')
                ->label('Pedido Urgente')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->action(function () {
                    $this->dispatch('urgent-paper-order');
                }),
        ];
    }
    
    private function getCreateDocumentUrl(string $documentTypeCode): string
    {
        $documentType = DocumentType::where('code', $documentTypeCode)->first();
        
        if (!$documentType) {
            return route('filament.admin.resources.documents.create');
        }
        
        return route('filament.admin.resources.documents.create', [
            'document_type_id' => $documentType->id
        ]);
    }
    
    public function getViewData(): array
    {
        return [
            'actions' => $this->getActions(),
        ];
    }
}