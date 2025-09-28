<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use App\Models\Paper;

class PaperHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            Section::make('Editar Papel')
                ->description('Modificar cantidad y margen de ganancia')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->suffix('pliegos')
                                ->live(),

                            Components\TextInput::make('profit_margin')
                                ->label('Margen de Ganancia')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(500)
                                ->live(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Components\TextInput::make('base_unit_price')
                                ->label('Precio Base por Pliego')
                                ->numeric()
                                ->prefix('$')
                                ->readOnly()
                                ->helperText('Precio del papel sin margen'),

                            Components\TextInput::make('unit_price')
                                ->label('Precio Final por Pliego')
                                ->numeric()
                                ->prefix('$')
                                ->readOnly()
                                ->extraAttributes(['class' => 'font-bold'])
                                ->helperText('Precio con margen incluido'),
                        ]),

                    Components\TextInput::make('total_price')
                        ->label('Total Final')
                        ->numeric()
                        ->prefix('$')
                        ->readOnly()
                        ->extraAttributes(['class' => 'font-bold text-lg'])
                        ->columnSpanFull(),
                ])
        ];
    }

    public function fillForm($record): array
    {
        $paper = Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($record->itemable_id);
        $baseUnitPrice = $paper ? $paper->price : 0;

        return [
            'quantity' => $record->quantity,
            'profit_margin' => $record->profit_margin ?? 0,
            'base_unit_price' => $baseUnitPrice,
            'unit_price' => $record->unit_price,
            'total_price' => $record->total_price,
        ];
    }

    public function handleUpdate($record, array $data): void
    {
        $paper = Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($record->itemable_id);

        if (!$paper) {
            throw new \Exception('Papel no encontrado');
        }

        $quantity = $data['quantity'];
        $profitMargin = $data['profit_margin'] ?? 0;

        $baseTotal = $paper->price * $quantity;
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        $record->update([
            'quantity' => $quantity,
            'profit_margin' => $profitMargin,
            'unit_price' => round($unitPriceWithMargin, 2),
            'total_price' => round($totalPriceWithMargin, 2),
        ]);
    }

    public function getFormSchema(): array
    {
        return [
            Components\Select::make('paper_id')
                ->label('Seleccionar Papel')
                ->options(function () {
                    $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                    $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                    if (!$company) {
                        return [];
                    }

                    if ($company->isLitografia()) {
                        $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                            ->where('is_active', true)
                            ->whereNotNull('approved_at')
                            ->pluck('supplier_company_id')
                            ->toArray();

                        $papers = Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                            $query->where('company_id', $currentCompanyId)
                                  ->orWhereIn('company_id', $supplierCompanyIds);
                        })
                        ->where('is_active', true)
                        ->with('company')
                        ->get()
                        ->mapWithKeys(function ($paper) use ($currentCompanyId) {
                            $origin = $paper->company_id === $currentCompanyId ? 'Propio' : $paper->company->name;
                            $label = $paper->code . ' - ' . $paper->name . ' (' . $paper->weight . 'gr) - ' . $origin;
                            return [$paper->id => $label];
                        });

                        return $papers->toArray();
                    } else {
                        return Paper::where('company_id', $currentCompanyId)
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(function ($paper) {
                                $label = $paper->code . ' - ' . $paper->name . ' (' . $paper->weight . 'gr)';
                                return [$paper->id => $label];
                            })
                            ->toArray();
                    }
                })
                ->required()
                ->searchable()
                ->live(),

            Grid::make(3)
                ->schema([
                    Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1)
                        ->suffix('pliegos')
                        ->live(),

                    Components\TextInput::make('unit_price')
                        ->label('Precio por Pliego')
                        ->prefix('$')
                        ->numeric()
                        ->readOnly(),

                    Components\Placeholder::make('stock_available')
                        ->label('Stock Disponible')
                        ->content(fn ($get) => ($get('stock_available') ?? 0) . ' pliegos'),
                ]),

            Grid::make(2)
                ->schema([
                    Components\TextInput::make('profit_margin')
                        ->label('Margen de Ganancia')
                        ->numeric()
                        ->suffix('%')
                        ->default(25)
                        ->minValue(0)
                        ->maxValue(500)
                        ->live(),

                    Components\TextInput::make('total_price')
                        ->label('Precio Total')
                        ->prefix('$')
                        ->numeric()
                        ->readOnly()
                        ->extraAttributes(['class' => 'font-bold text-lg']),
                ]),
        ];
    }

    public function getWizardStep(): Step
    {
        return Step::make('Papel')
            ->description('Papeles disponibles')
            ->icon('heroicon-o-document-text')
            ->schema($this->getFormSchema());
    }
}