<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Services\PesapalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PesapalController extends Controller
{
    protected $pesapalService;

    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    public function initiatePayment(PaymentRequest $request): JsonResponse
    {
        try {
            $paymentData = $this->preparePaymentData($request);
            $oauthHeader = $this->generateOAuthHeader($paymentData);

            $response = $this->pesapalService->initiatePayment($paymentData, $oauthHeader);

            return response()->json([
                'redirect_url' => $response['redirect_url'],
                'order_id' => $response['order_tracking_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Pesapal Payment Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function paymentCallback(Request $request): JsonResponse
    {
        try {
            $trackingId = $request->get('pesapal_transaction_tracking_id');
            $merchantReference = $request->get('pesapal_merchant_reference');

            if (!$trackingId || !$merchantReference) {
                return response()->json(['error' => 'Invalid callback data'], 400);
            }

            $status = $this->pesapalService->getTransactionStatus($trackingId, $merchantReference);

            // Update your database or perform necessary actions based on $status
            if ($status === 'COMPLETED') {
                // Example: Mark order as paid
                Log::info("Payment completed for order: $merchantReference");
            } else {
                Log::warning("Payment status for order $merchantReference: $status");
            }

            return response()->json(['status' => $status]);

        } catch (\Exception $e) {
            Log::error('Pesapal Callback Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function ipnListener(Request $request): JsonResponse
    {
        try {
            $trackingId = $request->get('pesapal_transaction_tracking_id');
            $merchantReference = $request->get('pesapal_merchant_reference');

            if (!$trackingId || !$merchantReference) {
                return response()->json(['error' => 'Invalid IPN data'], 400);
            }

            $status = $this->pesapalService->getTransactionStatus($trackingId, $merchantReference);

            // Update your database or perform necessary actions based on $status
            if ($status === 'COMPLETED') {
                // Example: Mark order as paid
                Log::info("IPN: Payment completed for order: $merchantReference");
            } else {
                Log::warning("IPN: Payment status for order $merchantReference: $status");
            }

            return response()->json(['status' => $status]);

        } catch (\Exception $e) {
            Log::error('Pesapal IPN Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function paymentStatus(string $orderId): JsonResponse
    {
        try {
            $status = $this->pesapalService->checkPaymentStatus($orderId);
            return response()->json($status);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    protected function preparePaymentData(PaymentRequest $request): array
    {
        return [
            'id' => uniqid('ORDER-'),
            'currency' => 'KES',
            'amount' => $request->amount,
            'description' => $request->description,
            'callback_url' => 'https://digitalnexifyk.com/payment/callback', // Updated with your domain
            'notification_id' => 'https://digitalnexifyk.com/api/payments/ipn', // Updated with your domain
            'billing_address' => [
                'email_address' => $request->email,
                'phone_number' => $request->phone,
                'country_code' => 'KE',
            ]
        ];
    }

    private function generateOAuthHeader(array $params): string
    {
        $consumerKey = config('services.pesapal.consumer_key');
        $consumerSecret = config('services.pesapal.consumer_secret');
        $signatureMethod = 'HMAC-SHA1';
        $timestamp = time();
        $nonce = Str::random(32);

        $oauthParams = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => $signatureMethod,
            'oauth_timestamp' => $timestamp,
            'oauth_version' => '1.0',
        ];

        $allParams = array_merge($oauthParams, $params);
        ksort($allParams);

        $baseString = 'POST&' . rawurlencode('https://www.pesapal.com/api/PostPesapalDirectOrderV4') . '&' . rawurlencode(http_build_query($allParams, '', '&', PHP_QUERY_RFC3986));
        $signingKey = rawurlencode($consumerSecret) . '&';
        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $header = 'OAuth ';
        foreach ($oauthParams as $key => $value) {
            $header .= rawurlencode($key) . '="' . rawurlencode($value) . '", ';
        }

        return rtrim($header, ', ');
    }
}
