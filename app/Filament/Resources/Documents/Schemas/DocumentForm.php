<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\DocumentType;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Principal')
                    ->schema([
                        // Campo oculto: siempre crea como QUOTE
                        Select::make('document_type_id')
                            ->label('Tipo de Documento')
                            ->relationship('documentType', 'name')
                            ->default(fn () => DocumentType::where('code', 'QUOTE')->first()?->id)
                            ->hidden()
                            ->dehydrated()
                            ->required(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('document_number')
                                    ->label('Número')
                                    ->disabled()
                                    ->placeholder('Se genera automáticamente'),

                                Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'draft' => 'Borrador',
                                        'sent' => 'Enviado',
                                        'approved' => 'Aprobado',
                                        'rejected' => 'Rechazado',
                                        'in_production' => 'En Producción',
                                        'completed' => 'Completado',
                                        'cancelled' => 'Cancelado',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->visible(fn ($record) => $record !== null), // Solo visible al editar
                            ]),
                            
                        Grid::make(1)
                            ->schema([
                                Select::make('contact_id')
                                    ->label('Cliente')
                                    ->relationship(
                                        name: 'contact',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function ($query) {
                                            $currentCompanyId = auth()->user()->company_id ?? config('app.current_tenant_id');

                                            if (!$currentCompanyId) {
                                                return $query->whereRaw('1 = 0');
                                            }

                                            // Solo contactos de tipo cliente de la empresa actual
                                            return $query->where('company_id', $currentCompanyId)
                                                ->whereIn('type', ['customer', 'both']);
                                        }
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Selecciona un cliente o crea uno nuevo')
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required(),
                                        Select::make('type')
                                            ->label('Tipo')
                                            ->options([
                                                'customer' => 'Cliente',
                                                'supplier' => 'Proveedor',
                                                'both' => 'Cliente y Proveedor',
                                            ])
                                            ->default('customer')
                                            ->required(),
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
                                        TextInput::make('phone')
                                            ->label('Teléfono'),
                                    ]),
                            ]),

                        Grid::make(1)
                            ->schema([
                                TextInput::make('reference')
                                    ->label('Referencia')
                                    ->maxLength(255),
                            ]),
                    ]),
                    
                Section::make('Fechas')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('date')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required(),

                                DatePicker::make('due_date')
                                    ->label('Fecha de Vencimiento')
                                    ->default(now()->addDays(30)),

                                DatePicker::make('valid_until')
                                    ->label('Válida Hasta')
                                    ->default(now()->addDays(15)),
                            ]),
                    ]),

                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas para el Cliente')
                            ->rows(3),

                        Textarea::make('internal_notes')
                            ->label('Notas Internas')
                            ->rows(3),
                    ])
                    ->collapsed(),
            ]);
    }
}