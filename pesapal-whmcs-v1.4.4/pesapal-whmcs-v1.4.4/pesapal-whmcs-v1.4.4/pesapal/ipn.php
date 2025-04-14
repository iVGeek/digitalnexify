<?php
// *************************************************************************
// *                                                                       *
// * WHMCS PesaPal payment Gateway                                         *
// * Copyright (c) WHMCS Ltd. All Rights Reserved,                         *
// * Tested on WHMCS Version: 8.5.2                                        *
// * Release Date: 9th May 2018                                            *
// * V1.4.2                                                                *
// *************************************************************************
// *                                                                       *
// * Author:  Lazaro Ong'ele | PesaPal Dev Team                            *
// * Email:   developer@pesapal.com                                        *
// * Website: http://developer.pesapal.com | http://www.pesapal.com        *
// *                                                                       *
// *************************************************************************

if (isset($_GET['demo'])){
    exit('working');   
}

require('pesapalV3Helper.php'); 
require_once __DIR__ . '/../../../init.php';
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "pesapal";
$gateway = getGatewayVariables($gatewaymodule);

if (!$gateway["type"]) {
    die("PesaPal Module Not Activated");
}

$orderTrackingId = $_GET['OrderTrackingId'] ?? null;
$pesapalMerchantReference = $_GET['OrderMerchantReference'] ?? null;
$pesapalNotification = $_GET['OrderNotificationType'] ?? null;
$dbUpdateSuccessful = false;

if (!$orderTrackingId || !$pesapalMerchantReference) {
    die("Error: Missing OrderTrackingId or OrderMerchantReference.");
}

$isDemoMode = $gateway['testmode'];
$apimode = $isDemoMode ? "demo" : "live";

$pesapalV3Helper = new pesapalV3Helper($apimode);
$access_token = $pesapalV3Helper->getAccessToken(trim($gateway['consumerkey']), trim($gateway['consumersecret']));

if ($access_token) {
    try {
        $response = $pesapalV3Helper->getTransactionStatus($orderTrackingId, $access_token);
        $status = strtoupper($response->payment_status_description);
        $confirmationCode = $response->confirmation_code;
        
        //var_dump($status);

        $invoiceid = checkCbInvoiceID($pesapalMerchantReference, $gateway['name']);
        $transactionData = [
            "invoiceid" => $invoiceid,
            "transid" => $orderTrackingId,
            "amount" => $response->amount,
            "fee" => 0, // Add fee if available from PesaPal
        ];

        switch ($status) {
            case "COMPLETED":
                $values["status"] = "Active";
                break;
            case "FAILED":
                $values["status"] = "Pending";
                break;
            case "REVERSED":
                $values["status"] = "Cancelled";
                break;
            case "INVALID":
                $values["status"] = "Fraud";
                break;    
            default:
                $values["status"] = "Pending";
                break;
        }

        // Update invoice status
        $command = "UpdateInvoice";
        $adminuser = $gateway["adminuser"];
        $invoiceValue = [
            "invoiceid" => $invoiceid,
            "status" => $values["status"], // Use the mapped status
            "paymentmethod" => $gateway["name"]
        ];
        
        $invoiceResults = localAPI($command, $invoiceValue, $adminuser);
    
        logTransaction($gateway["name"], $invoiceResults, "Invoice Update Result");
        
        // Get order ID from invoice
        $command = "GetInvoice";
        $getInvoiceParams = [
            "invoiceid" => $invoiceid
        ];
        $invoiceDetails = localAPI($command, $getInvoiceParams, $adminuser);

            if ($invoiceDetails['result'] == 'success' && isset($invoiceDetails['orderid'])) {
                $orderId = $invoiceDetails['orderid'];
            
                // Update order status
                $command = "UpdateOrder";
                $orderValue = [
                    "orderid" => $orderId,
                    "status" => $values["status"] // Use the same mapped status as invoice
                ];
            
                $orderResults = localAPI($command, $orderValue, $adminuser);
            
                // Log order update result
                logTransaction($gateway["name"], $orderResults, "Order Update Result");
            
                if ($invoiceResults['result'] == 'success' && $orderResults['result'] == 'success') {
                    $dbUpdateSuccessful = true;
                } else {
                    throw new Exception("Failed to update invoice or order: " . 
                        ($invoiceResults['message'] ?? 'Unknown invoice error') . " | " . 
                        ($orderResults['message'] ?? 'Unknown order error'));
                }
            } else {
                throw new Exception("Failed to retrieve order ID for invoice: " . $invoiceid);
            }
         
        $dbUpdateSuccessful = true;

        if ($pesapalNotification === "IPNCHANGE" && $dbUpdateSuccessful) {
            $statusCode = !empty($status) ? 200 : 500;
            
            // Prepare the JSON response
            $jsonResponse = json_encode([
                "orderNotificationType" => $pesapalNotification,
                "orderTrackingId" => $orderTrackingId,
                "orderMerchantReference" => $pesapalMerchantReference,
                "status" => $statusCode,
            ]);

            // Log the transaction
            logTransaction($gateway["name"], $debugInfo, "IPN Response");

            // Send the JSON response
            header('Content-Type: application/json');
            echo $jsonResponse;
            exit();
        }
    } catch (Exception $e) {
        // Log the error
        logTransaction($gateway["name"], [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], "Error");

        // If it's an IPN request, send an error response
        if ($pesapalNotification === "IPNCHANGE") {
            $errorResponse = json_encode([
                "orderNotificationType" => $pesapalNotification,
                "orderTrackingId" => $orderTrackingId,
                "orderMerchantReference" => $pesapalMerchantReference,
                "status" => 200,
            ]);
            header('Content-Type: application/json');
            http_response_code(500);
            echo $errorResponse;
        } else {
            // For non-IPN requests, display a generic error message
            die("An error occurred while processing the payment. Please contact support.");
        }
    }
} else {
    error_log("PesaPal Error: Unable to obtain access token");
    die("Unable to authenticate with PesaPal. Please check your credentials.");
}
?>
