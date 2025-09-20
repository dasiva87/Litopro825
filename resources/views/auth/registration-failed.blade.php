<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error en el Pago - LitoPro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
                <!-- Error Icon -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-4">
                    Error en el Pago
                </h1>

                <p class="text-gray-600 mb-6">
                    Tu cuenta fue creada, pero hubo un problema con el procesamiento del pago. Tu empresa no ha sido activada aún.
                </p>

                <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Posibles causas
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <li>Tarjeta declinada o fondos insuficientes</li>
                                    <li>Problemas temporales con el procesador de pagos</li>
                                    <li>Error en los datos de pago ingresados</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <a href="/admin/billing"
                       class="w-full bg-blue-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Intentar Pago Nuevamente
                    </a>

                    <a href="/admin/login"
                       class="w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Ir al Panel de Administración
                    </a>

                    <a href="/"
                       class="w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Volver al Inicio
                    </a>
                </div>

                <div class="mt-6 text-sm text-gray-500">
                    <p>
                        ¿Necesitas ayuda? Contacta nuestro soporte:
                        <br>
                        <a href="mailto:soporte@litopro.com" class="text-blue-600 hover:text-blue-500">
                            soporte@litopro.com
                        </a>
                        <br>
                        <a href="tel:+573001234567" class="text-blue-600 hover:text-blue-500">
                            +57 300 123 4567
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>