<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Company;
use App\Services\PayUService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Carbon\Carbon;

class PayUWebhookController extends Controller
{
    protected $payuService;

    public function __construct(PayUService $payuService)
    {
        $this->payuService = $payuService;
    }

    /**
     * Handle PayU webhook notifications
     */
    public function handle(Request $request)
    {
        Log::info('PayU Webhook Received', $request->all());

        try {
            // Validate webhook signature (if provided)
            $signature = $request->header('X-PayU-Signature');
            if ($signature && !$this->payuService->verifyWebhookSignature($request->getContent(), $signature)) {
                Log::warning('PayU Webhook: Invalid signature');
                return response('Invalid signature', 400);
            }

            $data = $request->all();

            // Handle different webhook events
            switch ($data['state_pol'] ?? '') {
                case 'APPROVED':
                    $this->handlePaymentApproved($data);
                    break;
                case 'DECLINED':
                    $this->handlePaymentDeclined($data);
                    break;
                case 'PENDING':
                    $this->handlePaymentPending($data);
                    break;
                case 'EXPIRED':
                    $this->handlePaymentExpired($data);
                    break;
                default:
                    Log::info('PayU Webhook: Unhandled state', ['state' => $data['state_pol'] ?? 'unknown']);
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('PayU Webhook Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response('Error processing webhook', 500);
        }
    }

    /**
     * Handle approved payments
     */
    protected function handlePaymentApproved(array $data)
    {
        $referenceCode = $data['reference_sale'] ?? '';
        $companyId = $this->extractCompanyIdFromReference($referenceCode);
        $planId = $this->extractPlanIdFromReference($referenceCode);

        if (!$companyId || !$planId) {
            Log::warning('PayU Webhook: Could not extract company/plan from reference', [
                'reference' => $referenceCode
            ]);
            return;
        }

        $company = Company::find($companyId);
        $plan = Plan::find($planId);

        if (!$company || !$plan) {
            Log::warning('PayU Webhook: Company or Plan not found', [
                'company_id' => $companyId,
                'plan_id' => $planId
            ]);
            return;
        }

        // Update company subscription
        $expiresAt = $this->calculateExpirationDate($plan);

        $company->update([
            'subscription_plan' => $plan->name,
            'subscription_expires_at' => $expiresAt,
        ]);

        // Create subscription record (simplified)
        \DB::table('subscriptions')->updateOrInsert(
            [
                'company_id' => $company->id,
                'name' => 'default'
            ],
            [
                'stripe_id' => $data['transaction_id'] ?? '', // Using PayU transaction ID
                'stripe_status' => 'active',
                'stripe_price' => $plan->stripe_price_id,
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Log::info('PayU Webhook: Subscription activated', [
            'company_id' => $company->id,
            'plan' => $plan->name,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Handle declined payments
     */
    protected function handlePaymentDeclined(array $data)
    {
        Log::info('PayU Webhook: Payment declined', $data);
        // Handle payment failure logic here
    }

    /**
     * Handle pending payments
     */
    protected function handlePaymentPending(array $data)
    {
        Log::info('PayU Webhook: Payment pending', $data);
        // Handle pending payment logic here
    }

    /**
     * Handle expired payments
     */
    protected function handlePaymentExpired(array $data)
    {
        Log::info('PayU Webhook: Payment expired', $data);
        // Handle expired payment logic here
    }

    /**
     * Extract company ID from reference code
     */
    protected function extractCompanyIdFromReference(string $reference): ?int
    {
        // Expected format: LITOPRO-{company_id}-{plan_id}-{timestamp}
        $parts = explode('-', $reference);
        return isset($parts[1]) ? (int) $parts[1] : null;
    }

    /**
     * Extract plan ID from reference code
     */
    protected function extractPlanIdFromReference(string $reference): ?int
    {
        // Expected format: LITOPRO-{company_id}-{plan_id}-{timestamp}
        $parts = explode('-', $reference);
        return isset($parts[2]) ? (int) $parts[2] : null;
    }

    /**
     * Calculate subscription expiration date
     */
    protected function calculateExpirationDate(Plan $plan): Carbon
    {
        return $plan->interval === 'year'
            ? now()->addYear()
            : now()->addMonth();
    }
}