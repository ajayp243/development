<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */
namespace Ceymox\FullCheckout\Plugin;

class Sidebar
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlInterface;

    /**
     * Link constructor.
     * @param \Magento\Framework\UrlInterface $urlInterface
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function afterGetCheckoutUrl()
    {
        //if ($this->_objectManager->get('Ceymox\FullCheckout\Helper\Data')->isEnabled()) {
            return $this->urlInterface->getUrl('fullcheckout/onepage');
        //}
    }
}
