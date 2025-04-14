class PesapalController extends Controller {
    // ...existing code...

    private function generateOAuthHeader($params) {
        $header = 'Authorization: OAuth ';
        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => bin2hex(random_bytes(8)), // Dynamic nonce
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(), // Current timestamp
            'oauth_version' => '1.0', // Required by Pesapal
        ];

        // Merge with input params (e.g., callback_url)
        $oauthParams = array_merge($oauthParams, $params);

        // Generate signature
        $oauthParams['oauth_signature'] = $this->generateSignature($oauthParams);

        // Build header string
        foreach ($oauthParams as $key => $value) {
            $header .= $key . '="' . rawurlencode($value) . '", ';
        }

        return rtrim($header, ', ');
    }

    private function generateSignature($params) {
        // Sort parameters alphabetically
        ksort($params);

        // URL-encode each key/value pair
        $encodedParams = [];
        foreach ($params as $key => $value) {
            $encodedParams[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        // Construct base string
        $httpMethod = 'POST'; // or 'GET' depending on the API
        $baseUrl = 'https://www.pesapal.com/api/PostPesapalDirectOrderV4'; // Your API endpoint
        $baseString = $httpMethod . '&' . rawurlencode($baseUrl) . '&' . rawurlencode(implode('&', $encodedParams));

        // Sign with HMAC-SHA1
        $signingKey = rawurlencode($this->consumerSecret) . '&';
        return base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
    }

    public function redirectToPesapal(Request $request) {
        $params = [
            'oauth_callback' => route('pesapal.callback'), // URL-encoded in generateSignature
            'pesapal_request_data' => json_encode([
                'Amount' => 100,
                'Description' => 'Test Payment',
                'Type' => 'MERCHANT',
                'Reference' => 'ORDER-' . uniqid(),
                'Email' => 'test@example.com',
            ]),
        ];

        $headers = [
            'Content-Type: application/json',
            $this->generateOAuthHeader($params),
        ];

        $response = Http::withHeaders($headers)
            ->post('https://www.pesapal.com/api/PostPesapalDirectOrderV4', $params);

        return $response;
    }

    // ...existing code...
}
