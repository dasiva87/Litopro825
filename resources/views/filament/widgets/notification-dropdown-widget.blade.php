<div style="position: relative; display: inline-block;" x-data="{ showDropdown: false }">
    <!-- Botón de notificaciones -->
    <button
        @click="showDropdown = !showDropdown"
        style="padding: 8px; background: transparent; border: none; border-radius: 8px; cursor: pointer; position: relative;"
    >
        <svg style="width: 20px; height: 20px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM21 12.3c-.6 0-1-.4-1-1V8.5c0-1.4-.6-2.8-1.6-3.7-1-.9-2.4-1.4-3.8-1.4s-2.8.5-3.8 1.4c-1 .9-1.6 2.3-1.6 3.7v2.8c0 .6-.4 1-1 1s-1-.4-1-1V8.5c0-2.1.8-4.1 2.3-5.6C10.9 1.4 12.9.6 15 .6s4.1.8 5.6 2.3c1.5 1.5 2.3 3.5 2.3 5.6v2.8c0 .6-.4 1-1 1z"/>
        </svg>

        @if($this->getUnreadCount() > 0)
            <div style="position: absolute; top: 4px; right: 4px; width: 18px; height: 18px; background: #ef4444; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600;">
                {{ $this->getUnreadCount() > 9 ? '9+' : $this->getUnreadCount() }}
            </div>
        @endif
    </button>

    <!-- Dropdown de notificaciones -->
    <div
        x-show="showDropdown"
        x-transition
        @click.away="showDropdown = false"
        style="position: absolute; right: 0; top: 100%; width: 400px; max-width: 90vw; background: white; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 50; margin-top: 8px;"
    >
        <!-- Header -->
        <div style="padding: 16px 20px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">Notificaciones</h3>
            @if($this->getUnreadCount() > 0)
                <button
                    wire:click="markAllAsRead"
                    style="font-size: 12px; color: #3b82f6; background: none; border: none; cursor: pointer; text-decoration: underline;"
                >
                    Marcar todas como leídas
                </button>
            @endif
        </div>

        <!-- Lista de notificaciones -->
        <div style="max-height: 400px; overflow-y: auto;">
            @forelse($this->getNotifications() as $notification)
                <div
                    wire:click="markAsRead({{ $notification->id }})"
                    style="padding: 12px 20px; border-bottom: 1px solid #f9fafb; cursor: pointer; transition: background-color 0.2s; {{ !$notification->isRead() ? 'background-color: #f0f9ff;' : '' }}"
                    onmouseover="this.style.backgroundColor='#f9fafb'"
                    onmouseout="this.style.backgroundColor='{{ !$notification->isRead() ? '#f0f9ff' : 'white' }}'"
                >
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <!-- Icono -->
                        <div style="width: 32px; height: 32px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;">
                            {{ $notification->getIcon() }}
                        </div>

                        <!-- Contenido -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <h4 style="font-size: 14px; font-weight: 500; color: #111827; margin: 0; line-height: 1.4;">
                                    {{ $notification->title }}
                                </h4>
                                @if(!$notification->isRead())
                                    <div style="width: 6px; height: 6px; background: #3b82f6; border-radius: 50%; flex-shrink: 0;"></div>
                                @endif
                            </div>

                            <p style="font-size: 13px; color: #6b7280; margin: 0 0 6px 0; line-height: 1.4;">
                                {{ $notification->message }}
                            </p>

                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 12px; color: #9ca3af;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                                <span style="font-size: 11px; color: #9ca3af; background: #f9fafb; padding: 2px 6px; border-radius: 4px;">
                                    {{ $notification->getTypeLabel() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div style="padding: 40px 20px; text-align: center; color: #9ca3af;">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 16px; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 17h5l-5 5v-5zM21 12.3c-.6 0-1-.4-1-1V8.5c0-1.4-.6-2.8-1.6-3.7-1-.9-2.4-1.4-3.8-1.4s-2.8.5-3.8 1.4c-1 .9-1.6 2.3-1.6 3.7v2.8c0 .6-.4 1-1 1s-1-.4-1-1V8.5c0-2.1.8-4.1 2.3-5.6C10.9 1.4 12.9.6 15 .6s4.1.8 5.6 2.3c1.5 1.5 2.3 3.5 2.3 5.6v2.8c0 .6-.4 1-1 1z"/>
                    </svg>
                    <p style="margin: 0; font-size: 14px;">No tienes notificaciones</p>
                </div>
            @endforelse
        </div>

        @if($this->getNotifications()->count() > 0)
            <!-- Footer -->
            <div style="padding: 12px 20px; border-top: 1px solid #f3f4f6; text-align: center;">
                <a href="/admin/notifications" style="font-size: 13px; color: #3b82f6; text-decoration: none;">
                    Ver todas las notificaciones
                </a>
            </div>
        @endif
    </div>
</div>