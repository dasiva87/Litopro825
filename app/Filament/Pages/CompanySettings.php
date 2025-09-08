<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class CompanySettings extends Page implements HasForms
{
    use InteractsWithForms;
    
    // Ocultar página original
    protected static bool $shouldRegisterNavigation = false;
    
    public static function getNavigationLabel(): string
    {
        return 'Configuración de Empresa';
    }
    
    public function getTitle(): string
    {
        return 'Configuración de Empresa';
    }
    
    protected function getViewData(): array
    {
        return [];
    }
    
    public function getView(): string
    {
        return 'filament.pages.company-settings';
    }
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $company = auth()->user()->company;
        $this->form->fill($company->toArray());
    }
    
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Configuración de Empresa')
                    ->tabs([
                        // TAB 1: Información General
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-building-office-2')
                            ->components([
                                Section::make('Información Básica')
                                    ->description('Información general de tu empresa')
                                    ->components([
                                        TextInput::make('name')
                                            ->label('Nombre de la Empresa')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, $set) => $set('slug', \Str::slug($state))),
                                            
                                        TextInput::make('slug')
                                            ->label('URL Amigable')
                                            ->helperText('Se genera automáticamente del nombre')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->maxLength(255),
                                            
                                        FileUpload::make('logo')
                                            ->label('Logo de la Empresa')
                                            ->image()
                                            ->directory('company-logos')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                            ])
                                            ->maxSize(2048)
                                            ->helperText('Logo oficial para documentos y reportes'),
                                            
                                        FileUpload::make('profile_image')
                                            ->label('Imagen de Perfil')
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
                                            ->helperText('Imagen que representa a tu empresa en el dashboard')
                                            ->columnSpanFull(),
                                    ])->columns(2),
                                    
                                Section::make('Información de Contacto')
                                    ->components([
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
                                    ])->columns(2),
                            ]),
                            
                        // TAB 2: Configuración Fiscal
                        Tabs\Tab::make('Fiscal')
                            ->icon('heroicon-o-document-text')
                            ->components([
                                Section::make('Información Fiscal')
                                    ->description('Datos necesarios para facturación y reportes')
                                    ->components([
                                        TextInput::make('tax_id')
                                            ->label('NIT / RUT / RFC')
                                            ->helperText('Número de identificación tributaria')
                                            ->maxLength(50),
                                            
                                        // Aquí se pueden agregar más campos fiscales cuando sea necesario
                                        TextInput::make('fiscal_regime')
                                            ->label('Régimen Fiscal')
                                            ->maxLength(100)
                                            ->helperText('Ej: Persona Física con Actividad Empresarial'),
                                            
                                        Toggle::make('invoice_auto_numbering')
                                            ->label('Numeración Automática de Facturas')
                                            ->helperText('Generar números de factura automáticamente')
                                            ->default(true),
                                    ])->columns(2),
                            ]),
                            
                        // TAB 3: Configuración de Negocio
                        Tabs\Tab::make('Negocio')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->components([
                                Section::make('Configuración de Precios')
                                    ->description('Configuraciones predeterminadas para cotizaciones')
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
                                    ])->columns(3),
                                    
                                Section::make('Configuración de Producción')
                                    ->components([
                                        TextInput::make('production_lead_time')
                                            ->label('Tiempo de Producción (días)')
                                            ->numeric()
                                            ->default(3)
                                            ->helperText('Días estimados para completar trabajos'),
                                            
                                        Toggle::make('stock_alerts_enabled')
                                            ->label('Alertas de Stock Habilitadas')
                                            ->default(true)
                                            ->helperText('Recibir notificaciones cuando el stock esté bajo'),
                                            
                                        TextInput::make('low_stock_threshold')
                                            ->label('Umbral de Stock Bajo')
                                            ->numeric()
                                            ->default(10)
                                            ->helperText('Cantidad mínima antes de mostrar alerta')
                                            ->visible(fn ($get) => $get('stock_alerts_enabled')),
                                    ])->columns(3),
                            ]),
                            
                        // TAB 4: Suscripción y Usuarios
                        Tabs\Tab::make('Suscripción')
                            ->icon('heroicon-o-credit-card')
                            ->components([
                                Section::make('Plan de Suscripción')
                                    ->description('Información de tu plan actual')
                                    ->components([
                                        Select::make('subscription_plan')
                                            ->label('Plan de Suscripción')
                                            ->options([
                                                'free' => 'Gratuito',
                                                'basic' => 'Básico',
                                                'pro' => 'Professional',
                                                'enterprise' => 'Enterprise'
                                            ])
                                            ->default('free')
                                            ->disabled() // Solo lectura por ahora
                                            ->helperText('Contacta soporte para cambiar de plan'),
                                            
                                        TextInput::make('max_users')
                                            ->label('Máximo de Usuarios')
                                            ->numeric()
                                            ->default(5)
                                            ->disabled() // Solo lectura
                                            ->helperText('Usuarios permitidos en tu plan actual'),
                                            
                                        Forms\Components\DatePicker::make('subscription_expires_at')
                                            ->label('Fecha de Renovación')
                                            ->disabled() // Solo lectura
                                            ->helperText('Próxima fecha de renovación del plan'),
                                    ])->columns(3),
                                    
                                Section::make('Estado de la Cuenta')
                                    ->components([
                                        Toggle::make('is_active')
                                            ->label('Cuenta Activa')
                                            ->disabled() // Solo lectura
                                            ->helperText('Estado actual de tu cuenta'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Configuración')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->save()),
        ];
    }
    
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            $company = auth()->user()->company;
            $company->update($data);
            
            Notification::make()
                ->title('Configuración guardada')
                ->body('La configuración de la empresa se ha actualizado correctamente.')
                ->success()
                ->send();
                
        } catch (Halt $exception) {
            return;
        }
    }
    
    public static function canAccess(): bool
    {
        // Solo los admin de empresa pueden acceder
        $user = auth()->user();
        return $user && $user->hasRole(['Super Admin', 'Company Admin']);
    }
    
    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return route('filament.admin.pages.company-settings');
    }
}