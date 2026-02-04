<x-filament-widgets::widget>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        {{-- Panel de Entrada --}}
        <x-filament::section>
            <x-slot name="heading">
                <div style="display: flex; align-items: center; gap: 0.375rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1rem; height: 1rem; color: #6366f1;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m7.848 8.25 1.536.887M7.848 8.25a3 3 0 1 1-5.196-3 3 3 0 0 1 5.196 3Zm1.536.887a2.165 2.165 0 0 1 1.083 1.839c.005.351.054.695.14 1.024M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 1 1-5.196 3 3 3 0 0 1 5.196-3Zm1.536-.887a2.165 2.165 0 0 0 1.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863 2.077-1.199m0-3.328a4.323 4.323 0 0 1 2.068-1.379l5.325-1.628a4.5 4.5 0 0 1 2.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0 0 10.607 12m3.736 0 7.794 4.5-.802.215a4.5 4.5 0 0 1-2.48-.043l-5.326-1.629a4.324 4.324 0 0 1-2.068-1.379M14.343 12l-2.882 1.664" />
                    </svg>
                    <span>Calculadora de Cortes</span>
                </div>
            </x-slot>

            <form wire:submit="calcular">
                {{-- Dimensiones del Papel --}}
                <div style="padding: 0.625rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 0.5rem; border: 1px solid #93c5fd; margin-bottom: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.375rem; margin-bottom: 0.5rem;">
                        <div style="padding: 0.25rem; background: #3b82f6; border-radius: 0.25rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" style="width: 0.75rem; height: 0.75rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <span style="font-weight: 600; font-size: 0.75rem; color: #1e40af;">Pliego de Papel</span>
                        <span style="margin-left: auto; font-size: 0.625rem; color: #2563eb;">{{ number_format(floatval($anchoPapel) * floatval($largoPapel), 0) }} cm²</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <div>
                            <label style="display: block; font-size: 0.625rem; font-weight: 500; color: #1e40af; margin-bottom: 0.25rem;">Ancho (cm)</label>
                            <input type="number" wire:model.live="anchoPapel" min="1" step="0.1"
                                style="width: 100%; padding: 0.375rem; font-size: 0.875rem; font-weight: 600; text-align: center; border: 1px solid #60a5fa; border-radius: 0.375rem; background: white;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.625rem; font-weight: 500; color: #1e40af; margin-bottom: 0.25rem;">Largo (cm)</label>
                            <input type="number" wire:model.live="largoPapel" min="1" step="0.1"
                                style="width: 100%; padding: 0.375rem; font-size: 0.875rem; font-weight: 600; text-align: center; border: 1px solid #60a5fa; border-radius: 0.375rem; background: white;">
                        </div>
                    </div>
                </div>

                {{-- Dimensiones del Corte --}}
                <div style="padding: 0.625rem; background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border-radius: 0.5rem; border: 1px solid #86efac; margin-bottom: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.375rem; margin-bottom: 0.5rem;">
                        <div style="padding: 0.25rem; background: #22c55e; border-radius: 0.25rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" style="width: 0.75rem; height: 0.75rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-7.5A2.25 2.25 0 0 1 8.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 0 0-2.25 2.25v6" />
                            </svg>
                        </div>
                        <span style="font-weight: 600; font-size: 0.75rem; color: #166534;">Pieza a Cortar</span>
                        <span style="margin-left: auto; font-size: 0.625rem; color: #16a34a;">{{ number_format(floatval($anchoCorte ?: 0) * floatval($largoCorte ?: 0), 0) }} cm²</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <div>
                            <label style="display: block; font-size: 0.625rem; font-weight: 500; color: #166534; margin-bottom: 0.25rem;">Ancho (cm)</label>
                            <input type="number" wire:model.live="anchoCorte" min="0.1" step="0.1"
                                style="width: 100%; padding: 0.375rem; font-size: 0.875rem; font-weight: 600; text-align: center; border: 1px solid #4ade80; border-radius: 0.375rem; background: white;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.625rem; font-weight: 500; color: #166534; margin-bottom: 0.25rem;">Largo (cm)</label>
                            <input type="number" wire:model.live="largoCorte" min="0.1" step="0.1"
                                style="width: 100%; padding: 0.375rem; font-size: 0.875rem; font-weight: 600; text-align: center; border: 1px solid #4ade80; border-radius: 0.375rem; background: white;">
                        </div>
                    </div>
                </div>

                {{-- Cantidad Deseada --}}
                <div style="padding: 0.625rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 0.5rem; border: 1px solid #fcd34d; margin-bottom: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.375rem; margin-bottom: 0.5rem;">
                        <div style="padding: 0.25rem; background: #f59e0b; border-radius: 0.25rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" style="width: 0.75rem; height: 0.75rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0 0 12 2.25Z" />
                            </svg>
                        </div>
                        <span style="font-weight: 600; font-size: 0.75rem; color: #92400e;">Cantidad</span>
                    </div>
                    <input type="number" wire:model.live="cantidadDeseada" min="1" step="1"
                        style="width: 100%; padding: 0.375rem; font-size: 1rem; font-weight: 700; text-align: center; border: 1px solid #fbbf24; border-radius: 0.375rem; background: white;">
                </div>

                {{-- Selector de Orientación --}}
                <div style="margin-bottom: 0.75rem;">
                    <label style="display: block; font-size: 0.625rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">Modo de Corte</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.375rem;">
                        <button type="button" wire:click="setOrientacion('vertical')"
                            style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0.5rem; border-radius: 0.5rem; border: 2px solid {{ $orientacion === 'vertical' ? '#6366f1' : '#e5e7eb' }}; background: {{ $orientacion === 'vertical' ? '#eef2ff' : '#f9fafb' }}; color: {{ $orientacion === 'vertical' ? '#4338ca' : '#6b7280' }}; cursor: pointer;">
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="8" y="3" width="8" height="18" rx="1" stroke-width="2"/>
                            </svg>
                            <span style="font-size: 0.625rem; font-weight: 600;">Vertical</span>
                            @if($orientacion === 'vertical')
                                <span style="position: absolute; top: -0.25rem; right: -0.25rem; width: 0.875rem; height: 0.875rem; background: #6366f1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <svg style="width: 0.5rem; height: 0.5rem; color: white;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                            @endif
                        </button>

                        <button type="button" wire:click="setOrientacion('horizontal')"
                            style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0.5rem; border-radius: 0.5rem; border: 2px solid {{ $orientacion === 'horizontal' ? '#10b981' : '#e5e7eb' }}; background: {{ $orientacion === 'horizontal' ? '#ecfdf5' : '#f9fafb' }}; color: {{ $orientacion === 'horizontal' ? '#047857' : '#6b7280' }}; cursor: pointer;">
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="8" width="18" height="8" rx="1" stroke-width="2"/>
                            </svg>
                            <span style="font-size: 0.625rem; font-weight: 600;">Horizontal</span>
                            @if($orientacion === 'horizontal')
                                <span style="position: absolute; top: -0.25rem; right: -0.25rem; width: 0.875rem; height: 0.875rem; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <svg style="width: 0.5rem; height: 0.5rem; color: white;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                            @endif
                        </button>

                        <button type="button" wire:click="setOrientacion('optimo')"
                            style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0.5rem; border-radius: 0.5rem; border: 2px solid {{ $orientacion === 'optimo' ? '#f97316' : '#e5e7eb' }}; background: {{ $orientacion === 'optimo' ? '#fff7ed' : '#f9fafb' }}; color: {{ $orientacion === 'optimo' ? '#c2410c' : '#6b7280' }}; cursor: pointer;">
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                            </svg>
                            <span style="font-size: 0.625rem; font-weight: 600;">Optimo</span>
                            @if($orientacion === 'optimo')
                                <span style="position: absolute; top: -0.25rem; right: -0.25rem; width: 0.875rem; height: 0.875rem; background: #f97316; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <svg style="width: 0.5rem; height: 0.5rem; color: white;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                            @endif
                        </button>
                    </div>
                </div>

                {{-- Botón Reset --}}
                <div style="display: flex; justify-content: center;">
                    <button type="button" wire:click="resetCalculator"
                        style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.625rem; font-weight: 500; color: #6b7280; background: #f3f4f6; border: none; border-radius: 0.375rem; cursor: pointer;">
                        <svg style="width: 0.75rem; height: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Restablecer
                    </button>
                </div>
            </form>
        </x-filament::section>

        {{-- Panel de Resultados --}}
        <div>
            @if($calculado && $resultado)
                @if(isset($resultado['error']))
                    <x-filament::section>
                        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem; background: #fef2f2; border-radius: 0.5rem; border: 1px solid #fecaca;">
                            <div style="padding: 0.375rem; background: #fee2e2; border-radius: 50%;">
                                <svg style="width: 1rem; height: 1rem; color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                            </div>
                            <div>
                                <p style="font-size: 0.75rem; font-weight: 600; color: #991b1b; margin: 0;">Error</p>
                                <p style="font-size: 0.625rem; color: #dc2626; margin: 0;">{{ $resultado['error'] }}</p>
                            </div>
                        </div>
                    </x-filament::section>
                @else
                    {{-- Header --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.625rem; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 0.5rem; color: white; margin-bottom: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.375rem;">
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <div>
                                <p style="font-size: 0.5rem; opacity: 0.9; margin: 0;">Modo</p>
                                <p style="font-size: 0.875rem; font-weight: 700; margin: 0;">{{ ucfirst($resultado['orientacion']) }}</p>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <p style="font-size: 0.5rem; opacity: 0.9; margin: 0;">Eficiencia</p>
                            <p style="font-size: 1.25rem; font-weight: 700; margin: 0;">{{ $resultado['eficiencia'] }}%</p>
                        </div>
                    </div>

                    {{-- Métricas --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.375rem; margin-bottom: 0.5rem;">
                        <div style="padding: 0.5rem; background: white; border-radius: 0.375rem; border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.25rem;">
                                <div style="padding: 0.125rem; background: #f3e8ff; border-radius: 0.25rem;">
                                    <svg style="width: 0.625rem; height: 0.625rem; color: #9333ea;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                    </svg>
                                </div>
                                <span style="font-size: 0.5rem; color: #6b7280;">Piezas/pliego</span>
                            </div>
                            <p style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">{{ $resultado['piezasPorHoja'] }}</p>
                            <p style="font-size: 0.5rem; color: #9ca3af; margin: 0;">{{ $resultado['piezasPorAncho'] }}x{{ $resultado['piezasPorLargo'] }}</p>
                        </div>

                        <div style="padding: 0.5rem; background: white; border-radius: 0.375rem; border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.25rem;">
                                <div style="padding: 0.125rem; background: #dbeafe; border-radius: 0.25rem;">
                                    <svg style="width: 0.625rem; height: 0.625rem; color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                                    </svg>
                                </div>
                                <span style="font-size: 0.5rem; color: #6b7280;">Pliegos</span>
                            </div>
                            <p style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">{{ $resultado['hojasNecesarias'] }}</p>
                            <p style="font-size: 0.5rem; color: #9ca3af; margin: 0;">Para {{ number_format($cantidadDeseada) }}</p>
                        </div>

                        <div style="padding: 0.5rem; background: white; border-radius: 0.375rem; border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.25rem;">
                                <div style="padding: 0.125rem; background: #dcfce7; border-radius: 0.25rem;">
                                    <svg style="width: 0.625rem; height: 0.625rem; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <span style="font-size: 0.5rem; color: #6b7280;">Obtenidas</span>
                            </div>
                            <p style="font-size: 1.25rem; font-weight: 700; color: #16a34a; margin: 0;">{{ number_format($resultado['piezasObtenidas']) }}</p>
                            @if($resultado['piezasSobrantes'] > 0)
                                <p style="font-size: 0.5rem; color: #16a34a; margin: 0;">+{{ $resultado['piezasSobrantes'] }} extra</p>
                            @endif
                        </div>

                        <div style="padding: 0.5rem; background: white; border-radius: 0.375rem; border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.25rem;">
                                <div style="padding: 0.125rem; background: #fef3c7; border-radius: 0.25rem;">
                                    <svg style="width: 0.625rem; height: 0.625rem; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </div>
                                <span style="font-size: 0.5rem; color: #6b7280;">Desperdicio</span>
                            </div>
                            <p style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">{{ number_format($resultado['desperdicioArea']) }}</p>
                            <p style="font-size: 0.5rem; color: #9ca3af; margin: 0;">cm²/pliego</p>
                        </div>
                    </div>

                    {{-- Barra de Eficiencia --}}
                    <div style="padding: 0.5rem; background: white; border-radius: 0.375rem; border: 1px solid #e5e7eb; margin-bottom: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.625rem; margin-bottom: 0.25rem;">
                            <span style="color: #6b7280;">Aprovechamiento</span>
                            <span style="font-weight: 600;">{{ $resultado['eficiencia'] }}%</span>
                        </div>
                        <div style="height: 0.5rem; background: #e5e7eb; border-radius: 9999px; overflow: hidden;">
                            <div style="height: 100%; border-radius: 9999px; width: {{ min($resultado['eficiencia'], 100) }}%; background: {{ $resultado['eficiencia'] >= 80 ? '#22c55e' : ($resultado['eficiencia'] >= 60 ? '#f59e0b' : '#ef4444') }};"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.5rem; color: #9ca3af; margin-top: 0.25rem;">
                            <span>Area: {{ number_format($resultado['areaPapel']) }} cm²</span>
                            <span>Util: {{ number_format($resultado['areaUtil']) }} cm²</span>
                        </div>
                    </div>

                    {{-- SVG --}}
                    <div style="padding: 0.5rem; background: #f9fafb; border-radius: 0.375rem; border: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: center; padding: 0.5rem;">
                            {!! $this->generateCuttingSVG() !!}
                        </div>
                        <div style="display: flex; justify-content: center; gap: 0.75rem; padding-top: 0.375rem; border-top: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 0.25rem;">
                                <div style="width: 0.5rem; height: 0.5rem; background: #93c5fd; border-radius: 0.125rem;"></div>
                                <span style="font-size: 0.5rem; color: #6b7280;">Pliego</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.25rem;">
                                <div style="width: 0.5rem; height: 0.5rem; background: #86efac; border-radius: 0.125rem;"></div>
                                <span style="font-size: 0.5rem; color: #6b7280;">Piezas</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.25rem;">
                                <div style="width: 0.5rem; height: 0.5rem; background: #fdba74; border-radius: 0.125rem;"></div>
                                <span style="font-size: 0.5rem; color: #6b7280;">Auxiliar</span>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <x-filament::section>
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center;">
                        <div style="padding: 0.75rem; background: #f3f4f6; border-radius: 50%; margin-bottom: 0.5rem;">
                            <svg style="width: 2rem; height: 2rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m7.848 8.25 1.536.887M7.848 8.25a3 3 0 1 1-5.196-3 3 3 0 0 1 5.196 3Zm1.536.887a2.165 2.165 0 0 1 1.083 1.839c.005.351.054.695.14 1.024M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 1 1-5.196 3 3 3 0 0 1 5.196-3Zm1.536-.887a2.165 2.165 0 0 0 1.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863 2.077-1.199m0-3.328a4.323 4.323 0 0 1 2.068-1.379l5.325-1.628a4.5 4.5 0 0 1 2.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0 0 10.607 12m3.736 0 7.794 4.5-.802.215a4.5 4.5 0 0 1-2.48-.043l-5.326-1.629a4.324 4.324 0 0 1-2.068-1.379M14.343 12l-2.882 1.664" />
                            </svg>
                        </div>
                        <p style="font-size: 0.75rem; font-weight: 600; color: #374151; margin: 0;">Vista previa</p>
                        <p style="font-size: 0.625rem; color: #6b7280; margin: 0.25rem 0 0 0;">Ingresa dimensiones para calcular</p>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
