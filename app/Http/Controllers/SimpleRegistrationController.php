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
                'status' => 'incomplete', // Estado inicial para perfil incompleto
                'is_active' => true,
                'subscription_plan' => 'free', // Plan inicial gratuito
                'subscription_expires_at' => now()->addMonth(), // Plan gratuito por 1 mes
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

            DB::commit();

            // Autenticar al usuario automáticamente
            Auth::login($user);

            // Redirigir al dashboard con mensaje de bienvenida
            return redirect()->route('filament.admin.pages.dashboard')
                ->with('registration_success', true)
                ->with('company_incomplete', true);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'general' => 'Error al crear la cuenta: '.$e->getMessage(),
            ]);
        }
    }
}
