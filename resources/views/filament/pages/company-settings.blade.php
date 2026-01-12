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
                    facebook: @entangle('data.facebook'),
                    instagram: @entangle('data.instagram'),
                    twitter: @entangle('data.twitter'),
                    linkedin: @entangle('data.linkedin'),
                    getInitials() {
                        if (!this.name) return 'LC';
                        return this.name.substring(0, 2).toUpperCase();
                    }
                }">
                    <!-- Banner -->
                    @if($company->banner && Storage::disk('r2')->exists($company->banner))
                        <div style="position: relative; width: 100%; height: 120px; background-image: url('{{ Storage::disk('r2')->url($company->banner) }}'); background-size: cover; background-position: center;">
                        </div>
                    @else
                        <div style="position: relative; width: 100%; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        </div>
                    @endif

                    <!-- Avatar -->
                    <div style="padding: 0 1.5rem; margin-top: -40px; position: relative;">
                        @if($company->avatar && Storage::disk('r2')->exists($company->avatar))
                            <img src="{{ Storage::disk('r2')->url($company->avatar) }}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
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

                        <!-- Social Networks -->
                        <div x-show="facebook || instagram || twitter || linkedin" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                <!-- Facebook -->
                                <a x-show="facebook" :href="'https://facebook.com/' + facebook" target="_blank"
                                   style="width: 36px; height: 36px; border-radius: 50%; background: #1877f2; display: flex; align-items: center; justify-content: center; transition: transform 0.2s, opacity 0.2s;"
                                   onmouseover="this.style.transform='scale(1.1)'; this.style.opacity='0.9'"
                                   onmouseout="this.style.transform='scale(1)'; this.style.opacity='1'">
                                    <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>

                                <!-- Instagram -->
                                <a x-show="instagram" :href="'https://instagram.com/' + instagram" target="_blank"
                                   style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); display: flex; align-items: center; justify-content: center; transition: transform 0.2s, opacity 0.2s;"
                                   onmouseover="this.style.transform='scale(1.1)'; this.style.opacity='0.9'"
                                   onmouseout="this.style.transform='scale(1)'; this.style.opacity='1'">
                                    <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>

                                <!-- Twitter/X -->
                                <a x-show="twitter" :href="'https://twitter.com/' + twitter" target="_blank"
                                   style="width: 36px; height: 36px; border-radius: 50%; background: #000000; display: flex; align-items: center; justify-content: center; transition: transform 0.2s, opacity 0.2s;"
                                   onmouseover="this.style.transform='scale(1.1)'; this.style.opacity='0.9'"
                                   onmouseout="this.style.transform='scale(1)'; this.style.opacity='1'">
                                    <svg style="width: 18px; height: 18px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>

                                <!-- LinkedIn -->
                                <a x-show="linkedin" :href="'https://linkedin.com/company/' + linkedin" target="_blank"
                                   style="width: 36px; height: 36px; border-radius: 50%; background: #0077b5; display: flex; align-items: center; justify-content: center; transition: transform 0.2s, opacity 0.2s;"
                                   onmouseover="this.style.transform='scale(1.1)'; this.style.opacity='0.9'"
                                   onmouseout="this.style.transform='scale(1)'; this.style.opacity='1'">
                                    <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
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