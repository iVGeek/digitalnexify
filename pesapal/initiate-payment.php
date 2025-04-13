<?php
// PesaPal Initiate Payment Script

// Replace with your PesaPal consumer key and secret
define('PESAPAL_CONSUMER_KEY', 'YOUR_CONSUMER_KEY');
define('PESAPAL_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET');

// Use the correct API endpoint (sandbox or production)
$apiEndpoint = 'https://www.pesapal.com/api/PostPesapalDirectOrderV4';

// Function to generate OAuth signature
function generateOAuthSignature($params, $consumerSecret) {
    ksort($params);
    $baseString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $signature = base64_encode(hash_hmac('sha1', $baseString, $consumerSecret, true));
    return $signature;
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

// Validate required fields
if (!$firstName || !$email || !$phone || !$plan || !$amount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Prepare OAuth parameters
$oauthParams = [
    'oauth_consumer_key' => PESAPAL_CONSUMER_KEY,
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => time(),
    'oauth_nonce' => uniqid(),
    'oauth_version' => '1.0',
];

// Generate OAuth signature
$oauthParams['oauth_signature'] = generateOAuthSignature($oauthParams, PESAPAL_CONSUMER_SECRET);

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
    'Authorization: OAuth ' . http_build_query($oauthParams, '', ','),
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    echo json_encode(['success' => true, 'paymentUrl' => $responseData['redirect_url'] ?? '']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to initiate payment']);
}
?>