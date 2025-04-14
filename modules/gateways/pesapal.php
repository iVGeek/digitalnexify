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
    $signatureBaseString = "POST&" . urlencode("https://www.pesapal.com/API/PostPesapalDirectOrderV4") . "&" . urlencode("callback_url=" . $callbackUrl . "&consumer_key=" . $consumerKey . "&nonce=" . $oauthNonce . "&signature_method=" . $oauthSignatureMethod . "&timestamp=" . $oauthTimestamp);
    $signatureKey = urlencode($consumerSecret) . "&";
    $oauthSignature = base64_encode(hash_hmac("sha1", $signatureBaseString, $signatureKey, true));

    $htmlOutput = '<form action="https://www.pesapal.com/API/PostPesapalDirectOrderV4" method="post">';
    $htmlOutput .= '<input type="hidden" name="amount" value="' . $params['amount'] . '">';
    $htmlOutput .= '<input type="hidden" name="description" value="' . $params['description'] . '">';
    $htmlOutput .= '<input type="hidden" name="type" value="MERCHANT">';
    $htmlOutput .= '<input type="hidden" name="reference" value="' . $params['invoiceid'] . '">';
    $htmlOutput .= '<input type="hidden" name="callback_url" value="' . $callbackUrl . '">';
    $htmlOutput .= '<input type="hidden" name="oauth_consumer_key" value="' . $consumerKey . '">';
    $htmlOutput .= '<input type="hidden" name="oauth_signature_method" value="' . $oauthSignatureMethod . '">';
    $htmlOutput .= '<input type="hidden" name="oauth_signature" value="' . $oauthSignature . '">';
    $htmlOutput .= '<input type="hidden" name="oauth_timestamp" value="' . $oauthTimestamp . '">';
    $htmlOutput .= '<input type="hidden" name="oauth_nonce" value="' . $oauthNonce . '">';
    $htmlOutput .= '<button type="submit">Pay Now</button>';
    $htmlOutput .= '</form>';
    return $htmlOutput;
}
?>
