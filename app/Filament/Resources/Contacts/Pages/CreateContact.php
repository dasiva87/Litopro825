<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    public function mount(): void
    {
        parent::mount();

        // Preseleccionar el tipo desde query parameter
        if (request()->has('type')) {
            $this->form->fill([
                'type' => request()->get('type'),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        // Redirigir según el tipo de contacto creado
        $type = $this->data['type'] ?? null;

        if ($type === 'customer' || $type === 'both') {
            return route('filament.admin.resources.clients.index');
        }

        if ($type === 'supplier') {
            return route('filament.admin.resources.suppliers.index');
        }

        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        // Asegurar valores por defecto para campos numéricos obligatorios
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['payment_terms'] = $data['payment_terms'] ?? 0;
        $data['discount_percentage'] = $data['discount_percentage'] ?? 0;

        // Siempre crear contactos como activos
        $data['is_active'] = true;

        return $data;
    }
}
