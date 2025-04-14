<?php
namespace Pesapal\Pesapalexpress\Helper;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Store\Model\Store;
use Pesapal\Pesapalexpress\Helper\pesapalCheckStatus;
use \Magento\Sales\Model\ResourceModel\Order;
 
// optional use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Checkout default helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
public function __construct(
        \Magento\Framework\App\Helper\Context $context,
         \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $salesOrderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Config\Source\Order\Status $statuses,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        pesapalCheckStatus $pesapal,
        Order $Order
        
     ) {
         $this->_checkoutSession = $checkoutSession;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->statuses = $statuses;
        $this->directory_list = $directory_list;
        $this->pesapal = $pesapal;
        $this->invoiceSender = $invoiceSender;
        $this->Order = $Order;

      // $this->orderSender= $orderSender;
        parent::__construct($context);
        
    }

    public function updateOrder($orderId, $trackingId, $action){
        $order = $this->salesOrderFactory->loadByIncrementId($orderId);
        $results = $this->checkStatus($trackingId, $orderId);
        $status = $results->payment_status_description;
        $paymentDetail = $results->confirmation_code;
    
        if($status == 'Invalid'){
            $status = $this->checkStatus($orderId, $trackingId);
        }
    
        // Check if the status needs to be updated
        $updateStatus = false;
        if($action == 'neworder' || $status != 'Pending'){
            if($status == 'Completed'){
                if ($order->getStatus() !== "completed") {
                    $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                    $order->setStatus('completed'); 
    
                    // Create invoice for this order
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $invoice = $objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                    $invoice->register();
                    $transaction = $objectManager->create('Magento\Framework\DB\Transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transaction->save();
                    $this->invoiceSender->send($invoice);
                    $order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getId()))
                        ->setIsCustomerNotified(true);
                }
                $updateStatus = true;
            }
            elseif (in_array($status, ['Reversed', 'Failed', 'Invalid'])) {
                if ($order->getStatus() !== strtolower($status)) {
                    $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                    $order->setStatus(strtolower($status));
                }
                $updateStatus = true;
            }
        }
    
        if($updateStatus) {
            $this->Order->save($order);
        }
    
        if($action == 'neworder' || $status == 'Completed' || $status == 'Failed'){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);
        }
    
        if ($paymentDetail) {
            $order->getPayment()->setAdditionalInformation('confirmation_code', $paymentDetail);
        }
             
        /// Get existing comments
            $existingComments = $order->getAllStatusHistory();

            // Check if payment detail already exists  
            $codeExists = false;
            $commentDetail = __("Payment confirmation code: %1", $paymentDetail);

            foreach ($existingComments as $existingComment) {
                $comment = $existingComment->getComment();
                if ($comment !== null && strpos($comment, $paymentDetail) !== false) {
                    $codeExists = true;
                    break;
                }
            }

            if (!$codeExists) {
                // Add a new comment only if the payment confirmation code doesn't already exist in the order history
                $order->addStatusHistoryComment($commentDetail);
                $this->Order->save($order);
            }


        return $status;
    }
    

     
   public function checkStatus($pesapalTrackingId,$order_id){
 
        $results=$this->pesapal->checkStatus($pesapalTrackingId,$order_id);

        return $results;
    }

     public function pesapalIframe($order, $redirect=false){

        $iframe=$this->pesapal->loadIframe($order,$redirect);
         return $iframe;
    }
    
    public function cancelAction() {
        if ($this->_checkoutSession->getLastOrderId()) {
            $order = $this->salesOrderFactory->load($this->_checkoutSession->getLastOrderId());
            if($order->getId()) {
                // Flag the order as 'cancelled' and save it
                $order->cancel()->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true, 'Pesapal Gateway has declined the payment.')->save();
            }
        }
    }
}