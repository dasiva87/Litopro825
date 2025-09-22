<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\InvalidRequestException;

class StripeSubscriptionController extends Controller
{
    /**
     * Mostrar página de planes de suscripción
     */
    public function pricing()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('subscription.pricing', compact('plans'));
    }

    /**
     * Iniciar proceso de suscripción
     */
    public function subscribe(Request $request, Plan $plan)
    {
        $user = Auth::user();

        // Verificar si ya tiene suscripción activa
        if ($user->subscribed('default')) {
            return redirect()->route('subscription.manage')
                ->with('warning', 'Ya tienes una suscripción activa.');
        }

        try {
            // Crear checkout session de Stripe
            $checkoutSession = $user->newSubscription('default', $plan->stripe_price_id)
                ->allowPromotionCodes()
                ->trialDays($plan->trial_days ?? 0)
                ->checkout([
                    'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('pricing'),
                    'customer_update' => [
                        'address' => 'auto',
                        'name' => 'auto',
                    ],
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'company_id' => $user->company_id,
                        'user_id' => $user->id,
                    ],
                ]);

            return redirect($checkoutSession->url);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar la suscripción: ' . $e->getMessage());
        }
    }

    /**
     * Página de éxito después del checkout
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('pricing')
                ->with('error', 'Sesión de pago no válida.');
        }

        $user = Auth::user();

        // Verificar que la suscripción fue creada
        if ($user->subscribed('default')) {
            $subscription = $user->subscription('default');

            return view('subscription.success', [
                'subscription' => $subscription,
                'plan' => Plan::where('stripe_price_id', $subscription->stripe_price)->first(),
            ]);
        }

        return redirect()->route('pricing')
            ->with('error', 'No se pudo verificar tu suscripción.');
    }

    /**
     * Página de gestión de suscripción
     */
    public function manage()
    {
        $user = Auth::user();

        if (!$user->subscribed('default')) {
            return redirect()->route('pricing')
                ->with('info', 'No tienes una suscripción activa.');
        }

        $subscription = $user->subscription('default');
        $currentPlan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();
        $availablePlans = Plan::where('is_active', true)
            ->where('stripe_price_id', '!=', $subscription->stripe_price)
            ->orderBy('sort_order')
            ->get();

        return view('subscription.manage', [
            'subscription' => $subscription,
            'currentPlan' => $currentPlan,
            'availablePlans' => $availablePlans,
        ]);
    }

    /**
     * Cambiar plan de suscripción
     */
    public function changePlan(Request $request, Plan $newPlan)
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->active()) {
            return back()->with('error', 'No tienes una suscripción activa.');
        }

        try {
            // Cambiar al nuevo plan con proration
            $subscription->swapAndInvoice($newPlan->stripe_price_id);

            return back()->with('success', "Tu plan ha sido actualizado a {$newPlan->name}.");

        } catch (IncompletePayment $e) {
            return redirect()->route('cashier.payment', [
                $e->payment->id,
                'redirect' => route('subscription.manage')
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cambiar el plan: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar suscripción
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->active()) {
            return back()->with('error', 'No tienes una suscripción activa para cancelar.');
        }

        try {
            if ($request->has('immediately')) {
                // Cancelar inmediatamente
                $subscription->cancelNow();
                $message = 'Tu suscripción ha sido cancelada inmediatamente.';
            } else {
                // Cancelar al final del período
                $subscription->cancel();
                $message = 'Tu suscripción será cancelada al final del período actual.';
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al cancelar la suscripción: ' . $e->getMessage());
        }
    }

    /**
     * Reanudar suscripción cancelada
     */
    public function resume()
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->cancelled()) {
            return back()->with('error', 'No hay una suscripción cancelada para reanudar.');
        }

        try {
            $subscription->resume();

            return back()->with('success', 'Tu suscripción ha sido reanudada exitosamente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al reanudar la suscripción: ' . $e->getMessage());
        }
    }

    /**
     * Descargar factura
     */
    public function downloadInvoice(Request $request, $invoiceId)
    {
        $user = Auth::user();

        try {
            return $user->downloadInvoice($invoiceId, [
                'vendor' => config('app.name'),
                'product' => 'Suscripción LitoPro',
                'street' => $user->company->address ?? '',
                'location' => $user->company->city ?? '',
                'phone' => $user->company->phone ?? '',
                'vendorVat' => 'NIT: ' . ($user->company->nit ?? ''),
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al descargar la factura: ' . $e->getMessage());
        }
    }

    /**
     * Portal de facturación (Stripe Customer Portal)
     */
    public function billingPortal()
    {
        $user = Auth::user();

        if (!$user->subscribed('default')) {
            return redirect()->route('pricing')
                ->with('info', 'Necesitas una suscripción activa para acceder al portal de facturación.');
        }

        try {
            return $user->redirectToBillingPortal(route('subscription.manage'));

        } catch (\Exception $e) {
            return back()->with('error', 'Error al acceder al portal de facturación: ' . $e->getMessage());
        }
    }
}