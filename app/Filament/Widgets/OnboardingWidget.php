<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class OnboardingWidget extends Widget
{
    protected string $view = 'filament.widgets.onboarding-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function getData(): array
    {
        return [
            'steps' => $this->getOnboardingSteps(),
            'progress' => $this->getOnboardingProgress(),
        ];
    }

    protected function getOnboardingSteps(): array
    {
        return [
            [
                'id' => 'create_client',
                'title' => 'Crear Cliente',
                'description' => 'Agrega tu primer cliente para poder generar cotizaciones.',
                'icon' => 'heroicon-o-user-plus',
                'completed' => $this->hasClients(),
                'action_url' => '/admin/clients',
                'action_label' => 'Agregar Cliente',
            ],
            [
                'id' => 'create_supplier',
                'title' => 'Crear Proveedor',
                'description' => 'Registra un proveedor de materiales o servicios.',
                'icon' => 'heroicon-o-truck',
                'completed' => $this->hasSuppliers(),
                'action_url' => '/admin/suppliers',
                'action_label' => 'Agregar Proveedor',
            ],
            [
                'id' => 'create_machine',
                'title' => 'Crear Máquina',
                'description' => 'Configura tu primera máquina de impresión.',
                'icon' => 'heroicon-o-cog-6-tooth',
                'completed' => $this->hasMachines(),
                'action_url' => '/admin/printing-machines',
                'action_label' => 'Agregar Máquina',
            ],
            [
                'id' => 'create_paper',
                'title' => 'Crear Papel',
                'description' => 'Agrega un tipo de papel a tu inventario.',
                'icon' => 'heroicon-o-document',
                'completed' => $this->hasPapers(),
                'action_url' => '/admin/papers',
                'action_label' => 'Agregar Papel',
            ],
            [
                'id' => 'create_simple_item',
                'title' => 'Agregar Item Sencillo',
                'description' => 'Crea tu primer producto o servicio para cotizar.',
                'icon' => 'heroicon-o-cube',
                'completed' => $this->hasSimpleItems(),
                'action_url' => '/admin/products',
                'action_label' => 'Agregar Producto',
            ],
            [
                'id' => 'create_quotation',
                'title' => 'Crear Cotización',
                'description' => 'Genera tu primera cotización utilizando los elementos creados.',
                'icon' => 'heroicon-o-document-text',
                'completed' => $this->hasQuotations(),
                'action_url' => '/admin/documents',
                'action_label' => 'Crear Cotización',
            ],
            [
                'id' => 'make_post',
                'title' => 'Hacer Publicación en Gremio',
                'description' => 'Comparte contenido con otras empresas de la red.',
                'icon' => 'heroicon-o-megaphone',
                'completed' => $this->hasPosts(),
                'action_url' => '/admin',
                'action_label' => 'Ir a Gremio',
            ],
        ];
    }

    protected function getOnboardingProgress(): int
    {
        $steps = $this->getOnboardingSteps();
        $completedSteps = collect($steps)->filter(fn ($step) => $step['completed'])->count();
        $totalSteps = count($steps);

        return $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
    }

    protected function hasClients(): bool
    {
        $company = auth()->user()?->company;

        if (! $company) {
            return false;
        }

        return $company->contacts()
            ->where(function ($query) {
                $query->where('type', 'customer')
                    ->orWhere('type', 'both');
            })
            ->exists();
    }

    protected function hasSuppliers(): bool
    {
        $company = auth()->user()?->company;

        if (! $company) {
            return false;
        }

        return $company->contacts()
            ->where(function ($query) {
                $query->where('type', 'supplier')
                    ->orWhere('type', 'both');
            })
            ->exists();
    }

    protected function hasMachines(): bool
    {
        return auth()->user()?->company
            ?->printingMachines()
            ->exists() ?? false;
    }

    protected function hasPapers(): bool
    {
        return auth()->user()?->company
            ?->papers()
            ->exists() ?? false;
    }

    protected function hasSimpleItems(): bool
    {
        return auth()->user()?->company
            ?->products()
            ->exists() ?? false;
    }

    protected function hasQuotations(): bool
    {
        return auth()->user()?->company
            ?->documents()
            ->whereHas('documentType', function ($query) {
                $query->where('code', 'QUOTE');
            })
            ->exists() ?? false;
    }

    protected function hasPosts(): bool
    {
        $company = auth()->user()?->company;

        if (! $company) {
            return false;
        }

        return \App\Models\SocialPost::where('company_id', $company->id)
            ->exists();
    }

    public function hideOnboarding(): void
    {
        $user = auth()->user();
        if ($user) {
            // Obtener preferences actuales
            $preferences = $user->preferences ?? [];
            $preferences['hide_onboarding'] = true;

            // Guardar preferencia
            $user->preferences = $preferences;
            $user->save();
        }

        // Redirigir para refrescar la página
        $this->redirect('/admin');
    }

    public static function canView(): bool
    {
        // Verificación temprana: si no hay usuario autenticado, ocultar
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // PRIMERA PRIORIDAD: Si el usuario ya ocultó el widget manualmente, SIEMPRE retornar false
        $preferences = $user->preferences ?? [];
        if (! empty($preferences['hide_onboarding'])) {
            return false;
        }

        // SEGUNDA PRIORIDAD: Verificar si el onboarding está al 100%
        try {
            $widget = new static;
            $progress = $widget->getOnboardingProgress();

            if ($progress >= 100) {
                // Auto-ocultar cuando esté al 100%
                $preferences['hide_onboarding'] = true;
                $user->preferences = $preferences;
                $user->save();

                return false;
            }
        } catch (\Exception $e) {
            // Si hay error al calcular progreso, ocultar el widget
            \Log::error('OnboardingWidget canView error: ' . $e->getMessage());
            return false;
        }

        // Si llegamos aquí, mostrar el widget
        return true;
    }
}
