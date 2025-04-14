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
        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => bin2hex(random_bytes(8)), // Generate a unique nonce
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(), // Current timestamp
            'oauth_version' => '1.0',
        ];

        // Generate the OAuth signature
        $oauthParams['oauth_signature'] = $this->generateSignature($oauthParams);

        // Build the Authorization header
        $authorizationHeader = 'OAuth ';
        foreach ($oauthParams as $key => $value) {
            $authorizationHeader .= $key . '="' . rawurlencode($value) . '", ';
        }
        $authorizationHeader = rtrim($authorizationHeader, ', ');

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $authorizationHeader,
        ];
    }

    protected function generateSignature(array $params)
    {
        // Sort parameters alphabetically
        ksort($params);

        // URL-encode each key/value pair
        $encodedParams = [];
        foreach ($params as $key => $value) {
            $encodedParams[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        // Construct the base string
        $baseString = 'POST&' . rawurlencode($this->baseUrl . '/api/Transactions/SubmitOrderRequest') . '&' . rawurlencode(implode('&', $encodedParams));

        // Generate the signature using HMAC-SHA1
        $signingKey = rawurlencode($this->consumerSecret) . '&';
        return base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
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
