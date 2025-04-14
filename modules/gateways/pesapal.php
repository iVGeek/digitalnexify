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
    $htmlOutput = '<form action="https://www.pesapal.com/API/PostPesapalDirectOrderV4" method="post">';
    // ...build form with $params...
    $htmlOutput .= '</form>';
    return $htmlOutput;
}
?>
