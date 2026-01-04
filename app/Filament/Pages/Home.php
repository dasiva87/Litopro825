<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard;

class Home extends Dashboard
{
    protected static ?string $title = 'Gremio';

    protected static ?string $navigationLabel = 'Gremio';

    protected static ?string $slug = '';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 0;

    /**
     * Widgets de la página Gremio
     */
    public function getWidgets(): array
    {
        return [
            // Onboarding Widget (se oculta automáticamente cuando se completa)
            \App\Filament\Widgets\OnboardingWidget::class,

            // Widgets principales del feed (2 columnas)
            \App\Filament\Widgets\CreatePostWidget::class,
            \App\Filament\Widgets\SocialPostWidget::class,

            // Widgets del sidebar (1 columna)
            \App\Filament\Widgets\CalculadoraButtonWidget::class,
            \App\Filament\Widgets\SuggestedCompaniesWidget::class,
        ];
    }

    /**
     * Columnas del layout responsive
     */
    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'md' => 3,
            'lg' => 3,
        ];
    }
}
