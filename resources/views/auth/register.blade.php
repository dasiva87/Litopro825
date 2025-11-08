<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrar - GrafiRed</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Smooth transitions */
        .step {
            transition: opacity 0.4s ease, transform 0.4s ease;
        }
        .step.hidden {
            display: none;
        }

        /* Input focus effects */
        .input-modern {
            transition: all 0.25s ease;
        }
        .input-modern:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px rgba(59, 130, 246, 0.3);
        }

        /* Input states */
        .input-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2;
        }
        .input-success {
            border-color: #10b981 !important;
        }

        /* Password strength */
        .strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 10px;
        }
        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        /* Plan cards */
        .plan-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .plan-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .plan-card.selected::before {
            transform: scaleX(1);
        }
        .plan-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.12);
        }
        .plan-card.selected {
            border-color: #3b82f6;
            box-shadow: 0 12px 32px -8px rgba(59, 130, 246, 0.25);
        }

        /* Progress dots */
        .progress-dot {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .progress-dot.active {
            transform: scale(1.3);
        }

        /* Loading spinner */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        /* Fade in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        /* Gradient background */
        .bg-gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Floating labels effect */
        .input-group {
            position: relative;
        }
        .input-group label {
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">

    <!-- Main Container -->
    <div class="min-h-screen flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-5xl">

            <!-- Header -->
            <div class="text-center mb-12 fade-in">
                <!-- Logo -->
                <div class="flex justify-center mb-6">
                    <div class="relative">
                        <div class="absolute inset-0 bg-blue-500 blur-2xl opacity-30 rounded-full animate-pulse"></div>
                        <div class="relative bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl p-4 shadow-xl">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-3">
                    LitoPro
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto font-medium">
                    Gestiona tu litografía de forma profesional
                </p>
            </div>

            <!-- Progress Bar - Minimalist -->
            <div class="mb-12">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="flex items-center">
                        <div id="progress-dot-1" class="progress-dot active w-3 h-3 rounded-full bg-blue-600"></div>
                        <span id="progress-label-1" class="ml-3 text-sm font-semibold text-blue-600">Empresa</span>
                    </div>
                    <div class="w-16 h-0.5 bg-slate-300 rounded-full">
                        <div id="progress-line-1" class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <div class="flex items-center">
                        <div id="progress-dot-2" class="progress-dot w-3 h-3 rounded-full bg-slate-300"></div>
                        <span id="progress-label-2" class="ml-3 text-sm font-medium text-slate-400">Usuario</span>
                    </div>
                    <div class="w-16 h-0.5 bg-slate-300 rounded-full">
                        <div id="progress-line-2" class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <div class="flex items-center">
                        <div id="progress-dot-3" class="progress-dot w-3 h-3 rounded-full bg-slate-300"></div>
                        <span id="progress-label-3" class="ml-3 text-sm font-medium text-slate-400">Plan</span>
                    </div>
                </div>

                <!-- Global progress -->
                <div class="max-w-md mx-auto">
                    <div class="flex justify-between items-center text-xs text-slate-500 mb-2">
                        <span id="progress-text" class="font-semibold">Paso 1 de 3</span>
                        <span id="progress-percent">33%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                        <div id="global-progress" class="bg-gradient-to-r from-blue-600 to-indigo-600 h-2 rounded-full transition-all duration-500" style="width: 33%"></div>
                    </div>
                </div>
            </div>

            <!-- Form Container -->
            <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/50 p-8 sm:p-12">

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mb-8 bg-red-50 border border-red-200 rounded-2xl p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-bold text-red-800 mb-2">
                                    Por favor corrige los siguientes errores:
                                </h3>
                                <ul class="list-disc pl-5 space-y-1 text-sm text-red-700">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form id="registration-form" method="POST" action="{{ route('register.store') }}">
                    @csrf

                    <!-- Step 1: Company Information -->
                    <div id="step-1" class="step fade-in">
                        <div class="mb-10">
                            <h2 class="text-3xl font-bold text-slate-900 mb-2">Información de tu Empresa</h2>
                            <p class="text-slate-600">Cuéntanos sobre tu negocio</p>
                        </div>

                        <div class="space-y-6">
                            <!-- Company Name -->
                            <div class="input-group">
                                <label for="company_name" class="block text-sm font-bold text-slate-700 mb-2">
                                    Nombre de la Empresa <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                                           class="input-modern block w-full pl-12 pr-4 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base"
                                           required
                                           data-validation="required|min:2|max:255"
                                           placeholder="Ej: Litografía Moderna S.A.S.">
                                </div>
                                <div class="field-error text-red-600 text-sm mt-2 font-medium hidden"></div>
                            </div>

                            <!-- Email & Phone -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="input-group">
                                    <label for="company_email" class="block text-sm font-bold text-slate-700 mb-2">
                                        Email Corporativo <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <input type="email" name="company_email" id="company_email" value="{{ old('company_email') }}"
                                               class="input-modern block w-full pl-12 pr-4 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base"
                                               required
                                               data-validation="required|email"
                                               placeholder="contacto@tuempresa.com">
                                    </div>
                                    <div class="field-error text-red-600 text-sm mt-2 font-medium hidden"></div>
                                </div>

                                <div class="input-group">
                                    <label for="company_phone" class="block text-sm font-bold text-slate-700 mb-2">
                                        Teléfono <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        <input type="tel" name="company_phone" id="company_phone" value="{{ old('company_phone') }}"
                                               class="input-modern block w-full pl-12 pr-4 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base"
                                               required
                                               data-validation="required|phone"
                                               placeholder="+57 300 123 4567">
                                    </div>
                                    <div class="field-error text-red-600 text-sm mt-2 font-medium hidden"></div>
                                </div>
                            </div>

                            <!-- NIT & Type -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="input-group">
                                    <label for="tax_id" class="block text-sm font-bold text-slate-700 mb-2">
                                        NIT / RUT <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id') }}"
                                               class="input-modern block w-full pl-12 pr-4 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base"
                                               required
                                               placeholder="000000000-0">
                                    </div>
                                </div>

                                <div class="input-group">
                                    <label for="company_type" class="block text-sm font-bold text-slate-700 mb-2">
                                        Tipo de Empresa <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        </div>
                                        <select name="company_type" id="company_type" required
                                                class="input-modern block w-full pl-12 pr-10 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base bg-white appearance-none">
                                            <option value="litografia" {{ old('company_type', 'litografia') === 'litografia' ? 'selected' : '' }}>
                                                Litografía
                                            </option>
                                            <option value="papeleria" {{ old('company_type') === 'papeleria' ? 'selected' : '' }}>
                                                Papelería
                                            </option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="input-group">
                                <label for="company_address" class="block text-sm font-bold text-slate-700 mb-2">
                                    Dirección Completa <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute top-4 left-0 pl-4 pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <textarea name="company_address" id="company_address" rows="3"
                                              class="input-modern block w-full pl-12 pr-4 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base resize-none"
                                              required
                                              placeholder="Calle 123 #45-67, Edificio XYZ, Oficina 101">{{ old('company_address') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-10 flex justify-end">
                            <button type="button" onclick="nextStep()"
                                    class="group bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-10 py-4 rounded-xl font-bold text-base shadow-lg hover:shadow-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 flex items-center gap-2">
                                Siguiente
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Admin User -->
                    <div id="step-2" class="step hidden">
                        <div class="mb-10">
                            <h2 class="text-3xl font-bold text-slate-900 mb-2">Usuario Administrador</h2>
                            <p class="text-slate-600">Crea tu cuenta con acceso total</p>
                        </div>

                        <div class="space-y-6">
                            <!-- Full Name -->
                            <div class="input-group">
                                <label for="name" class="block text-sm font-bold text-slate-700 mb-2">
                                    Nombre Completo <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                                           class="input-modern block w-full pl-12 pr-4 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base"
                                           required
                                           placeholder="Juan Pérez García">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="input-group">
                                <label for="email" class="block text-sm font-bold text-slate-700 mb-2">
                                    Email Personal <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                        </svg>
                                    </div>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                                           class="input-modern block w-full pl-12 pr-4 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base"
                                           required
                                           placeholder="tu-email@ejemplo.com">
                                </div>
                                <p class="mt-2 text-sm text-slate-500">Este será tu email de acceso al sistema</p>
                            </div>

                            <!-- Password & Confirmation -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="input-group">
                                    <label for="password" class="block text-sm font-bold text-slate-700 mb-2">
                                        Contraseña <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </div>
                                        <input type="password" name="password" id="password"
                                               class="input-modern block w-full pl-12 pr-12 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base"
                                               required
                                               data-validation="required|min:8"
                                               placeholder="••••••••">
                                        <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center z-10" onclick="togglePassword('password')">
                                            <svg id="eye-open-password" class="w-5 h-5 text-slate-400 hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg id="eye-closed-password" class="w-5 h-5 text-slate-400 hover:text-slate-600 transition-colors hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m-6.878-6.878l15 15"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="strength-bar">
                                        <div id="password-strength" class="strength-fill bg-slate-300" style="width: 0%"></div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span id="password-strength-text" class="text-xs font-semibold text-slate-500">Introduce una contraseña</span>
                                    </div>
                                    <div class="field-error text-red-600 text-sm mt-2 font-medium hidden"></div>
                                </div>

                                <div class="input-group">
                                    <label for="password_confirmation" class="block text-sm font-bold text-slate-700 mb-2">
                                        Confirmar Contraseña <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </div>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                               class="input-modern block w-full pl-12 pr-12 py-4 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-base"
                                               required
                                               data-validation="required|match:password"
                                               placeholder="••••••••">
                                        <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center z-10" onclick="togglePassword('password_confirmation')">
                                            <svg id="eye-open-password_confirmation" class="w-5 h-5 text-slate-400 hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg id="eye-closed-password_confirmation" class="w-5 h-5 text-slate-400 hover:text-slate-600 transition-colors hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m-6.878-6.878l15 15"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="field-error text-red-600 text-sm mt-2 font-medium hidden"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-10 flex justify-between items-center">
                            <button type="button" onclick="prevStep()"
                                    class="group border-2 border-slate-300 text-slate-700 px-8 py-4 rounded-xl font-bold text-base hover:bg-slate-50 transition-all duration-300 flex items-center gap-2">
                                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                                </svg>
                                Anterior
                            </button>
                            <button type="button" onclick="nextStep()"
                                    class="group bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-10 py-4 rounded-xl font-bold text-base shadow-lg hover:shadow-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 flex items-center gap-2">
                                Siguiente
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Plan Selection -->
                    <div id="step-3" class="step hidden">
                        <div class="mb-10 text-center">
                            <h2 class="text-3xl font-bold text-slate-900 mb-2">Elige tu Plan</h2>
                            <p class="text-slate-600">Selecciona el que mejor se adapte a tu negocio</p>
                        </div>

                        <!-- Plans Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                            @foreach($plans as $plan)
                                <div class="plan-card plan-option rounded-2xl border-2 {{ $plan->price == 0 ? 'border-blue-500 selected' : 'border-slate-200' }} bg-white p-8 cursor-pointer {{ $plan->price == 0 ? 'shadow-xl' : 'shadow-lg' }}"
                                     onclick="selectPlan({{ $plan->id }})">
                                    <input type="radio" name="plan_id" id="plan_{{ $plan->id }}" value="{{ $plan->id }}"
                                           class="sr-only" {{ $plan->price == 0 || old('plan_id') == $plan->id ? 'checked' : '' }}>

                                    @if($plan->price == 0)
                                        <div class="flex justify-center mb-4">
                                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-md">
                                                ✨ Recomendado
                                            </span>
                                        </div>
                                    @endif

                                    <div class="text-center mb-6">
                                        <h3 class="text-2xl font-black text-slate-900 mb-4">{{ $plan->name }}</h3>
                                        <div class="mb-4">
                                            <span class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">
                                                ${{ number_format($plan->price, 0, ',', '.') }}
                                            </span>
                                            <span class="text-slate-500 font-semibold">
                                                / {{ $plan->interval === 'month' ? 'mes' : 'año' }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-slate-600 leading-relaxed">{{ $plan->description }}</p>
                                    </div>

                                    @if($plan->features)
                                        <ul class="space-y-3 mb-6">
                                            @foreach(array_slice($plan->features, 0, 5) as $feature)
                                                <li class="flex items-start text-sm text-slate-700">
                                                    <svg class="h-5 w-5 text-emerald-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="font-medium">{{ is_array($feature) ? $feature['name'] : $feature }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <div class="text-center pt-4 border-t border-slate-100">
                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full border-2 {{ $plan->price == 0 ? 'border-blue-500 bg-blue-500' : 'border-slate-300' }} transition-all">
                                            @if($plan->price == 0)
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 mb-8">
                            <div class="flex items-start gap-4">
                                <input id="terms" name="terms" type="checkbox"
                                       class="mt-1 w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-2 focus:ring-blue-500 cursor-pointer"
                                       required>
                                <div>
                                    <label for="terms" class="font-bold text-slate-900 cursor-pointer">
                                        Acepto los términos y condiciones <span class="text-red-500">*</span> 
                                    </label>
                                    <p class="text-sm text-slate-600 mt-1">
                                        Al registrarme, acepto los
                                        <a href="#" class="font-semibold text-blue-600 hover:text-blue-700 underline">términos de servicio</a> y la
                                        <a href="#" class="font-semibold text-blue-600 hover:text-blue-700 underline">política de privacidad</a>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col-reverse md:flex-row justify-between items-stretch md:items-center gap-4">
                            <button type="button" onclick="prevStep()"
                                    class="group border-2 border-slate-300 text-slate-700 px-8 py-4 rounded-xl font-bold text-base hover:bg-slate-50 transition-all duration-300 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                                </svg>
                                Anterior
                            </button>
                            <button type="submit" id="submit-btn"
                                    class="group bg-gradient-to-r from-emerald-600 to-teal-600 text-white px-12 py-5 rounded-xl font-black text-lg shadow-2xl hover:shadow-emerald-500/50 hover:from-emerald-700 hover:to-teal-700 transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center gap-3">
                                <svg id="submit-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <svg id="submit-loading" class="w-6 h-6 loading-spinner hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span id="submit-text">Crear Mi Cuenta</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Already have account -->
            <div class="mt-8 text-center fade-in">
                <p class="text-slate-600">
                    ¿Ya tienes una cuenta?
                    <a href="/admin/login" class="font-bold text-blue-600 hover:text-blue-700 underline decoration-2 underline-offset-4 transition-colors ml-1">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;
        const formData = {};

        // Validation rules
        const validationRules = {
            required: (value) => value && value.trim().length > 0,
            email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            phone: (value) => /^[\+]?[1-9][\d]{8,14}$/.test(value.replace(/\s/g, '')),
            min: (value, min) => value && value.length >= parseInt(min),
            max: (value, max) => value && value.length <= parseInt(max),
            match: (value, fieldName) => value === document.getElementById(fieldName).value
        };

        // Initialization
        document.addEventListener('DOMContentLoaded', function() {
            setupValidation();
            loadSavedProgress();
            setupPasswordStrength();
        });

        // Navigation
        function nextStep() {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                currentStep++;
                showStep(currentStep);
                updateProgressIndicators();
            }
        }

        function prevStep() {
            currentStep--;
            showStep(currentStep);
            updateProgressIndicators();
        }

        function showStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.add('hidden'));
            const currentStepEl = document.querySelector(`#step-${step}`);
            currentStepEl.classList.remove('hidden');

            // Trigger fade-in animation
            setTimeout(() => {
                currentStepEl.style.opacity = '1';
            }, 50);
        }

        function updateProgressIndicators() {
            const progress = (currentStep / totalSteps) * 100;

            // Update global progress bar
            document.getElementById('global-progress').style.width = `${progress}%`;
            document.getElementById('progress-text').textContent = `Paso ${currentStep} de ${totalSteps}`;
            document.getElementById('progress-percent').textContent = `${Math.round(progress)}%`;

            // Update dots and labels
            for (let i = 1; i <= totalSteps; i++) {
                const dot = document.getElementById(`progress-dot-${i}`);
                const label = document.getElementById(`progress-label-${i}`);
                const line = document.getElementById(`progress-line-${i}`);

                if (i < currentStep) {
                    // Completed
                    dot.className = 'progress-dot w-3 h-3 rounded-full bg-emerald-500';
                    label.className = 'ml-3 text-sm font-semibold text-emerald-600';
                    if (line) line.style.width = '100%';
                } else if (i === currentStep) {
                    // Active
                    dot.className = 'progress-dot active w-3 h-3 rounded-full bg-blue-600';
                    label.className = 'ml-3 text-sm font-semibold text-blue-600';
                } else {
                    // Future
                    dot.className = 'progress-dot w-3 h-3 rounded-full bg-slate-300';
                    label.className = 'ml-3 text-sm font-medium text-slate-400';
                    if (line) line.style.width = '0%';
                }
            }
        }

        // Validation
        function setupValidation() {
            document.querySelectorAll('input[data-validation]').forEach(input => {
                input.addEventListener('blur', () => validateField(input));
                input.addEventListener('input', () => {
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
            const fields = currentStepDiv.querySelectorAll('input[data-validation], select[required], textarea[required]');
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

            const errorDiv = field.closest('.input-group')?.querySelector('.field-error');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
            }
        }

        function showFieldSuccess(field) {
            field.classList.add('input-success');
            field.classList.remove('input-error');

            const errorDiv = field.closest('.input-group')?.querySelector('.field-error');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
        }

        function clearFieldError(field) {
            field.classList.remove('input-error', 'input-success');

            const errorDiv = field.closest('.input-group')?.querySelector('.field-error');
            if (errorDiv) {
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
                strengthBar.className = 'strength-fill bg-slate-300';
                strengthText.textContent = 'Introduce una contraseña';
                return;
            }

            let score = 0;

            if (password.length >= 8) score += 1;
            if (password.length >= 12) score += 1;
            if (/[a-z]/.test(password)) score += 1;
            if (/[A-Z]/.test(password)) score += 1;
            if (/[0-9]/.test(password)) score += 1;
            if (/[^A-Za-z0-9]/.test(password)) score += 1;

            const strength = ['Muy débil', 'Débil', 'Regular', 'Buena', 'Fuerte', 'Muy fuerte'];
            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-emerald-500', 'bg-emerald-600'];
            const widths = [16, 33, 50, 66, 83, 100];

            const level = Math.min(score, 5);
            strengthBar.style.width = `${widths[level]}%`;
            strengthBar.className = `strength-fill ${colors[level]}`;
            strengthText.textContent = strength[level];
        }

        // Toggle password visibility
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

        // Save progress
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
                option.classList.remove('border-blue-500', 'selected', 'shadow-xl');
                option.classList.add('border-slate-200', 'shadow-lg');

                const checkmark = option.querySelector('.inline-flex.items-center.justify-center');
                if (checkmark) {
                    checkmark.classList.remove('border-blue-500', 'bg-blue-500');
                    checkmark.classList.add('border-slate-300');
                    const svg = checkmark.querySelector('svg');
                    if (svg) svg.remove();
                }
            });

            const selectedOption = document.querySelector(`input[value="${planId}"]`).closest('.plan-option');
            selectedOption.classList.remove('border-slate-200', 'shadow-lg');
            selectedOption.classList.add('border-blue-500', 'selected', 'shadow-xl');

            const checkmark = selectedOption.querySelector('.inline-flex.items-center.justify-center');
            if (checkmark) {
                checkmark.classList.remove('border-slate-300');
                checkmark.classList.add('border-blue-500', 'bg-blue-500');

                if (!checkmark.querySelector('svg')) {
                    checkmark.innerHTML = '<svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
                }
            }

            document.querySelector(`#plan_${planId}`).checked = true;
        }

        // Form submission
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submit-btn');
            const submitIcon = document.getElementById('submit-icon');
            const submitLoading = document.getElementById('submit-loading');
            const submitText = document.getElementById('submit-text');

            submitBtn.disabled = true;
            submitIcon.classList.add('hidden');
            submitLoading.classList.remove('hidden');
            submitText.textContent = 'Creando tu cuenta...';

            localStorage.removeItem('registrationProgress');
        });

        // Auto-save every 30 seconds
        setInterval(() => {
            if (currentStep < totalSteps) {
                saveCurrentStepData();
            }
        }, 30000);
    </script>
</body>
</html>
