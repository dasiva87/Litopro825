<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\Plan;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class RegistrationController extends Controller
{
    /**
     * Show the registration form
     */
    public function create()
    {
        $countries = Country::all();
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('auth.register', compact('countries', 'plans'));
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
            'company_phone' => ['required', 'string', 'max:20'],
            'company_address' => ['required', 'string', 'max:500'],
            'country_id' => ['required', 'exists:countries,id'],
            'state_id' => ['required', 'exists:states,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'tax_id' => ['required', 'string', 'max:50'],
            'company_type' => ['required', 'in:litografia,papeleria'],

            // Datos del usuario administrador
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            // Plan seleccionado
            'plan_id' => ['required', 'exists:plans,id'],

            // Términos y condiciones
            'terms' => ['accepted'],
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear empresa (inactiva, pendiente de pago)
            $company = Company::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name),
                'email' => $request->company_email,
                'phone' => $request->company_phone,
                'address' => $request->company_address,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'tax_id' => $request->tax_id,
                'company_type' => $request->company_type,
                'status' => 'pending',
                'is_active' => false,
                'subscription_plan' => 'free',
                'max_users' => 1, // Temporal hasta activación
            ]);

            // 2. Crear usuario administrador
            // No usar Hash::make() - el modelo User tiene 'password' => 'hashed' en casts()
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'company_id' => $company->id,
                'email_verified_at' => now(), // Auto-verificar email de admin
            ]);

            // 3. Asignar rol Company Admin
            $companyAdminRole = Role::where('name', 'Company Admin')->first();
            if ($companyAdminRole) {
                $user->assignRole($companyAdminRole);
            }

            // 4. Autenticar usuario para el proceso de pago
            Auth::login($user);

            DB::commit();

            // 5. Redirigir a selección de plan con pago
            $plan = Plan::findOrFail($request->plan_id);

            if ($plan->price == 0) {
                // Plan gratuito - activar inmediatamente
                return $this->activateFreePlan($company, $plan);
            } else {
                // Plan de pago - redirigir a PayU
                return $this->redirectToPayU($company, $plan);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'registration' => 'Error al crear la cuenta: '.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Activate free plan immediately
     */
    protected function activateFreePlan(Company $company, Plan $plan)
    {
        $company->update([
            'subscription_plan' => 'free', // Usar valor enum válido
            'subscription_expires_at' => null, // Plan gratuito no expira
            'status' => 'active',
            'is_active' => true,
            'max_users' => $this->getPlanMaxUsers($plan),
        ]);

        // Crear suscripción gratuita en la tabla subscriptions
        \App\Models\Subscription::create([
            'company_id' => $company->id,
            'user_id' => Auth::id(),
            'name' => $plan->name,
            'stripe_id' => 'free_' . $company->id . '_' . time(), // ID único para plan gratuito
            'stripe_status' => 'active', // Plan gratuito activo inmediatamente
            'stripe_price' => $plan->stripe_price_id ?? $plan->slug,
            'quantity' => 1,
            'trial_ends_at' => null, // Sin período de prueba
            'ends_at' => null, // Plan gratuito no expira
        ]);

        return redirect()->route('filament.admin.pages.home')->with('success',
            '¡Bienvenido a GrafiRed! Tu cuenta ha sido activada exitosamente.'
        );
    }

    /**
     * Redirect to PayU payment gateway
     */
    protected function redirectToPayU(Company $company, Plan $plan)
    {
        // Generar código de referencia único
        $referenceCode = 'GRAFIRED-'.$company->id.'-'.$plan->id.'-'.time();

        // Preparar datos para PayU
        $paymentData = [
            'reference_code' => $referenceCode,
            'description' => "Suscripción {$plan->name} - {$company->name}",
            'amount' => $plan->price,
            'buyer_id' => $company->id,
            'buyer_name' => Auth::user()->name,
            'buyer_email' => Auth::user()->email,
            'buyer_phone' => $company->phone,
            'buyer_address' => $company->address,
            'buyer_city' => $company->city->name ?? '',
            'buyer_state' => $company->state->name ?? '',
        ];

        // Generar URL de PayU
        $checkoutUrl = $this->generatePayUCheckoutUrl($paymentData);

        // Guardar referencia temporal para el webhook
        session(['registration_reference' => $referenceCode]);

        return redirect()->away($checkoutUrl);
    }

    /**
     * Generate PayU checkout URL
     */
    protected function generatePayUCheckoutUrl(array $data): string
    {
        $baseUrl = config('payu.payments_url');
        $merchantId = config('payu.merchant_id');
        $accountId = config('payu.account_id');
        $apiKey = config('payu.api_key');

        // Generar signature para PayU
        $signature = md5($apiKey.'~'.$merchantId.'~'.$data['reference_code'].'~'.$data['amount'].'~COP');

        $params = [
            'merchantId' => $merchantId,
            'accountId' => $accountId,
            'description' => $data['description'],
            'referenceCode' => $data['reference_code'],
            'amount' => $data['amount'],
            'currency' => 'COP',
            'signature' => $signature,
            'test' => config('payu.environment') === 'sandbox' ? '1' : '0',
            'buyerEmail' => $data['buyer_email'],
            'buyerFullName' => $data['buyer_name'],
            'telephone' => $data['buyer_phone'],
            'shippingAddress' => $data['buyer_address'],
            'shippingCity' => $data['buyer_city'],
            'shippingCountry' => 'CO',
            'responseUrl' => route('registration.payment-response'),
            'confirmationUrl' => route('payu.webhook'),
        ];

        return $baseUrl.'/ppp-web-gateway-payu/?'.http_build_query($params);
    }

    /**
     * Handle PayU payment response
     */
    public function paymentResponse(Request $request)
    {
        $transactionState = $request->get('transactionState');
        $referenceCode = $request->get('referenceCode');

        switch ($transactionState) {
            case '4': // APPROVED
                return redirect()->route('registration.success')->with('success',
                    '¡Pago exitoso! Tu cuenta será activada en unos momentos.'
                );

            case '6': // DECLINED
                return redirect()->route('registration.failed')->with('error',
                    'El pago fue rechazado. Por favor intenta con otro método de pago.'
                );

            case '7': // PENDING
                return redirect()->route('registration.pending')->with('info',
                    'Tu pago está siendo procesado. Te notificaremos cuando sea confirmado.'
                );

            default:
                return redirect()->route('registration.failed')->with('error',
                    'Error en el proceso de pago. Por favor contacta soporte.'
                );
        }
    }

    /**
     * Show registration success page
     */
    public function success()
    {
        return view('auth.registration-success');
    }

    /**
     * Show registration pending page
     */
    public function pending()
    {
        return view('auth.registration-pending');
    }

    /**
     * Show registration failed page
     */
    public function failed()
    {
        return view('auth.registration-failed');
    }

    /**
     * Get max users for a plan
     */
    protected function getPlanMaxUsers(Plan $plan): int
    {
        $limits = $plan->limits ?? [];

        foreach ($limits as $limit) {
            if (isset($limit['feature']) && $limit['feature'] === 'max_users') {
                return (int) ($limit['limit'] ?? 3);
            }
        }

        return 3; // Default
    }

    /**
     * Get states by country (AJAX)
     */
    public function getStates(Request $request)
    {
        $states = State::where('country_id', $request->country_id)->get();

        return response()->json($states);
    }

    /**
     * Get cities by state (AJAX)
     */
    public function getCities(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->get();

        return response()->json($cities);
    }
}
