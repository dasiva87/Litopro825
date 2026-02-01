@php
    $record = $getRecord();
    $record->refresh();
    $record->load('items.itemable');

    $totalQuantity = $record->items->sum(function($item) {
        return $item->itemable?->quantity ?? 1;
    });
    $unitPrice = $totalQuantity > 0 ? ($record->total ?? 0) / $totalQuantity : 0;
@endphp

<div wire:poll.3s style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; padding: 16px;">

    {{-- Header compacto --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <x-filament::icon icon="heroicon-o-calculator" class="h-4 w-4 text-white" />
            </div>
            <span style="font-size: 14px; font-weight: 600; color: #1e293b;">Resumen Financiero</span>
        </div>
        <div style="background: #eff6ff; padding: 4px 10px; border-radius: 12px;">
            <span style="font-size: 11px; color: #3b82f6; font-weight: 600;">{{ $record->items->count() }} items</span>
        </div>
    </div>

    {{-- Grid 2x2 de métricas --}}
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 12px;">

        {{-- Subtotal --}}
        <div style="background: white; border-radius: 10px; padding: 12px; border-left: 3px solid #3b82f6;">
            <div style="font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 4px;">Subtotal</div>
            <div style="font-size: 18px; font-weight: 700; color: #1e293b;">${{ number_format($record->subtotal ?? 0, 0, ',', '.') }}</div>
        </div>

        {{-- Descuento --}}
        <div style="background: white; border-radius: 10px; padding: 12px; border-left: 3px solid #f59e0b;">
            <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
                <span style="font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">Descuento</span>
                @if($record->discount_percentage > 0)
                    <span style="background: #fef3c7; color: #b45309; font-size: 8px; font-weight: 600; padding: 1px 5px; border-radius: 8px;">{{ $record->discount_percentage }}%</span>
                @endif
            </div>
            @if($record->discount_amount > 0)
                <div style="font-size: 18px; font-weight: 700; color: #d97706;">-${{ number_format($record->discount_amount, 0, ',', '.') }}</div>
            @else
                <div style="font-size: 13px; color: #94a3b8; font-style: italic;">Sin descuento</div>
            @endif
        </div>

        {{-- IVA --}}
        <div style="background: white; border-radius: 10px; padding: 12px; border-left: 3px solid #8b5cf6;">
            <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
                <span style="font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">IVA</span>
                @if($record->tax_percentage > 0)
                    <span style="background: #f3e8ff; color: #7c3aed; font-size: 8px; font-weight: 600; padding: 1px 5px; border-radius: 8px;">{{ $record->tax_percentage }}%</span>
                @endif
            </div>
            @if($record->tax_amount > 0)
                <div style="font-size: 18px; font-weight: 700; color: #7c3aed;">+${{ number_format($record->tax_amount, 0, ',', '.') }}</div>
            @else
                <div style="font-size: 13px; color: #94a3b8; font-style: italic;">Sin IVA</div>
            @endif
        </div>

        {{-- Total --}}
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 10px; padding: 12px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -15px; right: -15px; width: 50px; height: 50px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: relative; z-index: 1;">
                <div style="font-size: 10px; color: rgba(255,255,255,0.85); text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 4px;">Total</div>
                <div style="font-size: 22px; font-weight: 800; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">${{ number_format($record->total ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Footer con precio unitario --}}
    <div style="display: flex; justify-content: center; padding-top: 10px; border-top: 1px dashed #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 11px; color: #64748b;">Precio unitario:</span>
            <span style="font-size: 14px; font-weight: 700; color: #1e293b;">${{ number_format($unitPrice, 0, ',', '.') }}</span>
        </div>
    </div>
</div>
