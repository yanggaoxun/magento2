<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MGS\Mmegamenu\Helper;

/**
 * Contact base helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param \Magento\Framework\Registry $registry
     */

    protected $_registry;
    
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\Context $context
    ) {
        $this->_registry = $registry;
        $this->scopeConfig = $context->getScopeConfig();
    }
    
	public function getStoreConfig($node){
		return $this->scopeConfig->getValue($node, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
    
	public function getCurentCateId(){
        $category = $this->_registry->registry('current_category');
        return $category;
	}
}