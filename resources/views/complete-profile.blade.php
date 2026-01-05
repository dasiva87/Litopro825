<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Completar Perfil - GrafiRed</title>
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
        .progress-step {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .step-completed {
            background-color: #10b981;
            color: white;
        }
        .step-current {
            background-color: #3b82f6;
            color: white;
        }
        .step-pending {
            background-color: #e5e7eb;
            color: #6b7280;
        }
        .step-line {
            height: 2px;
            background-color: #e5e7eb;
        }
        .step-line.completed {
            background-color: #10b981;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center space-x-4">
                    <div class="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Completar Perfil</h1>
                        <p class="text-sm text-gray-600">{{ $company->name }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('complete-profile.skip') }}" class="text-sm text-gray-500 hover:text-gray-700">
                        Completar más tarde
                    </a>
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800">
                        Ir al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Progress Indicator -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <div class="progress-step step-completed">1</div>
                    <div class="step-line completed w-16"></div>
                    <div class="progress-step step-completed">2</div>
                    <div class="step-line completed w-16"></div>
                    <div class="progress-step step-current">3</div>
                </div>
                <div class="text-sm text-gray-600">Paso 3 de 3</div>
            </div>
            <div class="text-sm text-gray-600">
                <span class="font-medium">Completar información de la empresa</span> - Solo faltan unos datos más
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">
                        ¡Bienvenido a GrafiRed, {{ auth()->user()->name }}!
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Tu cuenta ha sido creada exitosamente. Solo necesitamos algunos datos adicionales de tu empresa para personalizar mejor tu experiencia.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Información de la Empresa</h2>
                <p class="mt-1 text-sm text-gray-600">Completa los datos faltantes de tu empresa</p>
            </div>

            <form id="completeProfileForm" method="POST" action="{{ route('complete-profile.update') }}" class="p-6">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
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

                <!-- Company Info Summary -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="font-medium text-gray-900 mb-2">Información ya registrada:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Nombre:</span>
                            <span class="font-medium text-gray-900 ml-2">{{ $company->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">NIT:</span>
                            <span class="font-medium text-gray-900 ml-2">{{ $company->tax_id }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Contact Information -->
                    <div class="space-y-4">
                        <h4 class="font-medium text-gray-900">Información de Contacto</h4>

                        <div class="field-group">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Correo Electrónico de la Empresa *
                            </label>
                            <input type="email" id="email" name="email"
                                   value="{{ old('email', $company->email) }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="contacto@empresa.com">
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="email_error"></div>
                        </div>

                        <div class="field-group">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Teléfono *
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   value="{{ old('phone', $company->phone) }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: +57 300 123 4567">
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="phone_error"></div>
                        </div>
                    </div>

                    <!-- Location Information -->
                    <div class="space-y-4">
                        <h4 class="font-medium text-gray-900">Ubicación</h4>

                        <div class="field-group">
                            <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">
                                País *
                            </label>
                            <select id="country_id" name="country_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Seleccionar país</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ old('country_id', $company->country_id) == $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="country_id_error"></div>
                        </div>

                        <div class="field-group">
                            <label for="state_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Departamento/Estado *
                            </label>
                            <select id="state_id" name="state_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Seleccionar departamento</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ old('state_id', $company->state_id) == $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="state_id_error"></div>
                        </div>

                        <div class="field-group">
                            <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Ciudad *
                            </label>
                            <select id="city_id" name="city_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Seleccionar ciudad</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ old('city_id', $company->city_id) == $city->id ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="field-feedback"></div>
                            <div class="mt-1 text-sm text-red-600" id="city_id_error"></div>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="mt-6">
                    <div class="field-group">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            Dirección Completa *
                        </label>
                        <textarea id="address" name="address" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Calle, número, barrio, información adicional...">{{ old('address', $company->address) }}</textarea>
                        <div class="field-feedback"></div>
                        <div class="mt-1 text-sm text-red-600" id="address_error"></div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200 mt-8">
                    <a href="{{ route('complete-profile.skip') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Completar más tarde
                    </a>

                    <button type="submit" id="submitBtn"
                            class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="submitText">Completar Perfil</span>
                        <svg id="loadingIcon" class="hidden loading-spinner ml-2 h-4 w-4 text-white inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-opacity="0.25"></circle>
                            <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" stroke-opacity="0.75"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Help Section -->
        <div class="mt-8 bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">¿Necesitas ayuda?</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">¿Por qué necesitamos esta información?</h4>
                    <p class="text-sm text-gray-600">
                        Esta información nos permite personalizar mejor tu experiencia, generar documentos correctos
                        y proporcionarte funcionalidades específicas para tu ubicación.
                    </p>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">¿Es segura mi información?</h4>
                    <p class="text-sm text-gray-600">
                        Sí, toda tu información está protegida y solo se usa para mejorar tu experiencia en GrafiRed.
                        Nunca compartimos tus datos con terceros.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('completeProfileForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const loadingIcon = document.getElementById('loadingIcon');

            // Validation functions
            const validationRules = {
                required: (value) => value && value.trim().length > 0,
                email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                phone: (value) => /^[\+]?[1-9][\d\s\-\(\)]{8,20}$/.test(value.replace(/\s/g, ''))
            };

            const validateField = (field, rules) => {
                const value = field.value;
                const errorDiv = document.getElementById(field.id + '_error');
                const feedback = field.parentElement.querySelector('.field-feedback');

                for (const rule of rules) {
                    if (!validationRules[rule](value)) {
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

            // Field validations
            const fieldValidations = {
                'email': ['required', 'email'],
                'phone': ['required', 'phone'],
                'address': ['required'],
                'country_id': ['required'],
                'state_id': ['required'],
                'city_id': ['required']
            };

            // Apply validations
            Object.keys(fieldValidations).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('blur', () => {
                        validateField(field, fieldValidations[fieldId]);
                    });
                    field.addEventListener('change', () => {
                        validateField(field, fieldValidations[fieldId]);
                    });
                }
            });

            // Location cascading dropdowns
            const countrySelect = document.getElementById('country_id');
            const stateSelect = document.getElementById('state_id');
            const citySelect = document.getElementById('city_id');

            countrySelect.addEventListener('change', async function() {
                const countryId = this.value;
                stateSelect.innerHTML = '<option value="">Seleccionar departamento</option>';
                citySelect.innerHTML = '<option value="">Seleccionar ciudad</option>';

                if (countryId) {
                    try {
                        const response = await fetch('{{ route("complete-profile.states") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ country_id: countryId })
                        });

                        const states = await response.json();
                        states.forEach(state => {
                            const option = document.createElement('option');
                            option.value = state.id;
                            option.textContent = state.name;
                            stateSelect.appendChild(option);
                        });
                    } catch (error) {
                        console.error('Error loading states:', error);
                    }
                }
            });

            stateSelect.addEventListener('change', async function() {
                const stateId = this.value;
                citySelect.innerHTML = '<option value="">Seleccionar ciudad</option>';

                if (stateId) {
                    try {
                        const response = await fetch('{{ route("complete-profile.cities") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ state_id: stateId })
                        });

                        const cities = await response.json();
                        cities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.name;
                            citySelect.appendChild(option);
                        });
                    } catch (error) {
                        console.error('Error loading cities:', error);
                    }
                }
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate all fields
                let isValid = true;
                Object.keys(fieldValidations).forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field && !validateField(field, fieldValidations[fieldId])) {
                        isValid = false;
                    }
                });

                if (isValid) {
                    // Show loading
                    submitBtn.disabled = true;
                    submitText.textContent = 'Completando...';
                    loadingIcon.classList.remove('hidden');

                    // Submit form
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>