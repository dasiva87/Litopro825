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
     * Show the simplified registration form
     */
    public function create()
    {
        return view('auth.register-simple');
    }

    /**
     * Handle simplified company registration
     */
    public function store(Request $request)
    {
        $request->validate([
            // Datos mínimos de la empresa
            'company_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:50', 'unique:companies,tax_id'],
            'company_type' => ['required', 'in:litografia,papeleria'],

            // Datos mínimos del usuario administrador
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            // Términos y condiciones
            'terms' => ['required', 'accepted'],
        ]);

        try {
            DB::beginTransaction();

            // Crear la empresa con estado 'incomplete'
            $company = Company::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name),
                'tax_id' => $request->tax_id,
                'company_type' => $request->company_type,
                'status' => 'active', // Empresa activa con plan gratuito
                'is_active' => true,
                'subscription_plan' => 'free', // Plan inicial gratuito
                'subscription_expires_at' => null, // Plan gratuito no expira
                'max_users' => 5, // Límite inicial para plan gratuito
                // Campos opcionales se llenarán después
                'email' => null,
                'phone' => null,
                'address' => null,
                'country_id' => null,
                'state_id' => null,
                'city_id' => null,
            ]);

            // Crear el usuario administrador
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $company->id,
                'is_active' => true,
                'email_verified_at' => now(), // Auto-verificar para simplificar
            ]);

            // Asignar rol de Company Admin
            $adminRole = Role::where('name', 'Company Admin')->first();
            if ($adminRole) {
                $user->assignRole($adminRole);
            }

            // Crear suscripción gratuita automáticamente
            $freePlan = \App\Models\Plan::where('slug', 'free')->first();
            if ($freePlan) {
                \App\Models\Subscription::create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'name' => $freePlan->name,
                    'stripe_id' => 'free_' . $company->id . '_' . time(), // ID único para plan gratuito
                    'stripe_status' => 'active', // Plan gratuito activo inmediatamente
                    'stripe_price' => $freePlan->stripe_price_id ?? $freePlan->slug,
                    'quantity' => 1,
                    'trial_ends_at' => null, // Sin período de prueba
                    'ends_at' => now()->addYear(), // Plan gratuito válido por 1 año
                ]);
            }

            DB::commit();

            // Autenticar al usuario automáticamente
            Auth::login($user);

            // Redirigir al home con mensaje de bienvenida
            return redirect()->route('filament.admin.pages.home')
                ->with('registration_success', true)
                ->with('welcome_message', '¡Bienvenido a LitoPro! Tu cuenta gratuita está lista para usar.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'general' => 'Error al crear la cuenta: '.$e->getMessage(),
            ]);
        }
    }
}
