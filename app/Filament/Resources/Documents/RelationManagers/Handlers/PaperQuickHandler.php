<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface;
use App\Models\Document;
use App\Models\Paper;

class PaperQuickHandler implements QuickActionHandlerInterface
{
    public function getFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Agregar Papel')
                ->description('Selecciona un papel disponible y especifica la cantidad')
                ->schema((new PaperHandler())->getFormSchema()),
        ];
    }

    public function handleCreate(array $data, Document $document): void
    {
        $paper = Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($data['paper_id']);

        if (!$paper) {
            throw new \Exception('Papel no encontrado');
        }

        $quantity = $data['quantity'];
        $profitMargin = $data['profit_margin'] ?? 0;

        $baseTotal = $paper->price * $quantity;
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        $document->items()->create([
            'itemable_type' => 'App\\Models\\Paper',
            'itemable_id' => $paper->id,
            'description' => 'Papel: ' . $paper->name . ' (' . $paper->weight . 'gr - ' . $paper->width . 'x' . $paper->height . 'cm)',
            'quantity' => $quantity,
            'unit_price' => round($unitPriceWithMargin, 2),
            'total_price' => round($totalPriceWithMargin, 2),
            'profit_margin' => $profitMargin,
            'item_type' => 'paper',
        ]);

        $document->recalculateTotals();
    }

    public function getLabel(): string
    {
        return 'Papel Rápido';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function getColor(): string
    {
        return 'green';
    }

    public function getModalWidth(): string
    {
        return '5xl';
    }

    public function getSuccessNotificationTitle(): string
    {
        return 'Papel agregado correctamente';
    }

    public function isVisible(): bool
    {
        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

        return $company && $company->isPapeleria();
    }
}