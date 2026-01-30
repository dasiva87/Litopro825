<x-filament-panels::page>
    <div class="companies-directory">
        <!-- Header -->
        <div class="directory-header">
            <div class="header-content">
                <h1 class="header-title">Directorio de Empresas</h1>
                <p class="header-subtitle">Conecta con empresas del sector gráfico en Colombia</p>
            </div>
            <div class="header-stats">
                <div class="stat-item">
                    <span class="stat-number">{{ $totalCompanies }}</span>
                    <span class="stat-label">Empresas</span>
                </div>
            </div>
        </div>

        <!-- Barra de búsqueda y filtros -->
        <div class="search-filters-bar">
            <!-- Campo de búsqueda -->
            <div class="search-box">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar empresa por nombre..."
                    class="search-input"
                >
                @if($search)
                    <button wire:click="$set('search', '')" class="clear-search">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Filtros -->
            <div class="filters-row">
                <!-- Filtro por tipo -->
                <div class="filter-select-wrapper">
                    <select wire:model.live="filterType" class="filter-select">
                        <option value="">Todos los tipos</option>
                        @foreach($availableTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <svg class="select-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </div>

                <!-- Filtro por ciudad -->
                <div class="filter-select-wrapper">
                    <select wire:model.live="filterCity" class="filter-select">
                        <option value="">Todas las ciudades</option>
                        @foreach($availableCities as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <svg class="select-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </div>

                <!-- Botón limpiar filtros -->
                @if($search || $filterType || $filterCity)
                    <button wire:click="clearFilters" class="clear-filters-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" clip-rule="evenodd" />
                        </svg>
                        Limpiar filtros
                    </button>
                @endif
            </div>

            <!-- Indicador de filtros activos -->
            @if($search || $filterType || $filterCity)
                <div class="active-filters">
                    <span class="active-filters-label">Filtros activos:</span>
                    @if($search)
                        <span class="filter-tag">
                            Búsqueda: "{{ $search }}"
                            <button wire:click="$set('search', '')">×</button>
                        </span>
                    @endif
                    @if($filterType)
                        <span class="filter-tag">
                            Tipo: {{ $availableTypes[$filterType] ?? $filterType }}
                            <button wire:click="$set('filterType', '')">×</button>
                        </span>
                    @endif
                    @if($filterCity)
                        <span class="filter-tag">
                            Ciudad: {{ $availableCities[$filterCity] ?? $filterCity }}
                            <button wire:click="$set('filterCity', '')">×</button>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <!-- Grid de cards -->
        <div class="companies-grid">
            @forelse($companies as $company)
                <div class="company-card">
                    <!-- Banner con avatar -->
                    <div class="card-banner" @if($company['banner']) style="background-image: url('{{ $company['banner'] }}')" @endif>
                        <div class="avatar-wrapper">
                            @if($company['avatar'])
                                <img src="{{ $company['avatar'] }}" alt="{{ $company['name'] }}" class="company-avatar">
                            @else
                                @php
                                    $gradients = [
                                        'linear-gradient(135deg, #1A2752 0%, #3d4f7c 100%)',
                                        'linear-gradient(135deg, #2c3e50 0%, #4a6fa5 100%)',
                                        'linear-gradient(135deg, #1A2752 0%, #5a6f94 100%)',
                                        'linear-gradient(135deg, #34495e 0%, #1A2752 100%)',
                                    ];
                                    $colorIndex = ord($company['avatar_initials'][0]) % count($gradients);
                                @endphp
                                <div class="avatar-placeholder" style="background: {{ $gradients[$colorIndex] }}">
                                    <span>{{ $company['avatar_initials'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="card-content">
                        <h3 class="company-name">{{ $company['name'] }}</h3>

                        <span class="company-type">{{ $company['company_type'] }}</span>

                        @if($company['bio'])
                            <p class="company-bio">{{ $company['bio'] }}</p>
                        @else
                            <p class="company-bio no-bio">Sin descripción disponible</p>
                        @endif

                        <!-- Info row -->
                        <div class="info-row">
                            @if($company['city'])
                                <div class="info-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ $company['city'] }}</span>
                                </div>
                            @endif
                            <div class="info-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                                <span>{{ $company['followers_count'] }} seguidores</span>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="card-actions">
                            <a href="/admin/empresa/{{ $company['slug'] }}" class="btn btn-outline">
                                Ver Perfil
                            </a>
                            @if($company['is_following'])
                                <button wire:click="followCompany({{ $company['id'] }})" class="btn btn-following">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Siguiendo
                                </button>
                            @else
                                <button wire:click="followCompany({{ $company['id'] }})" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Seguir
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                    @if($search || $filterType || $filterCity)
                        <h3>No se encontraron empresas</h3>
                        <p>Intenta con otros filtros o términos de búsqueda</p>
                        <button wire:click="clearFilters" class="btn btn-primary" style="margin-top: 1rem;">
                            Limpiar filtros
                        </button>
                    @else
                        <h3>No hay empresas disponibles</h3>
                        <p>Las empresas registradas aparecerán aquí</p>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <style>
        .companies-directory {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .directory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #1A2752 0%, #2d3a5c 100%);
            border-radius: 16px;
            color: white;
        }

        .header-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.25rem 0;
        }

        .header-subtitle {
            font-size: 0.95rem;
            opacity: 0.85;
            margin: 0;
        }

        .header-stats {
            display: flex;
            gap: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem 1.5rem;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
        }

        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        /* Search and Filters Bar */
        .search-filters-bar {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
        }

        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #9ca3af;
        }

        .search-input {
            width: 100%;
            padding: 0.875rem 2.5rem 0.875rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #f9fafb;
        }

        .search-input:focus {
            outline: none;
            border-color: #1A2752;
            background: white;
            box-shadow: 0 0 0 3px rgba(26, 39, 82, 0.1);
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

        .clear-search {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: #e5e7eb;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .clear-search svg {
            width: 14px;
            height: 14px;
            color: #6b7280;
        }

        .clear-search:hover {
            background: #d1d5db;
        }

        .filters-row {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-select-wrapper {
            position: relative;
            flex: 1;
            min-width: 180px;
        }

        .filter-select {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            background: #f9fafb;
            cursor: pointer;
            appearance: none;
            transition: all 0.2s;
            color: #374151;
        }

        .filter-select:focus {
            outline: none;
            border-color: #1A2752;
            background: white;
        }

        .select-arrow {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: #6b7280;
            pointer-events: none;
        }

        .clear-filters-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .clear-filters-btn svg {
            width: 16px;
            height: 16px;
        }

        .clear-filters-btn:hover {
            background: #fecaca;
        }

        .active-filters {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }

        .active-filters-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            background: #d6f4ff;
            color: #1A2752;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .filter-tag button {
            background: none;
            border: none;
            color: #1A2752;
            font-size: 1rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            opacity: 0.7;
        }

        .filter-tag button:hover {
            opacity: 1;
        }

        /* Grid */
        .companies-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        /* Card */
        .company-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .company-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(26, 39, 82, 0.15);
        }

        /* Banner */
        .card-banner {
            height: 100px;
            background: linear-gradient(135deg, #1A2752 0%, #3d4f7c 100%);
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .avatar-wrapper {
            position: absolute;
            bottom: -40px;
        }

        .company-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .avatar-placeholder span {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* Content */
        .card-content {
            padding: 3rem 1.25rem 1.25rem;
            text-align: center;
        }

        .company-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1A2752;
            margin: 0 0 0.5rem 0;
        }

        .company-type {
            display: inline-block;
            background: #d6f4ff;
            color: #1A2752;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .company-bio {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.5;
            margin: 0 0 1rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.625rem;
        }

        .company-bio.no-bio {
            color: #9ca3af;
            font-style: italic;
        }

        /* Info row */
        .info-row {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 0.75rem 0;
            margin-bottom: 1rem;
            border-top: 1px solid #f0f2f5;
            border-bottom: 1px solid #f0f2f5;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .info-item svg {
            width: 14px;
            height: 14px;
            color: #1A2752;
        }

        /* Buttons */
        .card-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            flex: 1;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            text-decoration: none;
        }

        .btn svg {
            width: 16px;
            height: 16px;
        }

        .btn-outline {
            background: white;
            border: 2px solid #1A2752;
            color: #1A2752;
        }

        .btn-outline:hover {
            background: #1A2752;
            color: white;
        }

        .btn-primary {
            background: #1A2752;
            border: 2px solid #1A2752;
            color: white;
        }

        .btn-primary:hover {
            background: #0f1a3d;
            border-color: #0f1a3d;
        }

        .btn-following {
            background: #059669;
            border: 2px solid #059669;
            color: white;
        }

        .btn-following:hover {
            background: #047857;
            border-color: #047857;
        }

        /* Empty state */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: #f9fafb;
            border-radius: 16px;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.25rem;
            background: #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-icon svg {
            width: 40px;
            height: 40px;
            color: #9ca3af;
        }

        .empty-state h3 {
            font-size: 1.125rem;
            color: #4b5563;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }

        .empty-state p {
            font-size: 0.875rem;
            color: #9ca3af;
            margin: 0;
        }

        /* Dark mode support */
        .dark .search-filters-bar {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .search-input {
            background: #374151;
            border-color: #4b5563;
            color: #f9fafb;
        }

        .dark .search-input:focus {
            background: #1f2937;
            border-color: #60a5fa;
        }

        .dark .filter-select {
            background: #374151;
            border-color: #4b5563;
            color: #f9fafb;
        }

        .dark .filter-select:focus {
            background: #1f2937;
            border-color: #60a5fa;
        }

        .dark .active-filters {
            border-color: #374151;
        }

        .dark .filter-tag {
            background: #374151;
            color: #d1d5db;
        }

        .dark .company-card {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .company-name {
            color: #f9fafb;
        }

        .dark .company-type {
            background: #374151;
            color: #d1d5db;
        }

        .dark .company-bio {
            color: #9ca3af;
        }

        .dark .info-row {
            border-color: #374151;
        }

        .dark .info-item {
            color: #9ca3af;
        }

        .dark .info-item svg {
            color: #60a5fa;
        }

        .dark .btn-outline {
            background: transparent;
            border-color: #60a5fa;
            color: #60a5fa;
        }

        .dark .btn-outline:hover {
            background: #60a5fa;
            color: #1f2937;
        }

        /* Responsive */
        @media (max-width: 1280px) {
            .companies-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .companies-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .directory-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .filters-row {
                flex-direction: column;
            }

            .filter-select-wrapper {
                min-width: 100%;
            }

            .companies-grid {
                grid-template-columns: 1fr;
            }

            .card-actions {
                flex-direction: column;
            }
        }
    </style>
</x-filament-panels::page>
