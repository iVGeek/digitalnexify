<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesapalService
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $baseUrl;

    public function __construct()
    {
        $this->consumerKey = config('pesapal.consumer_key');
        $this->consumerSecret = config('pesapal.consumer_secret');
        $this->baseUrl = config('pesapal.sandbox') 
            ? 'https://cybqa.pesapal.com/pesapalv3' 
            : 'https://www.pesapal.com';
    }

    public function initiatePayment(array $paymentData)
    {
        $endpoint = '/api/Transactions/SubmitOrderRequest';
        $headers = $this->generateHeaders();

        $response = Http::withHeaders($headers)
            ->post($this->baseUrl . $endpoint, $paymentData);

        return $this->handleResponse($response);
    }

    public function checkPaymentStatus(string $orderId)
    {
        $endpoint = '/api/Transactions/GetTransactionStatus';
        $headers = $this->generateHeaders();

        $response = Http::withHeaders($headers)
            ->get($this->baseUrl . $endpoint, [
                'orderTrackingId' => $orderId
            ]);

        return $this->handleResponse($response);
    }

    protected function generateHeaders()
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->generateToken(),
        ];
    }

    protected function generateToken()
    {
        $endpoint = '/api/Auth/RequestToken';
        $credentials = [
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ];

        $response = Http::post($this->baseUrl . $endpoint, $credentials);

        if ($response->successful()) {
            return $response->json()['token'];
        }

        throw new \Exception('Failed to generate Pesapal token');
    }

    protected function handleResponse($response)
    {
        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Pesapal API Error', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Pesapal API request failed');
    }
}
