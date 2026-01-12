<x-filament-panels::page>
    <style>
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .profile-header-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="profile-container">
        <!-- Encabezado del Perfil con Banner -->
        <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.07); margin-bottom: 24px;">
            <!-- Banner -->
            <div style="height: 200px; position: relative;" class="profile-header-gradient">
                @if($company->getBannerUrl())
                    <img src="{{ $company->getBannerUrl() }}" alt="Banner" style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;">
                @endif
                <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.15);"></div>
            </div>

            <!-- Información del Perfil -->
            <div style="padding: 0 32px 32px 32px; position: relative;">
                <!-- Avatar -->
                <div style="position: relative; margin-top: -80px; margin-bottom: 20px;">
                    <div style="width: 160px; height: 160px; background: white; border-radius: 50%; border: 6px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); overflow: hidden;">
                        @if($company->getAvatarUrl())
                            <img src="{{ $company->getAvatarUrl() }}" alt="{{ $company->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 48px; font-weight: 700; color: white; letter-spacing: 2px;">
                                    {{ strtoupper(substr($company->name, 0, 2)) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Nombre y Descripción -->
                <div style="margin-bottom: 24px;">
                    <h1 style="font-size: 32px; font-weight: 700; color: #111827; margin: 0 0 8px 0; line-height: 1.2;">
                        {{ $company->name }}
                    </h1>

                    @if($company->company_type)
                        <div style="display: inline-flex; align-items: center; padding: 6px 14px; background: #eff6ff; border-radius: 20px; margin-bottom: 12px;">
                            <span style="font-size: 13px; font-weight: 600; color: #2563eb;">{{ $company->company_type->label() }}</span>
                        </div>
                    @endif

                    @if($company->bio)
                        <p style="font-size: 16px; color: #6b7280; line-height: 1.6; margin-top: 12px; max-width: 800px;">
                            {{ $company->bio }}
                        </p>
                    @endif

                    <!-- Redes Sociales -->
                    @if($company->facebook || $company->instagram || $company->twitter || $company->linkedin)
                        <div style="display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;">
                            <!-- Facebook -->
                            @if($company->facebook)
                                <a href="{{ $company->facebook }}" target="_blank" rel="noopener noreferrer"
                                   style="width: 44px; height: 44px; border-radius: 50%; background: #1877f2; display: flex; align-items: center; justify-content: center; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                   onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'"
                                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'">
                                    <svg style="width: 24px; height: 24px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- Instagram -->
                            @if($company->instagram)
                                <a href="{{ $company->instagram }}" target="_blank" rel="noopener noreferrer"
                                   style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); display: flex; align-items: center; justify-content: center; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                   onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'"
                                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'">
                                    <svg style="width: 24px; height: 24px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- Twitter/X -->
                            @if($company->twitter)
                                <a href="{{ $company->twitter }}" target="_blank" rel="noopener noreferrer"
                                   style="width: 44px; height: 44px; border-radius: 50%; background: #000000; display: flex; align-items: center; justify-content: center; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                   onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'"
                                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'">
                                    <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- LinkedIn -->
                            @if($company->linkedin)
                                <a href="{{ $company->linkedin }}" target="_blank" rel="noopener noreferrer"
                                   style="width: 44px; height: 44px; border-radius: 50%; background: #0077b5; display: flex; align-items: center; justify-content: center; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                   onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'"
                                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'">
                                    <svg style="width: 24px; height: 24px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endif

                    <!-- Ubicación -->
                    @if($company->city || $company->state)
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 16px; color: #6b7280;">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span style="font-size: 15px; font-weight: 500;">
                                {{ $company->city ? $company->city->name : '' }}{{ $company->state && $company->city ? ', ' : '' }}{{ $company->state ? $company->state->name : '' }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Estadísticas -->
                <div style="display: flex; gap: 48px; padding: 24px 0; border-top: 2px solid #f3f4f6; border-bottom: 2px solid #f3f4f6;">
                    <div style="text-align: center;">
                        <div style="font-size: 32px; font-weight: 700; color: #111827; line-height: 1;">{{ $stats['posts_count'] }}</div>
                        <div style="font-size: 14px; color: #6b7280; margin-top: 4px; font-weight: 500;">Publicaciones</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 32px; font-weight: 700; color: #111827; line-height: 1;">{{ $stats['followers_count'] }}</div>
                        <div style="font-size: 14px; color: #6b7280; margin-top: 4px; font-weight: 500;">Seguidores</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 32px; font-weight: 700; color: #111827; line-height: 1;">{{ $stats['following_count'] }}</div>
                        <div style="font-size: 14px; color: #6b7280; margin-top: 4px; font-weight: 500;">Siguiendo</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Contenido -->
        <div class="profile-grid" style="display: grid; grid-template-columns: 1fr 400px; gap: 24px;">
            <!-- Columna Principal: Publicaciones -->
            <div>
                @livewire(\App\Filament\Widgets\CompanyPostsWidget::class, ['companyId' => $company->id])
            </div>

            <!-- Sidebar: Información de Contacto -->
            <div>
                @if($company->show_contact_info)
                    <div style="background: white; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.07); overflow: hidden; position: sticky; top: 24px;">
                        <!-- Header -->
                        <div style="padding: 20px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <svg style="width: 24px; height: 24px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <h3 style="font-size: 18px; font-weight: 700; color: white; margin: 0;">Información de Contacto</h3>
                            </div>
                        </div>

                        <!-- Contenido -->
                        <div style="padding: 24px;">
                            <div style="display: flex; flex-direction: column; gap: 20px;">
                                @if($company->email)
                                    <div style="display: flex; align-items: start; gap: 14px;">
                                        <div style="flex-shrink: 0; width: 40px; height: 40px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                            <svg style="width: 20px; height: 20px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-size: 12px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Email</div>
                                            <a href="mailto:{{ $company->email }}" style="font-size: 14px; color: #3b82f6; font-weight: 500; text-decoration: none; word-break: break-all;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                {{ $company->email }}
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                @if($company->phone)
                                    <div style="display: flex; align-items: start; gap: 14px;">
                                        <div style="flex-shrink: 0; width: 40px; height: 40px; background: #f0fdf4; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                            <svg style="width: 20px; height: 20px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-size: 12px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Teléfono</div>
                                            <a href="tel:{{ $company->phone }}" style="font-size: 14px; color: #10b981; font-weight: 500; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                {{ $company->phone }}
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                @if($company->website)
                                    <div style="display: flex; align-items: start; gap: 14px;">
                                        <div style="flex-shrink: 0; width: 40px; height: 40px; background: #fef3c7; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                            <svg style="width: 20px; height: 20px; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                            </svg>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-size: 12px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Sitio Web</div>
                                            <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer" style="font-size: 14px; color: #f59e0b; font-weight: 500; text-decoration: none; word-break: break-all;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                {{ str_replace(['http://', 'https://'], '', $company->website) }}
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                @if(!$company->email && !$company->phone && !$company->website)
                                    <div style="text-align: center; padding: 20px;">
                                        <svg style="width: 48px; height: 48px; color: #d1d5db; margin: 0 auto 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p style="font-size: 14px; color: #9ca3af;">No hay información de contacto disponible</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
