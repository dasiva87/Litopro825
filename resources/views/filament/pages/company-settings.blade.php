<x-filament-panels::page>
    <!-- Custom Header Styling -->
    <style>
        /* Header con gradiente */
        .fi-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border-radius: 12px !important;
            padding: 2rem !important;
            margin-bottom: 2rem !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            position: relative !important;
            overflow: hidden !important;
        }

        /* Patrón de fondo sutil */
        .fi-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Título del header */
        .fi-header-heading {
            color: white !important;
            font-size: 1.875rem !important;
            font-weight: 700 !important;
            margin: 0 0 0.5rem 0 !important;
            position: relative !important;
            z-index: 1 !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }

        /* Icono del header */
        .fi-header-heading svg {
            width: 2rem !important;
            height: 2rem !important;
            color: white !important;
        }

        /* Subtítulo del header */
        .fi-header-subheading {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1rem !important;
            font-weight: 400 !important;
            margin: 0 !important;
            position: relative !important;
            z-index: 1 !important;
        }

        /* Responsive - header más compacto en mobile */
        @media (max-width: 768px) {
            .fi-header {
                padding: 1.5rem !important;
            }

            .fi-header-heading {
                font-size: 1.5rem !important;
            }

            .fi-header-heading svg {
                width: 1.5rem !important;
                height: 1.5rem !important;
            }

            .fi-header-subheading {
                font-size: 0.875rem !important;
            }
        }
    </style>

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem; align-items: start;">
        <!-- Formulario (columna izquierda) -->
        <div>
            <form wire:submit="save">
                {{ $this->form }}

                <!-- Sticky Save Button Container -->
                <div class="sticky-save-container" style="position: sticky; bottom: 0; left: 0; right: 0; z-index: 40; padding: 1rem 0; background: linear-gradient(to top, rgba(255,255,255,0.98) 70%, rgba(255,255,255,0) 100%); backdrop-filter: blur(8px); margin-top: 2rem; border-top: 1px solid rgba(0,0,0,0.05);">
                    <div class="fi-ac fi-ac-gap-3 fi-ac-wrap" style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; color: #6b7280; font-size: 0.875rem;">
                            <svg style="width: 1.25rem; height: 1.25rem; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Los cambios se guardarán en tu perfil de empresa</span>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            @foreach($this->getFormActions() as $action)
                                {{ $action }}
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Vista Previa en Vivo (columna derecha) -->
        <div style="position: sticky; top: 1rem;">
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb;">
                <!-- Header con icono -->
                <div style="padding: 1rem 1.5rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 0.75rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <h3 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: white;">Vista Previa del Perfil</h3>
                </div>

                <!-- Preview Content -->
                <div x-data="{
                    name: @entangle('data.name'),
                    bio: @entangle('data.bio'),
                    phone: @entangle('data.phone'),
                    email: @entangle('data.email'),
                    website: @entangle('data.website'),
                    address: @entangle('data.address'),
                    getInitials() {
                        if (!this.name) return 'LC';
                        return this.name.substring(0, 2).toUpperCase();
                    }
                }">
                    <!-- Banner -->
                    @if($company->banner && Storage::disk('public')->exists($company->banner))
                        <div style="position: relative; width: 100%; height: 120px; background-image: url('{{ Storage::disk('public')->url($company->banner) }}'); background-size: cover; background-position: center;">
                        </div>
                    @else
                        <div style="position: relative; width: 100%; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        </div>
                    @endif

                    <!-- Avatar -->
                    <div style="padding: 0 1.5rem; margin-top: -40px; position: relative;">
                        @if($company->avatar && Storage::disk('public')->exists($company->avatar))
                            <img src="{{ Storage::disk('public')->url($company->avatar) }}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        @else
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); display: flex; align-items: center; justify-content: center; border: 4px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                                <span style="color: white; font-size: 1.5rem; font-weight: 700;" x-text="getInitials()"></span>
                            </div>
                        @endif
                    </div>

                    <!-- Company Info -->
                    <div style="padding: 1rem 1.5rem 1.5rem;">
                        <h2 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 700; color: #111827;" x-text="name || 'Nombre de la Empresa'"></h2>

                        <p style="margin: 0 0 1rem 0; font-size: 0.875rem; color: #6b7280; line-height: 1.5;" x-text="bio || 'Descripción de la empresa...'"></p>

                        <!-- Contact Info -->
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <!-- Email -->
                            <div x-show="email" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                <svg style="width: 1rem; height: 1rem; color: #3b82f6; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="email"></span>
                            </div>

                            <!-- Phone -->
                            <div x-show="phone" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                <svg style="width: 1rem; height: 1rem; color: #3b82f6; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span x-text="phone"></span>
                            </div>

                            <!-- Website -->
                            <div x-show="website" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                <svg style="width: 1rem; height: 1rem; color: #3b82f6; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                                <span x-text="website"></span>
                            </div>

                            <!-- Address -->
                            <div x-show="address" style="display: flex; align-items: start; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                <svg style="width: 1rem; height: 1rem; margin-top: 0.125rem; color: #3b82f6; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span x-text="address" style="flex: 1;"></span>
                            </div>
                        </div>

                        <!-- Live Update Indicator -->
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #10b981;">
                            <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></div>
                            <span>Vista previa en vivo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dark mode styles -->
    <style>
        .dark .sticky-save-container {
            background: linear-gradient(to top, rgba(17,24,39,0.98) 70%, rgba(17,24,39,0) 100%) !important;
            border-top-color: rgba(255,255,255,0.1) !important;
        }

        /* Add smooth transition */
        .sticky-save-container {
            transition: box-shadow 0.2s ease-in-out;
        }

        /* Add shadow when scrolling */
        @supports (backdrop-filter: blur(8px)) {
            .sticky-save-container {
                box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.05), 0 -2px 4px -1px rgba(0, 0, 0, 0.03);
            }
        }

        /* Pulse animation for live indicator */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        /* Responsive: hide preview on mobile */
        @media (max-width: 1280px) {
            div[style*="grid-template-columns: 1fr 400px"] {
                grid-template-columns: 1fr !important;
            }

            div[style*="grid-template-columns: 1fr 400px"] > div:last-child {
                display: none;
            }
        }
    </style>

    <x-filament-actions::modals />
</x-filament-panels::page>