<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Handle a Stripe webhook call.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();

        if (! $this->isIncomingFromStripe($payload, $request->header('Stripe-Signature'))) {
            return response('Webhook signature verification failed.', 400);
        }

        $payload = json_decode($payload, true);

        $method = 'handle'.str_replace('_', '', ucwords($payload['type'], '_'));

        if (method_exists($this, $method)) {
            return $this->{$method}($payload);
        }

        return $this->missingMethod($payload);
    }

    /**
     * Handle customer subscription created.
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $data = $payload['data']['object'];

        // Log the subscription creation
        \Log::info('Subscription created via webhook', [
            'customer_id' => $data['customer'],
            'subscription_id' => $data['id'],
            'status' => $data['status']
        ]);

        return $this->successMethod();
    }

    /**
     * Handle customer subscription updated.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $data = $payload['data']['object'];

        // Log the subscription update
        \Log::info('Subscription updated via webhook', [
            'customer_id' => $data['customer'],
            'subscription_id' => $data['id'],
            'status' => $data['status']
        ]);

        return $this->successMethod();
    }

    /**
     * Handle customer subscription deleted.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $data = $payload['data']['object'];

        // Log the subscription deletion
        \Log::info('Subscription deleted via webhook', [
            'customer_id' => $data['customer'],
            'subscription_id' => $data['id'],
            'status' => $data['status']
        ]);

        return $this->successMethod();
    }

    /**
     * Handle invoice payment succeeded.
     */
    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        $data = $payload['data']['object'];

        // Log successful payment
        \Log::info('Invoice payment succeeded', [
            'customer_id' => $data['customer'],
            'invoice_id' => $data['id'],
            'amount_paid' => $data['amount_paid'],
            'currency' => $data['currency']
        ]);

        return $this->successMethod();
    }

    /**
     * Handle invoice payment failed.
     */
    protected function handleInvoicePaymentFailed(array $payload)
    {
        $data = $payload['data']['object'];

        // Log failed payment
        \Log::error('Invoice payment failed', [
            'customer_id' => $data['customer'],
            'invoice_id' => $data['id'],
            'amount_due' => $data['amount_due'],
            'currency' => $data['currency']
        ]);

        // TODO: Notify the customer about failed payment

        return $this->successMethod();
    }

    /**
     * Verify the Stripe webhook signature.
     */
    protected function isIncomingFromStripe($payload, $signature)
    {
        if (empty($signature)) {
            return false;
        }

        try {
            \Stripe\WebhookSignature::verifyHeader(
                $payload,
                $signature,
                config('services.stripe.webhook.secret')
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Handle calls to missing webhook methods.
     */
    protected function missingMethod(array $payload = [])
    {
        // Log unhandled webhook types
        \Log::info('Unhandled webhook type: ' . ($payload['type'] ?? 'unknown'));

        return response('Webhook received but not handled.', 200);
    }

    /**
     * Handle a successful webhook call.
     */
    protected function successMethod()
    {
        return response('Webhook handled successfully.', 200);
    }
}
