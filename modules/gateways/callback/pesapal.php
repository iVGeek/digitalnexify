<?php

require_once '../../../init.php';
require_once '../../../includes/gatewayfunctions.php';
require_once '../../../includes/invoicefunctions.php';

$gatewayModule = "pesapal";
$gatewayParams = getGatewayVariables($gatewayModule);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$invoiceId = $_GET['invoiceId'];
$transactionId = $_GET['pesapal_transaction_tracking_id'];
$paymentStatus = $_GET['pesapal_notification_type'];

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayModule);
checkCbTransID($transactionId);

if ($paymentStatus == "COMPLETED") {
    addInvoicePayment($invoiceId, $transactionId, null, null, $gatewayModule);
    logTransaction($gatewayModule, $_GET, "Successful");
} else {
    logTransaction($gatewayModule, $_GET, "Unsuccessful");
}
?>
