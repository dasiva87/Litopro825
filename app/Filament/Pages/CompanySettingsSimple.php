<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

class CompanySettingsSimple extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Configuración de Empresa';

    protected static ?string $title = 'Configuración de Empresa';

    protected static ?string $slug = 'company-settings';
    
    public function getView(): string
    {
        return 'filament.pages.company-settings-simple';
    }
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $company = auth()->user()->company;
        $this->form->fill($company ? $company->toArray() : []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->components([
                        FileUpload::make('profile_image')
                            ->label('Imagen de Perfil de la Empresa')
                            ->image()
                            ->directory('company-profiles')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->maxSize(2048)
                            ->helperText('Imagen que representa a tu empresa. Tamaño máximo: 2MB')
                            ->columnSpanFull(),
                            
                        TextInput::make('name')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('email')
                            ->label('Email Principal')
                            ->email()
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),
                            
                        TextInput::make('website')
                            ->label('Sitio Web')
                            ->url()
                            ->prefix('https://')
                            ->maxLength(255),
                            
                        Textarea::make('address')
                            ->label('Dirección')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Section::make('Configuración de Negocio')
                    ->components([
                        TextInput::make('default_profit_margin')
                            ->label('Margen de Ganancia por Defecto (%)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->default(30)
                            ->helperText('Porcentaje de ganancia aplicado a nuevos items'),
                            
                        TextInput::make('default_tax_rate')
                            ->label('Tasa de Impuesto por Defecto (%)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->default(19)
                            ->helperText('IVA u otro impuesto aplicado por defecto'),
                            
                        TextInput::make('quote_validity_days')
                            ->label('Validez de Cotizaciones (días)')
                            ->numeric()
                            ->default(30)
                            ->helperText('Días de validez por defecto para cotizaciones'),
                            
                        Toggle::make('stock_alerts_enabled')
                            ->label('Alertas de Stock Habilitadas')
                            ->default(true)
                            ->helperText('Recibir notificaciones cuando el stock esté bajo'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public static function canAccess(): bool
    {
        // Solo los admin de empresa pueden acceder
        $user = auth()->user();
        return $user && $user->hasRole(['Super Admin', 'Company Admin']);
    }


    public function save(): void
    {
        $data = $this->form->getState();
        
        // Actualizar los datos de la empresa
        $company = auth()->user()->company;
        if ($company) {
            $company->update($data);
            
            Notification::make()
                ->title('Configuración guardada')
                ->body('La configuración de la empresa se ha actualizado correctamente.')
                ->success()
                ->send();
        }
    }
}