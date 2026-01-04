<div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <div style="text-align: center;">
        <div style="margin-bottom: 16px;">
            <svg style="width: 48px; height: 48px; color: #3b82f6; margin: 0 auto;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 8px 0;">Calculadora de Corte</h3>
        <p style="font-size: 14px; color: #6b7280; margin: 0 0 20px 0;">Calcula cuántas piezas caben en una hoja</p>

        {{ $this->calculadoraAction }}

        <x-filament-actions::modals />
    </div>
</div>
