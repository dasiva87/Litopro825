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
    protected Width | string | null $maxWidth = '7xl'; // Opciones: sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // Step 1: Company Information
                    Wizard\Step::make('Empresa')
                        ->description('Información de tu negocio')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('company_name')
                                                ->label('Nombre de la Empresa')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(2)
                                                ->prefixIcon('heroicon-o-building-office-2')
                                                ->placeholder('Ej: Litografía Moderna S.A.S.'),

                                            TextInput::make('company_email')
                                                ->label('Email Corporativo')
                                                ->email()
                                                ->required()
                                                ->columnSpan(1)
                                                ->prefixIcon('heroicon-o-envelope')
                                                ->placeholder('contacto@tuempresa.com'),

                                            TextInput::make('company_phone')
                                                ->label('Teléfono')
                                                ->tel()
                                                ->required()
                                                ->columnSpan(1)
                                                ->prefixIcon('heroicon-o-phone')
                                                ->placeholder('+57 300 123 4567'),

                                            TextInput::make('tax_id')
                                                ->label('NIT / RUT')
                                                ->required()
                                                ->unique('companies', 'tax_id')
                                                ->columnSpan(1)
                                                ->prefixIcon('heroicon-o-document-text')
                                                ->placeholder('000000000-0'),

                                            Select::make('company_type')
                                                ->label('Tipo de Empresa')
                                                ->required()
                                                ->options([
                                                    'litografia' => 'Litografía',
                                                    'papeleria' => 'Papelería',
                                                ])
                                                ->default('litografia')
                                                ->native(false)
                                                ->columnSpan(1)
                                                ->prefixIcon('heroicon-o-tag'),

                                            Textarea::make('company_address')
                                                ->label('Dirección Completa')
                                                ->required()
                                                ->rows(3)
                                                ->columnSpan(2)
                                                ->placeholder('Calle 123 #45-67, Edificio XYZ, Oficina 101'),
                                        ]),
                                ]),
                        ]),

                    // Step 2: User Information
                    Wizard\Step::make('Usuario')
                        ->description('Crea tu cuenta de administrador')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            $this->getNameFormComponent()
                                                ->columnSpan(2),

                                            $this->getEmailFormComponent()
                                                ->columnSpan(2),

                                            $this->getPasswordFormComponent()
                                                ->columnSpan(1),

                                            $this->getPasswordConfirmationFormComponent()
                                                ->columnSpan(1),
                                        ]),
                                ]),
                        ]),

                    // Step 3: Plan Selection
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
                ]),
            ]);
    }

    protected function handleRegistration(array $data): User
    {
        DB::beginTransaction();

        try {
            $selectedPlan = Plan::findOrFail($data['plan_id']);

            // Create company
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
                'subscription_expires_at' => $selectedPlan->price == 0 ? null : now()->addMonth(),
                'max_users' => $selectedPlan->max_users ?? 5,
            ]);

            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
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
                ->title('¡Bienvenido a LitoPro!')
                ->body('Tu cuenta ha sido creada exitosamente.')
                ->send();

            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
