<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use App\Models\Document;
use App\Models\SimpleItem;

class SimpleItemQuickHandler implements QuickActionHandlerInterface
{
    private $calculationContext;

    public function getFormSchema(): array
    {
        // Usar directamente el formulario de SimpleItem que ya incluye la sección de acabados
        return SimpleItemForm::configure(new \Filament\Schemas\Schema)->getComponents();
    }

    public function handleCreate(array $data, Document $document): void
    {
        // Extraer acabados antes de crear el SimpleItem (usando el nombre correcto del repeater)
        $finishingsData = $data['simple_item_finishings'] ?? [];

        // Filtrar campos para el SimpleItem (sin acabados)
        $simpleItemData = array_filter($data, function ($key) {
            return $key !== 'simple_item_finishings';
        }, ARRAY_FILTER_USE_KEY);

        // Crear el SimpleItem
        $simpleItem = SimpleItem::create($simpleItemData);

        // Sincronizar acabados si existen
        if (!empty($finishingsData)) {
            $syncData = [];
            foreach ($finishingsData as $index => $finishingData) {
                if (isset($finishingData['finishing_id'])) {
                    $syncData[$finishingData['finishing_id']] = [
                        'quantity' => $finishingData['quantity'] ?? null,
                        'width' => $finishingData['width'] ?? null,
                        'height' => $finishingData['height'] ?? null,
                        'calculated_cost' => $finishingData['calculated_cost'] ?? 0,
                        'is_default' => $finishingData['is_default'] ?? true,
                        'sort_order' => $index,
                    ];
                }
            }
            $simpleItem->finishings()->sync($syncData);

            // Recalcular después de agregar acabados
            $simpleItem->calculateAll();
            $simpleItem->save();
        }

        // Crear el DocumentItem asociado
        $documentItem = $document->items()->create([
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem->id,
            'description' => 'SimpleItem: '.$simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
            'total_price' => $simpleItem->final_price,
            'item_type' => 'simple',
        ]);

        // Recalcular totales del documento
        $document->recalculateTotals();
    }

    public function getLabel(): string
    {
        return 'Sencillo';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-bolt';
    }

    public function getColor(): string
    {
        return 'primary';
    }

    public function getModalWidth(): string
    {
        return '7xl';
    }

    public function getSuccessNotificationTitle(): string
    {
        return 'Item sencillo agregado correctamente';
    }

    public function isVisible(): bool
    {
        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

        return $company && $company->isLitografia();
    }

    public function setCalculationContext($context): void
    {
        $this->calculationContext = $context;
    }
}