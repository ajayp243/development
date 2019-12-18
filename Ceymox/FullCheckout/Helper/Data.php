<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */


namespace Ceymox\FullCheckout\Helper;

use \Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $scopeConfig;
      
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function isEnabled()
    {
        return (bool) $this->scopeConfig->getValue('customcheckout/general/enable', ScopeInterface::SCOPE_STORE);
    }
}
