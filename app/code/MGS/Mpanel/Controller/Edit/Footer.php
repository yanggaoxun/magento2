<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace MGS\Mpanel\Controller\Edit;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\ResultFactory;

class Footer extends \Magento\Framework\App\Action\Action
{
	
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		CustomerSession $customerSession
	)     
	{
		$this->customerSession = $customerSession;
		parent::__construct($context);
	}
    
    public function execute()
    {
		if($this->customerSession->getUsePanel() == 1){
			$this->_view->loadLayout();
			$this->_view->renderLayout();
		}else{
			$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
			$resultRedirect->setUrl($this->_redirect->getRefererUrl());
			return $resultRedirect;
		}
        
    }
}
