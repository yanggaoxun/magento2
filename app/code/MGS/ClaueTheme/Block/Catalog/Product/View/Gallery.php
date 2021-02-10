<?php
/**
 * Copyright � 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Simple product data view
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace MGS\ClaueTheme\Block\Catalog\Product\View;

use Magento\Framework\Data\Collection;
use Magento\Framework\Json\EncoderInterface;

class Gallery extends \Magento\Catalog\Block\Product\View\Gallery
{
    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
		\MGS\Mpanel\Helper\Data $themeHelper,
        array $data = []
    ) {
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context, $arrayUtils,  $jsonEncoder, $data);
		$this->themeHelper = $themeHelper;
    }

	public function getThemeHelper(){
		return $this->themeHelper;
	}

    /**
     * Retrieve collection of gallery images
     *
     * @return Collection
     */
    public function getGalleryImages()
    {
		$themeHelper = $this->getThemeHelper();
		$bigSize = $themeHelper->getImageSizeForDetails();
		$minSize = $themeHelper->getImageMinSize();
		$mediumSize = $themeHelper->getImageSize();
        $product = $this->getProduct();
        $images = $product->getMediaGalleryImages();
        $zoom_magnify = $themeHelper->getStoreConfig('mpanel/product_details/zoom_magnify');
        $zoom_magnify = $zoom_magnify ? $zoom_magnify : 1.5;
        if ($images instanceof \Magento\Framework\Data\Collection) {
            foreach ($images as $image) {
				if($this->isMainImage($image)){
					$image->setData('is_base_image', 1);
				}else{
					$image->setData('is_base_image', 0);
				}
				
				if($this->isDegreeImage($image) && $product->getData('mgs_j360')){
					$image->setData('degree_image', 1);
				}else{
					$image->setData('degree_image', 0);
				}
				
				if($this->isArImage($image)  && $product->getData('mgs_arimage')){
					$image->setData('ar_image', 1);
				}else{
					$image->setData('ar_image', 0);
				}
                /* @var \Magento\Framework\DataObject $image */
                $image->setData(
                    'small_image_url',
                    $this->_imageHelper->init($product, 'product_page_image_small')
                        ->setImageFile($image->getFile())
						->resize($minSize['width'], $minSize['height'])
                        ->getUrl()
                );
                $image->setData(
                    'medium_image_url',
                    $this->_imageHelper->init($product, 'product_page_image_medium')
                        ->setImageFile($image->getFile())
						->resize($mediumSize['width'], $mediumSize['height'])
                        ->getUrl()
                );
                $image->setData(
                    'large_image_url',
                    $this->_imageHelper->init($product, 'product_page_image_large')
                        ->setImageFile($image->getFile())
						->resize($bigSize['width'], $bigSize['height'])
                        ->getUrl()
                );
                $image->setData(
                    'image_zoom',
                    $this->_imageHelper->init($product, 'product_page_image_large')
                        ->setImageFile($image->getFile())
						->resize(($bigSize['width'] * $zoom_magnify), ($bigSize['height'] * $zoom_magnify))
                        ->getUrl()
                );
            }
        }

        return $images;
    }
	
	/**
     * Is 360 degree image
     *
     * @param \Magento\Framework\DataObject $image
     * @return bool
     */
    public function isDegreeImage($image)
    {
        $product = $this->getProduct();
        return $product->getThumbDegreeImage() == $image->getFile();
    }
	
	/**
     * Is 3d image
     *
     * @param \Magento\Framework\DataObject $image
     * @return bool
     */
    public function isArImage($image)
    {
        $product = $this->getProduct();
        return $product->getThumbArImage() == $image->getFile();
    }
}
