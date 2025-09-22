<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes de Suscripción - LitoPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">LitoPro</h1>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="/admin" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                        <a href="{{ route('simple.logout') }}" class="text-gray-700 hover:text-gray-900">Cerrar Sesión</a>
                    @else
                        <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-700 hover:text-gray-900">Iniciar Sesión</a>
                        <a href="{{ route('register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Registrarse</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                Escoge el plan perfecto para tu negocio
            </h2>
            <p class="text-xl text-gray-600 mb-8">
                Gestiona tu litografía con herramientas profesionales. Cancela cuando quieras.
            </p>
        </div>
    </section>

    <!-- Pricing Plans -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid md:grid-cols-3 gap-8">
                @foreach($plans as $plan)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden {{ $loop->index === 1 ? 'ring-2 ring-blue-500 relative' : '' }}">
                        @if($loop->index === 1)
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                <span class="bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-medium">
                                    Más Popular
                                </span>
                            </div>
                        @endif

                        <div class="p-6">
                            <!-- Plan Header -->
                            <div class="text-center mb-6">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                                <div class="flex items-center justify-center mb-2">
                                    <span class="text-4xl font-bold text-gray-900">
                                        ${{ number_format($plan->price, 0) }}
                                    </span>
                                    <span class="text-gray-600 ml-2">
                                        {{ strtoupper($plan->currency) }}/{{ $plan->interval === 'month' ? 'mes' : 'año' }}
                                    </span>
                                </div>
                                @if($plan->trial_days)
                                    <p class="text-green-600 font-medium">
                                        {{ $plan->trial_days }} días gratis
                                    </p>
                                @endif
                            </div>

                            <!-- Plan Description -->
                            @if($plan->description)
                                <p class="text-gray-600 text-center mb-6">{{ $plan->description }}</p>
                            @endif

                            <!-- Features -->
                            <ul class="space-y-3 mb-8">
                                @if($plan->features && is_array($plan->features))
                                    @foreach($plan->features as $feature)
                                        <li class="flex items-center">
                                            <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span class="text-gray-700">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="text-gray-600">No hay características disponibles</li>
                                @endif
                            </ul>

                            <!-- CTA Button -->
                            <div class="text-center">
                                @auth
                                    @if(auth()->user()->subscribed('default'))
                                        <a href="{{ route('subscription.manage') }}"
                                           class="block w-full bg-gray-600 text-white text-center px-6 py-3 rounded-md hover:bg-gray-700 transition duration-200">
                                            Gestionar Suscripción
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('subscription.subscribe', $plan) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="block w-full {{ $loop->index === 1 ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white text-center px-6 py-3 rounded-md transition duration-200">
                                                @if($plan->trial_days)
                                                    Comenzar Prueba Gratis
                                                @else
                                                    Suscribirse Ahora
                                                @endif
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <a href="{{ route('register') }}"
                                       class="block w-full {{ $loop->index === 1 ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white text-center px-6 py-3 rounded-md transition duration-200">
                                        Comenzar Ahora
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h3 class="text-3xl font-bold text-center text-gray-900 mb-12">Preguntas Frecuentes</h3>

            <div class="space-y-8">
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">¿Puedo cambiar de plan en cualquier momento?</h4>
                    <p class="text-gray-600">Sí, puedes actualizar o cambiar tu plan en cualquier momento desde tu panel de control. Los cambios se aplicarán inmediatamente con prorrateo automático.</p>
                </div>

                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">¿Cómo funciona el período de prueba?</h4>
                    <p class="text-gray-600">El período de prueba te permite usar todas las funciones del plan sin costo. No se requiere información de pago hasta que termine la prueba.</p>
                </div>

                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">¿Puedo cancelar mi suscripción?</h4>
                    <p class="text-gray-600">Por supuesto. Puedes cancelar tu suscripción en cualquier momento. Mantendrás acceso hasta el final de tu período de facturación actual.</p>
                </div>

                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">¿Qué métodos de pago aceptan?</h4>
                    <p class="text-gray-600">Aceptamos todas las tarjetas de crédito y débito principales (Visa, Mastercard, American Express) a través de Stripe, nuestra plataforma de pagos segura.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; {{ date('Y') }} LitoPro. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>