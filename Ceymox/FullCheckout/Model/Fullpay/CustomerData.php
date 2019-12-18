<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */

namespace Ceymox\FullCheckout\Model\Fullpay;

use Magento\Customer\Model\Session;

class CustomerData
{
    private $session;

    public function __construct(
        Session $session
    ) {
        $this->session = $session;
    }

    public function getCustomerName()
    {
        $name = '';
        $customerSession = $this->session;
        if ($customerSession->isLoggedIn()) {
            $name = $customerSession->getCustomer()->getName();
        }
        return $name;
    }
}
