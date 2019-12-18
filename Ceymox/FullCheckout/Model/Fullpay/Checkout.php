<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */


namespace Ceymox\FullCheckout\Model\Fullpay;

use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\Session;

class Checkout
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    private $customerData;
    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutData;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $coreUrl;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $quoteManagement;

    private $curl;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    private $order;

    private $coreData;

    private $quoteFactory;
    /**
     * @var EventManager
     */
    private $eventManager;

    private $customerFactory;

    private $customerRepositoryInterface;

    private $httpContext;

    const PAYMENT_INFO_TRANSPORT_TOKEN    = 'fullpay_token';

    /**
     * Checkout constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param CustomerData $customerData
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $coreUrl
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curl
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Ceymox\FullCheckout\Model\Fullpay\CoreData $coreData,
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        Session $checkoutSession,
        \Ceymox\FullCheckout\Model\Fullpay\CustomerData $customerData,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $coreUrl,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curl,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Ceymox\FullCheckout\Model\Fullpay\CoreData $coreData,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\Http\Context $httpContext
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerData = $customerData;
        $this->checkoutData = $checkoutData;
        $this->storeManager = $storeManager;
        $this->coreUrl = $coreUrl;
        $this->scopeConfig = $scopeConfig;
        $this->_quote = $this->checkoutSession->getQuote();
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->curl = $curl;
        $this->productMetadata = $productMetadata;
        $this->order = $order;
        $this->quoteFactory = $quoteFactory;
        $this->coreData = $coreData;
        $this->eventManager = $eventManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->httpContext = $httpContext;
    }

    /**
     * Get Magento current version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return 'Magento '.$this->productMetadata->getVersion();
    }

    /**
     * Get current store currency code
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * Gets the Weight unit
     *
     * @return string
     */
    public function getWeightUnit()
    {
        $unit = $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
        return str_replace("s", "", $unit);
    }

    /**
     * Gets the HipsPayments fulfill from the admin config
     *
     * @return string
     */
    public function getPaymentAction()
    {
        return $this->scopeConfig->getValue('payment/fullpay/payment_action');
    }

    /**
     * Gets the HipsPayments secret key from the admin config
     *
     * @return string Secret Key or empty string if not set
     */
    public function getSecretKey()
    {
        return $this->scopeConfig->getValue('payment/fullpay/private_key');
    }

    public function start()
    {
        $this->_quote->collectTotals();
        if (!$this->_quote->getGrandTotal() && !$this->_quote->hasNominalItems()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                'Hips does not support processing orders with zero amount.'
            );
        }
        $this->_quote->reserveOrderId();
        $this->quoteRepository->save($this->_quote);
        $request = [];
        $request['order_id'] = $this->_quote->getReservedOrderId();
        $request['purchase_currency'] = $this->_quote->getBaseCurrencyCode();

        $customerName = $this->customerData->getCustomerName();
        $request['user_session_id'] = $this->generateRandomString(15);
        $request['user_identifier'] = $customerName?$customerName:$this->generateRandomString(15);

        $request['meta_data_1'] ='' ;
        if ($this->getPaymentAction() == 'authorize') {
            $request['fulfill'] = 'false';
        } else {
            $request['fulfill'] = 'true';
        }
        $request['cart'] = $this->getCart();
        if (!$this->_quote->isVirtual()) {
            $request['require_shipping'] = 'true';
            $request['express_shipping'] = 'true';
        } else {
            $request['require_shipping'] = 'false';
            $request['express_shipping'] = 'false';
        }
        $request['ecommerce_platform'] = $this->getMagentoVersion();
        $request['ecommerce_module'] = "Hips Magento Module 2.0.0";
        $request['checkout_settings'] = ["extended_cart" => 'true'];
        $request['hooks'] = [
            "user_return_url_on_success" => $this->coreUrl->getUrl('hips/onepage/success'),
            "user_return_url_on_fail"=>$this->coreUrl->getUrl('hips/onepage/failure'),
            "terms_url"=> $this->coreUrl->getUrl('terms'),
            "webhook_url"=> $this->coreUrl->getUrl('hips/confirmations/index')
        ];
        $result = $this->call('orders', 'POST', $request);
        return $result;
    }

    /**
     * Get cart items
     *
     * @return array
     */
    public function getCart()
    {
        $cart = [];
        $DiscountTotal = 0;
        foreach ($this->_quote->getAllVisibleItems() as $item) {
            $cartItem = [];
            if ($item->getProduct()->getIsVirtual()) {
                $cartItem['type'] = 'digital';
            } else {
                $cartItem['type'] = 'physical';
            }
            $cartItem['sku'] = $item->getProduct()->getSku();
            $cartItem['name'] = $item->getProduct()->getName();
            $cartItem['quantity'] = $item->getQty();
            $cartItem['unit_price'] = $item->getPriceInclTax()*100;
            $cartItem['vat_amount'] = ($item->getPriceInclTax() - $item->getPrice())*100;
            $cartItem['weight_unit'] = $this->getWeightUnit();
            $DiscountTotal += $item->getDiscountAmount();
            $cartItem['weight'] = $item->getProduct()->getWeight();
            $cart['items'][] = $cartItem;

            if ($DiscountTotal > 0) {
                $code ='';
                if ($this->_quote->getCouponCode()) {
                    $code = ' ('.$this->_quote->getCouponCode().')';
                }
                $cartItem['type'] = 'digital';
                $cartItem['sku'] = 'Discount';
                $cartItem['name'] = 'Discount'.$code;
                $cartItem['quantity'] = 1;
                $cartItem['unit_price'] = $DiscountTotal*(-100);
                $cartItem['vat_amount'] = 0;
                $cart['items'][] = $cartItem;
            }
        }
        return $cart;
    }

    /**
     * Get payment token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->checkoutSession->getHipsToken();
    }

    /**
     * Complete the order
     *
     * @throws Mage_Core_Exception
     */
    public function placeOrder()
    {
        $path   = 'orders/'.$this->getToken();
        $data = [];
        $request = $this->call($path, 'GET', $data);
        $billing_address = $request->billing_address;
        $this->_quote->setCustomerEmail($billing_address->email);
        $addressData = [
            'email' => $billing_address->email,
            'firstname' => $billing_address->given_name,
            'lastname' => $billing_address->family_name,
            'street' => $billing_address->street_address,
            'city' => $billing_address->city,
            'postcode' => $billing_address->postal_code,
            'telephone' => $billing_address->phone_mobile,
            'country_id' => $billing_address->country
        ];

        $billingAddress = $this->_quote->getBillingAddress()->addData($addressData);

        if ($request->shipping_address->id) {
            $shipping_address = $request->shipping_address;
            if ($shipping_address->phone_mobile) {
                $addressTelephone = $shipping_address->phone_mobile;
            } else {
                $addressTelephone = $billing_address->phone_mobile;
            }
            $addressData = [
                'email' => $billing_address->email,
                'firstname' => $shipping_address->given_name,
                'lastname' => $shipping_address->family_name,
                'street' => $shipping_address->street_address,
                'city' => $shipping_address->city,
                'postcode' => $shipping_address->postal_code,
                'telephone' => $addressTelephone,
                'country_id' => $shipping_address->country
            ];
        }
        $shippingAddress = $this->_quote->getShippingAddress()->addData($addressData);
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod('hips_shipping_hips_shipping');
        $this->_quote->getPayment()->setMethod('fullpay');
        $this->_quote->getPayment()->importData(['method' => 'fullpay']);
        $this->_quote->getPayment()->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_TOKEN, $request->id);
        $this->prepareGuestQuote($billing_address->email);
        $this->_quote->collectTotals();
        $this->quoteRepository->save($this->_quote);
        $orderId = $this->quoteManagement->placeOrder($this->_quote->getId());
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @return $this
     */
    public function prepareGuestQuote($email)
    {
        $quote = $this->_quote;
        $quote->setCustomerId(null)
            ->setCustomerEmail($email)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
        return $this;
    }

    /**
     * Do the API call
     *
     * @param string $methodName
     * @param array $request
     * @return array
     * @throws Mage_Core_Exception
     */
    public function call($path, $method, array $request)
    {
        try {
            $key = $this->getSecretKey();
            $url = 'https://api.hips.com/v1/'.$path;
            $data = json_encode($request);

            $headers = [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization:'.$key
                ];

            $curl = $this->curl->create();
            $curl->setConfig(
                [
                    'timeout'   => 120
                ]
            );
            if ($method == 'POST') {
                $curl->write($method, $url, '1.1', $headers, $data);
            }
            $data = $curl->read();
            if ($data === false) {
                return false;
            }
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = trim($data[1]);
            $curl->close();

            return json_decode($data);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate random string
     *
     * @param int $length
     * @return string
     */
    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @return mixed
     */
    public function orderSuccess()
    {
        $request = $this->viewOrder();
        $merchantReference = (array)$request->merchant_reference;
        $order = $this->order->loadByIncrementId($merchantReference['order_id']);
        $redirectUrl = '';
        if ($order->getId()) {
            $this->checkoutSession->setLastOrderId($order->getId())
                ->setRedirectUrl($redirectUrl)
                ->setLastRealOrderId($order->getIncrementId());
        }
    }

    /**
     * Do the API call
     *
     * @param string $methodName
     * @param array $request
     * @return array
     * @throws Mage_Core_Exception
     */
    public function viewOrder()
    {
        try {
            $key = $this->getSecretKey();
            $id = $this->checkoutSession->getHipsToken();
            $url = $this->getApiEndpoint().'/orders/'.$id;
            $curl = $this->curl->create();
            $curl->setConfig(
                [
                   'timeout'=> 120
                ]
            );
            $header = [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization:'.$key
                ];
            
            $method = 'GET';
            $curl->write($method, $url, '1.1', $header);
            $data = $curl->read();
            if ($data === false) {
                return '';
            }
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = trim($data[1]);
            $curl->close();
            return json_decode($data);
        } catch (\Exception $e) {
            $debugData['http_error'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];
            $this->_debug($debugData);
            throw $e;
        }
    }

    public function getApiEndpoint()
    {
        $url = 'https://api.hips.com/v1';
        return $url;
    }

    public function placeOrderHook($data)
    {
        $request = (array)$data['resource'];
        $storeid = $this->storeManager->getStore()->getId();
        $merchantreference = (array) $request['merchant_reference'];
        $reserveorderid = trim($merchantreference['order_id']);
        $quote = $this->quoteFactory->create()->load($reserveorderid, 'reserved_order_id');
        $billingAdd = (array)$request['billing_address'];
        $store = $this->storeManager->getStore();
        $websiteId = $store->getWebsiteId();
        $isLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($billingAdd['email']);
            $customerRepository = $this->customerRepositoryInterface;
            $customer= $customerRepository->getById($customer->getEntityId());
            $quote->assignCustomer($customer);
        } else {
            $quote->setCustomerIsGuest(1);
        }
        if ($request['require_shipping'] == 1) {
            $shippingMtd = (array) $request['shipping'];
            $this->coreData->getCoreSession()->setHipsHookData($shippingMtd);
            $this->coreData->getCoreSession()->setHipsHook(1);
        }
        
        $quote->setCustomerEmail($billingAdd['email']);
        $addressData = [
            'firstname' => $billingAdd['given_name'],
            'lastname' => $billingAdd['family_name'],
            'street' => $billingAdd['street_address'],
            'city' => $billingAdd['city'],
            'postcode' => $billingAdd['postal_code'],
            'telephone' => $billingAdd['phone_mobile'],
            'country_id' => $billingAdd['country']
        ];
        $billingAddress = $quote->getBillingAddress()->addData($addressData);
        $shippingAdd = (array)$request['shipping_address'];
        if (isset($shippingAdd['id'])) {
            $shippingAdd = (array)$request['shipping_address'];
            if ($shippingAdd['phone_mobile']) {
                $tel = $shippingAdd['phone_mobile'];
            } else {
                $tel = $billingAdd['phone_mobile'];
            }

            $addressData = [
                'firstname' => $shippingAdd['given_name'],
                'lastname' => $shippingAdd['family_name'],
                'street' => $shippingAdd['street_address'],
                'city' => $shippingAdd['city'],
                'postcode' => $shippingAdd['postal_code'],
                'telephone' => $tel,
                'country_id' => $shippingAdd['country']
            ];
        }
        $shippingAddress = $quote->getShippingAddress()->addData($addressData);
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod('hips_shipping_hips_shipping');
        $quote->getPayment()->setMethod('fullpay');
        $quote->getPayment()->importData(['method' => 'fullpay']);
        $quote->getPayment()->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_TOKEN, $request['id']);
        $quote->collectTotals()->save();
        try {
            $order = $this->quoteManagement->submit($quote);
        } catch (\Exception $e) {
            throw new \CouldNotSaveException(__('Cannot place order'), $e);
        }
        if ($request['require_shipping'] == 1) {
            $this->coreData->getCoreSession()->unsHipsHookData();
            $this->coreData->getCoreSession()->unsHipsHook();
        }
    }

    /**
     * @return mixed
     */
    public function paymentSuccessHook($data)
    {
        return true;
    }
}
