<x-filament-panels::page>
    @php
        $viewData = $this->getViewData();
        $company = $viewData['company'];
        $plans = $viewData['plans'];
        $currentPlan = $viewData['currentPlan'];
        $hasActiveSubscription = $viewData['hasActiveSubscription'];
        $subscriptionExpiresAt = $viewData['subscriptionExpiresAt'];
        $daysRemaining = $subscriptionExpiresAt ? now()->diffInDays($subscriptionExpiresAt, false) : 0;
    @endphp

    <style>
        .billing-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);
        }
        .billing-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .billing-header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .billing-badge-active {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: rgba(34, 197, 94, 0.2);
            color: #bbf7d0;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .billing-badge-inactive {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: rgba(239, 68, 68, 0.2);
            color: #fecaca;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .billing-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
        }
        .billing-dot-green {
            background: #4ade80;
            animation: pulse 2s infinite;
        }
        .billing-dot-red {
            background: #f87171;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .plan-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            transition: all 0.2s ease;
            position: relative;
        }
        .plan-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        .plan-card-current {
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .plan-badge {
            position: absolute;
            top: -0.75rem;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .plan-badge-current {
            background: #3b82f6;
            color: white;
        }
        .plan-badge-popular {
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
            color: white;
        }
        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #111827;
        }
        .plan-interval {
            color: #6b7280;
            font-size: 1rem;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
            flex-grow: 1;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #4b5563;
        }
        .feature-check {
            width: 1.25rem;
            height: 1.25rem;
            background: #dcfce7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }
        .feature-check svg {
            width: 0.75rem;
            height: 0.75rem;
            color: #16a34a;
        }
        .current-plan-indicator {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-align: center;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .info-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }
        .info-icon {
            width: 3rem;
            height: 3rem;
            background: #dbeafe;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .info-icon svg {
            width: 1.5rem;
            height: 1.5rem;
            color: #2563eb;
        }
        .payment-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: #f3f4f6;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #4b5563;
        }
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .faq-item h5 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        .faq-item p {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }
        .faq-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1.5rem;
        }
        .dark .plan-card {
            background: #1f2937;
            border-color: #374151;
        }
        .dark .plan-card:hover {
            border-color: #60a5fa;
        }
        .dark .plan-card-current {
            background: rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }
        .dark .plan-price {
            color: white;
        }
        .dark .feature-item {
            color: #d1d5db;
        }
        .dark .info-card {
            background: #1f2937;
            border-color: #374151;
        }
        .dark .faq-card {
            background: #1f2937;
            border-color: #374151;
        }
        .dark .faq-item h5 {
            color: #e5e7eb;
        }
        .dark .faq-item p {
            color: #9ca3af;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Header con Estado de Suscripción -->
        <div class="billing-header">
            <div style="position: relative; z-index: 1;">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
                    <div>
                        <p style="font-size: 1rem; opacity: 0.9; margin: 0;">Tu Suscripción</p>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem; flex-wrap: wrap;">
                            <span style="font-size: 1.875rem; font-weight: 700;">{{ $currentPlan ? $currentPlan->name : 'Sin Plan' }}</span>
                            @if($hasActiveSubscription)
                                <span class="billing-badge-active">
                                    <span class="billing-dot billing-dot-green"></span>
                                    Activa
                                </span>
                            @else
                                <span class="billing-badge-inactive">
                                    <span class="billing-dot billing-dot-red"></span>
                                    Inactiva
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($hasActiveSubscription && $subscriptionExpiresAt)
                        <div style="text-align: right;">
                            <p style="font-size: 0.875rem; opacity: 0.8; margin: 0;">Válida hasta</p>
                            <p style="font-size: 1.25rem; font-weight: 600; margin: 0.25rem 0 0 0;">{{ $subscriptionExpiresAt->format('d M, Y') }}</p>
                            @if($daysRemaining > 0 && $daysRemaining <= 30)
                                <p style="font-size: 0.875rem; color: #fcd34d; margin: 0.25rem 0 0 0;">
                                    {{ $daysRemaining }} días restantes
                                </p>
                            @elseif($daysRemaining <= 0)
                                <p style="font-size: 0.875rem; color: #fca5a5; margin: 0.25rem 0 0 0;">
                                    Expirada
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                @if($hasActiveSubscription)
                    <div style="margin-top: 1rem;">
                        {{ $this->cancelSubscriptionAction }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Planes Disponibles -->
        <div>
            <div style="margin-bottom: 1rem;">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">Planes Disponibles</h3>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">Elige el plan que mejor se adapte a tus necesidades</p>
            </div>

            <div class="plans-grid">
                @foreach($plans as $plan)
                    @php
                        $isCurrentPlan = $currentPlan && $currentPlan->id === $plan->id;
                        $isPopular = $plan->slug === 'professional' || $plan->slug === 'pro';
                    @endphp

                    <div class="plan-card {{ $isCurrentPlan ? 'plan-card-current' : '' }}">
                        @if($isCurrentPlan)
                            <span class="plan-badge plan-badge-current">Plan Actual</span>
                        @elseif($isPopular)
                            <span class="plan-badge plan-badge-popular">Popular</span>
                        @endif

                        <div style="text-align: center; padding-top: {{ ($isCurrentPlan || $isPopular) ? '0.5rem' : '0' }};">
                            <h4 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">{{ $plan->name }}</h4>
                            <div style="margin-top: 0.75rem;">
                                @if($plan->isFree())
                                    <span class="plan-price">Gratis</span>
                                @else
                                    <span class="plan-price">${{ number_format($plan->price, 0) }}</span>
                                    <span class="plan-interval">/{{ $plan->interval === 'month' ? 'mes' : 'año' }}</span>
                                @endif
                            </div>
                            @if($plan->description)
                                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">{{ $plan->description }}</p>
                            @endif
                        </div>

                        <ul class="feature-list">
                            @php
                                $features = is_array($plan->features) ? $plan->features : [];
                            @endphp
                            @foreach($features as $feature)
                                <li class="feature-item">
                                    <span class="feature-check">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                    <span>{{ is_array($feature) ? ($feature['name'] ?? 'Característica') : $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div>
                            @if($isCurrentPlan)
                                <div class="current-plan-indicator">
                                    <svg style="width: 1.25rem; height: 1.25rem;" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Tu plan actual
                                </div>
                            @elseif(!$hasActiveSubscription || $plan->isFree())
                                <div style="width: 100%;" class="[&_.fi-btn]:w-full">
                                    {{ ($this->subscribeToAction)(['plan' => $plan->id]) }}
                                </div>
                            @else
                                <button disabled style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; background: #f3f4f6; color: #9ca3af; border-radius: 0.5rem; font-size: 0.875rem; cursor: not-allowed;">
                                    Cambio de plan próximamente
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Información de Pagos -->
        @if($hasActiveSubscription || $plans->count() > 0)
            <div class="info-card">
                <div class="info-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div style="flex: 1;">
                    <h4 style="font-weight: 600; color: #111827; margin: 0;">Pagos Seguros con PayU</h4>
                    <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">
                        Todos los pagos son procesados de forma segura a través de PayU, la plataforma de pagos líder en Latinoamérica.
                        Aceptamos tarjetas de crédito, débito, PSE y otros medios de pago locales.
                    </p>
                    <div class="payment-badges">
                        <span class="payment-badge">
                            <svg style="width: 0.875rem; height: 0.875rem;" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                            </svg>
                            Visa / Mastercard
                        </span>
                        <span class="payment-badge">
                            <svg style="width: 0.875rem; height: 0.875rem;" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 1h6v4H7V5zm8 8v2h1v-2h-1zm-2-2H7v4h6v-4zm2 0h1V9h-1v2zm1-4V5h-1v2h1zM5 5v2H4V5h1zm0 4H4v2h1V9zm-1 4h1v2H4v-2z" clip-rule="evenodd"/>
                            </svg>
                            PSE
                        </span>
                        <span class="payment-badge">
                            <svg style="width: 0.875rem; height: 0.875rem;" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            Cifrado SSL
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- FAQ Rápido -->
        <div class="faq-card">
            <h4 style="font-weight: 600; color: #111827; margin: 0 0 1rem 0;">Preguntas Frecuentes</h4>
            <div class="faq-grid">
                <div class="faq-item">
                    <h5>¿Puedo cancelar en cualquier momento?</h5>
                    <p>Sí, puedes cancelar tu suscripción cuando quieras. Mantendrás el acceso hasta el final del período pagado.</p>
                </div>
                <div class="faq-item">
                    <h5>¿Cómo cambio de plan?</h5>
                    <p>Próximamente podrás cambiar de plan directamente desde esta página. Por ahora, contacta a soporte.</p>
                </div>
                <div class="faq-item">
                    <h5>¿Qué métodos de pago aceptan?</h5>
                    <p>Aceptamos tarjetas Visa, Mastercard, American Express, PSE y efectivo en puntos autorizados.</p>
                </div>
                <div class="faq-item">
                    <h5>¿Necesitas ayuda?</h5>
                    <p>Escríbenos a soporte@grafired.com y te ayudaremos con cualquier duda sobre tu suscripción.</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
