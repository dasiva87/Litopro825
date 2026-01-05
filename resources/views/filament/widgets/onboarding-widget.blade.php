<div>
@php
    $hideOnboarding = auth()->user()?->preferences['hide_onboarding'] ?? false;
@endphp

@if(!$hideOnboarding)
<div style="background-color:#1A2752; border-radius: 16px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); margin-bottom: 24px; position: relative; overflow: hidden;">
    <!-- Decorative background elements -->
    <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; opacity: 0.5;"></div>
    <div style="position: absolute; bottom: -30px; left: -30px; width: 80px; height: 80px; background: rgba(255,255,255,0.08); border-radius: 50%; opacity: 0.7;"></div>

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; position: relative; z-index: 1;">
        <div style="display: flex; align-items: center;">
            <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 16px; backdrop-filter: blur(10px);">
                <svg style="width: 24px; height: 24px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h3 style="font-size: 24px; font-weight: 700; color: white; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                    Configuración Inicial
                </h3>
                <p style="font-size: 16px; color: rgba(255,255,255,0.9); margin: 4px 0 0 0; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
                    Completa estos pasos para empezar a usar la plataforma
                </p>
            </div>
        </div>

        <!-- Progress Circle -->
        <div style="position: relative; width: 80px; height: 80px;">
            <svg style="width: 80px; height: 80px; transform: rotate(-90deg);" viewBox="0 0 36 36">
                <!-- Background circle -->
                <path style="fill: none; stroke: rgba(255,255,255,0.2); stroke-width: 2;"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <!-- Progress circle -->
                <path style="fill: none; stroke: #22c55e; stroke-width: 2; stroke-linecap: round; stroke-dasharray: {{ $this->getData()['progress'] }}, 100;"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
            </svg>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                <span style="font-size: 18px; font-weight: 700; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                    {{ $this->getData()['progress'] }}%
                </span>
            </div>
        </div>
    </div>

    <!-- Steps Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; position: relative; z-index: 1;">
        @foreach($this->getData()['steps'] as $step)
            <div style="background: rgba(255,255,255,0.95); border-radius: 12px; padding: 20px; backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s ease; {{ $step['completed'] ? 'box-shadow: 0 4px 20px rgba(34, 197, 94, 0.2);' : '' }}"
                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='{{ $step['completed'] ? '0 4px 20px rgba(34, 197, 94, 0.2)' : '0 4px 15px rgba(0,0,0,0.1)' }}';">

                <!-- Step Header -->
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; background: {{ $step['completed'] ? '#22c55e' : '#e5e7eb' }}; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 12px; transition: all 0.3s ease;">
                        @if($step['completed'])
                            <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg style="width: 20px; height: 20px; color: #9ca3af;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="{{ $step['icon'] === 'heroicon-o-hand-raised' ? 'M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732L14.146 12.8l-1.179 4.456a1 1 0 01-1.898-.632L12.72 12H9a1 1 0 110-2h3.72l-1.651-4.624a1 1 0 011.898-.632L14.146 9.2 17.5 11.134a1 1 0 010-1.732L14.146 7.2l-1.179-4.456A1 1 0 0112 2z' : ($step['icon'] === 'heroicon-o-building-office' ? 'M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z' : 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z') }}" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </div>
                    <h4 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">
                        {{ $step['title'] }}
                    </h4>
                </div>

                <!-- Step Description -->
                <p style="font-size: 14px; color: #6b7280; margin: 0 0 16px 0; line-height: 1.5;">
                    {{ $step['description'] }}
                </p>

                <!-- Action Button -->
                @if(!$step['completed'])
                    <a href="{{ $step['action_url'] }}"
                       style="display: block; width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; text-align: center; text-decoration: none;"
                       onmouseover="this.style.background='#2563eb';"
                       onmouseout="this.style.background='#3b82f6';">
                        {{ $step['action_label'] }}
                    </a>
                @else
                    <div style="display: flex; align-items: center; justify-content: center; padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                        <svg style="width: 16px; height: 16px; color: #16a34a; margin-right: 8px;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span style="font-size: 14px; font-weight: 500; color: #16a34a;">✓ Completado</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Footer with Quick Actions -->
    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); position: relative; z-index: 1;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="font-size: 14px; color: rgba(255,255,255,0.8); margin: 0;">
                    {{ $this->getData()['progress'] === 100 ? '¡Felicitaciones! Has completado la configuración inicial.' : 'Completa todos los pasos para aprovechar al máximo GrafiRed.' }}
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                @if($this->getData()['progress'] < 100)
                    <button wire:click="hideOnboarding"
                            style="padding: 10px 20px; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; backdrop-filter: blur(10px); transition: all 0.2s ease;"
                            onmouseover="this.style.background='rgba(255,255,255,0.3)';"
                            onmouseout="this.style.background='rgba(255,255,255,0.2)';">
                        Ocultar guía
                    </button>
                @else
                    <button wire:click="hideOnboarding"
                            style="padding: 10px 20px; background: rgba(255,255,255,0.95); color: #1A2752; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.15);"
                            onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='translateY(-1px)';"
                            onmouseout="this.style.background='rgba(255,255,255,0.95)'; this.style.transform='translateY(0)';">
                        ✓ Cerrar guía
                    </button>
                @endif
                <a href="/admin/dashboard"
                   style="padding: 10px 20px; background: #22c55e; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3); text-decoration: none; display: inline-block;"
                   onmouseover="this.style.background='#16a34a';"
                   onmouseout="this.style.background='#22c55e';">
                    Ver Panel de Control
                </a>
            </div>
        </div>
    </div>
</div>
@endif
</div>