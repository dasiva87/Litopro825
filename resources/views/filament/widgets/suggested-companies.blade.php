<div>
    <x-filament-widgets::widget>
        <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.07);">
            <!-- Header con gradiente -->
            <div style="padding: 20px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <svg style="width: 24px; height: 24px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <h2 style="font-size: 18px; font-weight: 700; color: white; margin: 0;">Empresas Sugeridas</h2>
                </div>
                <p style="font-size: 14px; color: rgba(255,255,255,0.9); margin: 0;">Conecta con proveedores de tu zona</p>
            </div>

            <!-- Lista de empresas -->
            <div style="padding: 16px;">
                @forelse($this->getViewData()['suggestions'] as $company)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; margin-bottom: 12px; border-radius: 12px; border: 1px solid #e5e7eb; background: #fafafa; transition: all 0.2s;"
                         onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.borderColor='#d1d5db'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'"
                         onmouseout="this.style.backgroundColor='#fafafa'; this.style.borderColor='#e5e7eb'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">

                        <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 0;">
                            <!-- Avatar circular -->
                            @if($company['avatar_url'])
                                <img src="{{ $company['avatar_url'] }}"
                                     alt="{{ $company['name'] }}"
                                     style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            @else
                                @php
                                    $gradients = [
                                        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                                        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                                        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                                        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                                        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                                    ];
                                    $colorIndex = ord($company['avatar_initials'][0]) % count($gradients);
                                @endphp
                                <div style="width: 52px; height: 52px; border-radius: 50%; background: {{ $gradients[$colorIndex] }}; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <span style="color: white; font-size: 18px; font-weight: 700; letter-spacing: 1px;">{{ $company['avatar_initials'] }}</span>
                                </div>
                            @endif

                            <!-- Info de la empresa -->
                            <div style="flex: 1; min-width: 0;">
                                <p style="font-size: 15px; font-weight: 600; color: #111827; margin: 0 0 6px 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $company['name'] }}
                                </p>
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280;">
                                    @if($company['city'])
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span>{{ $company['city'] }}</span>
                                        </div>
                                        <span style="color: #d1d5db;">•</span>
                                    @endif
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <span style="font-weight: 500;">{{ $company['followers_count'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón seguir -->
                        <button
                            wire:click="followCompany({{ $company['id'] }})"
                            style="padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3); white-space: nowrap;"
                            onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.4)'"
                            onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(102, 126, 234, 0.3)'"
                        >
                            + Seguir
                        </button>
                    </div>
                @empty
                    <div style="text-align: center; padding: 48px 24px;">
                        <div style="width: 64px; height: 64px; margin: 0 auto 16px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 32px; height: 32px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <p style="font-size: 15px; color: #6b7280; font-weight: 500;">No hay sugerencias disponibles</p>
                        <p style="font-size: 13px; color: #9ca3af; margin-top: 8px;">Pronto encontrarás empresas cerca de ti</p>
                    </div>
                @endforelse
            </div>

            <!-- Footer con link -->
            @if($this->getViewData()['suggestions']->count() > 0)
                <div style="padding: 16px 24px; border-top: 1px solid #e5e7eb; background: #fafafa; text-align: center;">
                    <a href="/admin/companies" style="font-size: 14px; font-weight: 600; color: #667eea; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s;" onmouseover="this.style.color='#764ba2'" onmouseout="this.style.color='#667eea'">
                        Ver todas las empresas
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            @endif
        </div>
    </x-filament-widgets::widget>
</div>