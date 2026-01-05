<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Suscripción Exitosa! - GrafiRed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <!-- Success Icon -->
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <!-- Success Message -->
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    ¡Bienvenido a GrafiRed!
                </h2>

                <p class="text-lg text-gray-600 mb-8">
                    Tu suscripción se ha activado exitosamente.
                </p>

                <!-- Subscription Details -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalles de tu Suscripción</h3>

                    <div class="space-y-3 text-left">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Plan:</span>
                            <span class="font-medium text-gray-900">{{ $plan->name ?? 'Plan Seleccionado' }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Estado:</span>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                @if($subscription->onTrial())
                                    En Período de Prueba
                                @else
                                    Activo
                                @endif
                            </span>
                        </div>

                        @if($subscription->onTrial())
                            <div class="flex justify-between">
                                <span class="text-gray-600">Prueba termina:</span>
                                <span class="font-medium text-gray-900">
                                    {{ $subscription->trial_ends_at->format('d/m/Y') }}
                                </span>
                            </div>
                        @endif

                        @if($plan)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Precio:</span>
                                <span class="font-medium text-gray-900">
                                    ${{ number_format($plan->price, 0) }} {{ strtoupper($plan->currency) }}/{{ $plan->interval === 'month' ? 'mes' : 'año' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-blue-50 p-6 rounded-lg mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Próximos Pasos</h3>

                    <ul class="text-left space-y-2 text-gray-700">
                        <li class="flex items-center">
                            <svg class="h-5 w-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            Completa la configuración de tu empresa
                        </li>
                        <li class="flex items-center">
                            <svg class="h-5 w-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            Invita a tu equipo de trabajo
                        </li>
                        <li class="flex items-center">
                            <svg class="h-5 w-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            Crea tu primera cotización
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <a href="/admin"
                       class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                        Ir al Dashboard
                    </a>

                    <a href="{{ route('subscription.manage') }}"
                       class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                        Gestionar Suscripción
                    </a>
                </div>

                <!-- Support -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-600">
                        ¿Necesitas ayuda?
                        <a href="mailto:soporte@grafired.com" class="text-blue-600 hover:text-blue-500">
                            Contáctanos
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>