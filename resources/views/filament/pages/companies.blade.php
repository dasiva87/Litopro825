<x-filament-panels::page>
    <div style="padding: 0;">
        <!-- Header con descripción -->
        <div style="margin-bottom: 32px;">
            <h1 style="font-size: 28px; font-weight: 700; color: #111827; margin: 0 0 8px 0;">
                Directorio de Empresas
            </h1>
            <p style="font-size: 16px; color: #6b7280; margin: 0;">
                Conecta con empresas del sector gráfico en toda Colombia
            </p>
        </div>

        <!-- Grid de cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px;">
            @forelse($companies as $company)
                <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.07); transition: all 0.3s;"
                     onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.12)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.07)'">

                    <!-- Header del card con avatar grande -->
                    <div style="height: 140px; @if($company['banner']) background: url('{{ $company['banner'] }}') center/cover; @else background: #3CC8FF; @endif position: relative; display: flex; align-items: center; justify-content: center;">
                        @if($company['avatar'])
                            <img src="{{ $company['avatar'] }}"
                                 alt="{{ $company['name'] }}"
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
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
                            <div style="width: 100px; height: 100px; border-radius: 50%; background: {{ $gradients[$colorIndex] }}; display: flex; align-items: center; justify-content: center; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                <span style="color: white; font-size: 32px; font-weight: 700; letter-spacing: 2px;">{{ $company['avatar_initials'] }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Contenido del card -->
                    <div style="padding: 20px;">
                        <!-- Nombre de la empresa -->
                        <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 8px 0; text-align: center;">
                            {{ $company['name'] }}
                        </h3>

                        <!-- Tipo de empresa -->
                        <div style="display: flex; justify-content: center; margin-bottom: 12px;">
                            <span style="background: #f3f4f6; color: #6b7280; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                {{ $company['company_type'] }}
                            </span>
                        </div>

                        <!-- Bio (descripción) -->
                        @if($company['bio'])
                            <p style="font-size: 14px; color: #6b7280; margin: 0 0 16px 0; text-align: center; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $company['bio'] }}
                            </p>
                        @else
                            <p style="font-size: 14px; color: #9ca3af; margin: 0 0 16px 0; text-align: center; font-style: italic;">
                                Sin descripción
                            </p>
                        @endif

                        <!-- Información adicional -->
                        <div style="display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 16px; padding: 12px 0; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">
                            <!-- Ubicación -->
                            @if($company['city'])
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <svg style="width: 16px; height: 16px; color: #667eea;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span style="font-size: 13px; color: #6b7280;">{{ $company['city'] }}</span>
                                </div>
                            @endif

                            <!-- Seguidores -->
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <svg style="width: 16px; height: 16px; color: #667eea;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span style="font-size: 13px; color: #6b7280; font-weight: 500;">{{ $company['followers_count'] }}</span>
                            </div>

                            <!-- Posts -->
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <svg style="width: 16px; height: 16px; color: #667eea;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                                <span style="font-size: 13px; color: #6b7280; font-weight: 500;">{{ $company['posts_count'] }} posts</span>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div style="display: flex; gap: 12px;">
                            <!-- Botón Ver Perfil -->
                            <a href="/admin/empresa/{{ $company['slug'] }}"
                               style="flex: 1; padding: 10px 16px; background: white; border: 2px solid #667eea; color: #667eea; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; transition: all 0.2s;"
                               onmouseover="this.style.background='#667eea'; this.style.color='white'"
                               onmouseout="this.style.background='white'; this.style.color='#667eea'">
                                Ver Perfil
                            </a>

                            <!-- Botón Seguir/Siguiendo -->
                            @if($company['is_following'])
                                <button wire:click="followCompany({{ $company['id'] }})"
                                        style="flex: 1; padding: 10px 16px; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                                        onmouseover="this.style.background='#059669'"
                                        onmouseout="this.style.background='#10b981'">
                                    ✓ Siguiendo
                                </button>
                            @else
                                <button wire:click="followCompany({{ $company['id'] }})"
                                        style="flex: 1; padding: 10px 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);"
                                        onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.4)'"
                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(102, 126, 234, 0.3)'">
                                    + Seguir
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 64px 24px;">
                    <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 40px; height: 40px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 style="font-size: 18px; color: #6b7280; font-weight: 600; margin: 0 0 8px 0;">No hay empresas disponibles</h3>
                    <p style="font-size: 14px; color: #9ca3af;">Las empresas registradas aparecerán aquí</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
