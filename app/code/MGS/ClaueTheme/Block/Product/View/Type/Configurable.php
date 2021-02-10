<?php
/**
 * Catalog super product configurable part block
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MGS\ClaueTheme\Block\Product\View\Type;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Configurable extends \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
{
    /**
     * Get product images for configurable variations
     *
     * @return array
     * @since 100.2.0
     */
    protected function getOptionImages()
    {
        $images = [];
        foreach ($this->getAllowProducts() as $product) {
            $productImages = $this->helper->getGalleryImages($product) ?: [];
            foreach ($productImages as $image) {
                $images[$product->getId()][] =
                    [
                        'thumb' => $image->getData('small_image_url'),
                        'img' => $image->getData('medium_image_url'),
                        'full' => $image->getData('large_image_url'),
                        'zoom' => $image->getData('zoom_image_url'),
                        'caption' => $image->getLabel(),
                        'position' => $image->getPosition(),
                        'isMain' => $image->getFile() == $product->getImage(),
                        'type' => str_replace('external-', '', $image->getMediaType()),
                        'videoUrl' => $image->getVideoUrl(),
                    ];
            }
        }

        return $images;
    }

}
