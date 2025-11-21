<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\Widget;

class ActiveProjectsWidget extends Widget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getView(): string
    {
        return 'filament.widgets.active-projects-widget';
    }

    public function getActiveProjects(): array
    {
        return Project::all()
            ->filter(fn ($project) => in_array($project->status, ['approved', 'in_production', 'sent']))
            ->take(5)
            ->toArray();
    }
}
