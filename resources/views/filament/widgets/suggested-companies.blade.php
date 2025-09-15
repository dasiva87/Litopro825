<div>
    <x-filament-widgets::widget>
        <div style="background-color: #f9fafb; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 16px;">
            <!-- Header -->
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <span style="color: #2563eb; font-size: 20px;">👥</span>
                <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">Empresas Sugeridas</h3>
            </div>

            <!-- Content -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @if($this->getViewData()['suggestions']->count() > 0)
                    @foreach($this->getViewData()['suggestions'] as $company)
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                <!-- Avatar -->
                                <div style="flex-shrink: 0;">
                                    @if($company['avatar_url'])
                                        <img src="{{ $company['avatar_url'] }}"
                                             alt="{{ $company['name'] }}"
                                             style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                                    @else
                                        @php
                                            // Match the exact colors from the image
                                            $avatarData = [
                                                'I' => '#6366f1', // Indigo-500 for "Imprenta Gráfica" -> IG
                                                'P' => '#ef4444', // Red-500 for "Papeles y Diseños" -> PD
                                                'D' => '#8b5cf6', // Purple-500 for Default
                                                'L' => '#3b82f6', // Blue-500
                                                'M' => '#10b981', // Green-500
                                                'A' => '#f59e0b', // Yellow-500
                                            ];
                                            $firstLetter = strtoupper(substr($company['name'], 0, 1));
                                            $avatarColor = $avatarData[$firstLetter] ?? '#6b7280';
                                        @endphp
                                        <div style="width: 48px; height: 48px; background-color: {{ $avatarColor }}; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <span style="color: white; font-size: 14px; font-weight: bold;">
                                                {{ $company['avatar_initials'] }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Company Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <a href="{{ $company['profile_url'] }}"
                                       style="display: block; font-weight: bold; color: #111827; text-decoration: none; font-size: 16px; margin-bottom: 2px;">
                                        {{ $company['name'] }}
                                    </a>
                                    <p style="font-size: 14px; color: #6b7280; margin: 0;">
                                        @if($company['city'])
                                            {{ $company['city'] }} • {{ $company['followers_count'] }} seguidores
                                        @else
                                            {{ $company['followers_count'] }} seguidores
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Follow Button -->
                            <div style="flex-shrink: 0;">
                                <button
                                    wire:click="followCompany({{ $company['id'] }})"
                                    style="padding: 8px 16px; font-size: 14px; font-weight: 500; color: white; background-color: #2563eb; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;"
                                    onmouseover="this.style.backgroundColor='#1d4ed8'"
                                    onmouseout="this.style.backgroundColor='#2563eb'"
                                >
                                    Seguir
                                </button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Empty State -->
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                            <div style="width: 48px; height: 48px; background-color: #9ca3af; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <span style="color: white; font-size: 14px; font-weight: bold;">--</span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <p style="font-weight: bold; color: #111827; font-size: 16px; margin-bottom: 2px;">
                                    No hay sugerencias
                                </p>
                                <p style="font-size: 14px; color: #6b7280; margin: 0;">
                                    No hay empresas para seguir
                                </p>
                            </div>
                        </div>
                        <div style="flex-shrink: 0;">
                            <button disabled style="padding: 8px 16px; font-size: 14px; font-weight: 500; color: #9ca3af; background-color: #d1d5db; border: none; border-radius: 6px; cursor: not-allowed;">
                                Seguir
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Footer Link -->
            <div style="margin-top: 16px; padding-top: 12px; text-align: center; border-top: 1px solid #e5e7eb;">
                <a href="/admin/companies"
                   style="color: #2563eb; font-weight: 500; font-size: 14px; text-decoration: none;"
                   onmouseover="this.style.color='#1d4ed8'"
                   onmouseout="this.style.color='#2563eb'">
                    Ver todas las sugerencias
                </a>
            </div>
        </div>
    </x-filament-widgets::widget>

    <!-- Flash Messages -->
    @if(session()->has('social-success') || session()->has('social-error') || session()->has('social-info'))
        <div style="position: fixed; top: 16px; right: 16px; z-index: 50;" x-data="{ show: true }" x-show="show" x-transition>
            @if(session()->has('social-success'))
                <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); max-width: 320px;">
                    <div style="display: flex; align-items: center;">
                        <svg style="height: 20px; width: 20px; color: #4ade80; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span style="font-size: 14px; font-weight: 500;">{{ session('social-success') }}</span>
                        <button @click="show = false" style="margin-left: auto; background: none; border: none; cursor: pointer;">
                            <svg style="height: 16px; width: 16px; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @if(session()->has('social-error'))
                <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); max-width: 320px;">
                    <div style="display: flex; align-items: center;">
                        <svg style="height: 20px; width: 20px; color: #f87171; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <span style="font-size: 14px; font-weight: 500;">{{ session('social-error') }}</span>
                        <button @click="show = false" style="margin-left: auto; background: none; border: none; cursor: pointer;">
                            <svg style="height: 16px; width: 16px; color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @if(session()->has('social-info'))
                <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 12px 16px; border-radius: 8px; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); max-width: 320px;">
                    <div style="display: flex; align-items: center;">
                        <svg style="height: 20px; width: 20px; color: #60a5fa; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span style="font-size: 14px; font-weight: 500;">{{ session('social-info') }}</span>
                        <button @click="show = false" style="margin-left: auto; background: none; border: none; cursor: pointer;">
                            <svg style="height: 16px; width: 16px; color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <script>
            // Auto-hide flash messages after 4 seconds
            setTimeout(function() {
                const flashMessage = document.querySelector('[x-data="{ show: true }"]');
                if (flashMessage) {
                    flashMessage.querySelector('[x-data="{ show: true }"]').__x.$data.show = false;
                }
            }, 4000);
        </script>
    @endif

    <style>
    .suggested-companies-widget .company-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    </style>
</div>