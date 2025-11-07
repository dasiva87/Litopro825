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
                @if($company->banner)
                    <img src="{{ asset('storage/' . $company->banner) }}" alt="Banner" style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;">
                @endif
                <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.15);"></div>
            </div>

            <!-- Información del Perfil -->
            <div style="padding: 0 32px 32px 32px; position: relative;">
                <!-- Avatar -->
                <div style="position: relative; margin-top: -80px; margin-bottom: 20px;">
                    <div style="width: 160px; height: 160px; background: white; border-radius: 50%; border: 6px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); overflow: hidden;">
                        @if($company->avatar)
                            <img src="{{ asset('storage/' . $company->avatar) }}" alt="{{ $company->name }}" style="width: 100%; height: 100%; object-fit: cover;">
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
                            <span style="font-size: 13px; font-weight: 600; color: #2563eb;">{{ ucfirst($company->company_type) }}</span>
                        </div>
                    @endif

                    @if($company->bio)
                        <p style="font-size: 16px; color: #6b7280; line-height: 1.6; margin-top: 12px; max-width: 800px;">
                            {{ $company->bio }}
                        </p>
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
                <div style="background: white; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.07); overflow: hidden;">
                    <!-- Header de Publicaciones -->
                    <div style="padding: 20px 24px; border-bottom: 2px solid #f3f4f6; background: linear-gradient(to right, #f9fafb, #ffffff);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg style="width: 24px; height: 24px; color: #667eea;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                            <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0;">Publicaciones Recientes</h2>
                        </div>
                    </div>

                    <!-- Lista de Posts -->
                    <div>
                        @forelse($this->posts as $post)
                            <div style="padding: 24px; border-bottom: 1px solid #f3f4f6;">
                                <div style="display: flex; gap: 16px;">
                                    <!-- Avatar del Autor -->
                                    <div style="flex-shrink: 0;">
                                        @if($post->author->company && $post->author->company->avatar)
                                            <img src="{{ asset('storage/' . $post->author->company->avatar) }}" alt="{{ $post->author->company->name }}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb;">
                                        @else
                                            <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                                <span style="color: white; font-size: 16px; font-weight: 700;">
                                                    {{ strtoupper(substr($post->author->name ?? 'U', 0, 2)) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Contenido del Post -->
                                    <div style="flex: 1; min-width: 0;">
                                        <!-- Header del Post -->
                                        <div style="margin-bottom: 12px;">
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <span style="font-size: 15px; font-weight: 600; color: #111827;">{{ $post->author->name }}</span>
                                                <span style="color: #d1d5db;">•</span>
                                                <span style="font-size: 14px; color: #9ca3af;">{{ $post->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>

                                        <!-- Contenido -->
                                        <div style="margin-bottom: 12px;">
                                            <p style="font-size: 15px; color: #374151; line-height: 1.6; white-space: pre-wrap; word-break: break-word;">{{ $post->content }}</p>
                                        </div>

                                        <!-- Imagen del Post (si existe) -->
                                        @if($post->image_path)
                                            <div style="margin-top: 16px; margin-bottom: 16px;">
                                                <img src="{{ asset('storage/' . $post->image_path) }}" alt="Post image" style="max-width: 100%; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                            </div>
                                        @endif

                                        <!-- Estadísticas de Interacción -->
                                        @if($post->reactions->count() > 0 || $post->comments->count() > 0)
                                            <div style="display: flex; gap: 20px; padding-top: 12px; border-top: 1px solid #f3f4f6;">
                                                @if($post->reactions->count() > 0)
                                                    <div style="display: flex; align-items: center; gap: 6px;">
                                                        <svg style="width: 16px; height: 16px; color: #ef4444;" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <span style="font-size: 14px; color: #6b7280; font-weight: 500;">{{ $post->reactions->count() }}</span>
                                                    </div>
                                                @endif
                                                @if($post->comments->count() > 0)
                                                    <div style="display: flex; align-items: center; gap: 6px;">
                                                        <svg style="width: 16px; height: 16px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                                        </svg>
                                                        <span style="font-size: 14px; color: #6b7280; font-weight: 500;">{{ $post->comments->count() }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 80px 24px; text-align: center;">
                                <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <svg style="width: 40px; height: 40px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <p style="font-size: 16px; color: #9ca3af; font-weight: 500;">No hay publicaciones aún</p>
                                <p style="font-size: 14px; color: #d1d5db; margin-top: 8px;">Las publicaciones de esta empresa aparecerán aquí</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Paginación -->
                    @if($this->posts->hasPages())
                        <div style="padding: 20px 24px; border-top: 2px solid #f3f4f6; background: #f9fafb;">
                            {{ $this->posts->links() }}
                        </div>
                    @endif
                </div>
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
