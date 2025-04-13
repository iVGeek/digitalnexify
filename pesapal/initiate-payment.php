<?php
// PesaPal Initiate Payment Script

// Replace hardcoded credentials with environment variables
define('PESAPAL_CONSUMER_KEY', getenv('PESAPAL_CONSUMER_KEY'));
define('PESAPAL_CONSUMER_SECRET', getenv('PESAPAL_CONSUMER_SECRET'));

if (!PESAPAL_CONSUMER_KEY || !PESAPAL_CONSUMER_SECRET) {
    error_log('PesaPal credentials are not set in environment variables.');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit;
}

// Use the correct API endpoint (sandbox or production)
$apiEndpoint = 'https://www.pesapal.com/v3/api/PostPesapalDirectOrderV4'; // Updated endpoint

// Function to generate OAuth signature
function generateOAuthSignature($method, $url, $params, $consumerSecret) {
    ksort($params);
    $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    $signatureKey = rawurlencode($consumerSecret) . '&';
    return base64_encode(hash_hmac('sha1', $baseString, $signatureKey, true));
}

// Handle incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Extract payment details
$firstName = $data['firstName'] ?? '';
$lastName = $data['lastName'] ?? '';
$email = $data['email'] ?? '';
$phone = $data['phone'] ?? '';
$plan = $data['plan'] ?? '';
$amount = $data['amount'] ?? '';

// Validate required fields with stricter checks
if (!$firstName || !$email || !$phone || !$plan || !$amount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

// Log the payment request for debugging
error_log('Initiating payment for: ' . json_encode($data));

// Prepare OAuth parameters
$oauthParams = [
    'oauth_consumer_key' => PESAPAL_CONSUMER_KEY,
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => time(),
    'oauth_nonce' => uniqid(),
    'oauth_version' => '1.0',
];

// Generate OAuth signature
$oauthParams['oauth_signature'] = generateOAuthSignature('POST', $apiEndpoint, $oauthParams, PESAPAL_CONSUMER_SECRET);

// Format OAuth parameters for the Authorization header
$oauthHeader = 'OAuth ';
foreach ($oauthParams as $key => $value) {
    $oauthHeader .= rawurlencode($key) . '="' . rawurlencode($value) . '", ';
}
$oauthHeader = rtrim($oauthHeader, ', ');

// Prepare payment request payload
$paymentDetails = [
    'Amount' => $amount,
    'Description' => "$plan Plan",
    'Type' => 'MERCHANT',
    'Reference' => uniqid(),
    'FirstName' => $firstName,
    'LastName' => $lastName,
    'Email' => $email,
    'PhoneNumber' => $phone,
    'Currency' => 'USD',
];

// Send payment request to PesaPal
$ch = curl_init($apiEndpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentDetails));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . $oauthHeader, // Use the formatted OAuth header
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    error_log('cURL error: ' . curl_error($ch));
}

curl_close($ch);

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    if (isset($responseData['redirect_url'])) {
        echo json_encode(['success' => true, 'paymentUrl' => $responseData['redirect_url']]);
    } else {
        error_log('Missing redirect_url in PesaPal response: ' . $response);
        echo json_encode(['success' => false, 'message' => 'Unexpected response from payment gateway']);
    }
} else {
    error_log('Failed to initiate payment. HTTP Code: ' . $httpCode . '. Response: ' . $response);
    echo json_encode(['success' => false, 'message' => 'Failed to initiate payment']);
}
?>