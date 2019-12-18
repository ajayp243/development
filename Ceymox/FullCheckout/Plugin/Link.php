<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */

namespace Ceymox\FullCheckout\Plugin;

class Link
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlInterface;

    /**
     * Link constructor.
     * @param \Magento\Framework\UrlInterface $urlInterface
     */
    protected $helperData;

    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface,
        \Ceymox\FullCheckout\Helper\Data $helperData
    ) {
        $this->urlInterface = $urlInterface;
        $this->helperData = $helperData;

    }

    public function afterGetCheckoutUrl()
    {
        if($this->helperData->isEnabled()){
            return $this->urlInterface->getUrl('fullcheckout/onepage');
        }
    }
}
