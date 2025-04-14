<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Services\PesapalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            $response = $this->pesapalService->initiatePayment($paymentData);

            return response()->json([
                'redirect_url' => $response['redirect_url'],
                'order_id' => $response['order_tracking_id']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function paymentCallback(Request $request)
    {
        // Handle callback logic
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
}
