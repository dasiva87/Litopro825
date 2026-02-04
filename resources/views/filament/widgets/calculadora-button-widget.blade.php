<x-filament-widgets::widget class="fi-wi-calculator-btn">
    <div style="background: white; border-radius: 0.5rem; border: 1px solid #e5e7eb; display: flex; align-items: stretch;">
        {{-- Left accent --}}
        <div style="width: 4px; background: linear-gradient(180deg, #f59e0b 0%, #ea580c 100%); border-radius: 0.5rem 0 0 0.5rem; flex-shrink: 0;"></div>

        {{-- Content --}}
        <div style="flex: 1; display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; gap: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <div style="width: 1.75rem; height: 1.75rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: 1rem; height: 1rem; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div style="min-width: 0;">
                    <p style="font-size: 0.75rem; font-weight: 600; color: #111827; margin: 0; line-height: 1.1;">Calculadora de Corte</p>
                    <p style="font-size: 0.5rem; color: #6b7280; margin: 0; line-height: 1.2;">Optimiza pliegos</p>
                </div>
            </div>

            {{ $this->calculadoraAction }}
        </div>
    </div>

    <x-filament-actions::modals />

    <style>
        .fi-wi-calculator-btn {
            padding: 0 !important;
            margin: 0 !important;
        }
        .fi-wi-calculator-btn > div:first-child {
            margin: 0 !important;
        }
    </style>
</x-filament-widgets::widget>
