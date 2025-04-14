<?php

// *************************************************************************
// *                                                                       *
// * WHMCS PesaPal payment Gateway                                         *
// * Copyright (c) WHMCS Ltd. All Rights Reserved,                         *
// * Tested on WHMCS Version: 8.5.2                                       *
// * Release Date: 9th May 2018                                             *
// * V1.4.2                                                                       *
// *************************************************************************
// *                                                                       *
// * Author:  Lazaro Ong'ele | PesaPal Dev Team                            *
// * Email:   developer@pesapal.com                                        *
// * Website: http://developer.pesapal.com | http://www.pesapal.com        *
// *                                                                       *
// *************************************************************************


require('pesapalV3Helper.php'); 
require_once __DIR__ . '/../../../init.php';
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

global $CONFIG;
$ca = new WHMCS_ClientArea();
$ca->initPage();
$ca->requireLogin();

$gatewaymodule = "pesapal"; 
$gateway = getGatewayVariables($gatewaymodule);
$systemurl = ($CONFIG['SystemSSLURL']) ? $CONFIG['SystemSSLURL'] . '/' : $CONFIG['SystemURL'] . '/';

if (!$gateway["type"]) die("PesaPal Module Not Activated");

$data = $_GET['pid'];
$data = base64_decode($data);
$data = unserialize($data);
$data = base64_decode($data);
$data = unserialize($data);

$pesapalTrackingId = $data['transactionid'] ?? null;
$pesapalMerchantReference = $data['invoiceid'] ?? null;

if (!$pesapalTrackingId || !$pesapalMerchantReference) {
    die("Error: Missing transaction ID or invoice ID.");
}

$isDemoMode = $gateway['testmode'];
$apimode = ($isDemoMode) ? "demo" : "live";

$pesapalV3Helper = new pesapalV3Helper($apimode);
$access_token = $pesapalV3Helper->getAccessToken(trim($gateway['consumerkey']), trim($gateway['consumersecret']));

if($access_token) {
    $response = $pesapalV3Helper->getTransactionStatus($pesapalTrackingId, $access_token);
    $status = strtoupper($response->payment_status_description);
    
    
    $transid = $response->confirmation_code;
    $amount = NULL;
    $fee = NULL;
    
    $exploded = explode('-', $pesapalMerchantReference);
    $invoiceId = count($exploded) === 1 ? $pesapalMerchantReference : $exploded[1];
    
    $invoiceid = checkCbInvoiceID($invoiceId, $gateway['name']);

    // Prepare transaction data for logging
     $transactionData = [
            'status' => $status,
            'tracking_id' => $pesapalTrackingId,
            'merchant_reference' => $pesapalMerchantReference
        ];

        switch ($status) {
            case "COMPLETED":
                addInvoicePayment($invoiceid, $transid, $response->amount, 0, $gatewaymodule);
                logTransaction($gateway["name"], $transactionData, "Complete");
                $values["status"] = "Paid";
                break;
            case "FAILED":
                logTransaction($gateway["name"], $transactionData, "Failed");
                $values["status"] = "Pending";
                break;
            case "REVERSED":
                logTransaction($gateway["name"], $transactionData, "Refunded");
                $values["status"] = "Cancelled";
                break;
            default:
                logTransaction($gateway["name"], $transactionData, "Pending");
                $values["status"] = "Pending";
                break;
        }

    
    $command = "UpdateInvoice";
    $adminuser = $gateway["adminuser"];
    $OrderValue = [
            "invoiceid" => $invoiceid,
            "status" => $values["status"],
            "paymentmethod" => $gateway["name"]
        ];
    
    $results = localAPI($command, $OrderValue, $adminuser);
    
    logTransaction($gateway["name"], $results, "API Update Result");
    
}    

$invoice_url = $systemurl . 'viewinvoice.php?id=' . $invoiceid;

$ca->setPageTitle("Pesapal | Payment Summary");
$ca->addToBreadCrumb('index.php', 'Payment Summary');
$ca->assign('status', $status);
$ca->assign('invoiceid', $invoiceid);
$ca->assign('pesapalTrackingId', $transid);
$ca->setTemplate('pesapal_callback');
$ca->output();
?>