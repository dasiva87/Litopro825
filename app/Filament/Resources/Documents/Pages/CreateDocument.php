<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\DocumentType;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['user_id'] = auth()->id();

        // Asegurar que siempre se cree como QUOTE
        if (!isset($data['document_type_id'])) {
            $data['document_type_id'] = DocumentType::where('code', 'QUOTE')->first()?->id;
        }

        // Siempre crear en estado borrador (draft)
        $data['status'] = 'draft';

        // Limpiar campos que no existen en la BD
        unset($data['client_type']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}