<?php

namespace App\Filament\Resources\Documents\Widgets;

use App\Models\Document;
use Filament\Widgets\Widget;

class FinancialSummaryWidget extends Widget
{
    protected  string $view = 'filament.resources.documents.widgets.financial-summary-widget';

    public ?Document $record = null;

    protected int | string | array $columnSpan = 'full';

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }
}
