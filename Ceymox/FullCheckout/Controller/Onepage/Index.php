<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */

namespace Ceymox\FullCheckout\Controller\Onepage;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Checkout\Model\Session;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    private $cartHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Index constructor.
     * @param Context $context
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Ceymox\FullCheckout\Model\Fullpay\CheckoutFactory $checkoutFactory,
        \Magento\Framework\Registry $registry,
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->cartHelper = $cartHelper;
        $this->registry = $registry;
        $this->checkoutFactory = $checkoutFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if ($this->cartHelper->getItemsCount() > 0) {
            $checkout = $this->checkoutFactory->create();
            $result = $checkout->start();
           /* if (empty($result->error)) {
                $this->registry->register('token', $result->id);
                $this->checkoutSession->setHipsToken($result->id);
            } else {
                $error = $result->error;
                $this->messageManager->addError(__($error->message));
                $this->_redirect('checkout/cart');
            }*/
        } else {
            $this->_redirect('checkout/cart');
        }
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
