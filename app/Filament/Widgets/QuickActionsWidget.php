<?php

namespace App\Filament\Widgets;

use App\Models\DocumentType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class QuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-actions';
    
    protected static ?int $sort = 2;
    
    public function getActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('new_quotation')
                    ->label('Nueva Cotización')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->url(fn () => route('filament.admin.resources.documents.create-quotation'))
                    ->openUrlInNewTab(false),
                    
                Action::make('new_invoice')
                    ->label('Nueva Factura')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('success')
                    ->url(fn () => $this->getCreateDocumentUrl('INVOICE'))
                    ->openUrlInNewTab(false),
                    
                Action::make('new_order')
                    ->label('Nueva Orden')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->url(fn () => $this->getCreateDocumentUrl('ORDER'))
                    ->openUrlInNewTab(false),
            ])
            ->label('📄 Documentos')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->button(),
            
            ActionGroup::make([
                Action::make('new_client')
                    ->label('Nuevo Cliente')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->url(fn () => route('filament.admin.resources.contacts.create', [
                        'type' => 'customer'
                    ]))
                    ->openUrlInNewTab(false),
                    
                Action::make('new_supplier')
                    ->label('Nuevo Proveedor')
                    ->icon('heroicon-o-building-office-2')
                    ->color('info')
                    ->url(fn () => route('filament.admin.resources.contacts.create', [
                        'type' => 'supplier'
                    ]))
                    ->openUrlInNewTab(false),
                    
                Action::make('view_contacts')
                    ->label('Ver Contactos')
                    ->icon('heroicon-o-users')
                    ->color('secondary')
                    ->url(fn () => route('filament.admin.resources.contacts.index'))
                    ->openUrlInNewTab(false),
            ])
            ->label('👥 Contactos')
            ->icon('heroicon-o-users')
            ->color('success')
            ->button(),
            
            ActionGroup::make([
                Action::make('production_register')
                    ->label('Registrar Producción')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('warning')
                    ->action(function () {
                        $this->dispatch('production-modal-opened');
                    }),
                    
                Action::make('production_schedule')
                    ->label('Programar Producción')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->action(function () {
                        $this->dispatch('schedule-modal-opened');
                    }),
                    
                Action::make('production_report')
                    ->label('Reporte de Producción')
                    ->icon('heroicon-o-chart-bar')
                    ->color('secondary')
                    ->action(function () {
                        $this->dispatch('production-report-opened');
                    }),
            ])
            ->label('⚙️ Producción')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('warning')
            ->button(),
            
            ActionGroup::make([
                Action::make('urgent_paper_order')
                    ->label('Pedido Urgente')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->action(function () {
                        $this->dispatch('urgent-paper-order');
                    }),
                    
                Action::make('check_inventory')
                    ->label('Ver Inventario')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('info')
                    ->url(fn () => route('filament.admin.resources.products.index'))
                    ->openUrlInNewTab(false),
                    
                Action::make('marketplace')
                    ->label('Ver Marketplace')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('success')
                    ->action(function () {
                        $this->dispatch('marketplace-opened');
                    }),
            ])
            ->label('📦 Inventario')
            ->icon('heroicon-o-squares-2x2')
            ->color('secondary')
            ->button(),
            
            Action::make('paper_calculator')
                ->label('Calculadora de Papel')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->size('xl')
                ->url(fn () => route('filament.admin.pages.cutting-calculator'))
                ->openUrlInNewTab(false),
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