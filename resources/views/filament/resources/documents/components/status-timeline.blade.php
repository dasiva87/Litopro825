@php
    $record = $getRecord();

    // Timeline de estados basado en el estado actual
    $timeline = [
        [
            'title' => 'Creada',
            'description' => 'Cotización generada en el sistema',
            'date' => $record->created_at,
            'user' => $record->user?->name ?? 'Sistema',
            'icon' => 'heroicon-o-document-plus',
            'color' => 'success',
            'completed' => true,
        ],
    ];

    // Email enviado
    if ($record->email_sent_at) {
        $timeline[] = [
            'title' => 'Email Enviado',
            'description' => 'Cotización enviada al cliente por correo',
            'date' => $record->email_sent_at,
            'user' => $record->emailSentBy?->name ?? 'Sistema',
            'icon' => 'heroicon-o-envelope',
            'color' => 'info',
            'completed' => true,
        ];
    }

    // Aprobada
    if ($record->status === 'approved') {
        $timeline[] = [
            'title' => 'Aprobada',
            'description' => 'Cliente aprobó la cotización',
            'date' => $record->updated_at,
            'user' => 'Cliente',
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'completed' => true,
        ];
    } elseif ($record->status === 'sent') {
        $timeline[] = [
            'title' => 'Aprobación Pendiente',
            'description' => 'Esperando respuesta del cliente',
            'date' => null,
            'user' => '-',
            'icon' => 'heroicon-o-clock',
            'color' => 'warning',
            'completed' => false,
        ];
    } else {
        $timeline[] = [
            'title' => 'Aprobación Pendiente',
            'description' => 'Aún no enviada al cliente',
            'date' => null,
            'user' => '-',
            'icon' => 'heroicon-o-clock',
            'color' => 'gray',
            'completed' => false,
        ];
    }

    // En Producción
    if ($record->status === 'in_production') {
        $timeline[] = [
            'title' => 'En Producción',
            'description' => 'Orden en proceso de fabricación',
            'date' => $record->updated_at,
            'user' => 'Producción',
            'icon' => 'heroicon-o-cog-6-tooth',
            'color' => 'warning',
            'completed' => true,
        ];
    } elseif (in_array($record->status, ['approved', 'sent'])) {
        $timeline[] = [
            'title' => 'En Producción',
            'description' => 'Pendiente de iniciar producción',
            'date' => null,
            'user' => '-',
            'icon' => 'heroicon-o-cog-6-tooth',
            'color' => 'gray',
            'completed' => false,
        ];
    }
@endphp

<div class="space-y-4">
    @foreach ($timeline as $index => $event)
        <div class="flex gap-4">
            {{-- Icono --}}
            <div class="relative flex-shrink-0">
                <div class="flex h-10 w-10 items-center justify-center rounded-full
                    {{ $event['completed']
                        ? 'bg-' . $event['color'] . '-100 dark:bg-' . $event['color'] . '-900/20'
                        : 'bg-gray-100 dark:bg-gray-800'
                    }}">
                    <x-filament::icon
                        :icon="$event['icon']"
                        class="h-5 w-5 {{ $event['completed']
                            ? 'text-' . $event['color'] . '-600 dark:text-' . $event['color'] . '-400'
                            : 'text-gray-400 dark:text-gray-600'
                        }}"
                    />
                </div>

                {{-- Línea conectora --}}
                @if (!$loop->last)
                    <div class="absolute left-1/2 top-10 -ml-px h-full w-0.5
                        {{ $event['completed'] && $timeline[$index + 1]['completed']
                            ? 'bg-gray-300 dark:bg-gray-700'
                            : 'bg-gray-200 dark:bg-gray-800'
                        }}">
                    </div>
                @endif
            </div>

            {{-- Contenido --}}
            <div class="flex-1 pb-8">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $event['title'] }}
                        </p>
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                            {{ $event['description'] }}
                        </p>
                        <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-500">
                            @if ($event['date'])
                                <span class="flex items-center gap-1">
                                    <x-filament::icon
                                        icon="heroicon-o-calendar"
                                        class="h-3 w-3"
                                    />
                                    {{ $event['date']->format('d M, Y H:i') }}
                                </span>
                            @endif
                            <span class="flex items-center gap-1">
                                <x-filament::icon
                                    icon="heroicon-o-user"
                                    class="h-3 w-3"
                                />
                                {{ $event['user'] }}
                            </span>
                        </div>
                    </div>

                    @if ($event['completed'])
                        <x-filament::badge :color="$event['color']" size="sm">
                            Completado
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="gray" size="sm">
                            Pendiente
                        </x-filament::badge>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
