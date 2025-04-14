<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function pesapal_config() {
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'PesaPal',
        ],
        'consumerKey' => [
            'FriendlyName' => 'Consumer Key',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter your PesaPal Consumer Key here.',
            'Default' => 'dJx8ofTbwuSs3rPH0m8s7c142c1mVZht', // Added default key
        ],
        'consumerSecret' => [
            'FriendlyName' => 'Consumer Secret',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter your PesaPal Consumer Secret here.',
            'Default' => 'PVjWH6PhjIVrz0+Zhcqtxnnp9NU=', // Added default secret
        ],
        // ...additional configuration...
    ];
}

function pesapal_link($params) {
    $callbackUrl = $params['systemurl'] . 'modules/gateways/callback/pesapal.php';

    // OAuth parameters
    $consumerKey = $params['consumerKey'];
    $consumerSecret = $params['consumerSecret'];
    $oauthNonce = md5(uniqid(rand(), true));
    $oauthTimestamp = time();
    $oauthSignatureMethod = "HMAC-SHA1";

    // Generate OAuth signature
    $parameters = [
        'oauth_callback' => $callbackUrl,
        'oauth_consumer_key' => $consumerKey,
        'oauth_nonce' => $oauthNonce,
        'oauth_signature_method' => $oauthSignatureMethod,
        'oauth_timestamp' => $oauthTimestamp,
    ];
    ksort($parameters); // Sort parameters alphabetically
    $parameterString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    $signatureBaseString = "POST&" . urlencode("https://www.pesapal.com/API/PostPesapalDirectOrderV4") . "&" . urlencode($parameterString);
    $signatureKey = urlencode($consumerSecret) . "&";
    $oauthSignature = base64_encode(hash_hmac("sha1", $signatureBaseString, $signatureKey, true));

    // Add OAuth signature to parameters
    $parameters['oauth_signature'] = $oauthSignature;

    // Build Authorization header
    $authorizationHeader = 'OAuth ';
    foreach ($parameters as $key => $value) {
        $authorizationHeader .= $key . '="' . rawurlencode($value) . '", ';
    }
    $authorizationHeader = rtrim($authorizationHeader, ', ');

    $htmlOutput = '<form action="https://www.pesapal.com/API/PostPesapalDirectOrderV4" method="post">';
    $htmlOutput .= '<input type="hidden" name="amount" value="' . $params['amount'] . '">';
    $htmlOutput .= '<input type="hidden" name="description" value="' . $params['description'] . '">';
    $htmlOutput .= '<input type="hidden" name="type" value="MERCHANT">';
    $htmlOutput .= '<input type="hidden" name="reference" value="' . $params['invoiceid'] . '">';
    $htmlOutput .= '<input type="hidden" name="callback_url" value="' . $callbackUrl . '">';
    $htmlOutput .= '<input type="hidden" name="authorization_header" value="' . htmlspecialchars($authorizationHeader) . '">';
    $htmlOutput .= '<button type="submit">Pay Now</button>';
    $htmlOutput .= '</form>';
    return $htmlOutput;
}
?>
