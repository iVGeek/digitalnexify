<?php

require_once '../../../init.php';
require_once '../../../includes/gatewayfunctions.php';
require_once '../../../includes/invoicefunctions.php';

$gatewayModule = "pesapal";
$gatewayParams = getGatewayVariables($gatewayModule);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// ...process callback logic...
$invoiceId = $_GET['invoiceId'];
$transactionId = $_GET['pesapal_transaction_tracking_id'];
$paymentStatus = $_GET['pesapal_notification_type'];

// ...update invoice based on $paymentStatus...
logTransaction($gatewayModule, $_GET, "Callback Received");
?>
