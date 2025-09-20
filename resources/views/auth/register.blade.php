<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrar Empresa - LitoPro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .step {
            transition: all 0.3s ease-in-out;
        }
        .step.hidden {
            display: none;
        }
        .step-indicator {
            transition: all 0.3s ease-in-out;
        }
        .step-indicator.active {
            background-color: #3b82f6;
            color: white;
        }
        .step-indicator.completed {
            background-color: #10b981;
            color: white;
        }
        .input-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        .input-success {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .progress-bar {
            transition: width 0.3s ease-in-out;
        }
        .field-group {
            position: relative;
        }
        .field-feedback {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .strength-meter {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 4px;
        }
        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-4xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-blue-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                    </svg>
                    <h1 class="text-3xl font-bold text-gray-900">Únete a LitoPro</h1>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Crea tu cuenta empresarial y comienza a gestionar tu litografía de forma profesional
                </p>

                <!-- Global Progress Bar -->
                <div class="mt-6 max-w-md mx-auto">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                        <span>Progreso</span>
                        <span id="progress-text">33% completado</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="global-progress" class="progress-bar bg-blue-600 h-2 rounded-full" style="width: 33%"></div>
                    </div>
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center space-x-8">
                    <div class="flex items-center">
                        <div id="step-indicator-1" class="step-indicator active flex items-center justify-center w-8 h-8 rounded-full border-2 border-blue-600 bg-blue-600 text-white text-sm font-medium">
                            1
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-900 hidden sm:block">Información de la Empresa</span>
                        <span class="ml-2 text-sm font-medium text-gray-900 sm:hidden">Empresa</span>
                    </div>
                    <div id="progress-line-1" class="w-8 sm:w-16 h-0.5 bg-gray-300"></div>
                    <div class="flex items-center">
                        <div id="step-indicator-2" class="step-indicator flex items-center justify-center w-8 h-8 rounded-full border-2 border-gray-300 bg-white text-gray-500 text-sm font-medium">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-500 hidden sm:block">Usuario Administrador</span>
                        <span class="ml-2 text-sm font-medium text-gray-500 sm:hidden">Usuario</span>
                    </div>
                    <div id="progress-line-2" class="w-8 sm:w-16 h-0.5 bg-gray-300"></div>
                    <div class="flex items-center">
                        <div id="step-indicator-3" class="step-indicator flex items-center justify-center w-8 h-8 rounded-full border-2 border-gray-300 bg-white text-gray-500 text-sm font-medium">
                            3
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-500 hidden sm:block">Plan y Pago</span>
                        <span class="ml-2 text-sm font-medium text-gray-500 sm:hidden">Plan</span>
                    </div>
                </div>
            </div>

            <!-- Form Container -->
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Hay errores en el formulario
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul role="list" class="list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <form id="registration-form" method="POST" action="{{ route('register.store') }}">
                    @csrf

                    <!-- Step 1: Company Information -->
                    <div id="step-1" class="step">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Información de la Empresa</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="company_name" class="block text-sm font-medium text-gray-700">
                                    Nombre de la Empresa *
                                </label>
                                <div class="field-group mt-1">
                                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           required
                                           data-validation="required|min:2|max:255"
                                           placeholder="Ej: Litografía Moderna S.A.S.">
                                    <div class="field-feedback"></div>
                                </div>
                                <div class="field-error text-red-500 text-sm mt-1 hidden"></div>
                                <div class="field-help text-gray-500 text-xs mt-1">Este será el nombre principal de tu empresa en LitoPro</div>
                            </div>

                            <div>
                                <label for="company_email" class="block text-sm font-medium text-gray-700">
                                    Email Corporativo *
                                </label>
                                <div class="field-group mt-1">
                                    <input type="email" name="company_email" id="company_email" value="{{ old('company_email') }}"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           required
                                           data-validation="required|email"
                                           placeholder="contacto@tuempresa.com">
                                    <div class="field-feedback"></div>
                                </div>
                                <div class="field-error text-red-500 text-sm mt-1 hidden"></div>
                                <div class="field-help text-gray-500 text-xs mt-1">Email principal para comunicaciones oficiales</div>
                            </div>

                            <div>
                                <label for="company_phone" class="block text-sm font-medium text-gray-700">
                                    Teléfono *
                                </label>
                                <div class="field-group mt-1">
                                    <input type="tel" name="company_phone" id="company_phone" value="{{ old('company_phone') }}"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           required
                                           data-validation="required|phone"
                                           placeholder="+57 300 123 4567">
                                    <div class="field-feedback"></div>
                                </div>
                                <div class="field-error text-red-500 text-sm mt-1 hidden"></div>
                                <div class="field-help text-gray-500 text-xs mt-1">Incluye código de país y número completo</div>
                            </div>

                            <div>
                                <label for="tax_id" class="block text-sm font-medium text-gray-700">
                                    NIT / RUT *
                                </label>
                                <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       required>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="company_address" class="block text-sm font-medium text-gray-700">
                                    Dirección *
                                </label>
                                <textarea name="company_address" id="company_address" rows="2"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                          required>{{ old('company_address') }}</textarea>
                            </div>

                            <div>
                                <label for="country_id" class="block text-sm font-medium text-gray-700">
                                    País *
                                </label>
                                <select name="country_id" id="country_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        required>
                                    <option value="">Selecciona un país</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="state_id" class="block text-sm font-medium text-gray-700">
                                    Departamento/Estado *
                                </label>
                                <select name="state_id" id="state_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        required disabled>
                                    <option value="">Selecciona un departamento</option>
                                </select>
                            </div>

                            <div>
                                <label for="city_id" class="block text-sm font-medium text-gray-700">
                                    Ciudad *
                                </label>
                                <select name="city_id" id="city_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        required disabled>
                                    <option value="">Selecciona una ciudad</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="button" onclick="nextStep()"
                                    class="bg-blue-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Siguiente
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Admin User -->
                    <div id="step-2" class="step hidden">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Usuario Administrador</h3>
                            <p class="text-sm text-gray-600">Este será el usuario principal de tu empresa con permisos administrativos.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Nombre Completo *
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       required>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email Personal *
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       required>
                                <p class="mt-1 text-xs text-gray-500">Este será tu email de acceso al sistema</p>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">
                                    Contraseña *
                                </label>
                                <div class="field-group mt-1">
                                    <input type="password" name="password" id="password"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-10"
                                           required
                                           data-validation="required|min:8"
                                           placeholder="Mínimo 8 caracteres">
                                    <button type="button" class="field-feedback absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('password')">
                                        <svg id="eye-open-password" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg id="eye-closed-password" class="w-4 h-4 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m-6.878-6.878l15 15"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="strength-meter mt-2">
                                    <div id="password-strength" class="strength-fill bg-gray-300" style="width: 0%"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1">
                                    <div class="field-error text-red-500 text-sm hidden"></div>
                                    <span id="password-strength-text" class="text-xs text-gray-500">Introduce una contraseña</span>
                                </div>
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                    Confirmar Contraseña *
                                </label>
                                <div class="field-group mt-1">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-10"
                                           required
                                           data-validation="required|match:password"
                                           placeholder="Repite la contraseña">
                                    <button type="button" class="field-feedback absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('password_confirmation')">
                                        <svg id="eye-open-password_confirmation" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg id="eye-closed-password_confirmation" class="w-4 h-4 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m-6.878-6.878l15 15"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="field-error text-red-500 text-sm mt-1 hidden"></div>
                                <div class="field-help text-gray-500 text-xs mt-1">Debe coincidir con la contraseña anterior</div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-between">
                            <button type="button" onclick="prevStep()"
                                    class="bg-gray-300 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-gray-700 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Anterior
                            </button>
                            <button type="button" onclick="nextStep()"
                                    class="bg-blue-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Siguiente
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Plan Selection -->
                    <div id="step-3" class="step hidden">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Selecciona tu Plan</h3>
                            <p class="text-sm text-gray-600">Elige el plan que mejor se adapte a las necesidades de tu litografía.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            @foreach($plans as $plan)
                                <div class="plan-option relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500 cursor-pointer {{ $plan->price == 0 ? 'ring-2 ring-blue-500' : '' }}"
                                     onclick="selectPlan({{ $plan->id }})">
                                    <input type="radio" name="plan_id" id="plan_{{ $plan->id }}" value="{{ $plan->id }}"
                                           class="sr-only" {{ $plan->price == 0 || old('plan_id') == $plan->id ? 'checked' : '' }}>
                                    <div class="flex-1 min-w-0">
                                        <div class="focus:outline-none">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-lg font-medium text-gray-900">{{ $plan->name }}</h3>
                                                @if($plan->price == 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Recomendado
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-2">
                                                <span class="text-2xl font-bold text-gray-900">
                                                    ${{ number_format($plan->price, 0, ',', '.') }}
                                                </span>
                                                <span class="text-sm text-gray-500">
                                                    / {{ $plan->interval === 'month' ? 'mes' : 'año' }}
                                                </span>
                                            </div>
                                            <p class="mt-2 text-sm text-gray-500">{{ $plan->description }}</p>

                                            @if($plan->features)
                                                <ul class="mt-3 space-y-1">
                                                    @foreach(array_slice($plan->features, 0, 3) as $feature)
                                                        <li class="flex items-center text-xs text-gray-600">
                                                            <svg class="h-3 w-3 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            {{ is_array($feature) ? $feature['name'] : $feature }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mt-6">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="terms" name="terms" type="checkbox"
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                           required>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms" class="font-medium text-gray-700">
                                        Acepto los términos y condiciones *
                                    </label>
                                    <p class="text-gray-500">
                                        Al registrarme, acepto los
                                        <a href="#" class="text-blue-600 hover:text-blue-500">términos de servicio</a> y
                                        <a href="#" class="text-blue-600 hover:text-blue-500">política de privacidad</a> de LitoPro.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-between">
                            <button type="button" onclick="prevStep()"
                                    class="bg-gray-300 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-gray-700 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Anterior
                            </button>
                            <button type="submit" id="submit-btn"
                                    class="bg-green-600 border border-transparent rounded-md shadow-sm py-3 px-6 inline-flex justify-center text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg id="submit-icon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <svg id="submit-loading" class="w-4 h-4 mr-2 loading-spinner hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span id="submit-text">Crear Cuenta y Proceder al Pago</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Already have account -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    ¿Ya tienes cuenta?
                    <a href="/admin/login" class="font-medium text-blue-600 hover:text-blue-500">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;
        const formData = {}; // Para guardar progreso

        // Configuración de validación
        const validationRules = {
            required: (value) => value && value.trim().length > 0,
            email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            phone: (value) => /^[\+]?[1-9][\d]{8,14}$/.test(value.replace(/\s/g, '')),
            min: (value, min) => value && value.length >= parseInt(min),
            max: (value, max) => value && value.length <= parseInt(max),
            match: (value, fieldName) => value === document.getElementById(fieldName).value
        };

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            setupValidation();
            loadSavedProgress();
            setupPasswordStrength();
        });

        // Funciones de navegación
        function nextStep() {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                currentStep++;
                showStep(currentStep);
                updateStepIndicators();
                updateGlobalProgress();
            }
        }

        function prevStep() {
            currentStep--;
            showStep(currentStep);
            updateStepIndicators();
            updateGlobalProgress();
        }

        function showStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.add('hidden'));
            document.querySelector(`#step-${step}`).classList.remove('hidden');
        }

        function updateStepIndicators() {
            document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
                const stepNum = index + 1;
                const progressLine = document.querySelector(`#progress-line-${stepNum}`);

                indicator.classList.remove('active', 'completed');

                if (stepNum < currentStep) {
                    indicator.classList.add('completed');
                    indicator.classList.remove('border-gray-300', 'bg-white', 'text-gray-500');
                    indicator.classList.add('border-green-600', 'bg-green-600', 'text-white');
                    if (progressLine) progressLine.classList.replace('bg-gray-300', 'bg-green-600');
                } else if (stepNum === currentStep) {
                    indicator.classList.add('active');
                    indicator.classList.remove('border-gray-300', 'bg-white', 'text-gray-500', 'border-green-600', 'bg-green-600');
                    indicator.classList.add('border-blue-600', 'bg-blue-600', 'text-white');
                } else {
                    indicator.classList.remove('active', 'completed', 'border-blue-600', 'bg-blue-600', 'border-green-600', 'bg-green-600');
                    indicator.classList.add('border-gray-300', 'bg-white', 'text-gray-500');
                }
            });
        }

        function updateGlobalProgress() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('global-progress').style.width = `${progress}%`;
            document.getElementById('progress-text').textContent = `${Math.round(progress)}% completado`;
        }

        // Validación en tiempo real
        function setupValidation() {
            document.querySelectorAll('input[data-validation]').forEach(input => {
                input.addEventListener('blur', () => validateField(input));
                input.addEventListener('input', () => {
                    // Limpiar errores mientras escribe
                    clearFieldError(input);
                    if (input.name === 'password') {
                        updatePasswordStrength(input.value);
                    }
                });
            });
        }

        function validateField(field) {
            const rules = field.getAttribute('data-validation').split('|');
            const value = field.value;

            for (let rule of rules) {
                const [ruleName, param] = rule.split(':');

                if (!validationRules[ruleName]) continue;

                const isValid = param ?
                    validationRules[ruleName](value, param) :
                    validationRules[ruleName](value);

                if (!isValid) {
                    showFieldError(field, getErrorMessage(ruleName, param));
                    return false;
                }
            }

            showFieldSuccess(field);
            return true;
        }

        function validateCurrentStep() {
            const currentStepDiv = document.querySelector(`#step-${currentStep}`);
            const fields = currentStepDiv.querySelectorAll('input[data-validation], select[required]');
            let isValid = true;

            fields.forEach(field => {
                if (field.hasAttribute('data-validation')) {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                } else if (field.hasAttribute('required') && !field.value.trim()) {
                    showFieldError(field, 'Este campo es requerido');
                    isValid = false;
                }
            });

            return isValid;
        }

        function showFieldError(field, message) {
            field.classList.add('input-error');
            field.classList.remove('input-success');

            const errorDiv = field.closest('.field-group')?.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('field-error')) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
            }

            // Icono de error
            const feedback = field.parentElement.querySelector('.field-feedback');
            if (feedback && !feedback.querySelector('button')) {
                feedback.innerHTML = '<svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
            }
        }

        function showFieldSuccess(field) {
            field.classList.add('input-success');
            field.classList.remove('input-error');

            const errorDiv = field.closest('.field-group')?.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('field-error')) {
                errorDiv.classList.add('hidden');
            }

            // Icono de éxito
            const feedback = field.parentElement.querySelector('.field-feedback');
            if (feedback && !feedback.querySelector('button')) {
                feedback.innerHTML = '<svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
            }
        }

        function clearFieldError(field) {
            field.classList.remove('input-error', 'input-success');

            const errorDiv = field.closest('.field-group')?.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('field-error')) {
                errorDiv.classList.add('hidden');
            }
        }

        function getErrorMessage(rule, param) {
            const messages = {
                required: 'Este campo es requerido',
                email: 'Introduce un email válido',
                phone: 'Introduce un número de teléfono válido',
                min: `Debe tener al menos ${param} caracteres`,
                max: `No puede tener más de ${param} caracteres`,
                match: 'Las contraseñas no coinciden'
            };
            return messages[rule] || 'Valor no válido';
        }

        // Password strength
        function setupPasswordStrength() {
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.addEventListener('input', (e) => updatePasswordStrength(e.target.value));
            }
        }

        function updatePasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength');
            const strengthText = document.getElementById('password-strength-text');

            if (!password) {
                strengthBar.style.width = '0%';
                strengthBar.className = 'strength-fill bg-gray-300';
                strengthText.textContent = 'Introduce una contraseña';
                return;
            }

            let score = 0;
            let feedback = '';

            // Criterios de fortaleza
            if (password.length >= 8) score += 1;
            if (password.length >= 12) score += 1;
            if (/[a-z]/.test(password)) score += 1;
            if (/[A-Z]/.test(password)) score += 1;
            if (/[0-9]/.test(password)) score += 1;
            if (/[^A-Za-z0-9]/.test(password)) score += 1;

            const strength = ['Muy débil', 'Débil', 'Regular', 'Buena', 'Fuerte', 'Muy fuerte'];
            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500', 'bg-green-600'];
            const widths = [16, 33, 50, 66, 83, 100];

            const level = Math.min(score, 5);
            strengthBar.style.width = `${widths[level]}%`;
            strengthBar.className = `strength-fill ${colors[level]}`;
            strengthText.textContent = strength[level];
        }

        // Mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eyeOpen = document.getElementById(`eye-open-${fieldId}`);
            const eyeClosed = document.getElementById(`eye-closed-${fieldId}`);

            if (field.type === 'password') {
                field.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                field.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }

        // Guardar progreso
        function saveCurrentStepData() {
            const currentStepDiv = document.querySelector(`#step-${currentStep}`);
            const inputs = currentStepDiv.querySelectorAll('input, select, textarea');

            inputs.forEach(input => {
                if (input.type !== 'password') {
                    formData[input.name] = input.value;
                }
            });

            localStorage.setItem('registrationProgress', JSON.stringify(formData));
        }

        function loadSavedProgress() {
            const saved = localStorage.getItem('registrationProgress');
            if (saved) {
                const data = JSON.parse(saved);
                Object.keys(data).forEach(key => {
                    const field = document.querySelector(`[name="${key}"]`);
                    if (field && data[key]) {
                        field.value = data[key];
                    }
                });
            }
        }

        // Plan selection
        function selectPlan(planId) {
            document.querySelectorAll('.plan-option').forEach(option => {
                option.classList.remove('ring-2', 'ring-blue-500');
            });

            const selectedOption = document.querySelector(`input[value="${planId}"]`).closest('.plan-option');
            selectedOption.classList.add('ring-2', 'ring-blue-500');

            document.querySelector(`#plan_${planId}`).checked = true;
        }

        // Form submission con loading
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submit-btn');
            const submitIcon = document.getElementById('submit-icon');
            const submitLoading = document.getElementById('submit-loading');
            const submitText = document.getElementById('submit-text');

            // Mostrar loading state
            submitBtn.disabled = true;
            submitIcon.classList.add('hidden');
            submitLoading.classList.remove('hidden');
            submitText.textContent = 'Procesando...';

            // Limpiar datos guardados
            localStorage.removeItem('registrationProgress');
        });

        // Location dropdowns con loading
        document.getElementById('country_id').addEventListener('change', function() {
            const countryId = this.value;
            const stateSelect = document.getElementById('state_id');
            const citySelect = document.getElementById('city_id');

            stateSelect.innerHTML = '<option value="">Cargando...</option>';
            citySelect.innerHTML = '<option value="">Selecciona una ciudad</option>';
            stateSelect.disabled = true;
            citySelect.disabled = true;

            if (countryId) {
                fetch('/get-states', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ country_id: countryId })
                })
                .then(response => response.json())
                .then(states => {
                    stateSelect.innerHTML = '<option value="">Selecciona un departamento</option>';
                    states.forEach(state => {
                        stateSelect.innerHTML += `<option value="${state.id}">${state.name}</option>`;
                    });
                    stateSelect.disabled = false;
                })
                .catch(error => {
                    stateSelect.innerHTML = '<option value="">Error al cargar</option>';
                    console.error('Error loading states:', error);
                });
            } else {
                stateSelect.innerHTML = '<option value="">Selecciona un departamento</option>';
            }
        });

        document.getElementById('state_id').addEventListener('change', function() {
            const stateId = this.value;
            const citySelect = document.getElementById('city_id');

            citySelect.innerHTML = '<option value="">Cargando...</option>';
            citySelect.disabled = true;

            if (stateId) {
                fetch('/get-cities', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ state_id: stateId })
                })
                .then(response => response.json())
                .then(cities => {
                    citySelect.innerHTML = '<option value="">Selecciona una ciudad</option>';
                    cities.forEach(city => {
                        citySelect.innerHTML += `<option value="${city.id}">${city.name}</option>`;
                    });
                    citySelect.disabled = false;
                })
                .catch(error => {
                    citySelect.innerHTML = '<option value="">Error al cargar</option>';
                    console.error('Error loading cities:', error);
                });
            } else {
                citySelect.innerHTML = '<option value="">Selecciona una ciudad</option>';
            }
        });

        // Auto-save cada 30 segundos
        setInterval(() => {
            if (currentStep < totalSteps) {
                saveCurrentStepData();
            }
        }, 30000);
    </script>
</body>
</html>