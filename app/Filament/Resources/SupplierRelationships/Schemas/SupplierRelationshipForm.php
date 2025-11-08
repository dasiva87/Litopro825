<?php

namespace App\Filament\Resources\SupplierRelationships\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierRelationshipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Proveedor')
                    ->schema([
                        Select::make('supplier_company_id')
                            ->label('Proveedor')
                            ->relationship('supplierCompany', 'name')
                            ->searchable()
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3),
                    ]),
            ]);
    }
}
