<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */

namespace Ceymox\FullCheckout\Model\Fullpay;

use Magento\Catalog\Model\Session;

class CoreData
{
    private $session;

    public function __construct(
        Session $session
    ) {
        $this->session = $session;
    }

    public function getCoreSession()
    {
        return $this->session;
    }
}
