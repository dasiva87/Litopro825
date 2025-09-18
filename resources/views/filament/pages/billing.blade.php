<x-filament-panels::page>
    @php
        $viewData = $this->getViewData();
        $company = $viewData['company'];
        $plans = $viewData['plans'];
        $currentPlan = $viewData['currentPlan'];
        $hasActiveSubscription = $viewData['hasActiveSubscription'];
        $subscriptionExpiresAt = $viewData['subscriptionExpiresAt'];
    @endphp

    <!-- Estado de Suscripción Actual -->
    <div class="fi-section fi-card">
        <div class="fi-section-header">
            <h3 class="fi-section-header-heading">
                Estado de Suscripción
            </h3>
        </div>

        <div class="fi-section-content">
            @if($hasActiveSubscription)
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Plan Actual</span>
                        <div style="margin-top: 0.25rem;">
                            <span class="fi-badge fi-color fi-color-blue">
                                {{ $currentPlan ? $currentPlan->name : 'Plan Básico' }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Estado</span>
                        <div style="margin-top: 0.25rem;">
                            @if($hasActiveSubscription)
                                <span class="fi-badge fi-color fi-color-success">
                                    ✓ Activa
                                </span>
                            @else
                                <span class="fi-badge fi-color fi-color-danger">
                                    ✗ Inactiva
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Expira</span>
                        <p style="margin-top: 0.25rem; font-size: 0.875rem;">
                            {{ $subscriptionExpiresAt ? $subscriptionExpiresAt->format('d/m/Y') : 'N/A' }}
                        </p>
                    </div>
                </div>

                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                    @if($hasActiveSubscription)
                        {{ $this->cancelSubscriptionAction }}
                    @endif
                </div>
            @else
                <div style="text-align: center; padding: 2rem 0;">
                    <x-heroicon-o-credit-card style="width: 3rem; height: 3rem; margin: 0 auto; color: #9ca3af;" />
                    <h3 style="margin-top: 0.5rem; font-size: 0.875rem; font-weight: 600;">Sin Suscripción Activa</h3>
                    <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">Selecciona un plan para comenzar a usar todas las funcionalidades de LitoPro.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Planes Disponibles -->
    <div class="fi-section fi-card" style="margin-top: 1.5rem;">
        <div class="fi-section-header">
            <h3 class="fi-section-header-heading">
                Planes Disponibles
            </h3>
        </div>

        <div class="fi-section-content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                @foreach($plans as $plan)
                    <div class="fi-card {{ $currentPlan && $currentPlan->id === $plan->id ? 'fi-selected' : '' }}" style="padding: 1.5rem; {{ $currentPlan && $currentPlan->id === $plan->id ? 'border: 2px solid #3b82f6; background-color: #eff6ff;' : 'border: 1px solid #e5e7eb; background-color: #ffffff;' }}">
                        <div style="text-align: center;">
                            <h3 style="font-size: 1.125rem; font-weight: 700;">{{ $plan->name }}</h3>
                            <div style="margin-top: 0.5rem;">
                                <span style="font-size: 1.5rem; font-weight: 700;">${{ number_format($plan->price, 2) }}</span>
                                <span style="color: #6b7280;">/ {{ $plan->interval === 'month' ? 'mes' : 'año' }}</span>
                            </div>
                        </div>

                        <p style="margin-top: 1rem; font-size: 0.875rem; color: #4b5563;">{{ $plan->description }}</p>

                        <ul style="margin-top: 1.5rem; list-style: none; padding: 0;">
                            @foreach($plan->features as $feature)
                                <li style="display: flex; align-items: center; margin-bottom: 0.5rem; font-size: 0.875rem; color: #4b5563;">
                                    <x-heroicon-o-check style="width: 1rem; height: 1rem; color: #10b981; margin-right: 0.5rem; flex-shrink: 0;" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <div style="margin-top: 1.5rem;">
                            @if(!$currentPlan || $currentPlan->id !== $plan->id)
                                @if(!$hasActiveSubscription)
                                    {{ ($this->subscribeToAction)(['plan' => $plan->id]) }}
                                @else
                                    <button class="fi-btn fi-outlined fi-disabled" disabled style="width: 100%;">
                                        Cambiar Plan (Próximamente)
                                    </button>
                                @endif
                            @else
                                <div style="text-align: center; padding: 0.5rem; color: #2563eb; font-weight: 500;">
                                    ✓ Plan Actual
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Información de PayU -->
    @if($hasActiveSubscription)
        <div class="fi-section fi-card" style="margin-top: 1.5rem;">
            <div class="fi-section-header">
                <h3 class="fi-section-header-heading">
                    Información de Pagos
                </h3>
            </div>

            <div class="fi-section-content">
                <div style="background-color: #f3f4f6; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem;">
                    <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        <x-heroicon-o-information-circle style="width: 1.25rem; height: 1.25rem; color: #3b82f6; margin-right: 0.5rem;" />
                        <span style="font-weight: 600; color: #1f2937;">Procesado por PayU</span>
                    </div>
                    <p style="font-size: 0.875rem; color: #4b5563;">
                        Los pagos son procesados de forma segura por PayU, la plataforma de pagos líder en Colombia.
                        Aceptamos tarjetas de crédito, débito, PSE, Efecty y Baloto.
                    </p>
                </div>

                <div style="text-align: center; padding: 2rem 0; color: #6b7280;">
                    <x-heroicon-o-document-text style="width: 3rem; height: 3rem; margin: 0 auto;" />
                    <p style="margin-top: 0.5rem;">El historial de transacciones estará disponible próximamente.</p>
                    <p style="font-size: 0.875rem; margin-top: 0.25rem;">Podrás consultar todas tus transacciones y recibos desde aquí.</p>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>