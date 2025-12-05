<div class="space-y-4">
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    @if($record->status === 'approved')
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                            ✓ Aprobada
                        </span>
                    @elseif($record->status === 'rejected')
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                            ✗ Rechazada
                        </span>
                    @endif
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Respuesta</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $record->responded_at?->format('d/m/Y H:i') ?? '—' }}
                </p>
            </div>
        </div>

        @if($record->respondedByUser)
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Respondido por</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $record->respondedByUser->name }}
                </p>
            </div>
        @endif
    </div>

    @if($record->response_message)
        <div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mensaje de Respuesta</p>
            <div class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $record->response_message }}</p>
            </div>
        </div>
    @endif

    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Solicitud original enviada el {{ $record->created_at->format('d/m/Y H:i') }}
            por {{ $record->requestedByUser?->name ?? 'Usuario desconocido' }}
        </p>
    </div>
</div>
