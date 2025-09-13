<div style="space-y: 24px;">
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
                        <div style="font-size: 15px; font-weight: 600; color: #111827; line-height: 1.2;">
                            {{ $post->author->name ?? 'Usuario' }} - {{ $post->author->company->name ?? 'Empresa' }}
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                            <span style="font-size: 13px; color: #6b7280;">{{ $post->created_at->diffForHumans() }}</span>
                            <span style="color: #6b7280;">•</span>
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
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
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

                <!-- Contactar (para ofertas/solicitudes) -->
                @if(in_array($post->post_type, ['offer', 'request']))
                    <button style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: #dcfce7; border: none; border-radius: 8px; cursor: pointer; color: #166534; font-size: 14px; font-weight: 500;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>Contactar</span>
                    </button>
                @else
                    <!-- Compartir para otros tipos -->
                    <button style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: transparent; border: none; border-radius: 8px; cursor: pointer; color: #6b7280; font-size: 14px; font-weight: 500;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                        </svg>
                        <span>Compartir</span>
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
                                    {{ $comment->author->name ?? 'Usuario' }}
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