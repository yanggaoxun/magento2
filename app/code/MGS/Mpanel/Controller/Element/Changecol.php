<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace MGS\Mpanel\Controller\Element;

use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\Session as CustomerSession;
class Changecol extends \Magento\Framework\App\Action\Action
{
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		CustomerSession $customerSession
	)     
	{
		$this->customerSession = $customerSession;
		parent::__construct($context);
	}
	
	public function getModel($model){
		return $this->_objectManager->create($model);
	}
	
    public function execute()
    {
		if($this->getRequest()->isAjax() && ($this->customerSession->getUsePanel() == 1)){
			if(($id = $this->getRequest()->getParam('id')) && ($col = $this->getRequest()->getParam('col'))){
				$block = $this->getModel('MGS\Mpanel\Model\Childs')->load($id);
				if($block->getId()){
					$block->setCol($col)->setId($id);
					try{
						$block->save();
						return $this->getResponse()->setBody($col);
					}catch (\Exception $e) {
						return $this->getResponse()->setBody($e->getMessage());
					}
					
				}else{
					return $this->getResponse()->setBody(__('Can not delete this block !'));
				}
			}else{
				return $this->getResponse()->setBody(__('Can not delete this block !'));
			}
		}else{
			$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
			$resultRedirect->setUrl($this->_redirect->getRefererUrl());
			return $resultRedirect;
		}
    }
}
