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
}