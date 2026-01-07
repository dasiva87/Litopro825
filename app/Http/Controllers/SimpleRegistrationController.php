<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class SimpleRegistrationController extends Controller
{
    /**
     * Show the registration form (redirects to Filament page)
     */
    public function create()
    {
        return redirect()->route('filament.pages.auth.register');
    }

    /**
     * Handle company registration
     */
    public function store(Request $request)
    {
        $request->validate([
            // Datos de la empresa
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'string', 'email', 'max:255'],
            'company_phone' => ['required', 'string', 'max:50'],
            'tax_id' => ['required', 'string', 'max:50', 'unique:companies,tax_id'],
            'company_type' => ['required', 'in:litografia,papeleria'],
            'company_address' => ['required', 'string', 'max:500'],

            // Datos del usuario administrador
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            // Plan seleccionado
            'plan_id' => ['required', 'exists:plans,id'],

            // Términos y condiciones
            'terms' => ['required', 'accepted'],
        ]);

        try {
            DB::beginTransaction();

            // Obtener el plan seleccionado
            $selectedPlan = \App\Models\Plan::findOrFail($request->plan_id);

            // Crear la empresa con todos los datos
            $company = Company::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name),
                'email' => $request->company_email,
                'phone' => $request->company_phone,
                'address' => $request->company_address,
                'tax_id' => $request->tax_id,
                'company_type' => $request->company_type,
                'status' => 'active',
                'is_active' => true,
                'subscription_plan' => $selectedPlan->slug,
                'subscription_expires_at' => $selectedPlan->price == 0 ? null : now()->addMonth(),
                'max_users' => $selectedPlan->max_users ?? 5,
                // Campos de ubicación opcionales
                'country_id' => null,
                'state_id' => null,
                'city_id' => null,
            ]);

            // Crear el usuario administrador
            // No usar Hash::make() - el modelo User tiene 'password' => 'hashed' en casts()
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'company_id' => $company->id,
                'is_active' => true,
                'email_verified_at' => now(), // Auto-verificar para simplificar
            ]);

            // Asignar rol de Company Admin
            $adminRole = Role::where('name', 'Company Admin')->first();
            if ($adminRole) {
                $user->assignRole($adminRole);
            }

            // Crear suscripción según el plan seleccionado
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

            // Autenticar al usuario automáticamente
            Auth::login($user);

            // Redirigir al home con mensaje de bienvenida
            return redirect()->route('filament.admin.pages.home')
                ->with('registration_success', true)
                ->with('welcome_message', '¡Bienvenido a GrafiRed! Tu cuenta gratuita está lista para usar.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'general' => 'Error al crear la cuenta: '.$e->getMessage(),
            ]);
        }
    }
}
