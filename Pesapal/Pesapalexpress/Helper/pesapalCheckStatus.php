<?php
  namespace Pesapal\Pesapalexpress\Helper;
 use Pesapal\Pesapalexpress\Helper\pesapalV30Helper;





class pesapalCheckStatus extends \Magento\Framework\App\Helper\AbstractHelper{

	var $token;
	var $params;
	var $consumer_key; // merchant key
	var $consumer_secret;//  merchant secret
	var $signature_method;//
	var $iframelink;
	var $statusrequest;
	var $detailedstatusrequest;
	private $IPN_Id;
	private $helper;



	public function __construct(
	\Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
 	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
	{

 			$this->scopeConfig = $scopeConfig;
			$this->consumer_key 		= 	$this->scopeConfig->getValue('payment/pesapal/consumer_key');
	        $this->consumer_secret 		= 	$this->scopeConfig->getValue('payment/pesapal/consumer_secret');
	        $this->sandbox			= 	$this->scopeConfig->getValue('payment/pesapal/test_api');

			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
			$storeManager->getStore()->getBaseUrl();
		//$this->sandbox = $this->scopeConfig->getValue('payment/pesapal/test_api');

        //echo $storeManager->getStore()->getBaseUrl();


        if ($this->sandbox) {
            $iframelink = 'demo';
        } else {
            $iframelink = 'live';
        }

        $this->helper = new pesapalV30Helper($iframelink);

        $accessToken = $this->helper->getAccessToken($this->consumer_key, $this->consumer_secret);
		$this->token =$accessToken->token;

		$ipn_url = $storeManager->getStore()->getBaseUrl()."pesapalexpress/payment/ipn";
		//echo $ipn_url;
		$IPN_respose = $this->helper->getNotificationId($this->token, $ipn_url);
	
		$this->IPN_Id =$IPN_respose->ipn_id;


                $this->directory_list = $directory_list;
		
	 parent::__construct($context);
	}
	

	public function checkStatus($orderTrackingId){
		
		$accessToken = $this->helper->getAccessToken($this->consumer_key, $this->consumer_secret);
        $access = $accessToken->token;
		$status = $this->helper->getTransactionStatus($orderTrackingId, $this->token);	
		
		return $status;
	}
		
	public function loadIframe($orderDetails=array(),$redirect=false){
		// Original increment_id
			$originalIncrementId = $orderDetails['increment_id'];
			// Generate a unique 4-character string
			$uniqueString = substr(md5(uniqid(mt_rand(), true)), 0, 4);
	if(count($orderDetails)){
		$amount = number_format($orderDetails['grand_total'], 2);
		$order = array(
			'id' => $originalIncrementId. '-' . $uniqueString,
			'currency' => $orderDetails['order_currency_code'],
			'amount' => $amount,
			'callback_url' => $orderDetails['callback_url'],
			'notification_id' => $this->IPN_Id,
			'language' => 'EN',
			'terms_and_conditions_id' => '',
			'phone_number' => '',
			'email_address' => $orderDetails['customer_email'],
			'country_code' => '',
			'first_name' => $orderDetails['customer_firstname'],
			'middle_name' => '',
			'description' => $orderDetails['desc'],
			'last_name' => $orderDetails['customer_lastname'],
			'line_1' => '',
			'line_2' => '',
			'city' => '',
			'state' => '',
			'postal_code' => '',
			'zip_code' => ''
		);
			$data = $this->helper->getMerchertOrderURL($order, $this->token);
			//var_dump($data);		

			
	
			$iframe_src = '';
			if($data->redirect_url){
			$iframe_src = $data->redirect_url;

			$iframe='<iframe src="'. $iframe_src.'" width="100%" height="950px"  scrolling="no" frameBorder="0">
            <p>Browser unable to load iFrame</p>
        	</iframe>';
			if(!$redirect) return $iframe;
			else return $iframe_src;
	
}

	}

	
	}

}
?>