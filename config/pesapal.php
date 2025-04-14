<?php

return [
    'sandbox' => env('PESAPAL_SANDBOX', true),
    'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
    'api_endpoints' => [
        'submit_order' => env('PESAPAL_SANDBOX') 
            ? 'https://cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest'
            : 'https://www.pesapal.com/api/PostPesapalDirectOrderV4',
        'transaction_status' => env('PESAPAL_SANDBOX')
            ? 'https://cybqa.pesapal.com/pesapalv3/api/Transactions/GetTransactionStatus'
            : 'https://www.pesapal.com/api/QueryPaymentStatus',
    ],
    'callback_url' => env('PESAPAL_CALLBACK_URL'),
    'ipn_url' => env('PESAPAL_IPN_URL'),
];
