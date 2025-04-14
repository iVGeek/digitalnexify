<?php
/**
 * Copyright � 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Pesapal\Pesapalexpress\Controller\Payment;

 
/**
 * DirectPost Payment Controller
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Ipn extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Pesapal\Pesapalexpress\Helper\Data $datafunctions
    ) {
        $this->datafunctions = $datafunctions;

        parent::__construct($context);
    }
    public function execute()
    { 
        $pesapalorderId = $_GET['OrderMerchantReference'];
        $orderIdpart   =explode('-', $pesapalorderId);
        $orderId = $orderIdpart[0];
        $trackingId =   $_GET['OrderTrackingId'];
        $notificationType   =   $_GET['OrderNotificationType'];
        //echo $orderId;
        if($orderId && $trackingId) {

            $this->datafunctions->updateOrder($orderId, $trackingId, 'completeorder');
            $status = $this->datafunctions->updateOrder($orderId, $trackingId, 'completeorder');
            //echo $rob;
            if($notificationType ==="IPNCHANGE" && $trackingId!=''&& $status)
            {
                
                
                $ipn_resp="";  
                     if ($status = $this->datafunctions->updateOrder($orderId, $trackingId, 'completeorder')) 
                    {
                                $responseStatus = isset($status) ? 200 : 500;

                                $payment_notification = $notificationType;
                                $transaction_tracking_id = $trackingId;
                                $invoice = $orderId;
                        
                            // Prepare the JSON response
                            $jsonResponse = [
                            "orderNotificationType" => $payment_notification,
                            "orderTrackingId" => $transaction_tracking_id,
                            "orderMerchantReference" => $invoice,
                            "status" => $responseStatus
                        ];
                        $ipn_resp = json_encode($jsonResponse);
                        // Send the JSON response
                        header('Content-Type: application/json');
                        echo json_encode($jsonResponse);
                        
                        // Stop further execution
                           exit();
                    } 
            return $ipn_resp; 
                }   
    }
  
}}