<x-filament-widgets::widget>
    <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0;">

        {{-- Header --}}
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-calculator" class="h-5 w-5 text-white" />
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Resumen Financiero</h3>
                    <p style="margin: 0; font-size: 11px; color: #64748b;">{{ $record->documentType?->name ?? 'Cotización' }} #{{ $record->document_number }}</p>
                </div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Items</span>
                <div style="font-size: 20px; font-weight: 700; color: #6366f1;">{{ $record->items->count() }}</div>
            </div>
        </div>

        {{-- Grid de métricas --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">

            {{-- Subtotal --}}
            <div style="background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);"></div>
                <div style="padding-left: 8px;">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                        <div style="background: #eff6ff; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                            <x-filament::icon icon="heroicon-o-currency-dollar" class="h-4 w-4" style="color: #3b82f6;" />
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-weight: 500;">Subtotal</span>
                    </div>
                    <div style="font-size: 22px; font-weight: 700; color: #1e293b;">
                        ${{ number_format($record->subtotal ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            {{-- Descuento --}}
            <div style="background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);"></div>
                <div style="padding-left: 8px;">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                        <div style="background: #fef3c7; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                            <x-filament::icon icon="heroicon-o-tag" class="h-4 w-4" style="color: #f59e0b;" />
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-weight: 500;">Descuento</span>
                        @if($record->discount_percentage > 0)
                            <span style="background: #fef3c7; color: #b45309; font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 10px;">{{ $record->discount_percentage }}%</span>
                        @endif
                    </div>
                    @if($record->discount_amount > 0)
                        <div style="font-size: 22px; font-weight: 700; color: #d97706;">
                            -${{ number_format($record->discount_amount, 0, ',', '.') }}
                        </div>
                    @else
                        <div style="font-size: 14px; color: #94a3b8; font-style: italic;">Sin descuento</div>
                    @endif
                </div>
            </div>

            {{-- IVA --}}
            <div style="background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);"></div>
                <div style="padding-left: 8px;">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                        <div style="background: #f3e8ff; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                            <x-filament::icon icon="heroicon-o-receipt-percent" class="h-4 w-4" style="color: #8b5cf6;" />
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-weight: 500;">IVA</span>
                        @if($record->tax_percentage > 0)
                            <span style="background: #f3e8ff; color: #7c3aed; font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 10px;">{{ $record->tax_percentage }}%</span>
                        @endif
                    </div>
                    @if($record->tax_amount > 0)
                        <div style="font-size: 22px; font-weight: 700; color: #7c3aed;">
                            +${{ number_format($record->tax_amount, 0, ',', '.') }}
                        </div>
                    @else
                        <div style="font-size: 14px; color: #94a3b8; font-style: italic;">Sin IVA</div>
                    @endif
                </div>
            </div>

            {{-- Total --}}
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 16px; position: relative; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);">
                <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                <div style="position: relative; z-index: 1;">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                        <div style="background: rgba(255,255,255,0.2); width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <x-filament::icon icon="heroicon-o-banknotes" class="h-4 w-4 text-white" />
                        </div>
                        <span style="font-size: 11px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total</span>
                    </div>
                    <div style="font-size: 26px; font-weight: 800; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        ${{ number_format($record->total ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer con información adicional --}}
        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed #e2e8f0;">
            <div style="display: flex; gap: 16px;">
                @if($record->contact)
                <div style="display: flex; align-items: center; gap: 6px;">
                    <x-filament::icon icon="heroicon-o-user" class="h-4 w-4" style="color: #64748b;" />
                    <span style="font-size: 12px; color: #475569;">{{ $record->contact->name }}</span>
                </div>
                @endif
                @if($record->valid_until)
                <div style="display: flex; align-items: center; gap: 6px;">
                    <x-filament::icon icon="heroicon-o-calendar" class="h-4 w-4" style="color: #64748b;" />
                    <span style="font-size: 12px; color: #475569;">Válido hasta: {{ $record->valid_until->format('d/m/Y') }}</span>
                </div>
                @endif
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; color: #94a3b8;">Precio unitario promedio:</span>
                <span style="font-size: 13px; font-weight: 600; color: #1e293b;">
                    @php
                        $totalQuantity = $record->items->sum(function($item) {
                            return $item->itemable?->quantity ?? 1;
                        });
                        $unitPrice = $totalQuantity > 0 ? ($record->total ?? 0) / $totalQuantity : 0;
                    @endphp
                    ${{ number_format($unitPrice, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
