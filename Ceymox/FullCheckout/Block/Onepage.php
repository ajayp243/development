<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */

namespace Ceymox\FullCheckout\Block;

use Magento\Framework\View\Element\Template;

class Onepage extends Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * @return string
     */
    public function getHtmlSnippet()
    {
        return $this->registry->registry('html');
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->registry->registry('token');
    }
}