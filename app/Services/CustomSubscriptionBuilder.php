<?php

namespace App\Services;

use Laravel\Cashier\SubscriptionBuilder;

class CustomSubscriptionBuilder extends SubscriptionBuilder
{
    /**
     * Create a new Stripe subscription.
     */
    public function create($paymentMethod = null, array $customerOptions = [], array $subscriptionOptions = [])
    {
        if (! $this->customer->hasStripeId()) {
            $this->customer->createAsStripeCustomer($customerOptions);
        }

        $stripeSubscription = $this->customer->stripe()->subscriptions->create(array_filter([
            'customer' => $this->customer->stripeId(),
            'items' => $this->buildPayload(),
            'trial_period_days' => $this->trialDays,
            'trial_end' => $this->trialUntil?->getTimestamp(),
            'default_payment_method' => $paymentMethod,
            'metadata' => $this->metadata,
        ] + $subscriptionOptions));

        $subscription = $this->customer->subscriptions()->create([
            'name' => $this->name,
            'stripe_id' => $stripeSubscription->id,
            'stripe_status' => $stripeSubscription->status,
            'stripe_price' => $stripeSubscription->items->data[0]->price->id,
            'quantity' => $stripeSubscription->items->data[0]->quantity ?? null,
            'trial_ends_at' => $this->trialUntil,
            'ends_at' => null,
            'company_id' => $this->customer->id, // Agregar company_id
        ]);

        foreach ($stripeSubscription->items->data as $item) {
            $subscription->items()->create([
                'stripe_id' => $item->id,
                'stripe_product' => $item->price->product,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity ?? null,
                'company_id' => $this->customer->id, // Agregar company_id
            ]);
        }

        return $subscription;
    }
}
