<?php

namespace App\Filament\Resources\Papers\Pages;

use App\Filament\Resources\Papers\PaperResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaper extends CreateRecord
{
    protected static string $resource = PaperResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        return $data;
    }
}