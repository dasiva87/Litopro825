<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class Home extends Page
{
    protected string $view = 'filament.pages.home';

    protected static ?string $title = 'Home';

    protected static ?string $navigationLabel = 'Home';

    protected static ?string $slug = 'home';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 0;

    /**
     * Obtener widgets para la página Home
     */
    public function getWidgets(): array
    {
        return [

            // Widgets de acciones y calculadoras
            \App\Filament\Widgets\QuickActionsWidget::class,
            \App\Filament\Widgets\CalculadoraCorteWidget::class,

            // Widgets sociales
            \App\Filament\Widgets\CreatePostWidget::class,
            \App\Filament\Widgets\SocialFeedWidget::class,
            \App\Filament\Widgets\SuggestedCompaniesWidget::class,

            // Widgets de negocio
           // \App\Filament\Widgets\MrrWidget::class,

            // Widget de onboarding (si es necesario)
          //  \App\Filament\Widgets\OnboardingWidget::class,
        ];
    }

    /**
     * Obtener columnas del layout
     */
    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }
}