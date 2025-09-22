<?php

namespace App\Filament\Pages;

use App\Models\Plan;
use App\Services\PayUService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class Billing extends Page implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Facturación';

    protected static ?string $title = 'Gestión de Facturación';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.billing';

    public function mount(): void
    {
        if (! Auth::user() || ! Auth::user()->company) {
            redirect()->route('filament.admin.auth.login');
        }
    }

    public function getViewData(): array
    {
        $company = Auth::user()->company;
        $plans = Plan::active()->ordered()->get();
        $currentPlan = $company->getCurrentPlan();

        return [
            'company' => $company,
            'plans' => $plans,
            'currentPlan' => $currentPlan,
            'hasActiveSubscription' => $company->hasActiveSubscription(),
            'subscriptionExpiresAt' => $company->subscription_expires_at,
        ];
    }

    public function subscribeToAction(): Action
    {
        return Action::make('subscribeTo')
            ->label('Suscribirse')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Confirmar Suscripción')
            ->modalDescription('¿Estás seguro de que quieres suscribirte a este plan?')
            ->action(function (array $arguments) {
                $planId = $arguments['plan'];
                $plan = Plan::findOrFail($planId);

                try {
                    $company = Auth::user()->company;
                    $user = Auth::user();

                    // Si es plan gratuito, procesarlo directamente sin PayU
                    if ($plan->isFree()) {
                        // Activar suscripción gratuita inmediatamente
                        $company->update([
                            'subscription_plan' => $plan->slug,
                            'subscription_expires_at' => now()->addMonth(), // 1 mes gratuito
                            'is_active' => true,
                        ]);

                        Notification::make()
                            ->title('¡Suscripción Activada!')
                            ->body("Te has suscrito exitosamente al {$plan->name}. ¡Disfruta de LitoPro!")
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.pages.billing');
                    }

                    // Para planes de pago, continuar con PayU
                    // Generar código de referencia único
                    $referenceCode = 'LITOPRO-'.$company->id.'-'.$plan->id.'-'.time();

                    // Preparar datos para PayU
                    $paymentData = [
                        'reference_code' => $referenceCode,
                        'description' => "Suscripción {$plan->name} - {$company->name}",
                        'amount' => $plan->price,
                        'buyer_id' => $company->id,
                        'buyer_name' => $user->name,
                        'buyer_email' => $user->email,
                        'buyer_phone' => $company->phone ?? '',
                        'buyer_address' => $company->address ?? '',
                        'buyer_city' => $company->city->name ?? '',
                        'buyer_state' => $company->state->name ?? '',
                    ];

                    // Redirigir a página de pago PayU
                    $payuService = app(PayUService::class);
                    $checkoutUrl = $this->generatePayUCheckoutUrl($paymentData);

                    Notification::make()
                        ->title('Redirigiendo a PayU')
                        ->body('Serás redirigido a la página de pago de PayU para completar tu suscripción.')
                        ->info()
                        ->send();

                    // En lugar de procesar aquí, redirigir a PayU
                    return redirect()->away($checkoutUrl);

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error al Procesar Suscripción')
                        ->body('Hubo un problema al procesar tu suscripción: '.$e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function cancelSubscriptionAction(): Action
    {
        return Action::make('cancelSubscription')
            ->label('Cancelar Suscripción')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancelar Suscripción')
            ->modalDescription('¿Estás seguro de que quieres cancelar tu suscripción? Tendrás acceso hasta que expire tu período actual.')
            ->action(function () {
                try {
                    $company = Auth::user()->company;

                    // Simplemente limpiar la suscripción - PayU no maneja cancelaciones automáticas
                    $company->update([
                        'subscription_plan' => 'free',
                        'subscription_expires_at' => now()->addDays(30), // Gracia de 30 días
                    ]);

                    Notification::make()
                        ->title('Suscripción Cancelada')
                        ->body('Tu suscripción ha sido cancelada. Tendrás acceso por 30 días más.')
                        ->warning()
                        ->send();

                    return redirect()->route('filament.admin.pages.billing');

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error al Cancelar Suscripción')
                        ->body('Hubo un problema al cancelar tu suscripción: '.$e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Generar URL de checkout de PayU
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
            'responseUrl' => route('filament.admin.pages.billing'),
            'confirmationUrl' => route('payu.webhook'),
        ];

        return $baseUrl.'/ppp-web-gateway-payu/?'.http_build_query($params);
    }
}
