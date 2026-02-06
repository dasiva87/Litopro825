<?php

namespace App\Filament\Resources\SimpleItems\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use App\Models\Paper;
use App\Models\PrintingMachine;

class SimpleItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                // ═══════════════════════════════════════════════════════════════
                // COLUMNA IZQUIERDA (7/12) - Datos de Entrada
                // ═══════════════════════════════════════════════════════════════
                Grid::make(1)
                    ->columnSpan(7)
                    ->schema([
                        // Sección 1: Información del Producto
                        Section::make('')
                            ->compact()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Textarea::make('description')
                                            ->label('Descripción del Trabajo')
                                            ->required()
                                            ->rows(1)
                                            ->placeholder('Ej: Volantes, membretes, carpetas...')
                                            ->columnSpan(2),

                                        Grid::make(1)
                                            ->schema([
                                                TextInput::make('quantity')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->suffix('unid.')
                                                    ->live(onBlur: true),
                                            ])
                                            ->columnSpan(1),
                                        Grid::make(1)
                                            ->schema([
                                                TextInput::make('sobrante_papel')
                                                    ->label('Sobrante')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->suffix('unid.')
                                                    ->live(onBlur: true),
                                            ])
                                            ->columnSpan(1),
                                    ]),

                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('horizontal_size')
                                            ->label('Ancho')
                                            ->numeric()
                                            ->required()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->live(onBlur: true),

                                        TextInput::make('vertical_size')
                                            ->label('Alto')
                                            ->numeric()
                                            ->required()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->live(onBlur: true),

                                        Placeholder::make('area_calculation')
                                            ->label('Área')
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

                                                if (abs($h - 9) < 0.5 && abs($v - 5) < 0.5) return '<span class="text-blue-600 font-semibold">Tarjeta</span>';
                                                if (abs($h - 14.8) < 0.5 && abs($v - 21) < 0.5) return '<span class="text-blue-600 font-semibold">A5</span>';
                                                if (abs($h - 21) < 0.5 && abs($v - 29.7) < 0.5) return '<span class="text-blue-600 font-semibold">A4</span>';
                                                return '<span class="text-gray-500">Personalizado</span>';
                                            })
                                            ->html(),
                                    ]),
                            ]),

                        // Sección 2: Configuración de Impresión
                        Section::make('')
                            ->compact()
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
                                                              ->orWhere(function ($q) use ($supplierCompanyIds) {
                                                                  $q->whereIn('company_id', $supplierCompanyIds)
                                                                    ->where('is_public', true);
                                                              });
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
                                            ->preload(),

                                        Select::make('printing_machine_id')
                                            ->label('Máquina de Impresión')
                                            ->relationship(
                                                'printingMachine',
                                                'name',
                                                fn ($query) => $query->where('type', 'offset')->where('is_active', true)
                                            )
                                            ->getOptionLabelFromRecordUsing(fn($record) =>
                                                $record->name . ' (' . $record->max_width . '×' . $record->max_height . 'cm)'
                                            )
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live(),
                                    ]),

                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('ink_front_count')
                                            ->label('Tintas Frente')
                                            ->numeric()
                                            ->required()
                                            ->default(4)
                                            ->minValue(0)
                                            ->maxValue(8)
                                            ->live(onBlur: true),

                                        TextInput::make('ink_back_count')
                                            ->label('Tintas Reverso')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(8)
                                            ->live(onBlur: true),

                                        TextInput::make('margin_per_side')
                                            ->label('Margen de la pinza')
                                            ->numeric()
                                            ->default(1.0)
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(5)
                                            ->suffix('cm')
                                            ->live(onBlur: true),

                                        Placeholder::make('total_colors')
                                            ->label('Total Tintas')
                                            ->live()
                                            ->content(function ($get) {
                                                $front = $get('ink_front_count') ?? 0;
                                                $back = $get('ink_back_count') ?? 0;
                                                $total = $front + $back;
                                                return '<span class="text-lg font-bold text-primary-600">' . $total . '</span> <span class="text-xs text-gray-500">(' . $front . '+' . $back . ')</span>';
                                            })
                                            ->html(),
                                    ]),
                            ]),

                        // Sección 3: Tipo de Montaje
                        Section::make('')
                            ->compact()
                            ->schema([
                                \Filament\Forms\Components\Radio::make('mounting_type')
                                    ->label('')
                                    ->options([
                                        'automatic' => 'Automático (usa dimensiones de la máquina)',
                                        'custom' => 'Manual (papel personalizado)',
                                    ])
                                    ->default('automatic')
                                    ->inline()
                                    ->live(),

                                // Campos para montaje manual (solo visibles si es custom)
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('custom_paper_width')
                                            ->label('Ancho de Hoja')
                                            ->numeric()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->live(onBlur: true)
                                            ->helperText('Ancho del corte del pliego'),

                                        TextInput::make('custom_paper_height')
                                            ->label('Alto de Hoja')
                                            ->numeric()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->live(onBlur: true)
                                            ->helperText('Alto del corte del pliego'),

                                        Placeholder::make('custom_paper_area')
                                            ->label('Área Hoja')
                                            ->content(function ($get) {
                                                $w = $get('custom_paper_width');
                                                $h = $get('custom_paper_height');
                                                return $w && $h ? '<strong>' . number_format($w * $h, 2) . ' cm²</strong>' : '-';
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn ($get) => $get('mounting_type') === 'custom'),
                            ]),

                        // Sección 4: Costos y Márgenes
                        Section::make('Costos y Márgenes')
                            ->icon('heroicon-o-currency-dollar')
                            ->compact()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('design_value')
                                            ->label('Diseño')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->minValue(0)
                                            ->live(onBlur: true),

                                        TextInput::make('transport_value')
                                            ->label('Transporte')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->minValue(0)
                                            ->live(onBlur: true),

                                        TextInput::make('rifle_value')
                                            ->label('Rifle/Doblez')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->minValue(0)
                                            ->live(onBlur: true),

                                        TextInput::make('profit_percentage')
                                            ->label('Ganancia')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(30)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->live(onBlur: true),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('cutting_cost')
                                            ->label('Corte (0=auto)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->minValue(0)
                                            ->live(onBlur: true),

                                        TextInput::make('mounting_cost')
                                            ->label('Montaje (0=auto)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->minValue(0)
                                            ->live(onBlur: true),
                                    ]),
                            ]),
                    ]),

                // ═══════════════════════════════════════════════════════════════
                // COLUMNA DERECHA (5/12) - Vista Previa y Resultados
                // ═══════════════════════════════════════════════════════════════
                Grid::make(1)
                    ->columnSpan(5)
                    ->schema([
                        // Vista Previa de Montaje
                        Section::make('')
                            ->compact()
                            ->schema([
                                Placeholder::make('mounting_preview')
                                    ->label('')
                                    ->live()
                                    ->content(function ($get) {
                                        $horizontalSize = $get('horizontal_size');
                                        $verticalSize = $get('vertical_size');
                                        $machineId = $get('printing_machine_id');
                                        $mountingType = $get('mounting_type') ?? 'automatic';
                                        $customWidth = $get('custom_paper_width');
                                        $customHeight = $get('custom_paper_height');
                                        $quantity = $get('quantity') ?? 0;
                                        $sobrante = $get('sobrante_papel') ?? 0;
                                        $marginPerSide = $get('margin_per_side') ?? 1.0;

                                        // Validaciones iniciales
                                        if (!$horizontalSize || !$verticalSize) {
                                            return new \Illuminate\Support\HtmlString('
                                                <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; padding: 32px 16px; text-align: center;">
                                                    <div style="font-size: 48px; margin-bottom: 8px; opacity: 0.6;">📐</div>
                                                    <div style="color: #64748b; font-size: 13px; font-weight: 500;">Ingresa las dimensiones del trabajo</div>
                                                </div>
                                            ');
                                        }

                                        // Determinar dimensiones de la hoja según tipo de montaje
                                        if ($mountingType === 'custom') {
                                            if (!$customWidth || !$customHeight) {
                                                return new \Illuminate\Support\HtmlString('
                                                    <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 12px; padding: 32px 16px; text-align: center;">
                                                        <div style="font-size: 48px; margin-bottom: 8px;">✏️</div>
                                                        <div style="color: #3b82f6; font-size: 13px; font-weight: 500;">Ingresa dimensiones de hoja personalizada</div>
                                                    </div>
                                                ');
                                            }
                                            $sheetWidth = (float) $customWidth;
                                            $sheetHeight = (float) $customHeight;
                                            $sheetLabel = $customWidth . ' × ' . $customHeight . ' cm';
                                            $accentGradient = 'linear-gradient(135deg, #a855f7 0%, #7c3aed 100%)';
                                            $accentLight = '#f3e8ff';
                                            $accentBorder = '#a855f7';
                                        } else {
                                            if (!$machineId) {
                                                return new \Illuminate\Support\HtmlString('
                                                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; padding: 32px 16px; text-align: center;">
                                                        <div style="font-size: 48px; margin-bottom: 8px; opacity: 0.6;">🖨️</div>
                                                        <div style="color: #64748b; font-size: 13px; font-weight: 500;">Selecciona una máquina</div>
                                                    </div>
                                                ');
                                            }
                                            $machine = \App\Models\PrintingMachine::find($machineId);
                                            if (!$machine) {
                                                return new \Illuminate\Support\HtmlString('<div style="padding: 12px; background: #fef3c7; border-radius: 8px; color: #92400e; font-size: 13px;">Máquina no encontrada</div>');
                                            }
                                            $sheetWidth = $machine->max_width ?? 50.0;
                                            $sheetHeight = $machine->max_height ?? 70.0;
                                            $sheetLabel = $sheetWidth . ' × ' . $sheetHeight . ' cm';
                                            $accentGradient = 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)';
                                            $accentLight = '#eff6ff';
                                            $accentBorder = '#3b82f6';
                                        }

                                        try {
                                            $calc = new \App\Services\MountingCalculatorService();
                                            $result = $calc->calculateMounting(
                                                workWidth: (float) $horizontalSize,
                                                workHeight: (float) $verticalSize,
                                                machineWidth: $sheetWidth,
                                                machineHeight: $sheetHeight,
                                                marginPerSide: $marginPerSide
                                            );

                                            $best = $result['maximum'];

                                            if ($best['copies_per_sheet'] == 0) {
                                                return new \Illuminate\Support\HtmlString('
                                                    <div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-radius: 12px; padding: 24px; text-align: center;">
                                                        <div style="font-size: 40px; margin-bottom: 8px;">❌</div>
                                                        <div style="color: #dc2626; font-weight: 600; font-size: 14px;">El trabajo no cabe</div>
                                                        <div style="color: #f87171; font-size: 11px; margin-top: 4px;">' . $sheetLabel . '</div>
                                                    </div>
                                                ');
                                            }

                                            // Generar SVG
                                            $svgVisual = self::generateMountingSVG($best, $sheetWidth, $sheetHeight, (float) $horizontalSize, (float) $verticalSize);

                                            // Header con copias destacadas
                                            $headerHtml = '
                                                <div style="background: ' . $accentGradient . '; border-radius: 10px; padding: 12px 16px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                                    <div>
                                                        <div style="color: rgba(255,255,255,0.8); font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Copias por pliego</div>
                                                        <div style="color: white; font-size: 28px; font-weight: 700; line-height: 1;">' . $best['copies_per_sheet'] . '</div>
                                                    </div>
                                                    <div style="text-align: right;">
                                                        <div style="color: rgba(255,255,255,0.8); font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Layout</div>
                                                        <div style="color: white; font-size: 14px; font-weight: 600;">' . $best['layout'] . '</div>
                                                        <div style="color: rgba(255,255,255,0.7); font-size: 11px;">' . ucfirst($best['orientation']) . '</div>
                                                    </div>
                                                </div>
                                            ';

                                            // SVG Container
                                            $svgHtml = '
                                                <div style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px; padding: 12px; margin-bottom: 12px; display: flex; justify-content: center; align-items: center; min-height: 160px;">
                                                    ' . $svgVisual . '
                                                </div>
                                            ';

                                            // Info de hoja
                                            $infoHtml = '
                                                <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                                    <div style="flex: 1; background: #f8fafc; border-radius: 6px; padding: 8px 10px; border-left: 3px solid ' . $accentBorder . ';">
                                                        <div style="color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: 0.3px;">Hoja</div>
                                                        <div style="color: #1e293b; font-size: 12px; font-weight: 600;">' . $sheetLabel . '</div>
                                                    </div>
                                                    <div style="flex: 1; background: #f8fafc; border-radius: 6px; padding: 8px 10px; border-left: 3px solid #10b981;">
                                                        <div style="color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: 0.3px;">Trabajo</div>
                                                        <div style="color: #1e293b; font-size: 12px; font-weight: 600;">' . $horizontalSize . ' × ' . $verticalSize . ' cm</div>
                                                    </div>
                                                </div>
                                            ';

                                            // Calcular producción si hay cantidad
                                            $productionHtml = '';
                                            if ($quantity > 0) {
                                                $sheets = $calc->calculateRequiredSheets(
                                                    requiredCopies: (int) $quantity + (int) $sobrante,
                                                    copiesPerSheet: $best['copies_per_sheet']
                                                );

                                                $efficiency = $calc->calculateEfficiency(
                                                    workWidth: $best['work_width'],
                                                    workHeight: $best['work_height'],
                                                    copiesPerSheet: $best['copies_per_sheet'],
                                                    usableWidth: $sheetWidth - 2.0,
                                                    usableHeight: $sheetHeight - 2.0
                                                );

                                                $efficiencyColor = $efficiency >= 70 ? '#10b981' : ($efficiency >= 50 ? '#f59e0b' : '#ef4444');

                                                $productionHtml = '
                                                    <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 10px; padding: 12px; border: 1px solid #bbf7d0;">
                                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                                            <span style="color: #166534; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Producción</span>
                                                            <span style="background: ' . $efficiencyColor . '; color: white; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 10px;">' . number_format($efficiency, 0) . '% aprov.</span>
                                                        </div>
                                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                                                            <div style="text-align: center; background: white; border-radius: 6px; padding: 8px 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                                <div style="font-size: 20px; font-weight: 700; color: #1e40af;">' . $sheets['sheets_needed'] . '</div>
                                                                <div style="font-size: 9px; color: #64748b; text-transform: uppercase;">Pliegos</div>
                                                            </div>
                                                            <div style="text-align: center; background: white; border-radius: 6px; padding: 8px 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                                <div style="font-size: 20px; font-weight: 700; color: #059669;">' . number_format($sheets['total_copies_produced']) . '</div>
                                                                <div style="font-size: 9px; color: #64748b; text-transform: uppercase;">Total</div>
                                                            </div>
                                                            <div style="text-align: center; background: white; border-radius: 6px; padding: 8px 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                                <div style="font-size: 20px; font-weight: 700; color: #d97706;">' . $sheets['waste_copies'] . '</div>
                                                                <div style="font-size: 9px; color: #64748b; text-transform: uppercase;">Sobrante</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ';
                                            }

                                            // Opciones de montaje comparativas
                                            $hSelected = $best['orientation'] === 'horizontal';
                                            $vSelected = $best['orientation'] === 'vertical';

                                            $optionsHtml = '
                                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; margin-bottom: 12px;">
                                                    <div style="background: ' . ($hSelected ? $accentLight : '#f8fafc') . '; border: 2px solid ' . ($hSelected ? $accentBorder : '#e2e8f0') . '; border-radius: 8px; padding: 8px; text-align: center; position: relative;">
                                                        ' . ($hSelected ? '<div style="position: absolute; top: -6px; right: -6px; background: #10b981; color: white; font-size: 10px; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">✓</div>' : '') . '
                                                        <div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">↔ Horizontal</div>
                                                        <div style="font-size: 18px; font-weight: 700; color: #1e293b;">' . $result['horizontal']['copies_per_sheet'] . '</div>
                                                        <div style="font-size: 9px; color: #94a3b8;">' . $result['horizontal']['layout'] . '</div>
                                                    </div>
                                                    <div style="background: ' . ($vSelected ? $accentLight : '#f8fafc') . '; border: 2px solid ' . ($vSelected ? $accentBorder : '#e2e8f0') . '; border-radius: 8px; padding: 8px; text-align: center; position: relative;">
                                                        ' . ($vSelected ? '<div style="position: absolute; top: -6px; right: -6px; background: #10b981; color: white; font-size: 10px; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">✓</div>' : '') . '
                                                        <div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">↕ Vertical</div>
                                                        <div style="font-size: 18px; font-weight: 700; color: #1e293b;">' . $result['vertical']['copies_per_sheet'] . '</div>
                                                        <div style="font-size: 9px; color: #94a3b8;">' . $result['vertical']['layout'] . '</div>
                                                    </div>
                                                </div>
                                            ';

                                            $content = '<div>' . $headerHtml . $svgHtml . $infoHtml . $optionsHtml . $productionHtml . '</div>';

                                            return new \Illuminate\Support\HtmlString($content);

                                        } catch (\Exception $e) {
                                            return new \Illuminate\Support\HtmlString('
                                                <div style="padding: 16px; background: #fef2f2; border-radius: 8px; color: #dc2626; font-size: 12px;">
                                                    Error: ' . $e->getMessage() . '
                                                </div>
                                            ');
                                        }
                                    }),
                            ]),
                    ]),
            ]);
    }

    /**
     * Genera visualización SVG del montaje (versión compacta)
     */
    public static function generateMountingSVG(
        array $mounting,
        float $machineWidth,
        float $machineHeight,
        float $workWidth,
        float $workHeight
    ): string {
        // Escala para SVG compacto (max 280px de ancho)
        $maxSvgWidth = 280;
        $scale = $maxSvgWidth / max($machineWidth, $machineHeight);

        $svgWidth = $machineWidth * $scale;
        $svgHeight = $machineHeight * $scale;

        $margin = 1 * $scale;

        $usableWidth = $svgWidth - (2 * $margin);
        $usableHeight = $svgHeight - (2 * $margin);

        $itemWidth = $mounting['work_width'] * $scale;
        $itemHeight = $mounting['work_height'] * $scale;

        $cols = $mounting['cols'];
        $rows = $mounting['rows'];

        $totalWorksWidth = $cols * $itemWidth;
        $totalWorksHeight = $rows * $itemHeight;

        $offsetX = $margin + ($usableWidth - $totalWorksWidth) / 2;
        $offsetY = $margin + ($usableHeight - $totalWorksHeight) / 2;

        $svg = '<svg width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" xmlns="http://www.w3.org/2000/svg" class="rounded shadow-sm">';

        // Fondo del pliego
        $svg .= '<rect x="0" y="0" width="' . $svgWidth . '" height="' . $svgHeight . '" fill="#dbeafe" stroke="#3b82f6" stroke-width="2" rx="4"/>';

        // Márgenes (pattern simplificado)
        $svg .= '<rect x="0" y="0" width="' . $svgWidth . '" height="' . $margin . '" fill="#fef3c7" opacity="0.7"/>';
        $svg .= '<rect x="0" y="' . ($svgHeight - $margin) . '" width="' . $svgWidth . '" height="' . $margin . '" fill="#fef3c7" opacity="0.7"/>';
        $svg .= '<rect x="0" y="' . $margin . '" width="' . $margin . '" height="' . ($svgHeight - 2 * $margin) . '" fill="#fef3c7" opacity="0.7"/>';
        $svg .= '<rect x="' . ($svgWidth - $margin) . '" y="' . $margin . '" width="' . $margin . '" height="' . ($svgHeight - 2 * $margin) . '" fill="#fef3c7" opacity="0.7"/>';

        // Dibujar copias
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $x = $offsetX + ($col * $itemWidth);
                $y = $offsetY + ($row * $itemHeight);

                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $itemWidth . '" height="' . $itemHeight . '"
                    fill="#86efac" stroke="#16a34a" stroke-width="1" rx="2" opacity="0.85"/>';

                // Número de copia (si cabe)
                $fontSize = $mounting['copies_per_sheet'] > 20 ? 7 : 9;
                $copyNumber = ($row * $cols) + $col + 1;

                if ($itemWidth > 12 && $itemHeight > 12) {
                    $svg .= '<text x="' . ($x + $itemWidth / 2) . '" y="' . ($y + $itemHeight / 2) . '"
                        font-size="' . $fontSize . '" fill="#166534" font-weight="bold"
                        text-anchor="middle" dominant-baseline="middle">' . $copyNumber . '</text>';
                }
            }
        }

        // Dimensiones
        $svg .= '<text x="' . ($svgWidth / 2) . '" y="12" font-size="10" fill="#1e40af" font-weight="bold" text-anchor="middle">' . $machineWidth . 'cm</text>';
        $svg .= '<text x="10" y="' . ($svgHeight / 2) . '" font-size="10" fill="#1e40af" font-weight="bold" text-anchor="middle" transform="rotate(-90 10 ' . ($svgHeight / 2) . ')">' . $machineHeight . 'cm</text>';

        $svg .= '</svg>';

        return $svg;
    }
}
