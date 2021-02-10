<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MGS\Mpanel\Block\Panel\Create;

use MGS\Mpanel\Block\Panel\AbstractPanel;

/**
 * Main contact form block
 */
class Element extends AbstractPanel
{
	protected $_params = array();
	
	/**
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
		$this->_params = $this->getRequest()->getParams();
		if(isset($this->_params['type'])){
			$this->setTemplate('MGS_Mpanel::panel/create/element/'.$this->_params['type'].'.phtml');
		}else{
			if($this->_params['cms']=='block'){
				$this->setTemplate('MGS_Mpanel::panel/edit/block.phtml');
			}else{
				$this->setTemplate('MGS_Mpanel::panel/edit/page.phtml');
			}
			
		}
        
    }
	
	public function getParams(){
		return $this->_params;
	}
	
	public function getPanelUploadSrc($type, $file){
		return $this->getPanelUploadFolder($type).$file;
	}
	
	public function getPanelUploadFolder($type){
		return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]).'wysiwyg/'.$type.'/';
	}
}

