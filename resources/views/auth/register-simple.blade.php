<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear Cuenta - LitoPro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
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
        .password-strength {
            margin-top: 8px;
        }
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background-color: #e5e7eb;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .strength-weak { background-color: #ef4444; }
        .strength-medium { background-color: #f59e0b; }
        .strength-strong { background-color: #10b981; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Crear tu cuenta LitoPro</h2>
            <p class="mt-2 text-sm text-gray-600">
                Comienza gratis en menos de 2 minutos
            </p>
        </div>

        <!-- Formulario -->
        <form id="registerForm" method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-6">
            @csrf

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Hay algunos errores en el formulario</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="space-y-6">
                <!-- Información de la Empresa -->
                <div class="bg-white p-6 rounded-lg shadow-sm border">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información de tu Empresa</h3>

                    <div class="space-y-4">
                        <div class="field-group">
                            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre de la Empresa *
                            </label>
                            <input type="text" id="company_name" name="company_name"
                                   value="{{ old('company_name') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: Litografía ABC">
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="company_name_error"></div>
                        </div>

                        <div class="field-group">
                            <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-2">
                                NIT / ID Fiscal *
                            </label>
                            <input type="text" id="tax_id" name="tax_id"
                                   value="{{ old('tax_id') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: 123456789-1">
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="tax_id_error"></div>
                        </div>

                        <div class="field-group">
                            <label for="company_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Empresa *
                            </label>
                            <select id="company_type" name="company_type" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="litografia" {{ old('company_type', 'litografia') === 'litografia' ? 'selected' : '' }}>
                                    Litografía
                                </option>
                                <option value="papeleria" {{ old('company_type') === 'papeleria' ? 'selected' : '' }}>
                                    Papelería
                                </option>
                            </select>
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="company_type_error"></div>
                            <p class="mt-1 text-xs text-gray-500">
                                Las litografías pueden recibir órdenes de compra de papelerías. Las papelerías pueden enviar órdenes a litografías.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Información del Usuario Administrador -->
                <div class="bg-white p-6 rounded-lg shadow-sm border">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tu Información Personal</h3>

                    <div class="space-y-4">
                        <div class="field-group">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre Completo *
                            </label>
                            <input type="text" id="name" name="name"
                                   value="{{ old('name') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: Juan Carlos Pérez">
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="name_error"></div>
                        </div>

                        <div class="field-group">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Correo Electrónico *
                            </label>
                            <input type="email" id="email" name="email"
                                   value="{{ old('email') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="tu-email@empresa.com">
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="email_error"></div>
                        </div>

                        <div class="field-group">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Contraseña *
                            </label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required
                                       class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Mínimo 8 caracteres">
                                <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg id="eyeIcon" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div id="strengthFill" class="strength-fill" style="width: 0%"></div>
                                </div>
                                <div id="strengthText" class="text-xs text-gray-500 mt-1"></div>
                            </div>
                            <div class="mt-1 text-sm text-red-600" id="password_error"></div>
                        </div>

                        <div class="field-group">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirmar Contraseña *
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Confirma tu contraseña">
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="password_confirmation_error"></div>
                        </div>
                    </div>
                </div>

                <!-- Términos y Condiciones -->
                <div class="bg-white p-6 rounded-lg shadow-sm border">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" required
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="text-gray-700">
                                Acepto los <a href="#" class="text-blue-600 hover:text-blue-500">Términos y Condiciones</a> y la <a href="#" class="text-blue-600 hover:text-blue-500">Política de Privacidad</a> *
                            </label>
                        </div>
                    </div>
                    <div class="mt-1 text-sm text-red-600" id="terms_error"></div>
                </div>
            </div>

            <!-- Botón de Envío -->
            <div>
                <button type="submit" id="submitBtn"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="submitText">Crear mi cuenta gratis</span>
                    <svg id="loadingIcon" class="hidden loading-spinner ml-2 h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-opacity="0.25"></circle>
                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" stroke-opacity="0.75"></path>
                    </svg>
                </button>
            </div>

            <!-- Login Link -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    ¿Ya tienes una cuenta?
                    <a href="{{ route('filament.admin.auth.login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const loadingIcon = document.getElementById('loadingIcon');

            // Validación en tiempo real
            const validationRules = {
                required: (value) => value && value.trim().length > 0,
                email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                min: (value, min) => value && value.length >= parseInt(min),
                match: (value, fieldName) => value === document.getElementById(fieldName).value
            };

            const validateField = (field, rules) => {
                const value = field.value;
                const errorDiv = document.getElementById(field.id + '_error');
                const feedback = field.parentElement.querySelector('.field-feedback');

                for (const rule of rules) {
                    const [ruleName, ...params] = rule.split(':');
                    if (!validationRules[ruleName](value, ...params)) {
                        field.classList.add('input-error');
                        field.classList.remove('input-success');
                        if (feedback) feedback.innerHTML = '❌';
                        return false;
                    }
                }

                field.classList.remove('input-error');
                field.classList.add('input-success');
                if (feedback) feedback.innerHTML = '✅';
                if (errorDiv) errorDiv.textContent = '';
                return true;
            };

            // Configurar validaciones
            const fieldValidations = {
                'company_name': ['required', 'min:2'],
                'tax_id': ['required', 'min:5'],
                'company_type': ['required'],
                'name': ['required', 'min:2'],
                'email': ['required', 'email'],
                'password': ['required', 'min:8'],
                'password_confirmation': ['required', 'match:password']
            };

            // Aplicar validaciones
            Object.keys(fieldValidations).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('blur', () => {
                        validateField(field, fieldValidations[fieldId]);
                    });
                    field.addEventListener('input', () => {
                        if (field.classList.contains('input-error')) {
                            validateField(field, fieldValidations[fieldId]);
                        }
                    });
                }
            });

            // Password strength meter
            const passwordField = document.getElementById('password');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');

            passwordField.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let feedback = [];

                if (password.length >= 8) strength += 20;
                else feedback.push('Mínimo 8 caracteres');

                if (/[a-z]/.test(password)) strength += 20;
                else feedback.push('Una minúscula');

                if (/[A-Z]/.test(password)) strength += 20;
                else feedback.push('Una mayúscula');

                if (/\d/.test(password)) strength += 20;
                else feedback.push('Un número');

                if (/[^a-zA-Z\d]/.test(password)) strength += 20;
                else feedback.push('Un símbolo');

                strengthFill.style.width = strength + '%';

                if (strength < 40) {
                    strengthFill.className = 'strength-fill strength-weak';
                    strengthText.textContent = 'Débil - Faltan: ' + feedback.join(', ');
                } else if (strength < 80) {
                    strengthFill.className = 'strength-fill strength-medium';
                    strengthText.textContent = 'Media - Faltan: ' + feedback.join(', ');
                } else {
                    strengthFill.className = 'strength-fill strength-strong';
                    strengthText.textContent = 'Fuerte ✓';
                }
            });

            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const eyeIcon = document.getElementById('eyeIcon');

            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);

                if (type === 'text') {
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>';
                } else {
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
                }
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validar todos los campos
                let isValid = true;
                Object.keys(fieldValidations).forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field && !validateField(field, fieldValidations[fieldId])) {
                        isValid = false;
                    }
                });

                // Validar términos
                const termsCheckbox = document.getElementById('terms');
                if (!termsCheckbox.checked) {
                    const errorDiv = document.getElementById('terms_error');
                    errorDiv.textContent = 'Debes aceptar los términos y condiciones';
                    isValid = false;
                }

                if (isValid) {
                    // Mostrar loading
                    submitBtn.disabled = true;
                    submitText.textContent = 'Creando cuenta...';
                    loadingIcon.classList.remove('hidden');

                    // Enviar formulario
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>