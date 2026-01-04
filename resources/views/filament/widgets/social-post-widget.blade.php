<div style="space-y: 24px;">
    <!-- Panel de Filtros -->
    <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: {{ $showFilters ? '16px' : '0' }};">
            <div style="display: flex; align-items: center; gap: 12px;">
                <svg style="width: 20px; height: 20px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"/>
                </svg>
                <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">Filtros del Feed</h3>
            </div>
            <button
                wire:click="$toggle('showFilters')"
                style="background: #f3f4f6; border: none; border-radius: 6px; padding: 6px 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; color: #6b7280; font-size: 14px; transition: background-color 0.2s;"
                onmouseover="this.style.backgroundColor='#e5e7eb'"
                onmouseout="this.style.backgroundColor='#f3f4f6'"
                title="{{ $showFilters ? 'Ocultar filtros' : 'Mostrar filtros' }}"
            >
                <span>{{ $showFilters ? 'Ocultar' : 'Mostrar' }}</span>
                <svg style="width: 16px; height: 16px; transform: rotate({{ $showFilters ? '180deg' : '0deg' }}); transition: transform 0.2s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>

        @if($showFilters)

            <!-- Campo de Búsqueda Principal -->
            <div style="margin-bottom: 20px;">
                <div style="position: relative; max-width: 500px; margin: 0 auto;">
                    <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="filterSearch"
                        placeholder="Buscar en publicaciones (título, contenido, hashtags...)..."
                        style="width: 100%; padding: 12px 16px 12px 48px; border: 2px solid #e5e7eb; border-radius: 24px; font-size: 15px; background: white; color: #374151; transition: border-color 0.2s, box-shadow 0.2s;"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'"
                        onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'"
                    >
                    @if(!empty($filterSearch))
                        <button
                            wire:click="$set('filterSearch', '')"
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: #f3f4f6; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #6b7280; transition: background-color 0.2s;"
                            onmouseover="this.style.backgroundColor='#e5e7eb'"
                            onmouseout="this.style.backgroundColor='#f3f4f6'"
                            title="Limpiar búsqueda"
                        >
                            ×
                        </button>
                    @endif
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <!-- Filtro por Tipo -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Tipo de Post</label>
                    <select wire:model.live="filterType" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #374151; background: white; cursor: pointer;">
                        <option value="">Todos los tipos</option>
                        @foreach($this->getPostTypes() as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Ciudad -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Ciudad</label>
                    <select wire:model.live="filterCity" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #374151; background: white; cursor: pointer;">
                        <option value="">Todas las ciudades</option>
                        @foreach($this->getCities() as $cityId => $cityName)
                            <option value="{{ $cityId }}">{{ $cityName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Fecha Desde -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Desde</label>
                    <input type="date" wire:model.live="filterDateFrom" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #374151; background: white;">
                </div>

                <!-- Filtro por Fecha Hasta -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Hasta</label>
                    <input type="date" wire:model.live="filterDateTo" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #374151; background: white;">
                </div>
            </div>

            <!-- Botón Limpiar Filtros -->
            @if(!empty($filterType) || !empty($filterCity) || !empty($filterDateFrom) || !empty($filterDateTo) || !empty($filterSearch))
                <div style="margin-top: 16px; text-align: center;">
                    <button wire:click="clearFilters" style="padding: 8px 16px; background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#e5e7eb'" onmouseout="this.style.backgroundColor='#f3f4f6'">
                        <svg style="width: 16px; height: 16px; display: inline-block; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Limpiar Filtros
                    </button>
                </div>
            @endif

            <!-- Contador de resultados -->
            <div style="margin-top: 12px; text-align: center; font-size: 13px; color: #6b7280;">
                Mostrando {{ $this->getSocialPosts()->count() }} publicaciones
            </div>
        @endif
    </div>

    @foreach($this->getSocialPosts() as $post)
        <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px;">
            <!-- Header del post -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center;">
                    <!-- Avatar -->
                    <div style="width: 48px; height: 48px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                        <span style="color: white; font-size: 16px; font-weight: 600;">
                            {{ strtoupper(substr($post->author->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(explode(' ', $post->author->name ?? 'User')[1] ?? '', 0, 1)) }}
                        </span>
                    </div>

                    <!-- Info del autor -->
                    <div>
                        <div style="font-size: 15px; font-weight: 600; line-height: 1.2;">
                            <a href="{{ $post->getCompanyProfileUrl() }}" style="color: #2563eb; text-decoration: none;"
                               onmouseover="this.style.textDecoration='underline'"
                               onmouseout="this.style.textDecoration='none'">
                                {{ $post->getCompanyName() }}
                            </a>
                            <span style="color: #111827;"> - {{ $post->author->name ?? 'Usuario' }}</span>

                            <!-- Indicador de empresa seguida -->
                            @if($post->isFromFollowedCompany())
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; background: #eff6ff; color: #3b82f6; padding: 2px 6px; border-radius: 8px; margin-left: 8px;">
                                    <svg style="width: 10px; height: 10px;" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Siguiendo
                                </span>
                            @endif
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                            <span style="font-size: 13px; color: #6b7280;">{{ $post->created_at->diffForHumans() }}</span>
                            <span style="color: #6b7280;">•</span>

                            @if($post->getCompanyLocation())
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <svg style="width: 12px; height: 12px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span style="font-size: 13px; color: #6b7280;">{{ $post->getCompanyLocation() }}</span>
                                </div>
                                <span style="color: #6b7280;">•</span>
                            @endif

                            @if($post->getCompanyFollowersCount() > 0)
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <svg style="width: 12px; height: 12px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span style="font-size: 13px; color: #6b7280;">{{ $post->getCompanyFollowersCount() }} seguidores</span>
                                </div>
                                <span style="color: #6b7280;">•</span>
                            @endif

                            <div style="display: flex; align-items: center; gap: 4px;">
                                <svg style="width: 12px; height: 12px; color: #6b7280;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"/>
                                </svg>
                                <span style="font-size: 13px; color: #6b7280;">{{ $post->is_public ? 'Público' : 'Privado' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tipo de post -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 12px; background: {{ $post->getPostTypeColor() === 'success' ? '#dcfce7' : ($post->getPostTypeColor() === 'warning' ? '#fef3c7' : '#dbeafe') }}; color: {{ $post->getPostTypeColor() === 'success' ? '#166534' : ($post->getPostTypeColor() === 'warning' ? '#92400e' : '#1e40af') }};">
                        {{ $post->getPostTypeLabel() }}
                    </span>
                </div>
            </div>

            <!-- Título del post (si existe) -->
            @if($post->title)
                <div style="margin-bottom: 12px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">{{ $post->title }}</h3>
                </div>
            @endif

            <!-- Contenido del post -->
            <div style="margin-bottom: 16px;">
                <p style="font-size: 15px; line-height: 1.5; color: #111827; margin: 0; white-space: pre-wrap;">{{ $post->content }}</p>
            </div>

            <!-- Imagen del post (si existe) -->
            @if($post->hasImage())
                <div style="margin-bottom: 16px;">
                    <div style="border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; aspect-ratio: 1 / 1;">
                        <img src="{{ $post->getImageUrl() }}"
                             style="width: 100%; height: 100%; object-fit: cover; display: block;"
                             alt="Imagen del post">
                    </div>
                </div>
            @endif

            <!-- Adjunto (Reporte) si tiene metadata -->
            @if($post->metadata && isset($post->metadata['attachment_type']) && $post->metadata['attachment_type'] === 'report')
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 32px; text-align: center; margin-bottom: 16px; position: relative; overflow: hidden;">
                    <!-- Icono del reporte -->
                    <div style="margin-bottom: 16px;">
                        <svg style="width: 48px; height: 48px; color: rgba(255,255,255,0.9); margin: 0 auto;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>

                    <!-- Título del reporte -->
                    <h3 style="color: white; font-size: 24px; font-weight: 600; margin: 0 0 8px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        {{ $post->metadata['report_data']['title'] ?? 'Reporte' }}
                    </h3>

                    <!-- Subtítulo -->
                    <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin: 0; font-weight: 500;">
                        {{ $post->metadata['report_data']['subtitle'] ?? '' }}
                    </p>
                </div>
            @endif

            @php
                $reactionCounts = $this->getReactionCounts($post);
                $totalReactions = array_sum($reactionCounts);
                $totalComments = $post->comments()->count();
            @endphp

            <!-- Stats de interacciones -->
            @if($totalReactions > 0 || $totalComments > 0)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f3f4f6; margin-bottom: 12px; font-size: 14px; color: #6b7280;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        @if($totalReactions > 0)
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <div style="width: 18px; height: 18px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <svg style="width: 10px; height: 10px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                    </svg>
                                </div>
                                <span>{{ $totalReactions }} {{ $totalReactions === 1 ? 'reacción' : 'reacciones' }}</span>
                            </div>
                        @endif
                        @if($totalComments > 0)
                            <span>{{ $totalComments }} {{ $totalComments === 1 ? 'comentario' : 'comentarios' }}</span>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Botones de acción -->
            <div style="display: grid; grid-template-columns: repeat({{ in_array($post->post_type, ['offer', 'request']) ? '4' : '3' }}, 1fr); gap: 8px;">
                <!-- Me gusta -->
                <button wire:click="toggleReaction({{ $post->id }}, 'like')"
                    style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: {{ $this->hasUserReacted($post, 'like') ? '#eff6ff' : 'transparent' }}; border: none; border-radius: 8px; cursor: pointer; color: {{ $this->hasUserReacted($post, 'like') ? '#3b82f6' : '#6b7280' }}; font-size: 14px; font-weight: 500; transition: background-color 0.2s;">
                    <svg style="width: 18px; height: 18px;" fill="{{ $this->hasUserReacted($post, 'like') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2v0a2 2 0 00-2 2v0M7 20l-2-2m2 2l2-2m-2 2v-2.5a2.5 2.5 0 011.3-2.2L11 14"/>
                    </svg>
                    <span>Me gusta</span>
                </button>

                <!-- Me interesa -->
                <button wire:click="toggleReaction({{ $post->id }}, 'interested')"
                    style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: {{ $this->hasUserReacted($post, 'interested') ? '#fef3c7' : 'transparent' }}; border: none; border-radius: 8px; cursor: pointer; color: {{ $this->hasUserReacted($post, 'interested') ? '#d97706' : '#6b7280' }}; font-size: 14px; font-weight: 500; transition: background-color 0.2s;">
                    <span style="font-size: 16px;">💡</span>
                    <span>Interesa</span>
                </button>

                <!-- Comentar -->
                <button onclick="document.getElementById('comment-{{ $post->id }}').style.display = document.getElementById('comment-{{ $post->id }}').style.display === 'none' ? 'block' : 'none'"
                    style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: transparent; border: none; border-radius: 8px; cursor: pointer; color: #6b7280; font-size: 14px; font-weight: 500; transition: background-color 0.2s;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>Comentar</span>
                </button>

                <!-- Contactar (solo para ofertas/solicitudes) -->
                @if(in_array($post->post_type, ['offer', 'request']))
                    <button style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: #dcfce7; border: none; border-radius: 8px; cursor: pointer; color: #166534; font-size: 14px; font-weight: 500;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>Contactar</span>
                    </button>
                @endif
            </div>

            <!-- Sección de comentarios -->
            <div id="comment-{{ $post->id }}" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6;">
                <!-- Agregar comentario -->
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="color: white; font-size: 12px; font-weight: 600;">
                                {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 1)) . strtoupper(substr(explode(' ', auth()->user()->name)[1] ?? '', 0, 1)) : 'U' }}
                            </span>
                        </div>
                        <div style="flex: 1;">
                            <form wire:submit="addComment({{ $post->id }}, $event.target.comment.value); $event.target.comment.value = ''">
                                <textarea name="comment" placeholder="Escribe un comentario..."
                                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical; min-height: 80px;"
                                    required></textarea>
                                <div style="margin-top: 8px; text-align: right;">
                                    <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer;">
                                        Comentar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista de comentarios existentes -->
                @foreach($post->comments()->with('author')->latest()->take(5)->get() as $comment)
                    <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 32px; height: 32px; background: #6b7280; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="color: white; font-size: 12px; font-weight: 600;">
                                {{ strtoupper(substr($comment->author->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(explode(' ', $comment->author->name ?? 'User')[1] ?? '', 0, 1)) }}
                            </span>
                        </div>
                        <div style="flex: 1;">
                            <div style="background: #f3f4f6; padding: 12px; border-radius: 12px;">
                                <div style="font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 4px;">
                                    @if($comment->author && $comment->author->company)
                                        <a href="{{ $comment->author->company->getProfileUrl() }}" style="color: #2563eb; text-decoration: none; font-weight: 600;"
                                           onmouseover="this.style.textDecoration='underline'"
                                           onmouseout="this.style.textDecoration='none'">
                                            {{ $comment->author->company->name }}
                                        </a>
                                    @else
                                        <span style="color: #6b7280;">Empresa Desconocida</span>
                                    @endif
                                    <span style="color: #6b7280;"> ({{ $comment->author->name ?? 'Usuario' }})</span>
                                </div>
                                <div style="font-size: 14px; color: #374151; line-height: 1.4;">
                                    {{ $comment->content }}
                                </div>
                            </div>
                            <div style="margin-top: 4px; font-size: 12px; color: #6b7280; padding-left: 12px;">
                                {{ $comment->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($post->comments()->count() > 5)
                    <div style="text-align: center; margin-top: 12px;">
                        <button style="color: #3b82f6; background: none; border: none; font-size: 14px; cursor: pointer;">
                            Ver todos los comentarios ({{ $post->comments()->count() }})
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    @if($this->getSocialPosts()->isEmpty())
        <div style="background: white; border-radius: 12px; padding: 40px; text-align: center; border: 1px solid #e5e7eb;">
            <svg style="width: 48px; height: 48px; margin: 0 auto 16px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 8px 0;">
                No hay publicaciones aún
            </h3>
            <p style="color: #6b7280; margin: 0;">
                Sé el primero en compartir algo con la comunidad.
            </p>
        </div>
    @endif
</div>