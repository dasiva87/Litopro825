<div>
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                🏢 Empresas Sugeridas
            </x-slot>

            <x-slot name="description">
                Conecta con proveedores de tu zona
            </x-slot>

            <div class="space-y-2">
                @forelse($this->getViewData()['suggestions'] as $company)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            @if($company['avatar_url'])
                                <img src="{{ $company['avatar_url'] }}"
                                     alt="{{ $company['name'] }}"
                                     class="w-10 h-10 rounded-lg object-cover">
                            @else
                                @php
                                    $colors = ['bg-blue-500', 'bg-green-500', 'bg-red-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500'];
                                    $colorIndex = ord($company['avatar_initials'][0]) % count($colors);
                                @endphp
                                <div class="w-10 h-10 {{ $colors[$colorIndex] }} rounded-lg flex items-center justify-center">
                                    <span class="text-white text-sm font-bold">{{ $company['avatar_initials'] }}</span>
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ $company['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @if($company['city'])
                                        {{ $company['city'] }} •
                                    @endif
                                    {{ $company['followers_count'] }} seguidores
                                </p>
                            </div>
                        </div>

                        <x-filament::button
                            wire:click="followCompany({{ $company['id'] }})"
                            size="xs"
                            color="primary"
                        >
                            Seguir
                        </x-filament::button>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <x-filament::icon icon="heroicon-o-building-office-2" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay sugerencias disponibles</p>
                    </div>
                @endforelse
            </div>

            @if($this->getViewData()['suggestions']->count() > 0)
                <div class="mt-4 text-center">
                    <x-filament::link href="/admin/companies" size="sm">
                        Ver todas las empresas
                    </x-filament::link>
                </div>
            @endif
        </x-filament::section>
    </x-filament-widgets::widget>
</div>