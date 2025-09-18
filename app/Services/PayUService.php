<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class PayUService
{
    protected $client;
    protected $apiKey;
    protected $merchantId;
    protected $accountId;
    protected $apiLogin;
    protected $baseUrl;
    protected $environment;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('payu.api_key');
        $this->merchantId = config('payu.merchant_id');
        $this->accountId = config('payu.account_id');
        $this->apiLogin = config('payu.api_login');
        $this->baseUrl = config('payu.base_url');
        $this->environment = config('payu.environment');
    }

    /**
     * Create a payment subscription
     */
    public function createSubscription(array $data)
    {
        try {
            $payload = [
                'language' => 'es',
                'command' => 'SUBMIT_TRANSACTION',
                'merchant' => [
                    'apiKey' => $this->apiKey,
                    'apiLogin' => $this->apiLogin,
                ],
                'transaction' => [
                    'order' => [
                        'accountId' => $this->accountId,
                        'referenceCode' => $data['reference_code'],
                        'description' => $data['description'],
                        'language' => 'es',
                        'signature' => $this->generateSignature($data),
                        'notifyUrl' => route('payu.webhook'),
                        'additionalValues' => [
                            'TX_VALUE' => [
                                'value' => $data['amount'],
                                'currency' => 'COP'
                            ]
                        ],
                        'buyer' => [
                            'merchantBuyerId' => $data['buyer_id'],
                            'fullName' => $data['buyer_name'],
                            'emailAddress' => $data['buyer_email'],
                            'contactPhone' => $data['buyer_phone'] ?? '',
                            'dniNumber' => $data['buyer_dni'] ?? '',
                            'shippingAddress' => [
                                'street1' => $data['buyer_address'] ?? '',
                                'city' => $data['buyer_city'] ?? '',
                                'state' => $data['buyer_state'] ?? '',
                                'country' => 'CO',
                                'postalCode' => $data['buyer_postal_code'] ?? '',
                                'phone' => $data['buyer_phone'] ?? ''
                            ]
                        ]
                    ],
                    'payer' => [
                        'merchantPayerId' => $data['buyer_id'],
                        'fullName' => $data['buyer_name'],
                        'emailAddress' => $data['buyer_email'],
                        'contactPhone' => $data['buyer_phone'] ?? '',
                        'dniNumber' => $data['buyer_dni'] ?? '',
                        'billingAddress' => [
                            'street1' => $data['buyer_address'] ?? '',
                            'city' => $data['buyer_city'] ?? '',
                            'state' => $data['buyer_state'] ?? '',
                            'country' => 'CO',
                            'postalCode' => $data['buyer_postal_code'] ?? '',
                            'phone' => $data['buyer_phone'] ?? ''
                        ]
                    ],
                    'creditCard' => [
                        'number' => $data['card_number'],
                        'securityCode' => $data['card_cvv'],
                        'expirationDate' => $data['card_expiry'],
                        'name' => $data['card_holder_name']
                    ],
                    'extraParameters' => [
                        'INSTALLMENTS_NUMBER' => 1
                    ],
                    'type' => 'AUTHORIZATION_AND_CAPTURE',
                    'paymentMethod' => $data['payment_method'] ?? 'VISA',
                    'paymentCountry' => 'CO',
                    'deviceSessionId' => $data['device_session_id'] ?? uniqid(),
                    'ipAddress' => request()->ip(),
                    'cookie' => 'pt1t38347bs6jc9ruv2ecpv7o2',
                    'userAgent' => request()->header('User-Agent')
                ],
                'test' => $this->environment === 'sandbox'
            ];

            $response = $this->client->post($this->baseUrl . '/payments-api/4.0/service.cgi', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            Log::info('PayU Payment Response', $result);

            return $result;

        } catch (RequestException $e) {
            Log::error('PayU Payment Error', [
                'message' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            throw $e;
        }
    }

    /**
     * Generate signature for PayU
     */
    protected function generateSignature(array $data)
    {
        $signature = $this->apiKey . '~' . $this->merchantId . '~' . $data['reference_code'] . '~' . $data['amount'] . '~COP';
        return md5($signature);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature)
    {
        $expectedSignature = md5($this->apiKey . '~' . $this->merchantId . '~' . $payload);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get payment methods available
     */
    public function getPaymentMethods()
    {
        return [
            'VISA' => 'Visa',
            'MASTERCARD' => 'Mastercard',
            'AMEX' => 'American Express',
            'DINERS' => 'Diners',
            'PSE' => 'PSE',
            'EFECTY' => 'Efecty',
            'BALOTO' => 'Baloto'
        ];
    }
}