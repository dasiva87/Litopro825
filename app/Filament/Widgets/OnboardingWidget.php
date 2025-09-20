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
                'id' => 'welcome',
                'title' => 'Bienvenido a LitoPro',
                'description' => 'Conoce las funcionalidades principales de tu nueva plataforma de gestión para litografías.',
                'icon' => 'heroicon-o-hand-raised',
                'completed' => true,
            ],
            [
                'id' => 'company_setup',
                'title' => 'Configurar Empresa',
                'description' => 'Completa la información de tu empresa y configura los parámetros básicos.',
                'icon' => 'heroicon-o-building-office',
                'completed' => $this->isCompanySetupCompleted(),
            ],
            [
                'id' => 'add_contacts',
                'title' => 'Agregar Contactos',
                'description' => 'Agrega tus primeros clientes y proveedores para comenzar a trabajar.',
                'icon' => 'heroicon-o-users',
                'completed' => $this->hasContacts(),
            ],
            [
                'id' => 'inventory_setup',
                'title' => 'Configurar Inventario',
                'description' => 'Agrega papeles, máquinas de impresión y productos a tu catálogo.',
                'icon' => 'heroicon-o-cube',
                'completed' => $this->hasInventoryItems(),
            ],
            [
                'id' => 'first_quotation',
                'title' => 'Primera Cotización',
                'description' => 'Crea tu primera cotización utilizando los items configurados.',
                'icon' => 'heroicon-o-document-text',
                'completed' => $this->hasQuotations(),
            ],
            [
                'id' => 'explore_features',
                'title' => 'Explorar Funciones',
                'description' => 'Descubre la calculadora de papel, widgets del dashboard y más funciones avanzadas.',
                'icon' => 'heroicon-o-sparkles',
                'completed' => false,
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

    protected function isCompanySetupCompleted(): bool
    {
        $user = auth()->user();
        $company = $user?->company;

        return $company &&
               $company->name &&
               $company->email &&
               $company->phone;
    }

    protected function hasContacts(): bool
    {
        return auth()->user()?->company
            ?->contacts()
            ->exists() ?? false;
    }

    protected function hasInventoryItems(): bool
    {
        $company = auth()->user()?->company;

        if (! $company) {
            return false;
        }

        return $company->papers()->exists() ||
               $company->printingMachines()->exists() ||
               $company->products()->exists();
    }

    protected function hasQuotations(): bool
    {
        return auth()->user()?->company
            ?->documents()
            ->where('type', 'quotation')
            ->exists() ?? false;
    }

    public static function canView(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        // Create a temporary instance to check progress
        $widget = new static;

        return $widget->getOnboardingProgress() < 100;
    }
}
