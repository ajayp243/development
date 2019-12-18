<?php
/**
* @author Ceymox Team
* @copyright Copyright (c)2019 Ceymox (https://ceymox.com)
* @package Ceymox_FullCheckout
 */

 
namespace Ceymox\FullCheckout\Controller\Onepage;
 
use Magento\Framework\App\Action\Context;
 
class Result extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
 
    public function __construct(Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
 
    public function execute()
    {
        if($this->getRequest()->getPost('flag') ==2){
            echo "<h2>Klarana Payment option</h2> Klarana Test contents......................";
            exit();
            $resultPage = $this->_resultPageFactory->create();
            return $resultPage;
        }
        else if($this->getRequest()->getPost('flag') ==3){
            echo "<h2>Other Payment option</h2> Other Test contents......................";
            exit();
            $resultPage = $this->_resultPageFactory->create();
            return $resultPage;
        }
        else{
            echo "<h2>SEVA Payment option</h2> Test contents......................";
            exit();
            $resultPage = $this->_resultPageFactory->create();
            return $resultPage;
        }    

    }
}