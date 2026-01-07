<?php

namespace App\Filament\Pages\Auth;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    // Ancho optimizado: 5xl para mejor lectura en escritorio, responsive en móvil
    protected Width | string | null $maxWidth = '5xl';

    public function getHeading(): string
    {
        return 'Registrarse';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // Paso 1: Información de la Empresa
                    Wizard\Step::make('Empresa')
                        ->description('Información de tu negocio')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Section::make()
                                ->schema([
                                    // Grid responsive: 2 columnas en escritorio, 1 en móvil
                                    Grid::make(['default' => 1, 'sm' => 1, 'md' => 2])
                                        ->schema([
                                            TextInput::make('company_name')
                                                ->label('Nombre de la Empresa')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(['default' => 1, 'md' => 2])
                                                ->prefixIcon('heroicon-o-building-office-2')
                                                ->placeholder('Ej: Litografía Moderna S.A.S.'),

                                            TextInput::make('company_email')
                                                ->label('Email Corporativo')
                                                ->email()
                                                ->required()
                                                ->columnSpan(['default' => 1, 'md' => 1])
                                                ->prefixIcon('heroicon-o-envelope')
                                                ->placeholder('contacto@tuempresa.com'),

                                            TextInput::make('company_phone')
                                                ->label('Teléfono')
                                                ->tel()
                                                ->required()
                                                ->columnSpan(['default' => 1, 'md' => 1])
                                                ->prefixIcon('heroicon-o-phone')
                                                ->placeholder('+57 300 123 4567'),

                                            TextInput::make('tax_id')
                                                ->label('NIT / RUT')
                                                ->required()
                                                ->unique('companies', 'tax_id')
                                                ->columnSpan(['default' => 1, 'md' => 1])
                                                ->prefixIcon('heroicon-o-document-text')
                                                ->placeholder('000000000-0'),

                                            Select::make('company_type')
                                                ->label('Tipo de Empresa')
                                                ->required()
                                                ->options([
                                                    'litografia' => 'Litografía',
                                                    'papeleria' => 'Papelería y Productos',
                                                ])
                                                ->default('litografia')
                                                ->native(false)
                                                ->columnSpan(['default' => 1, 'md' => 1])
                                                ->prefixIcon('heroicon-o-tag'),

                                            Textarea::make('company_address')
                                                ->label('Dirección Completa')
                                                ->required()
                                                ->rows(3)
                                                ->columnSpan(['default' => 1, 'md' => 2])
                                                ->placeholder('Calle 123 #45-67, Edificio XYZ, Oficina 101'),
                                        ]),
                                ]),
                        ]),

                    // Paso 2: Información del Usuario
                    Wizard\Step::make('Usuario')
                        ->description('Crea tu cuenta de administrador')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Grid::make(['default' => 1, 'sm' => 1, 'md' => 2])
                                        ->schema([
                                            $this->getNameFormComponent()
                                                ->label('Nombre Completo')
                                                ->placeholder('Juan Pérez')
                                                ->columnSpan(['default' => 1, 'md' => 2]),

                                            $this->getEmailFormComponent()
                                                ->label('Correo Electrónico')
                                                ->placeholder('tu@email.com')
                                                ->columnSpan(['default' => 1, 'md' => 2]),

                                            $this->getPasswordFormComponent()
                                                ->label('Contraseña')
                                                ->placeholder('Mínimo 8 caracteres')
                                                ->columnSpan(['default' => 1, 'md' => 1]),

                                            $this->getPasswordConfirmationFormComponent()
                                                ->label('Confirmar Contraseña')
                                                ->placeholder('Repite tu contraseña')
                                                ->columnSpan(['default' => 1, 'md' => 1]),
                                        ]),
                                ]),
                        ]),

                    // Paso 3: Selección de Plan
                    Wizard\Step::make('Plan')
                        ->description('Selecciona tu plan')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Section::make('Elige tu Plan')
                                ->schema([
                                    Radio::make('plan_id')
                                        ->label('Planes Disponibles')
                                        ->options(function () {
                                            return Plan::where('is_active', true)
                                                ->orderBy('price')
                                                ->get()
                                                ->mapWithKeys(function ($plan) {
                                                    $price = $plan->price == 0
                                                        ? 'Gratis'
                                                        : '$' . number_format($plan->price, 0, ',', '.') . ' / mes';

                                                    return [
                                                        $plan->id => "{$plan->name} - {$price}"
                                                    ];
                                                });
                                        })
                                        ->default(function () {
                                            return Plan::where('is_active', true)
                                                ->where('price', 0)
                                                ->first()?->id;
                                        })
                                        ->required()
                                        ->helperText('Puedes cambiar tu plan en cualquier momento'),

                                    Checkbox::make('terms')
                                        ->label('Acepto los términos y condiciones')
                                        ->accepted()
                                        ->validationAttribute('términos y condiciones'),
                                ]),
                        ]),
                ])
                    ->submitAction(view('filament.pages.auth.register-submit-button')),
            ]);
    }

    protected function handleRegistration(array $data): User
    {
        DB::beginTransaction();

        try {
            $selectedPlan = Plan::findOrFail($data['plan_id']);

            // Create company
            // Para planes de pago, agregar 1 mes + 1 día de gracia
            // Para planes gratuitos, no expiran (null)
            $expiresAt = $selectedPlan->price == 0 ? null : now()->addMonth()->addDay();

            $company = Company::create([
                'name' => $data['company_name'],
                'slug' => Str::slug($data['company_name']),
                'email' => $data['company_email'],
                'phone' => $data['company_phone'],
                'address' => $data['company_address'],
                'tax_id' => $data['tax_id'],
                'company_type' => $data['company_type'],
                'status' => 'active',
                'is_active' => true,
                'subscription_plan' => $selectedPlan->slug,
                'subscription_expires_at' => $expiresAt,
                'max_users' => $selectedPlan->max_users ?? 5,
            ]);

            // Create user
            // No usar Hash::make() porque el modelo User tiene 'password' => 'hashed' en casts()
            // Esto evita doble hashing que causaría fallos de login
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // El cast 'hashed' se encarga del hashing automático
                'company_id' => $company->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assign role
            $adminRole = Role::where('name', 'Company Admin')->first();
            if ($adminRole) {
                $user->assignRole($adminRole);
            }

            // Create subscription
            \App\Models\Subscription::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'name' => $selectedPlan->name,
                'stripe_id' => ($selectedPlan->price == 0 ? 'free_' : 'plan_') . $company->id . '_' . time(),
                'stripe_status' => 'active',
                'stripe_price' => $selectedPlan->stripe_price_id ?? $selectedPlan->slug,
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => $selectedPlan->price == 0 ? now()->addYear() : now()->addMonth(),
            ]);

            DB::commit();

            Notification::make()
                ->success()
                ->title('¡Bienvenido a GrafiRed!')
                ->body('Tu cuenta ha sido creada exitosamente.')
                ->send();

            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
