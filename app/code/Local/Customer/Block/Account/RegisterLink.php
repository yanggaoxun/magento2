<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Local\Customer\Block\Account;

use Magento\Customer\Model\Context;

/**
 * Customer register link
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class RegisterLink extends \Magento\Customer\Block\Account\RegisterLink
{


    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->_registration->isAllowed()
            || $this->httpContext->getValue(Context::CONTEXT_AUTH)
        ) {
            return '';
        }
        //return parent::_toHtml();
        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        }


        return '<li class="link authorization-link"><a ' . $this->getLinkAttributes() . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';
    }
}
