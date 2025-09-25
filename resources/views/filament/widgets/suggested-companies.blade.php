<div>
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                Empresas Sugeridas
            </x-slot>

            <div class="space-y-3">
                @if($this->getViewData()['suggestions']->count() > 0)
                    @foreach($this->getViewData()['suggestions'] as $company)
                        <x-filament::card class="p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3 flex-1">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0">
                                        @if($company['avatar_url'])
                                            <img src="{{ $company['avatar_url'] }}"
                                                 alt="{{ $company['name'] }}"
                                                 class="h-12 w-12 rounded-full object-cover">
                                        @else
                                            @php
                                                $avatarColors = [
                                                    'I' => 'bg-indigo-500',
                                                    'P' => 'bg-red-500',
                                                    'D' => 'bg-purple-500',
                                                    'L' => 'bg-blue-500',
                                                    'M' => 'bg-green-500',
                                                    'A' => 'bg-yellow-500',
                                                ];
                                                $firstLetter = strtoupper(substr($company['name'], 0, 1));
                                                $avatarClass = $avatarColors[$firstLetter] ?? 'bg-gray-500';
                                            @endphp
                                            <div class="h-12 w-12 {{ $avatarClass }} rounded-full flex items-center justify-center">
                                                <span class="text-white text-sm font-bold">
                                                    {{ $company['avatar_initials'] }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Company Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-white truncate">
                                            {{ $company['name'] }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            @if($company['city'])
                                                {{ $company['city'] }} • {{ $company['followers_count'] }} seguidores
                                            @else
                                                {{ $company['followers_count'] }} seguidores
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Follow Button -->
                                <div class="flex-shrink-0">
                                    <x-filament::button
                                        wire:click="followCompany({{ $company['id'] }})"
                                        size="sm"
                                        color="primary"
                                    >
                                        Seguir
                                    </x-filament::button>
                                </div>
                            </div>
                        </x-filament::card>
                    @endforeach
                @else
                    <!-- Empty State -->
                    <x-filament::card class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 flex-1">
                                <div class="h-12 w-12 bg-gray-400 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-bold">--</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        No hay sugerencias
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        No hay empresas para seguir
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <x-filament::button
                                    disabled
                                    size="sm"
                                    color="gray"
                                >
                                    Seguir
                                </x-filament::button>
                            </div>
                        </div>
                    </x-filament::card>
                @endif
            </div>

            <!-- Footer Link -->
            <div class="mt-4 pt-3 text-center border-t border-gray-200 dark:border-gray-700">
                <x-filament::link href="/admin/companies" size="sm">
                    Ver todas las sugerencias
                </x-filament::link>
            </div>
        </x-filament::section>
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