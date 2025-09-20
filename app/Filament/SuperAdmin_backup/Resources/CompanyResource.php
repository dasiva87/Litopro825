<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Enums\SuperAdminNavigationGroup;
use App\Filament\SuperAdmin\Resources\Companies\Pages;
use App\Models\Company;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Empresas';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $pluralModelLabel = 'Empresas';

    protected static ?SuperAdminNavigationGroup $navigationGroup = SuperAdminNavigationGroup::TenantManagement;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tax_id')
                            ->label('NIT/RUC')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Estado y Suscripción')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'trial' => 'Prueba',
                                'suspended' => 'Suspendido',
                                'cancelled' => 'Cancelado',
                            ])
                            ->required()
                            ->default('active'),

                        Forms\Components\Select::make('subscription_plan')
                            ->label('Plan de Suscripción')
                            ->options([
                                'free' => 'Gratuito',
                                'basic' => 'Básico',
                                'professional' => 'Profesional',
                                'enterprise' => 'Empresarial',
                            ])
                            ->default('free'),

                        Forms\Components\DateTimePicker::make('subscription_expires_at')
                            ->label('Suscripción Expira'),

                        Forms\Components\TextInput::make('max_users')
                            ->label('Máximo de Usuarios')
                            ->numeric()
                            ->default(5),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])->columns(2),

                Section::make('Suspensión/Cancelación')
                    ->schema([
                        Forms\Components\DateTimePicker::make('suspended_at')
                            ->label('Suspendido en')
                            ->disabled(),

                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Razón de Suspensión/Cancelación')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription_plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'gray',
                        'basic' => 'info',
                        'professional' => 'success',
                        'enterprise' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (Company $record): string => match ($record->status) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (Company $record): string => match ($record->status) {
                        'active' => 'Activo',
                        'trial' => 'Prueba',
                        'suspended' => 'Suspendido',
                        'cancelled' => 'Cancelado',
                        default => 'Desconocido',
                    }),

                Tables\Columns\TextColumn::make('subscription_expires_at')
                    ->label('Expira')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'trial' => 'Prueba',
                        'suspended' => 'Suspendido',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('subscription_plan')
                    ->label('Plan')
                    ->options([
                        'free' => 'Gratuito',
                        'basic' => 'Básico',
                        'professional' => 'Profesional',
                        'enterprise' => 'Empresarial',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),

                Actions\Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Razón de la suspensión')
                            ->required(),
                    ])
                    ->action(function (Company $record, array $data) {
                        $record->suspend($data['reason']);

                        Notification::make()
                            ->title('Empresa suspendida')
                            ->body("La empresa {$record->name} ha sido suspendida")
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (Company $record) => $record->status !== 'suspended'),

                Actions\Action::make('reactivate')
                    ->label('Reactivar')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Company $record) {
                        $record->reactivate();

                        Notification::make()
                            ->title('Empresa reactivada')
                            ->body("La empresa {$record->name} ha sido reactivada")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Company $record) => $record->status === 'suspended'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),

                    Actions\BulkAction::make('bulk_suspend')
                        ->label('Suspender Seleccionadas')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Razón de la suspensión')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->suspend($data['reason']);
                            }

                            Notification::make()
                                ->title('Empresas suspendidas')
                                ->body(count($records).' empresas han sido suspendidas')
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relations can be added here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
