<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Filament\Resources\Documents\Forms\DocumentItemFormBuilder;
use App\Filament\Resources\Documents\RelationManagers\Handlers\CustomItemQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\DigitalItemQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\MagazineItemHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\PaperHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\PaperQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\ProductHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\ProductQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\SimpleItemQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\TalonarioItemHandler;
use App\Filament\Resources\Documents\RelationManagers\Traits\CalculatesFinishings;
use App\Filament\Resources\Documents\RelationManagers\Traits\CalculatesProducts;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use App\Filament\Resources\TalonarioItems\Schemas\TalonarioItemForm;
use App\Models\DocumentItem;
use App\Models\SimpleItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentItemsRelationManager extends RelationManager
{
    use CalculatesFinishings, CalculatesProducts;

    protected static string $relationship = 'items';

    protected static ?string $title = null;

    protected static ?string $inverseRelationship = 'document';

    protected static ?string $modelLabel = 'Item';

    protected static ?string $pluralModelLabel = 'Items';

    private function shouldShowSizeFields(?int $finishingId): bool
    {
        if (! $finishingId) {
            return false;
        }

        $finishing = \App\Models\Finishing::find($finishingId);

        return $finishing && $finishing->measurement_unit === \App\Enums\FinishingMeasurementUnit::TAMAÑO;
    }

    public function calculateFinishingCost($set, $get): void
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 0;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;

        \Log::info('calculateFinishingCost called', [
            'finishing_id' => $finishingId,
            'quantity' => $quantity,
            'width' => $width,
            'height' => $height,
        ]);

        if ($finishingId && $quantity > 0) {
            try {
                $finishing = \App\Models\Finishing::find($finishingId);
                if ($finishing) {
                    $calculator = app(\App\Services\FinishingCalculatorService::class);
                    $cost = $calculator->calculateCost($finishing, [
                        'quantity' => $quantity,
                        'width' => $width,
                        'height' => $height,
                    ]);

                    \Log::info('Calculated cost', ['cost' => $cost]);
                    $set('calculated_cost', $cost);

                    // Recalcular el total del item incluyendo todos los acabados
                    $this->recalculateItemTotal($set, $get);
                }
            } catch (\Exception $e) {
                \Log::error('Error calculating finishing cost', ['error' => $e->getMessage()]);
                $set('calculated_cost', 0);
            }
        } else {
            $set('calculated_cost', 0);
            // Recalcular el total del item incluso si se quita un acabado
            $this->recalculateItemTotal($set, $get);
        }
    }

    // Método simplificado para SimpleItems (sin recalcular total del item)
    public function calculateSimpleFinishingCost($set, $get): void
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 0;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;

        if ($finishingId && $quantity > 0) {
            try {
                $finishing = \App\Models\Finishing::find($finishingId);
                if ($finishing) {
                    $calculator = app(\App\Services\FinishingCalculatorService::class);
                    $cost = $calculator->calculateCost($finishing, [
                        'quantity' => $quantity,
                        'width' => $width,
                        'height' => $height,
                    ]);

                    $set('calculated_cost', $cost);
                }
            } catch (\Exception $e) {
                $set('calculated_cost', 0);
            }
        } else {
            $set('calculated_cost', 0);
        }
    }

    public function recalculateItemTotal($set, $get): void
    {
        try {
            // Determinar la ruta de acceso basado en el contexto (desde repeater o desde form principal)
            $isFromRepeater = str_contains(json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)), 'calculateFinishingCost');
            $pathPrefix = $isFromRepeater ? '../../' : '';

            // Obtener el precio base del item
            $basePrice = 0;
            $quantity = $get($pathPrefix.'quantity') ?? 1;

            if ($get($pathPrefix.'item_type') === 'digital') {
                // Para items digitales, obtener el precio base del DigitalItem
                $itemableId = $get($pathPrefix.'itemable_id');

                if ($itemableId) {
                    $digitalItem = \App\Models\DigitalItem::find($itemableId);
                    if ($digitalItem) {
                        $unitValue = $digitalItem->unit_value;

                        if ($digitalItem->pricing_type === 'size') {
                            $width = $get($pathPrefix.'width') ?? 0;
                            $height = $get($pathPrefix.'height') ?? 0;
                            $area = ($width / 100) * ($height / 100); // convertir cm a m²
                            $basePrice = $area * $unitValue * $quantity;
                        } else {
                            $basePrice = $unitValue * $quantity;
                        }
                    }
                }
            }

            // Sumar todos los costos de acabados
            $finishings = $get($pathPrefix.'finishings') ?? [];
            $finishingsCost = 0;

            foreach ($finishings as $finishing) {
                if (isset($finishing['calculated_cost'])) {
                    $finishingsCost += (float) $finishing['calculated_cost'];
                }
            }

            $totalPrice = $basePrice + $finishingsCost;
            $unitPrice = $quantity > 0 ? $totalPrice / $quantity : 0;

            \Log::info('Recalculating item total', [
                'base_price' => $basePrice,
                'finishings_cost' => $finishingsCost,
                'total_price' => $totalPrice,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'context' => $isFromRepeater ? 'repeater' : 'main_form',
            ]);

            // Actualizar los precios en el formulario principal
            $set($pathPrefix.'unit_price', round($unitPrice, 2));
            $set($pathPrefix.'total_price', round($totalPrice, 2));

        } catch (\Exception $e) {
            \Log::error('Error recalculating item total', ['error' => $e->getMessage()]);
        }
    }

    public function form(Schema $schema): Schema
    {
        $formBuilder = new DocumentItemFormBuilder($this);

        return $formBuilder->buildSchema($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('itemable_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'App\\Models\\SimpleItem' => 'Sencillo',
                        'App\\Models\\Product' => 'Producto',
                        'App\\Models\\TalonarioItem' => 'Talonario',
                        'App\\Models\\MagazineItem' => 'Revista',
                        'App\\Models\\DigitalItem' => 'Impresión Digital',
                        'App\\Models\\CustomItem' => 'Personalizado',
                        default => 'Otro'
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'App\\Models\\SimpleItem' => 'success',
                        'App\\Models\\Product' => 'purple',
                        'App\\Models\\TalonarioItem' => 'warning',
                        'App\\Models\\MagazineItem' => 'info',
                        'App\\Models\\DigitalItem' => 'primary',
                        'App\\Models\\CustomItem' => 'secondary',
                        default => 'gray'
                    }),

                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->getStateUsing(function ($record) {
                        // Para productos, usar quantity del DocumentItem
                        if ($record->itemable_type === 'App\\Models\\Product') {
                            return $record->quantity;
                        }

                        // Para papeles, usar quantity del DocumentItem
                        if ($record->itemable_type === 'App\\Models\\Paper') {
                            return $record->quantity;
                        }

                        // Para items digitales, usar quantity del DocumentItem (DigitalItem no tiene quantity)
                        if ($record->itemable_type === 'App\\Models\\DigitalItem') {
                            return $record->quantity;
                        }

                        // Para SimpleItems, MagazineItems, TalonarioItems, CustomItems: usar quantity del item relacionado
                        return $record->itemable ? $record->itemable->quantity : $record->quantity;
                    })
                    ->numeric()
                    ->suffix(' uds'),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->getStateUsing(function ($record) {
                        // Para papeles, mostrar información del papel
                        if ($record->itemable_type === 'App\\Models\\Paper' && $record->itemable) {
                            $paper = $record->itemable;
                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;

                            $origin = $paper->company_id === $currentCompanyId ? 'Propio' : ($paper->company->name ?? 'N/A');

                            return $paper->code.' - '.$paper->name.' ('.$paper->weight.'gr - '.$paper->width.'x'.$paper->height.'cm) - '.$origin;
                        }

                        // Para productos, mostrar el nombre del producto con origen
                        if ($record->itemable_type === 'App\\Models\\Product' && $record->itemable) {
                            $product = $record->itemable;
                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;

                            // Si no se puede cargar la relación company, obtenerla directamente
                            if (! $product->company) {
                                $product = \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                                    ->with('company')
                                    ->find($product->id);
                            }

                            $origin = '';
                            if ($product && $product->company_id === $currentCompanyId) {
                                $origin = ' (Propio)';
                            } elseif ($product && $product->company) {
                                $origin = ' ('.$product->company->name.')';
                            }

                            return $product->name.$origin;
                        }
                        // Para SimpleItems, usar la descripción del item
                        if ($record->itemable && isset($record->itemable->description)) {
                            return $record->itemable->description;
                        }

                        return $record->description;
                    })
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('unit_price')
                    ->label('Precio Unitario')
                    ->getStateUsing(function ($record) {
                        // Para productos, usar unit_price que ya incluye el margen
                        if ($record->itemable_type === 'App\\Models\\Product') {
                            return $record->unit_price; // Ya incluye margen de ganancia
                        }

                        // Para papeles, usar unit_price que ya incluye el margen
                        if ($record->itemable_type === 'App\\Models\\Paper') {
                            return $record->unit_price; // Ya incluye margen de ganancia
                        }
                        // Para SimpleItems, calcular desde final_price
                        if ($record->itemable && isset($record->itemable->final_price) && $record->itemable->quantity > 0) {
                            return $record->itemable->final_price / $record->itemable->quantity;
                        }
                        // Para TalonarioItems, calcular desde final_price
                        if ($record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable && $record->itemable->quantity > 0) {
                            return $record->itemable->final_price / $record->itemable->quantity;
                        }
                        // Para MagazineItems, calcular desde final_price
                        if ($record->itemable_type === 'App\\Models\\MagazineItem' && $record->itemable && $record->itemable->quantity > 0) {
                            return $record->itemable->final_price / $record->itemable->quantity;
                        }
                        // Para CustomItems, usar unit_price directo
                        if ($record->itemable_type === 'App\\Models\\CustomItem' && $record->itemable) {
                            return $record->itemable->unit_price ?? 0;
                        }
                        // Para DigitalItems, usar método que incluye acabados
                        if ($record->itemable_type === 'App\\Models\\DigitalItem') {
                            return $record->getUnitPriceWithFinishings();
                        }

                        return $record->unit_price ?? 0;
                    })
                    ->money('COP'),

                TextColumn::make('total_price')
                    ->label('Precio Total')
                    ->getStateUsing(function ($record) {
                        // Para productos, usar total_price del DocumentItem
                        if ($record->itemable_type === 'App\\Models\\Product') {
                            return $record->total_price;
                        }

                        // Para papeles, usar total_price del DocumentItem
                        if ($record->itemable_type === 'App\\Models\\Paper') {
                            return $record->total_price;
                        }
                        // Para CustomItems, usar total_price calculado
                        if ($record->itemable_type === 'App\\Models\\CustomItem' && $record->itemable) {
                            return $record->itemable->total_price ?? 0;
                        }
                        // Para SimpleItems, usar final_price del item
                        if ($record->itemable_type === 'App\\Models\\SimpleItem' && $record->itemable && isset($record->itemable->final_price)) {
                            return $record->itemable->final_price;
                        }
                        // Para TalonarioItems, usar final_price del item
                        if ($record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable && isset($record->itemable->final_price)) {
                            return $record->itemable->final_price;
                        }
                        // Para MagazineItems, usar final_price del item
                        if ($record->itemable_type === 'App\\Models\\MagazineItem' && $record->itemable && isset($record->itemable->final_price)) {
                            return $record->itemable->final_price;
                        }
                        // Para DigitalItems, usar método que incluye acabados
                        if ($record->itemable_type === 'App\\Models\\DigitalItem') {
                            return $record->getTotalPriceWithFinishings();
                        }

                        // Fallback al total_price del DocumentItem
                        return $record->total_price ?? 0;
                    })
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('finishings_info')
                    ->label('Acabados')
                    ->getStateUsing(function ($record) {
                        if ($record->itemable_type === 'App\\Models\\DigitalItem' && $record->itemable) {
                            $finishings = $record->itemable->finishings;
                            if ($finishings->count() > 0) {
                                $names = $finishings->pluck('name')->take(2)->implode(', ');
                                $total = $finishings->count();

                                return $total > 2 ? $names.' (+'.($total - 2).' más)' : $names;
                            }
                        }

                        return '—';
                    })
                    ->badge()
                    ->color(fn ($state) => $state !== '—' ? 'primary' : 'gray')
                    ->visible(fn () => true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('itemable_type')
                    ->label('Tipo de Item')
                    ->options([
                        'App\\Models\\SimpleItem' => 'Sencillo',
                        'App\\Models\\Product' => 'Producto',
                        'App\\Models\\TalonarioItem' => 'Talonario',
                        'App\\Models\\MagazineItem' => 'Revista',
                        'App\\Models\\DigitalItem' => 'Impresión Digital',
                        'App\\Models\\CustomItem' => 'Personalizado',
                    ]),
            ])
            ->headerActions([

               /* Action::make('quick_magazine_item')
                    ->label('Revista')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('primary')
                    ->visible(function () {
                        // Verificar si estamos en modo edición usando la clase de la página
                        $pageClass = $this->getPageClass();
                        $isEditPage = $pageClass === \App\Filament\Resources\Documents\Pages\EditDocument::class;

                        if (!$isEditPage) {
                            return false;
                        }

                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                        return $company && $company->isLitografia();
                    })
                    ->form([
                        \Filament\Schemas\Components\Wizard::make(
                            (new MagazineItemHandler)->getWizardSteps()
                        )
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        // Usar el handler para crear la revista completa con páginas
                        $handler = new MagazineItemHandler;
                        $handler->setRecord($this->getOwnerRecord());

                        try {
                            $handler->handleCreate($data);

                            // Recalcular totales del documento
                            $this->getOwnerRecord()->recalculateTotals();

                            // Refrescar la tabla
                            $this->dispatch('$refresh');

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al crear la revista')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalWidth('7xl')
                    ->successNotificationTitle('Revista creada correctamente con todas sus páginas'),

                Action::make('quick_talonario_item')
                    ->label('Talonario')
                    ->icon('heroicon-o-document-check')
                    ->color('primary')
                    ->visible(function () {
                        // Verificar si estamos en modo edición usando la clase de la página
                        $pageClass = $this->getPageClass();
                        $isEditPage = $pageClass === \App\Filament\Resources\Documents\Pages\EditDocument::class;

                        if (!$isEditPage) {
                            return false;
                        }

                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                        return $company && $company->isLitografia();
                    })
                    ->form(TalonarioItemForm::configure(new \Filament\Schemas\Schema)->getComponents())
                    ->action(function (array $data) {
                        // Agregar company_id para multi-tenancy
                        $data['company_id'] = auth()->user()->company_id;

                        // Separar selected_finishings y sheets antes de crear el talonario
                        $selectedFinishings = $data['selected_finishings'] ?? [];
                        $sheetsData = $data['sheets'] ?? [];
                        unset($data['selected_finishings'], $data['sheets']);

                        // Crear el TalonarioItem usando la misma lógica que talonario-items/create
                        $talonario = \App\Models\TalonarioItem::create($data);

                        // Crear hojas desde el formulario o usar hojas por defecto
                        if (! empty($sheetsData)) {
                            $this->createSheetsFromFormData($talonario, $sheetsData);
                        } else {
                            // Fallback: crear hojas básicas si no hay datos del formulario
                            $this->createDefaultSheets($talonario);
                        }

                        // Procesar selected_finishings si se seleccionaron
                        if (! empty($selectedFinishings)) {
                            foreach ($selectedFinishings as $finishingId) {
                                $finishing = \App\Models\Finishing::find($finishingId);
                                if ($finishing) {
                                    // Calcular cantidad y costo según el tipo de acabado
                                    if ($finishing->measurement_unit === \App\Enums\FinishingMeasurementUnit::POR_NUMERO) {
                                        // Por número: usar total de números
                                        $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
                                        $quantity = $totalNumbers * $talonario->quantity;
                                    } else {
                                        // Por talonario: usar cantidad de talonarios
                                        $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
                                        $totalTalonarios = ceil($totalNumbers / $talonario->numeros_por_talonario);
                                        $quantity = $totalTalonarios * $talonario->quantity;
                                    }

                                    $totalCost = $quantity * $finishing->unit_price;

                                    $talonario->finishings()->attach($finishingId, [
                                        'quantity' => $quantity,
                                        'unit_cost' => $finishing->unit_price,
                                        'total_cost' => $totalCost,
                                        'finishing_options' => null,
                                        'notes' => null,
                                    ]);
                                }
                            }
                        }

                        // Recalcular precios del talonario
                        $talonario->load(['sheets.simpleItem', 'finishings']);
                        $talonario->calculateAll();
                        $talonario->save();

                        // Crear el DocumentItem asociado
                        $unitPrice = $talonario->quantity > 0 ? $talonario->final_price / $talonario->quantity : 0;
                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\TalonarioItem',
                            'itemable_id' => $talonario->id,
                            'description' => 'Talonario: '.$talonario->description,
                            'quantity' => $talonario->quantity,
                            'unit_price' => $unitPrice,
                            'total_price' => $talonario->final_price,
                        ]);

                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();

                        // Refrescar la tabla
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('7xl')
                    ->successNotificationTitle('Talonario con hojas creado correctamente'),
*/
                // ✨ NUEVA ARQUITECTURA - Handlers refactorizados
                ...$this->createQuickActions([
                    'quick_simple_refactored' => new SimpleItemQuickHandler,
                    'quick_digital_refactored' => new DigitalItemQuickHandler,
                    'quick_product_refactored' => new ProductQuickHandler,
                    'quick_custom_refactored' => new CustomItemQuickHandler,
                    'quick_paper_refactored' => new PaperQuickHandler,
                ]),

                Action::make('quick_paper_item')
                    ->label('Papel Rápido')
                    ->icon('heroicon-o-document-text')
                    ->color('green')
                    ->visible(function () {
                        // Verificar si estamos en modo edición usando la clase de la página
                        $pageClass = $this->getPageClass();
                        $isEditPage = $pageClass === \App\Filament\Resources\Documents\Pages\EditDocument::class;

                        if (!$isEditPage) {
                            return false;
                        }

                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                        return $company && $company->isPapeleria();
                    })
                    ->form([
                        \Filament\Schemas\Components\Section::make('Agregar Papel')
                            ->description('Selecciona un papel disponible y especifica la cantidad')
                            ->schema((new PaperHandler)->getFormSchema()),
                    ])
                    ->action(function (array $data) {
                        $paper = \App\Models\Paper::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($data['paper_id']);

                        if (! $paper) {
                            throw new \Exception('Papel no encontrado');
                        }

                        $quantity = $data['quantity'];
                        $profitMargin = $data['profit_margin'] ?? 0;

                        $baseTotal = $paper->price * $quantity;
                        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
                        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\Paper',
                            'itemable_id' => $paper->id,
                            'description' => 'Papel: '.$paper->name.' ('.$paper->weight.'gr - '.$paper->width.'x'.$paper->height.'cm)',
                            'quantity' => $quantity,
                            'unit_price' => round($unitPriceWithMargin, 2),
                            'total_price' => round($totalPriceWithMargin, 2),
                            'profit_margin' => $profitMargin,
                            'item_type' => 'paper',
                        ]);

                        $this->getOwnerRecord()->recalculateTotals();
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('5xl')
                    ->successNotificationTitle('Papel agregado correctamente'),
            ])
            ->actions([
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil')
                    ->visible(function ($record) {
                        return $record && $record->itemable !== null;
                    })
                    ->form(function ($record) {
                        if ($record->itemable_type === 'App\\Models\\SimpleItem') {
                            // Usar el mismo handler que para crear (incluye Resumen de Precios)
                            $handler = new SimpleItemQuickHandler();
                            $handler->setCalculationContext($this);
                            return $handler->getFormSchema();
                        }

                        if ($record->itemable_type === 'App\\Models\\MagazineItem') {
                            $handler = new MagazineItemHandler;

                            return $handler->getEditForm($record);
                        }

                        if ($record->itemable_type === 'App\\Models\\CustomItem') {
                            return [
                                \Filament\Schemas\Components\Section::make('Editar Item Personalizado')
                                    ->description('Modificar los detalles del item personalizado')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción del Item')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->suffix('unidades')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $get, $set) {
                                                        $unitPrice = $get('unit_price') ?? 0;
                                                        $total = $state * $unitPrice;
                                                        $set('calculated_total', number_format($total, 2));
                                                    }),

                                                Forms\Components\TextInput::make('unit_price')
                                                    ->label('Precio Unitario')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $get, $set) {
                                                        $quantity = $get('quantity') ?? 1;
                                                        $total = $quantity * $state;
                                                        $set('calculated_total', number_format($total, 2));
                                                    }),

                                                Forms\Components\Placeholder::make('calculated_total')
                                                    ->label('Total Calculado')
                                                    ->content(function ($get) {
                                                        $quantity = $get('quantity') ?? 1;
                                                        $unitPrice = $get('unit_price') ?? 0;

                                                        return '$'.number_format($quantity * $unitPrice, 2);
                                                    }),
                                            ]),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notas Adicionales')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ];
                        }

                        if ($record->itemable_type === 'App\\Models\\Product') {
                            $handler = new ProductHandler;

                            return $handler->getEditForm($record);
                        }

                        if ($record->itemable_type === 'App\\Models\\Paper') {
                            $handler = new PaperHandler;

                            return $handler->getEditForm($record);
                        }

                        // TalonarioItem - Con gestión de hojas
                        if ($record->itemable_type === 'App\Models\TalonarioItem') {
                            return TalonarioItemForm::configure(new \Filament\Schemas\Schema)->getComponents();
                        }

                        // DigitalItem - Con acabados opcionales
                        if ($record->itemable_type === 'App\\Models\\DigitalItem') {
                            $handler = new \App\Filament\Resources\Documents\RelationManagers\Handlers\DigitalItemHandler;

                            return $handler->getEditForm($record);
                        }
                    })
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        if ($record->itemable_type === 'App\\Models\\SimpleItem' && $record->itemable) {
                            // Usar el handler para cargar datos (igual que para crear)
                            $handler = new SimpleItemQuickHandler();
                            return $handler->fillFormData($record);
                        }

                        if ($record->itemable_type === 'App\\Models\\MagazineItem' && $record->itemable) {
                            // Usar el handler para cargar datos correctamente formateados
                            $handler = new MagazineItemHandler;

                            return $handler->fillForm($record);
                        }

                        if ($record->itemable_type === 'App\\Models\\CustomItem' && $record->itemable) {
                            // Cargar todos los datos del CustomItem para mostrar en el formulario
                            return $record->itemable->toArray();
                        }

                        if ($record->itemable_type === 'App\\Models\\Product' && $record->itemable) {
                            $handler = new ProductHandler;

                            return $handler->fillForm($record);
                        }

                        if ($record->itemable_type === 'App\\Models\\Paper' && $record->itemable) {
                            $handler = new PaperHandler;

                            return $handler->fillForm($record);
                        }

                        // Para TalonarioItems, usar el handler
                        if ($record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable) {
                            $handler = new TalonarioItemHandler;

                            return $handler->fillForm($record);
                        }

                        // Para DigitalItems, usar el handler
                        if ($record->itemable_type === 'App\\Models\\DigitalItem' && $record->itemable) {
                            $handler = new \App\Filament\Resources\Documents\RelationManagers\Handlers\DigitalItemHandler;

                            return $handler->fillForm($record);
                        }

                        // Para otros tipos, usar datos del DocumentItem
                        return [
                            'description' => $record->itemable ? $record->itemable->description : $record->description,
                            'quantity' => $record->quantity,
                            'unit_price' => $record->unit_price,
                        ];
                    })
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        if ($record->itemable_type === 'App\\Models\\SimpleItem' && $record->itemable) {
                            // Usar el handler para actualizar (igual que para crear)
                            $handler = new SimpleItemQuickHandler();
                            $handler->handleUpdate($data, $record);
                        } elseif ($record->itemable_type === 'App\\Models\\MagazineItem' && $record->itemable) {
                            // Manejar edición de MagazineItems
                            $record->load('itemable');
                            $magazine = $record->itemable;

                            // Verificar que es una instancia válida
                            if (! $magazine instanceof \App\Models\MagazineItem) {
                                throw new \Exception('Error: El item relacionado no es un MagazineItem válido');
                            }

                            // Filtrar campos del MagazineItem
                            $magazineData = array_filter($data, function ($key) {
                                return ! in_array($key, ['item_type', 'itemable_type', 'itemable_id']);
                            }, ARRAY_FILTER_USE_KEY);

                            // Actualizar el MagazineItem
                            $magazine->fill($magazineData);

                            // Recalcular automáticamente
                            if (method_exists($magazine, 'calculateAll')) {
                                $magazine->calculateAll();
                            }
                            $magazine->save();

                            // Actualizar también el DocumentItem con los nuevos valores
                            $unitPrice = $magazine->quantity > 0 ? $magazine->final_price / $magazine->quantity : 0;
                            $record->update([
                                'description' => 'Revista: '.$magazine->description,
                                'quantity' => $magazine->quantity,
                                'unit_price' => $unitPrice,
                                'total_price' => $magazine->final_price,
                            ]);
                        } elseif ($record->itemable_type === 'App\\Models\\CustomItem' && $record->itemable) {
                            // Manejar edición de CustomItems
                            $record->load('itemable');
                            $customItem = $record->itemable;

                            // Verificar que es una instancia válida
                            if (! $customItem instanceof \App\Models\CustomItem) {
                                throw new \Exception('Error: El item relacionado no es un CustomItem válido');
                            }

                            // Actualizar el CustomItem (el total se calcula automáticamente en el modelo)
                            $customItem->fill([
                                'description' => $data['description'],
                                'quantity' => $data['quantity'],
                                'unit_price' => $data['unit_price'],
                                'notes' => $data['notes'] ?? null,
                            ]);
                            $customItem->save();

                            // Actualizar también el DocumentItem con los nuevos valores
                            $record->update([
                                'description' => 'Personalizado: '.$customItem->description,
                                'quantity' => $customItem->quantity,
                                'unit_price' => $customItem->unit_price,
                                'total_price' => $customItem->total_price,
                            ]);
                        } elseif ($record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable) {
                            // Usar el handler para manejar la edición de TalonarioItems
                            $handler = new TalonarioItemHandler;
                            $handler->handleUpdate($record, $data);
                        } elseif ($record->itemable_type === 'App\\Models\\DigitalItem' && $record->itemable) {
                            // Usar el handler para manejar la edición de DigitalItems
                            $handler = new \App\Filament\Resources\Documents\RelationManagers\Handlers\DigitalItemHandler;
                            $handler->handleUpdate($record, $data);
                        } elseif ($record->itemable_type === 'App\\Models\\Product' && $record->itemable) {
                            // Usar el handler para manejar la edición de Products
                            $handler = new ProductHandler;
                            $handler->handleUpdate($record, $data);
                        } elseif ($record->itemable_type === 'App\\Models\\Paper' && $record->itemable) {
                            // Usar el handler para manejar la edición de Papers
                            $handler = new PaperHandler;
                            $handler->handleUpdate($record, $data);
                        } else {
                            // Para otros tipos de items, actualizar los datos básicos
                            $totalPrice = $data['quantity'] * $data['unit_price'];

                            // Actualizar el item relacionado si existe
                            if ($record->itemable) {
                                $record->itemable->update([
                                    'description' => $data['description'],
                                    'quantity' => $data['quantity'],
                                ]);
                            }

                            // Actualizar el DocumentItem
                            $record->update([
                                'description' => $data['description'],
                                'quantity' => $data['quantity'],
                                'unit_price' => $data['unit_price'],
                                'total_price' => $totalPrice,
                            ]);
                        }

                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();

                        return $data;
                    })
                    ->modalWidth('7xl')
                    ->slideOver()
                    ->successNotificationTitle('Item actualizado correctamente')
                    ->after(function () {
                        // Refrescar la tabla después de editar
                        $this->dispatch('$refresh');
                    }),

                Action::make('duplicate')
                    ->label('')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('secondary')
                    ->visible(function ($record) {
                        if (!$record || $record->itemable === null) {
                            return false;
                        }

                        $document = $this->getOwnerRecord();
                        return !$document->isApproved() && !$document->isRejected();
                    })
                    ->authorize(false)
                    ->requiresConfirmation()
                    ->modalHeading('Duplicar Item')
                    ->modalDescription('¿Deseas crear una copia de este item en el documento?')
                    ->action(function ($record) {
                        if ($record->itemable) {
                            // Duplicar el item relacionado
                            $newItem = $record->itemable->replicate();
                            $newItem->description = $newItem->description.' (Copia)';
                            $newItem->save();

                            // Crear nuevo DocumentItem
                            if ($record->itemable_type === 'App\\Models\\SimpleItem') {
                                // Para SimpleItems, usar los cálculos automáticos
                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $newItem->id,
                                    'description' => 'SimpleItem: '.$newItem->description,
                                    'quantity' => $newItem->quantity,
                                    'unit_price' => $newItem->final_price / $newItem->quantity,
                                    'total_price' => $newItem->final_price,
                                ]);
                            } elseif ($record->itemable_type === 'App\\Models\\MagazineItem') {
                                // Para MagazineItems, duplicar también las páginas y relaciones
                                $originalMagazine = $record->itemable;

                                // Duplicar las páginas asociadas
                                foreach ($originalMagazine->pages as $page) {
                                    $newItem->pages()->create([
                                        'simple_item_id' => $page->simple_item_id,
                                        'page_type' => $page->page_type,
                                        'page_order' => $page->page_order,
                                        'page_quantity' => $page->page_quantity,
                                        'page_notes' => $page->page_notes,
                                    ]);
                                }

                                // Duplicar acabados
                                foreach ($originalMagazine->finishings as $finishing) {
                                    $newItem->finishings()->attach($finishing->id, [
                                        'quantity' => $finishing->pivot->quantity,
                                        'unit_cost' => $finishing->pivot->unit_cost,
                                        'total_cost' => $finishing->pivot->total_cost,
                                        'finishing_options' => $finishing->pivot->finishing_options,
                                        'notes' => $finishing->pivot->notes,
                                    ]);
                                }

                                // Recalcular precios de la revista duplicada
                                $newItem->calculateAll();
                                $newItem->save();

                                $unitPrice = $newItem->quantity > 0 ? $newItem->final_price / $newItem->quantity : 0;
                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $newItem->id,
                                    'description' => 'Revista: '.$newItem->description,
                                    'quantity' => $newItem->quantity,
                                    'unit_price' => $unitPrice,
                                    'total_price' => $newItem->final_price,
                                ]);
                            } else {
                                // Para otros tipos de items, copiar los datos del DocumentItem original
                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $newItem->id,
                                    'description' => $record->description.' (Copia)',
                                    'quantity' => $record->quantity,
                                    'unit_price' => $record->unit_price,
                                    'total_price' => $record->total_price,
                                ]);
                            }

                            // Recalcular totales
                            $this->getOwnerRecord()->recalculateTotals();
                        }
                    })
                    ->successNotificationTitle('Item duplicado correctamente')
                    ->after(function () {
                        $this->dispatch('$refresh');
                    }),

                Action::make('duplicate')
                    ->label('')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('secondary')
                    ->visible(function ($record) {
                        if (!$record || $record->itemable === null) {
                            return false;
                        }

                        $document = $this->getOwnerRecord();
                        return !$document->isApproved() && !$document->isRejected();
                    })
                    ->action(function ($record) {
                        if ($record->itemable) {
                            // For products, just duplicate the DocumentItem without creating a new product
                            if ($record->itemable_type === 'App\\Models\\Product') {
                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $record->itemable_id, // Use same product
                                    'description' => $record->description.' (Copia)',
                                    'quantity' => $record->quantity,
                                    'unit_price' => $record->unit_price,
                                    'total_price' => $record->total_price,
                                    'profit_margin' => $record->profit_margin,
                                ]);
                            }

                            // For papers, just duplicate the DocumentItem without creating a new paper
                            elseif ($record->itemable_type === 'App\\Models\\Paper') {
                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $record->itemable_id, // Use same paper
                                    'description' => $record->description.' (Copia)',
                                    'quantity' => $record->quantity,
                                    'unit_price' => $record->unit_price,
                                    'total_price' => $record->total_price,
                                    'profit_margin' => $record->profit_margin,
                                ]);
                            } else {
                                // For other item types, replicate the item
                                $newItem = $record->itemable->replicate();
                                $newItem->description = $newItem->description.' (Copia)';
                                $newItem->save();

                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $newItem->id,
                                    'description' => $record->description.' (Copia)',
                                    'quantity' => $record->quantity,
                                    'unit_price' => $record->unit_price,
                                    'total_price' => $record->total_price,
                                ]);
                            }

                            $this->getOwnerRecord()->recalculateTotals();
                        }
                    }),

                DeleteAction::make()
                    ->label('')
                    ->after(function ($record) {
                        // Eliminar el item relacionado también
                        if ($record->itemable) {
                            $record->itemable->delete();
                        }

                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                    }),

                Action::make('add_sheet')
                    ->label('')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->tooltip('Agregar Hoja')
                    ->visible(function ($record) {
                        return $record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable !== null;
                    })
                    ->modalHeading('Crear Nueva Hoja para el Talonario')
                    ->modalWidth('7xl')
                    ->form(fn () => [
                        \Filament\Forms\Components\Section::make('Información de la Hoja')
                            ->schema([
                                \Filament\Forms\Components\Grid::make(3)
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('sheet_type')
                                            ->label('Tipo de Hoja')
                                            ->required()
                                            ->options([
                                                'original' => 'Original',
                                                'copia_1' => '1ª Copia',
                                                'copia_2' => '2ª Copia',
                                                'copia_3' => '3ª Copia',
                                            ])
                                            ->default('original'),

                                        \Filament\Forms\Components\Select::make('paper_color')
                                            ->label('Color del Papel')
                                            ->required()
                                            ->options([
                                                'blanco' => '🤍 Blanco',
                                                'amarillo' => '💛 Amarillo',
                                                'rosado' => '💗 Rosado',
                                                'azul' => '💙 Azul',
                                                'verde' => '💚 Verde',
                                                'naranja' => '🧡 Naranja',
                                            ])
                                            ->default('blanco'),

                                        \Filament\Forms\Components\TextInput::make('sheet_order')
                                            ->label('Orden')
                                            ->numeric()
                                            ->required()
                                            ->default(function ($livewire) {
                                                $record = $livewire->ownerRecord ?? $livewire->record;
                                                if ($record && $record->itemable) {
                                                    return $record->itemable->getNextSheetOrder();
                                                }

                                                return 1;
                                            })
                                            ->minValue(1)
                                            ->helperText('Orden de la hoja en el talonario'),
                                    ]),

                                \Filament\Forms\Components\Textarea::make('description')
                                    ->label('Descripción del Contenido')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->placeholder('Describe el contenido de esta hoja...'),
                            ]),

                        \Filament\Forms\Components\Section::make('Materiales')
                            ->schema([
                                \Filament\Forms\Components\Grid::make(2)
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('paper_id')
                                            ->label('Papel')
                                            ->options(function () {
                                                $companyId = auth()->user()->company_id ?? 1;

                                                return \App\Models\Paper::query()
                                                    ->where('company_id', $companyId)
                                                    ->get()
                                                    ->mapWithKeys(function ($paper) {
                                                        $label = $paper->full_name ?: ($paper->code.' - '.$paper->name);

                                                        return [$paper->id => $label];
                                                    })
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->searchable()
                                            ->placeholder('Seleccionar papel'),

                                        \Filament\Forms\Components\Select::make('printing_machine_id')
                                            ->label('Máquina de Impresión')
                                            ->options(function () {
                                                $companyId = auth()->user()->company_id ?? 1;

                                                return \App\Models\PrintingMachine::query()
                                                    ->where('company_id', $companyId)
                                                    ->get()
                                                    ->mapWithKeys(function ($machine) {
                                                        $label = $machine->name.' - '.ucfirst($machine->type);

                                                        return [$machine->id => $label];
                                                    })
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->searchable()
                                            ->placeholder('Seleccionar máquina'),
                                    ]),
                            ]),

                        \Filament\Forms\Components\Section::make('Configuración de Tintas')
                            ->schema([
                                \Filament\Forms\Components\Grid::make(3)
                                    ->schema([
                                        \Filament\Forms\Components\TextInput::make('ink_front_count')
                                            ->label('Tintas Frente')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(0)
                                            ->maxValue(8)
                                            ->helperText('Talonarios normalmente usan 1 tinta (negro)'),

                                        \Filament\Forms\Components\TextInput::make('ink_back_count')
                                            ->label('Tintas Reverso')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(8),

                                        \Filament\Forms\Components\Select::make('front_back_plate')
                                            ->label('Placa Frente y Reverso')
                                            ->options([
                                                0 => 'No - Placas separadas',
                                                1 => 'Sí - Misma placa',
                                            ])
                                            ->default(0)
                                            ->required()
                                            ->helperText('Para talonarios normalmente es "No"'),
                                    ]),
                            ]),
                    ])
                    ->action(function (array $data, $record, $livewire) {
                        $talonario = $record->itemable;

                        // Extraer datos específicos de la hoja
                        $sheetType = $data['sheet_type'] ?? 'original';
                        $paperColor = $data['paper_color'] ?? 'blanco';
                        $sheetOrder = $data['sheet_order'] ?? 1;

                        // Calcular cantidad correcta basada en el talonario
                        $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
                        $correctQuantity = $totalNumbers * $talonario->quantity;

                        // Preparar datos del SimpleItem (sin campos de hoja)
                        $simpleItemData = $data;
                        unset($simpleItemData['sheet_type'], $simpleItemData['paper_color'], $simpleItemData['sheet_order']);

                        // Configurar dimensiones y cantidad automáticamente
                        $simpleItemData['quantity'] = $correctQuantity;
                        $simpleItemData['horizontal_size'] = $talonario->ancho;
                        $simpleItemData['vertical_size'] = $talonario->alto;
                        $simpleItemData['profit_percentage'] = 0; // Sin ganancia doble

                        // Asegurar que front_back_plate sea boolean
                        $simpleItemData['front_back_plate'] = (bool) ($simpleItemData['front_back_plate'] ?? false);

                        // Crear el SimpleItem
                        $simpleItem = \App\Models\SimpleItem::create(array_merge($simpleItemData, [
                            'company_id' => auth()->user()->company_id,
                            'user_id' => auth()->id(),
                            'description' => $data['description'],
                        ]));

                        // Crear la hoja del talonario
                        \App\Models\TalonarioSheet::create([
                            'talonario_item_id' => $talonario->id,
                            'simple_item_id' => $simpleItem->id,
                            'sheet_type' => $sheetType,
                            'sheet_order' => $sheetOrder,
                            'paper_color' => $paperColor,
                            'sheet_notes' => $data['description'],
                        ]);

                        // Recalcular precios del talonario
                        $talonario->calculateAll();
                        $talonario->save();

                        // Recalcular el DocumentItem
                        $record->calculateAndUpdatePrices();

                        // Notificación de éxito
                        \Filament\Notifications\Notification::make()
                            ->title('Hoja agregada correctamente')
                            ->body("La hoja '{$sheetType}' ({$paperColor}) se ha creado y agregado al talonario.")
                            ->success()
                            ->send();

                        // Refrescar la tabla
                        $livewire->dispatch('$refresh');
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records) {
                            // Eliminar los items relacionados también
                            foreach ($records as $record) {
                                if ($record->itemable) {
                                    $record->itemable->delete();
                                }
                            }

                            // Recalcular totales del documento
                            $this->getOwnerRecord()->recalculateTotals();
                        }),
                ]),
            ])
            ->modifyQueryUsing(function ($query) {
                // Forzar la carga de itemable sin restricciones de scope
                return $query->with(['itemable']);
            })
            ->defaultSort('created_at', 'desc');
    }

    private function createSheetsFromFormData(\App\Models\TalonarioItem $talonario, array $sheetsData): void
    {
        // Calcular cantidad correcta basada en el talonario
        $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
        $correctQuantity = $totalNumbers * $talonario->quantity;

        foreach ($sheetsData as $sheetData) {
            // Validar que no exista ya una hoja del mismo tipo
            $existingSheet = \App\Models\TalonarioSheet::where('talonario_item_id', $talonario->id)
                ->where('sheet_type', $sheetData['sheet_type'])
                ->first();

            if ($existingSheet) {
                continue; // Saltar hojas duplicadas
            }

            // Crear el SimpleItem con datos del formulario
            $simpleItem = \App\Models\SimpleItem::create([
                'company_id' => $talonario->company_id,
                'user_id' => auth()->id(),
                'description' => $sheetData['description'],
                'quantity' => $correctQuantity,
                'horizontal_size' => $talonario->ancho,
                'vertical_size' => $talonario->alto,
                'paper_id' => $sheetData['paper_id'],
                'printing_machine_id' => $sheetData['printing_machine_id'],
                'ink_front_count' => $sheetData['ink_front_count'] ?? 1,
                'ink_back_count' => $sheetData['ink_back_count'] ?? 0,
                'front_back_plate' => (bool) ($sheetData['front_back_plate'] ?? false),
                'profit_percentage' => 0, // Sin ganancia doble, solo en el talonario final
                'design_value' => 0,
                'transport_value' => 0,
                'rifle_value' => 0,
            ]);

            // Crear la hoja del talonario
            \App\Models\TalonarioSheet::create([
                'talonario_item_id' => $talonario->id,
                'simple_item_id' => $simpleItem->id,
                'sheet_type' => $sheetData['sheet_type'],
                'sheet_order' => $sheetData['sheet_order'] ?? 1,
                'paper_color' => $sheetData['paper_color'] ?? 'blanco',
                'sheet_notes' => $sheetData['description'],
            ]);
        }
    }

    private function createDefaultSheets(\App\Models\TalonarioItem $talonario): void
    {
        $defaultSheets = [
            ['sheet_type' => 'original', 'sheet_notes' => 'Hoja original'],
            ['sheet_type' => 'copia_1', 'sheet_notes' => 'Primera copia'],
            ['sheet_type' => 'copia_2', 'sheet_notes' => 'Segunda copia'],
        ];

        foreach ($defaultSheets as $index => $sheetData) {
            // Crear SimpleItem básico para cada hoja
            $simpleItem = \App\Models\SimpleItem::create([
                'company_id' => $talonario->company_id,
                'description' => "{$talonario->description} - {$sheetData['sheet_notes']}",
                'quantity' => $talonario->quantity * $talonario->numeros_por_talonario,
                'horizontal_size' => $talonario->ancho,
                'vertical_size' => $talonario->alto,
                'ink_front_count' => 1,
                'ink_back_count' => 0,
                'profit_percentage' => 25,
                // Valores por defecto básicos
                'design_value' => 0,
                'transport_value' => 0,
                'rifle_value' => 0,
            ]);

            // Crear la hoja del talonario
            \App\Models\TalonarioSheet::create([
                'talonario_item_id' => $talonario->id,
                'simple_item_id' => $simpleItem->id,
                'sheet_type' => $sheetData['sheet_type'],
                'sheet_order' => $index + 1,
                'sheet_notes' => $sheetData['sheet_notes'],
            ]);
        }
    }

    /**
     * Calcular precio total de producto con margen de ganancia
     */
    public function calculateProductTotal($get, $set): void
    {
        $quantity = $get('quantity') ?? 0;
        $unitPrice = $get('unit_price') ?? 0;
        $profitMargin = $get('profit_margin') ?? 0;

        if ($quantity > 0 && $unitPrice > 0) {
            // Precio base sin margen
            $baseTotal = $quantity * $unitPrice;

            // Aplicar margen de ganancia
            $finalTotal = $baseTotal * (1 + ($profitMargin / 100));

            $set('total_price', round($finalTotal, 2));
        } else {
            $set('total_price', 0);
        }
    }

    /**
     * Create a quick action from a handler
     */
    private function createQuickAction(string $actionKey, $handler): Action
    {
        // Setup context for handlers that need calculation methods
        $this->setupHandlerContext($handler);

        return Action::make($actionKey)
            ->label($handler->getLabel())
            ->icon($handler->getIcon())
            ->color($handler->getColor())
            ->visible(function () use ($handler) {
                // Verificar si estamos en modo edición usando la clase de la página
                $pageClass = $this->getPageClass();
                $isEditPage = $pageClass === \App\Filament\Resources\Documents\Pages\EditDocument::class;

                if (!$isEditPage) {
                    return false;
                }

                return $handler->isVisible();
            })
            ->form($handler->getFormSchema())
            ->action(function (array $data) use ($handler) {
                $handler->handleCreate($data, $this->getOwnerRecord());
                $this->dispatch('$refresh');
            })
            ->modalWidth($handler->getModalWidth())
            ->successNotificationTitle($handler->getSuccessNotificationTitle());
    }

    /**
     * Setup calculation context for handlers that need it
     */
    private function setupHandlerContext($handler): void
    {
        if (method_exists($handler, 'setCalculationContext')) {
            $handler->setCalculationContext($this);
        }
    }

    /**
     * Create multiple quick actions from handlers
     */
    private function createQuickActions(array $handlers): array
    {
        $actions = [];

        foreach ($handlers as $actionKey => $handler) {
            $actions[] = $this->createQuickAction($actionKey, $handler);
        }

        return $actions;
    }
}
