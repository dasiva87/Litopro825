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
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        .step.hidden {
            display: none;
        }
        .step-indicator {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .field-group {
            position: relative;
        }
        .field-feedback {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .strength-meter {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .plan-card {
            transition: all 0.3s ease;
        }
        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
        }
        .plan-card.selected {
            border-width: 2px;
            box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-gray-50 min-h-screen">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-6xl">
            <!-- Header -->
            <div class="text-center mb-10">
                <!-- Logo y Título -->
                <div class="flex items-center justify-center mb-6">
                    <div class="relative">
                        <div class="absolute inset-0 bg-blue-600 blur-xl opacity-20 rounded-full"></div>
                        <svg class="w-14 h-14 text-blue-600 relative z-10" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-extrabold text-gray-900 mb-3">
                    Bienvenido a <span class="text-blue-600">LitoPro</span>
                </h1>
                <p class="text-base text-gray-600 max-w-2xl mx-auto">
                    Crea tu cuenta empresarial en minutos y comienza a gestionar tu litografía con las herramientas más avanzadas
                </p>
            </div>

            <!-- Progress Steps - Redesigned -->
            <div class="mb-10">
                <div class="flex items-center justify-between max-w-2xl mx-auto px-4">
                    <div class="flex flex-col items-center flex-1">
                        <div id="step-indicator-1" class="step-indicator w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-base shadow-lg relative z-10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <span class="mt-3 text-sm font-semibold text-gray-900 text-center">Empresa</span>
                    </div>

                    <div id="progress-line-1" class="flex-1 h-1 bg-gray-300 mx-2 rounded-full -mt-6"></div>

                    <div class="flex flex-col items-center flex-1">
                        <div id="step-indicator-2" class="step-indicator w-12 h-12 rounded-full bg-gray-300 text-gray-500 flex items-center justify-center font-bold text-base relative z-10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <span class="mt-3 text-sm font-medium text-gray-500 text-center">Usuario</span>
                    </div>

                    <div id="progress-line-2" class="flex-1 h-1 bg-gray-300 mx-2 rounded-full -mt-6"></div>

                    <div class="flex flex-col items-center flex-1">
                        <div id="step-indicator-3" class="step-indicator w-12 h-12 rounded-full bg-gray-300 text-gray-500 flex items-center justify-center font-bold text-base relative z-10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <span class="mt-3 text-sm font-medium text-gray-500 text-center">Plan</span>
                    </div>
                </div>

                <!-- Global Progress Bar -->
                <div class="mt-8 max-w-2xl mx-auto px-4">
                    <div class="flex items-center justify-between text-xs font-medium text-gray-600 mb-2">
                        <span>Progreso del registro</span>
                        <span id="progress-text" class="text-blue-600">Paso 1 de 3</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div id="global-progress" class="progress-bar bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 rounded-full" style="width: 33%"></div>
                    </div>
                </div>
            </div>

            <!-- Form Container -->
            <div class="bg-white py-10 px-6 shadow-xl sm:rounded-2xl sm:px-12 lg:px-16 border border-gray-100">
                @if ($errors->any())
                    <div class="mb-8 bg-red-50 border-l-4 border-red-400 rounded-lg p-5">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-red-800">
                                    Por favor corrige los siguientes errores:
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
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Información de tu Empresa</h3>
                            <p class="text-sm text-gray-600">Completa los datos básicos de tu negocio</p>
                        </div>

                        <div class="grid grid-cols-1 gap-7 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="company_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nombre de la Empresa <span class="text-red-500">*</span>
                                </label>
                                <div class="field-group">
                                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                           required
                                           data-validation="required|min:2|max:255"
                                           placeholder="Ej: Litografía Moderna S.A.S.">
                                    <div class="field-feedback"></div>
                                </div>
                                <div class="field-error text-red-600 text-xs mt-1.5 font-medium hidden"></div>
                                <div class="field-help text-gray-500 text-xs mt-1.5">Este será el nombre principal de tu empresa en LitoPro</div>
                            </div>

                            <div>
                                <label for="company_email" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Email Corporativo <span class="text-red-500">*</span>
                                </label>
                                <div class="field-group">
                                    <input type="email" name="company_email" id="company_email" value="{{ old('company_email') }}"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                           required
                                           data-validation="required|email"
                                           placeholder="contacto@tuempresa.com">
                                    <div class="field-feedback"></div>
                                </div>
                                <div class="field-error text-red-600 text-xs mt-1.5 font-medium hidden"></div>
                                <div class="field-help text-gray-500 text-xs mt-1.5">Email principal para comunicaciones oficiales</div>
                            </div>

                            <div>
                                <label for="company_phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Teléfono <span class="text-red-500">*</span>
                                </label>
                                <div class="field-group">
                                    <input type="tel" name="company_phone" id="company_phone" value="{{ old('company_phone') }}"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                           required
                                           data-validation="required|phone"
                                           placeholder="+57 300 123 4567">
                                    <div class="field-feedback"></div>
                                </div>
                                <div class="field-error text-red-600 text-xs mt-1.5 font-medium hidden"></div>
                                <div class="field-help text-gray-500 text-xs mt-1.5">Incluye código de país y número completo</div>
                            </div>

                            <div>
                                <label for="tax_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                    NIT / RUT <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id') }}"
                                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                       required
                                       placeholder="000000000-0">
                            </div>
                            <div>
                                <label for="company_type" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Tipo de Empresa <span class="text-red-500">*</span>
                                </label>
                                <select name="company_type" id="company_type" required
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white">
                                    <option value="litografia" {{ old('company_type', 'litografia') === 'litografia' ? 'selected' : '' }}>
                                        Litografía
                                    </option>
                                    <option value="papeleria" {{ old('company_type') === 'papeleria' ? 'selected' : '' }}>
                                        Papelería
                                    </option>
                                </select>
                                <div class="field-help text-gray-500 text-xs mt-1.5">Las litografías pueden recibir órdenes de compra de papelerías</div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="company_address" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Dirección Completa <span class="text-red-500">*</span>
                                </label>
                                <textarea name="company_address" id="company_address" rows="2"
                                          class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                          required
                                          placeholder="Calle 123 #45-67, Edificio XYZ, Oficina 101">{{ old('company_address') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="button" onclick="nextStep()"
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 border border-transparent rounded-lg shadow-md py-3 px-8 inline-flex items-center justify-center text-sm font-semibold text-white hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                Siguiente Paso
                                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Admin User -->
                    <div id="step-2" class="step hidden">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Usuario Administrador</h3>
                            <p class="text-sm text-gray-600">Crea tu usuario principal con permisos completos de administración</p>
                        </div>

                        <div class="grid grid-cols-1 gap-7 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nombre Completo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                       required
                                       placeholder="Juan Pérez García">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Email Personal <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                       required
                                       placeholder="tu-email@ejemplo.com">
                                <p class="mt-1.5 text-xs text-gray-500">Este será tu email de acceso al sistema</p>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Contraseña <span class="text-red-500">*</span>
                                </label>
                                <div class="field-group relative">
                                    <input type="password" name="password" id="password"
                                           class="block w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                           required
                                           data-validation="required|min:8"
                                           placeholder="Mínimo 8 caracteres">
                                    <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer z-10" onclick="togglePassword('password')">
                                        <svg id="eye-open-password" class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg id="eye-closed-password" class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m-6.878-6.878l15 15"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="strength-meter">
                                    <div id="password-strength" class="strength-fill bg-gray-300" style="width: 0%"></div>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <div class="field-error text-red-600 text-xs font-medium hidden"></div>
                                    <span id="password-strength-text" class="text-xs font-medium text-gray-500">Introduce una contraseña</span>
                                </div>
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Confirmar Contraseña <span class="text-red-500">*</span>
                                </label>
                                <div class="field-group relative">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           class="block w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                           required
                                           data-validation="required|match:password"
                                           placeholder="Repite la contraseña">
                                    <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer z-10" onclick="togglePassword('password_confirmation')">
                                        <svg id="eye-open-password_confirmation" class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg id="eye-closed-password_confirmation" class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m-6.878-6.878l15 15"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="field-error text-red-600 text-xs mt-1.5 font-medium hidden"></div>
                                <div class="field-help text-gray-500 text-xs mt-1.5">Debe coincidir con la contraseña anterior</div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between items-center">
                            <button type="button" onclick="prevStep()"
                                    class="bg-white border border-gray-300 rounded-lg shadow-sm py-3 px-8 inline-flex items-center justify-center text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Anterior
                            </button>
                            <button type="button" onclick="nextStep()"
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 border border-transparent rounded-lg shadow-md py-3 px-8 inline-flex items-center justify-center text-sm font-semibold text-white hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                Siguiente Paso
                                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Plan Selection -->
                    <div id="step-3" class="step hidden">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2 text-center">Elige tu Plan Perfecto</h3>
                            <p class="text-sm text-gray-600 text-center max-w-2xl mx-auto">Selecciona el plan que mejor se adapte a las necesidades de tu litografía. Puedes cambiar tu plan en cualquier momento.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-8">
                            @foreach($plans as $plan)
                                <div class="plan-card plan-option relative rounded-2xl border-2 {{ $plan->price == 0 ? 'border-blue-500 selected' : 'border-gray-200' }} bg-white p-8 cursor-pointer {{ $plan->price == 0 ? 'shadow-lg' : 'shadow-md' }}"
                                     onclick="selectPlan({{ $plan->id }})">
                                    <input type="radio" name="plan_id" id="plan_{{ $plan->id }}" value="{{ $plan->id }}"
                                           class="sr-only" {{ $plan->price == 0 || old('plan_id') == $plan->id ? 'checked' : '' }}>

                                    @if($plan->price == 0)
                                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-md">
                                                ✨ Recomendado
                                            </span>
                                        </div>
                                    @endif

                                    <div class="text-center mb-6">
                                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                                        <div class="mt-4">
                                            <span class="text-4xl font-extrabold text-gray-900">
                                                ${{ number_format($plan->price, 0, ',', '.') }}
                                            </span>
                                            <span class="text-base font-medium text-gray-500">
                                                / {{ $plan->interval === 'month' ? 'mes' : 'año' }}
                                            </span>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">{{ $plan->description }}</p>
                                    </div>

                                    @if($plan->features)
                                        <ul class="space-y-3 mb-6">
                                            @foreach(array_slice($plan->features, 0, 5) as $feature)
                                                <li class="flex items-start text-sm text-gray-700">
                                                    <svg class="h-5 w-5 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span>{{ is_array($feature) ? $feature['name'] : $feature }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <div class="text-center">
                                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-full border-2 {{ $plan->price == 0 ? 'border-blue-500 bg-blue-500' : 'border-gray-300' }}">
                                            @if($plan->price == 0)
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <div class="flex items-start">
                                <div class="flex items-center h-6">
                                    <input id="terms" name="terms" type="checkbox"
                                           class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded transition-all duration-200 cursor-pointer"
                                           required>
                                </div>
                                <div class="ml-4">
                                    <label for="terms" class="font-semibold text-gray-900 cursor-pointer select-none">
                                        Acepto los términos y condiciones <span class="text-red-500">*</span>
                                    </label>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Al registrarme, acepto los
                                        <a href="#" class="font-medium text-blue-600 hover:text-blue-700 hover:underline">términos de servicio</a> y la
                                        <a href="#" class="font-medium text-blue-600 hover:text-blue-700 hover:underline">política de privacidad</a> de LitoPro.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col gap-4 w-full">
                            <button type="submit" id="submit-btn"
                                    class="w-full bg-gradient-to-r from-green-600 to-emerald-600 border border-transparent rounded-lg shadow-lg py-4 px-10 inline-flex justify-center items-center text-base font-bold text-white hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105">
                                <svg id="submit-icon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <svg id="submit-loading" class="w-5 h-5 mr-2 loading-spinner hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span id="submit-text">Crear Mi Cuenta</span>
                            </button>
                            <button type="button" onclick="prevStep()"
                                    class="w-full bg-white border border-gray-300 rounded-lg shadow-sm py-3 px-8 inline-flex items-center justify-center text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Anterior
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Already have account -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    ¿Ya tienes una cuenta?
                    <a href="/admin/login" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline transition-colors">
                        Inicia sesión aquí →
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
                const stepLabel = indicator.nextElementSibling;

                if (stepNum < currentStep) {
                    // Completed step
                    indicator.classList.remove('bg-gray-300', 'text-gray-500', 'bg-blue-600');
                    indicator.classList.add('bg-green-500', 'text-white', 'shadow-lg');
                    if (stepLabel) {
                        stepLabel.classList.remove('text-gray-500', 'font-medium');
                        stepLabel.classList.add('text-gray-900', 'font-semibold');
                    }
                    if (progressLine) {
                        progressLine.classList.remove('bg-gray-300');
                        progressLine.classList.add('bg-green-500');
                    }
                } else if (stepNum === currentStep) {
                    // Active step
                    indicator.classList.remove('bg-gray-300', 'text-gray-500', 'bg-green-500');
                    indicator.classList.add('bg-blue-600', 'text-white', 'shadow-lg');
                    if (stepLabel) {
                        stepLabel.classList.remove('text-gray-500', 'font-medium');
                        stepLabel.classList.add('text-gray-900', 'font-semibold');
                    }
                } else {
                    // Future step
                    indicator.classList.remove('bg-blue-600', 'bg-green-500', 'shadow-lg');
                    indicator.classList.add('bg-gray-300', 'text-gray-500');
                    if (stepLabel) {
                        stepLabel.classList.remove('text-gray-900', 'font-semibold');
                        stepLabel.classList.add('text-gray-500', 'font-medium');
                    }
                    if (progressLine) {
                        progressLine.classList.remove('bg-green-500');
                        progressLine.classList.add('bg-gray-300');
                    }
                }
            });
        }

        function updateGlobalProgress() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('global-progress').style.width = `${progress}%`;
            document.getElementById('progress-text').textContent = `Paso ${currentStep} de ${totalSteps}`;
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
            // Remove selection from all plans
            document.querySelectorAll('.plan-option').forEach(option => {
                option.classList.remove('border-blue-500', 'selected', 'shadow-lg');
                option.classList.add('border-gray-200', 'shadow-md');

                // Update checkmark
                const checkmark = option.querySelector('.inline-flex.items-center.justify-center');
                if (checkmark) {
                    checkmark.classList.remove('border-blue-500', 'bg-blue-500');
                    checkmark.classList.add('border-gray-300');
                    const svg = checkmark.querySelector('svg');
                    if (svg) svg.classList.add('hidden');
                }
            });

            // Add selection to chosen plan
            const selectedOption = document.querySelector(`input[value="${planId}"]`).closest('.plan-option');
            selectedOption.classList.remove('border-gray-200', 'shadow-md');
            selectedOption.classList.add('border-blue-500', 'selected', 'shadow-lg');

            // Update checkmark
            const checkmark = selectedOption.querySelector('.inline-flex.items-center.justify-center');
            if (checkmark) {
                checkmark.classList.remove('border-gray-300');
                checkmark.classList.add('border-blue-500', 'bg-blue-500');
                const svg = checkmark.querySelector('svg');
                if (svg) svg.classList.remove('hidden');
            }

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
            submitBtn.classList.remove('hover:scale-105');
            submitIcon.classList.add('hidden');
            submitLoading.classList.remove('hidden');
            submitText.textContent = 'Creando tu cuenta...';

            // Limpiar datos guardados
            localStorage.removeItem('registrationProgress');
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