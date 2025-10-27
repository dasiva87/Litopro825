<?php

namespace App\Filament\Resources\SimpleItems\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use App\Models\Paper;
use App\Models\PrintingMachine;

class SimpleItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Sección Información del Producto - Ancho completo
                Section::make('📝 Información del Producto')
                    ->description('Datos básicos del trabajo de impresión')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Textarea::make('description')
                                    ->label('Descripción del Trabajo')
                                    ->required()
                                    ->rows(3)
                                    ->placeholder('Ej: Volantes promocionales full color...')
                                    ->columnSpan(2),

                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->suffix('unidades')
                                            ->live(onBlur: true),

                                        TextInput::make('sobrante_papel')
                                            ->label('Sobrante')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->suffix('unidades')
                                            ->helperText('Desperdicios (si >100 se cobra)')
                                            ->live(onBlur: true),
                                    ])
                                    ->columnSpan(1),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('horizontal_size')
                                    ->label('Ancho del Trabajo')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->step(0.1)
                                    ->live(onBlur: true),

                                TextInput::make('vertical_size')
                                    ->label('Alto del Trabajo')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->step(0.1)
                                    ->live(onBlur: true),

                                Placeholder::make('area_calculation')
                                    ->label('Área Total')
                                    ->content(function ($get) {
                                        $h = $get('horizontal_size');
                                        $v = $get('vertical_size');
                                        return $h && $v ? '<strong>' . number_format($h * $v, 2) . ' cm²</strong>' : '-';
                                    })
                                    ->html(),

                                Placeholder::make('format_info')
                                    ->label('Formato')
                                    ->content(function ($get) {
                                        $h = $get('horizontal_size');
                                        $v = $get('vertical_size');
                                        if (!$h || !$v) return '-';

                                        // Detectar formatos comunes
                                        if (abs($h - 9) < 0.5 && abs($v - 5) < 0.5) return '<span class="text-blue-600 font-semibold">📇 Tarjeta</span>';
                                        if (abs($h - 14.8) < 0.5 && abs($v - 21) < 0.5) return '<span class="text-blue-600 font-semibold">📄 A5</span>';
                                        if (abs($h - 21) < 0.5 && abs($v - 29.7) < 0.5) return '<span class="text-blue-600 font-semibold">📄 A4</span>';
                                        return '<span class="text-gray-500">Personalizado</span>';
                                    })
                                    ->html(),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Sección Configuración de Impresión - Ancho completo
                Section::make('🖨️ Configuración de Impresión')
                    ->description('Papel, máquina y tintas para el trabajo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('paper_id')
                                    ->label('Tipo de Papel')
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
                                                $query->forTenant($currentCompanyId)
                                                      ->orWhereIn('company_id', $supplierCompanyIds);
                                            })
                                            ->where('is_active', true)
                                            ->with('company')
                                            ->get()
                                            ->mapWithKeys(function ($paper) use ($currentCompanyId) {
                                                $origin = $paper->company_id === $currentCompanyId ? '✓' : '📦';
                                                $label = "$origin {$paper->code} - {$paper->name} ({$paper->width}x{$paper->height}cm)";
                                                return [$paper->id => $label];
                                            });

                                            return $papers->toArray();
                                        } else {
                                            return Paper::where('company_id', $currentCompanyId)
                                                ->where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(function ($paper) {
                                                    return [$paper->id => "{$paper->code} - {$paper->name} ({$paper->width}x{$paper->height}cm)"];
                                                })
                                                ->toArray();
                                        }
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Select::make('printing_machine_id')
                                    ->label('Máquina de Impresión')
                                    ->relationship('printingMachine', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) =>
                                        $record->name . ' - ' . ucfirst($record->type) .
                                        ' (Max: ' . $record->max_colors . ' tintas)'
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->columnSpan(1),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('ink_front_count')
                                    ->label('Tintas Tiro (Frente)')
                                    ->numeric()
                                    ->required()
                                    ->default(4)
                                    ->minValue(0)
                                    ->maxValue(8)
                                    ->helperText('Colores en la cara frontal'),

                                TextInput::make('ink_back_count')
                                    ->label('Tintas Retiro (Reverso)')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(8)
                                    ->helperText('Colores en la cara posterior'),

                                Toggle::make('front_back_plate')
                                    ->label('Tiro y Retiro en Misma Plancha')
                                    ->helperText('Mismo arte en ambos lados')
                                    ->inline(false),

                                Placeholder::make('total_colors')
                                    ->label('Total de Tintas')
                                    ->content(function ($get) {
                                        $front = $get('ink_front_count') ?? 0;
                                        $back = $get('ink_back_count') ?? 0;
                                        $samePlate = $get('front_back_plate');

                                        if ($samePlate) {
                                            $total = max($front, $back);
                                            return '<span class="text-lg font-bold text-blue-600">' . $total . ' tintas</span><br><span class="text-xs text-gray-500">(Misma plancha)</span>';
                                        }

                                        $total = $front + $back;
                                        return '<span class="text-lg font-bold text-green-600">' . $total . ' tintas</span><br><span class="text-xs text-gray-500">' . $front . '+' . $back . '</span>';
                                    })
                                    ->html(),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Vista previa de montaje (visible siempre)
                Section::make('📐 Vista Previa de Montaje')
                    ->description('Cálculo en tiempo real de cómo se acomoda el trabajo en el pliego')
                    ->schema([
                        Placeholder::make('mounting_preview')
                            ->label('')
                            ->live()
                            ->content(function ($get) {
                                // Debug: Siempre mostrar algo
                                $horizontalSize = $get('horizontal_size');
                                $verticalSize = $get('vertical_size');
                                $machineId = $get('printing_machine_id');
                                $quantity = $get('quantity') ?? 0;
                                $sobrante = $get('sobrante_papel') ?? 0;

                                // Debug info
                                $debugInfo = '<div class="text-xs text-gray-500 mb-2">
                                    Debug: Ancho=' . ($horizontalSize ?? 'null') . ',
                                    Alto=' . ($verticalSize ?? 'null') . ',
                                    Máquina=' . ($machineId ?? 'null') . '
                                </div>';

                                if (!$horizontalSize || !$verticalSize || !$machineId) {
                                    return new \Illuminate\Support\HtmlString($debugInfo . '<div class="p-4 bg-gray-50 rounded text-gray-500 text-center">
                                        📋 Complete los campos de tamaño y máquina para ver el montaje
                                    </div>');
                                }

                                try {
                                    $machine = \App\Models\PrintingMachine::find($machineId);
                                    if (!$machine) {
                                        return new \Illuminate\Support\HtmlString('<div class="p-3 bg-yellow-50 rounded text-yellow-700 text-sm">
                                            ⚠️ Máquina no encontrada
                                        </div>');
                                    }

                                    $calc = new \App\Services\MountingCalculatorService();
                                    $result = $calc->calculateMounting(
                                        workWidth: (float) $horizontalSize,
                                        workHeight: (float) $verticalSize,
                                        machineWidth: $machine->max_width ?? 50.0,
                                        machineHeight: $machine->max_height ?? 70.0,
                                        marginPerSide: 1.0
                                    );

                                    $best = $result['maximum'];

                                    if ($best['copies_per_sheet'] == 0) {
                                        return new \Illuminate\Support\HtmlString('<div class="p-3 bg-red-50 rounded text-red-700 text-sm">
                                            ❌ El trabajo NO cabe en la máquina seleccionada<br>
                                            <span class="text-xs">Máquina: ' . $machine->name . ' (' . $machine->max_width . '×' . $machine->max_height . 'cm)</span>
                                        </div>');
                                    }

                                    // Calcular pliegos necesarios si hay cantidad
                                    $sheetsInfo = '';
                                    if ($quantity > 0) {
                                        $sheets = $calc->calculateRequiredSheets(
                                            requiredCopies: (int) $quantity + (int) $sobrante,
                                            copiesPerSheet: $best['copies_per_sheet']
                                        );

                                        $efficiency = $calc->calculateEfficiency(
                                            workWidth: $best['work_width'],
                                            workHeight: $best['work_height'],
                                            copiesPerSheet: $best['copies_per_sheet'],
                                            usableWidth: ($machine->max_width ?? 50.0) - 2.0,
                                            usableHeight: ($machine->max_height ?? 70.0) - 2.0
                                        );

                                        $sheetsInfo = '
                                            <div class="mt-3 p-3 bg-blue-50 rounded border border-blue-200">
                                                <div class="font-semibold text-blue-800 mb-2">📦 Pliegos Necesarios</div>
                                                <div class="grid grid-cols-3 gap-2 text-sm">
                                                    <div>
                                                        <div class="text-gray-600 text-xs">Pliegos</div>
                                                        <div class="font-bold text-blue-600">' . $sheets['sheets_needed'] . '</div>
                                                    </div>
                                                    <div>
                                                        <div class="text-gray-600 text-xs">Producción</div>
                                                        <div class="font-bold">' . number_format($sheets['total_copies_produced']) . '</div>
                                                    </div>
                                                    <div>
                                                        <div class="text-gray-600 text-xs">Desperdicio</div>
                                                        <div class="font-bold text-orange-600">' . $sheets['waste_copies'] . '</div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-xs text-gray-600">
                                                    Aprovechamiento: <strong>' . number_format($efficiency, 1) . '%</strong>
                                                </div>
                                            </div>';
                                    }

                                    // Generar visualización SVG
                                    $svgVisual = '';
                                    $svgDebug = '';
                                    try {
                                        $svgVisual = self::generateMountingSVG(
                                            $best,
                                            $machine->max_width ?? 50.0,
                                            $machine->max_height ?? 70.0,
                                            (float) $horizontalSize,
                                            (float) $verticalSize
                                        );

                                        $svgDebug = '<div class="text-xs text-green-600 mt-1">✅ SVG generado (' . strlen($svgVisual) . ' chars)</div>';

                                    } catch (\Exception $svgError) {
                                        // Fallback visual simple si falla el SVG
                                        $svgVisual = '<div class="p-4 bg-yellow-50 rounded border border-yellow-300 text-center">
                                            <div class="text-yellow-800 font-semibold mb-2">⚠️ Vista simplificada</div>
                                            <div class="text-sm text-gray-700">
                                                <strong>' . $best['copies_per_sheet'] . ' copias</strong> por pliego
                                                <br>
                                                Layout: ' . $best['layout'] . ' (' . ucfirst($best['orientation']) . ')
                                            </div>
                                            <div class="text-xs text-gray-500 mt-2">Error SVG: ' . $svgError->getMessage() . '</div>
                                        </div>';
                                        $svgDebug = '<div class="text-xs text-red-600 mt-1">❌ Error: ' . $svgError->getMessage() . '</div>';
                                    }

                                    $content = '
                                        <div class="space-y-4">
                                            <!-- Visualización Gráfica -->
                                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-4 rounded-lg border border-gray-300">
                                                <div class="text-sm font-semibold text-gray-700 mb-3 text-center">
                                                    🎨 Vista del Pliego - Orientación ' . ucfirst($best['orientation']) . '
                                                </div>
                                                <div class="flex justify-center">
                                                    ' . $svgVisual . '
                                                </div>
                                                <div class="mt-3 text-xs text-gray-600 text-center">
                                                    <span class="inline-block px-2 py-1 bg-blue-100 rounded">Pliego</span>
                                                    <span class="inline-block px-2 py-1 bg-green-100 rounded ml-2">Trabajo</span>
                                                    <span class="inline-block px-2 py-1 bg-yellow-100 rounded ml-2">Margen</span>
                                                </div>
                                                ' . $svgDebug . '
                                            </div>

                                            <div class="grid grid-cols-3 gap-3">
                                                <!-- Horizontal -->
                                                <div class="p-3 ' . ($best['orientation'] === 'horizontal' ? 'bg-green-50 border-2 border-green-300' : 'bg-gray-50 border border-gray-200') . ' rounded">
                                                    <div class="text-xs text-gray-600 mb-1">Horizontal</div>
                                                    <div class="font-bold text-lg">' . $result['horizontal']['copies_per_sheet'] . '</div>
                                                    <div class="text-xs text-gray-600">' . $result['horizontal']['layout'] . '</div>
                                                    ' . ($best['orientation'] === 'horizontal' ? '<div class="text-xs text-green-600 mt-1">✓ Mejor</div>' : '') . '
                                                </div>

                                                <!-- Vertical -->
                                                <div class="p-3 ' . ($best['orientation'] === 'vertical' ? 'bg-green-50 border-2 border-green-300' : 'bg-gray-50 border border-gray-200') . ' rounded">
                                                    <div class="text-xs text-gray-600 mb-1">Vertical</div>
                                                    <div class="font-bold text-lg">' . $result['vertical']['copies_per_sheet'] . '</div>
                                                    <div class="text-xs text-gray-600">' . $result['vertical']['layout'] . '</div>
                                                    ' . ($best['orientation'] === 'vertical' ? '<div class="text-xs text-green-600 mt-1">✓ Mejor</div>' : '') . '
                                                </div>

                                                <!-- Recomendado -->
                                                <div class="p-3 bg-green-100 border-2 border-green-400 rounded">
                                                    <div class="text-xs text-green-700 mb-1">⭐ Recomendado</div>
                                                    <div class="font-bold text-xl text-green-700">' . $best['copies_per_sheet'] . '</div>
                                                    <div class="text-xs text-green-600">copias/pliego</div>
                                                </div>
                                            </div>

                                            ' . $sheetsInfo . '

                                            <div class="text-xs text-gray-500 text-center">
                                                Máquina: ' . $machine->name . ' (' . ($machine->max_width ?? 50) . '×' . ($machine->max_height ?? 70) . 'cm) | Margen: 1cm por lado
                                            </div>
                                        </div>';

                                    return new \Illuminate\Support\HtmlString($content);

                                } catch (\Exception $e) {
                                    return new \Illuminate\Support\HtmlString('<div class="p-3 bg-red-50 rounded text-red-700 text-sm">
                                        ❌ Error al calcular montaje: ' . $e->getMessage() . '
                                    </div>');
                                }
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Sección de costos - ancho completo pero más compacta
                Section::make('💰 Costos y Márgenes')
                    ->collapsed()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('design_value')
                                    ->label('Diseño')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('transport_value')
                                    ->label('Transporte')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('rifle_value')
                                    ->label('Rifle/Doblez')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('profit_percentage')
                                    ->label('Ganancia')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(30)
                                    ->minValue(0)
                                    ->maxValue(100),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('cutting_cost')
                                    ->label('Corte (0 = automático)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('mounting_cost')
                                    ->label('Montaje (0 = automático)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),
                            ]),
                    ]),

                // Sección de resultados - solo visible en edición
                Section::make('📊 Resultados del Cálculo')
                    ->collapsed()
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Opciones de Montaje
                                Placeholder::make('mounting_options')
                                    ->label('Opciones de Montaje')
                                    ->content(function ($record) {
                                        if (!$record) return null;

                                        $simpleItem = $record;
                                        if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                            $simpleItem = $record->itemable;
                                        }

                                        if (!$simpleItem || !method_exists($simpleItem, 'getMountingOptions')) {
                                            return 'No disponible';
                                        }

                                        $options = $simpleItem->getMountingOptions();
                                        if (empty($options)) return 'Sin opciones';

                                        $content = '<div class="space-y-2">';
                                        foreach ($options as $index => $option) {
                                            $isSelected = $index === 0;
                                            $bgColor = $isSelected ? 'bg-green-50 border-green-300' : 'bg-gray-50 border-gray-200';

                                            $content .= "<div class='p-2 {$bgColor} rounded border'>";
                                            $content .= "<div class='flex justify-between items-center'>";
                                            $content .= "<div class='text-sm'>";
                                            $content .= "<span class='font-medium'>" . ucfirst($option->orientation) . "</span>";
                                            if ($isSelected) $content .= " <span class='text-green-600'>✓</span>";
                                            $content .= "<div class='text-xs text-gray-600'>";
                                            $content .= "{$option->cutsPerSheet} cortes | {$option->sheetsNeeded} pliegos | ";
                                            $content .= number_format($option->utilizationPercentage, 1) . "% aprovech.";
                                            $content .= "</div></div>";
                                            $content .= "<span class='font-bold text-sm'>$" . number_format($option->paperCost, 0) . "</span>";
                                            $content .= "</div></div>";
                                        }
                                        $content .= '</div>';

                                        return $content;
                                    })
                                    ->html(),

                                // Resumen Financiero
                                Placeholder::make('pricing_summary')
                                    ->label('Resumen Financiero')
                                    ->content(function ($record) {
                                        if (!$record) return null;

                                        $simpleItem = $record;
                                        if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                            $simpleItem = $record->itemable;
                                        }

                                        if (!$simpleItem || !isset($simpleItem->final_price)) {
                                            return 'No disponible';
                                        }

                                        $unitPrice = $simpleItem->final_price / max($simpleItem->quantity, 1);
                                        $profitAmount = ($simpleItem->final_price ?? 0) - ($simpleItem->total_cost ?? 0);

                                        $content = '<div class="space-y-1.5">';
                                        $content .= '<div class="flex justify-between text-sm">';
                                        $content .= '<span class="text-gray-600">Subtotal</span>';
                                        $content .= '<span>$' . number_format($simpleItem->total_cost ?? 0, 0) . '</span>';
                                        $content .= '</div>';

                                        $content .= '<div class="flex justify-between text-sm text-green-600">';
                                        $content .= '<span>Ganancia (' . ($simpleItem->profit_percentage ?? 0) . '%)</span>';
                                        $content .= '<span>+$' . number_format($profitAmount, 0) . '</span>';
                                        $content .= '</div>';

                                        $content .= '<div class="flex justify-between font-bold text-base border-t pt-1.5 mt-1">';
                                        $content .= '<span>TOTAL</span>';
                                        $content .= '<span class="text-blue-600">$' . number_format($simpleItem->final_price ?? 0, 0) . '</span>';
                                        $content .= '</div>';

                                        $content .= '<div class="text-center text-xs text-gray-500 mt-1">';
                                        $content .= 'Unitario: <strong>$' . number_format($unitPrice, 2) . '</strong>';
                                        $content .= '</div>';
                                        $content .= '</div>';

                                        return $content;
                                    })
                                    ->html(),
                            ]),

                        // Desglose de costos - ancho completo
                        Placeholder::make('detailed_breakdown')
                            ->label('Desglose Detallado')
                            ->content(function ($record) {
                                if (!$record) return null;

                                $simpleItem = $record;
                                if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                    $simpleItem = $record->itemable;
                                }

                                if (!$simpleItem || !method_exists($simpleItem, 'getDetailedCostBreakdown')) {
                                    return 'No disponible';
                                }

                                $breakdown = $simpleItem->getDetailedCostBreakdown();
                                if (empty($breakdown)) return 'Sin desglose';

                                $content = '<div class="grid grid-cols-2 gap-2">';
                                foreach ($breakdown as $key => $detail) {
                                    $cost = str_replace(['$', ','], '', $detail['cost']);
                                    if ($cost > 0) {
                                        $content .= '<div class="flex justify-between text-sm py-1 border-b border-gray-100">';
                                        $content .= '<span class="text-gray-700">' . $detail['description'] . '</span>';
                                        $content .= '<span class="font-medium">' . $detail['cost'] . '</span>';
                                        $content .= '</div>';
                                    }
                                }
                                $content .= '</div>';

                                return $content;
                            })
                            ->html()
                            ->columnSpanFull(),

                        // Validaciones técnicas
                        Placeholder::make('technical_validations')
                            ->label('Validaciones Técnicas')
                            ->content(function ($record) {
                                if (!$record) return null;

                                $simpleItem = $record;
                                if ($record instanceof \App\Models\DocumentItem && $record->itemable_type === 'App\\Models\\SimpleItem') {
                                    $simpleItem = $record->itemable;
                                }

                                if (!$simpleItem || !method_exists($simpleItem, 'validateTechnicalViability')) {
                                    return 'No disponible';
                                }

                                $validations = $simpleItem->validateTechnicalViability();

                                if (empty($validations)) {
                                    return '<div class="flex items-center text-green-600 text-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Todas las validaciones OK</span>
                                    </div>';
                                }

                                $content = '<div class="space-y-1">';
                                foreach ($validations as $validation) {
                                    $isError = $validation['type'] === 'error';
                                    $color = $isError ? 'red' : 'yellow';
                                    $content .= '<div class="flex items-start text-' . $color . '-600 text-sm">';
                                    $content .= '<span class="mr-1">⚠️</span>';
                                    $content .= '<span>' . $validation['message'] . '</span>';
                                    $content .= '</div>';
                                }
                                $content .= '</div>';

                                return $content;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Genera visualización SVG del montaje
     */
    public static function generateMountingSVG(
        array $mounting,
        float $machineWidth,
        float $machineHeight,
        float $workWidth,
        float $workHeight
    ): string {
        // Escala para que el SVG sea responsive (max 500px de ancho)
        $maxSvgWidth = 500;
        $scale = $maxSvgWidth / max($machineWidth, $machineHeight);

        // Dimensiones del SVG
        $svgWidth = $machineWidth * $scale;
        $svgHeight = $machineHeight * $scale;

        // Margen de 1cm escalado
        $margin = 1 * $scale;

        // Área útil
        $usableX = $margin;
        $usableY = $margin;
        $usableWidth = $svgWidth - (2 * $margin);
        $usableHeight = $svgHeight - (2 * $margin);

        // Dimensiones del trabajo (según orientación)
        $itemWidth = $mounting['work_width'] * $scale;
        $itemHeight = $mounting['work_height'] * $scale;

        // Número de copias en cada dirección
        $cols = $mounting['cols'];
        $rows = $mounting['rows'];

        // Calcular el espacio total que ocupan todos los trabajos PEGADOS (sin espaciado entre ellos)
        $totalWorksWidth = $cols * $itemWidth;
        $totalWorksHeight = $rows * $itemHeight;

        // NO hay espaciado entre copias - están pegadas
        $spacingX = 0;
        $spacingY = 0;

        // Calcular el offset para centrar todo el bloque de trabajos pegados
        $offsetX = $margin + ($usableWidth - $totalWorksWidth) / 2;
        $offsetY = $margin + ($usableHeight - $totalWorksHeight) / 2;

        $svg = '<svg width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" xmlns="http://www.w3.org/2000/svg" class="border-2 border-gray-400 rounded shadow-sm">';

        // Fondo del pliego (azul claro)
        $svg .= '<rect x="0" y="0" width="' . $svgWidth . '" height="' . $svgHeight . '" fill="#dbeafe" stroke="#3b82f6" stroke-width="2"/>';

        // Área de margen (amarillo claro con patrón)
        $svg .= '<defs>
            <pattern id="marginPattern" x="0" y="0" width="10" height="10" patternUnits="userSpaceOnUse">
                <rect width="10" height="10" fill="#fef3c7"/>
                <path d="M0,10 l10,-10 M-2.5,2.5 l5,-5 M7.5,12.5 l5,-5" stroke="#fbbf24" stroke-width="1" opacity="0.3"/>
            </pattern>
        </defs>';

        // Márgenes superior e inferior
        $svg .= '<rect x="0" y="0" width="' . $svgWidth . '" height="' . $margin . '" fill="url(#marginPattern)" stroke="#f59e0b" stroke-width="1" opacity="0.7"/>';
        $svg .= '<rect x="0" y="' . ($svgHeight - $margin) . '" width="' . $svgWidth . '" height="' . $margin . '" fill="url(#marginPattern)" stroke="#f59e0b" stroke-width="1" opacity="0.7"/>';

        // Márgenes izquierdo y derecho
        $svg .= '<rect x="0" y="' . $margin . '" width="' . $margin . '" height="' . ($svgHeight - 2 * $margin) . '" fill="url(#marginPattern)" stroke="#f59e0b" stroke-width="1" opacity="0.7"/>';
        $svg .= '<rect x="' . ($svgWidth - $margin) . '" y="' . $margin . '" width="' . $margin . '" height="' . ($svgHeight - 2 * $margin) . '" fill="url(#marginPattern)" stroke="#f59e0b" stroke-width="1" opacity="0.7"/>';

        // Dibujar cada copia del trabajo (centradas)
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $x = $offsetX + ($col * ($itemWidth + $spacingX));
                $y = $offsetY + ($row * ($itemHeight + $spacingY));

                // Rectángulo del trabajo (verde con gradiente)
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $itemWidth . '" height="' . $itemHeight . '"
                    fill="#86efac"
                    stroke="#16a34a"
                    stroke-width="1.5"
                    rx="2"
                    opacity="0.85"/>';

                // Número de copia (si caben más de 9, reducir tamaño de fuente)
                $fontSize = $mounting['copies_per_sheet'] > 20 ? 8 : 10;
                $copyNumber = ($row * $cols) + $col + 1;

                // Solo mostrar número si el item es lo suficientemente grande
                if ($itemWidth > 15 && $itemHeight > 15) {
                    $svg .= '<text x="' . ($x + $itemWidth / 2) . '" y="' . ($y + $itemHeight / 2) . '"
                        font-size="' . $fontSize . '"
                        fill="#166534"
                        font-weight="bold"
                        text-anchor="middle"
                        dominant-baseline="middle">' . $copyNumber . '</text>';
                }
            }
        }

        // Dimensiones del pliego (texto)
        $svg .= '<text x="' . ($svgWidth / 2) . '" y="15" font-size="12" fill="#1e40af" font-weight="bold" text-anchor="middle">' . $machineWidth . 'cm</text>';
        $svg .= '<text x="15" y="' . ($svgHeight / 2) . '" font-size="12" fill="#1e40af" font-weight="bold" text-anchor="middle" transform="rotate(-90 15 ' . ($svgHeight / 2) . ')">' . $machineHeight . 'cm</text>';

        // Dimensiones del trabajo (en la primera copia centrada)
        if ($cols > 0 && $rows > 0 && $itemWidth > 30 && $itemHeight > 20) {
            $firstX = $offsetX;
            $firstY = $offsetY;

            $svg .= '<text x="' . ($firstX + $itemWidth / 2) . '" y="' . ($firstY + $itemHeight + 12) . '" font-size="9" fill="#15803d" font-weight="bold" text-anchor="middle">' . number_format($mounting['work_width'], 1) . '×' . number_format($mounting['work_height'], 1) . 'cm</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }
}
