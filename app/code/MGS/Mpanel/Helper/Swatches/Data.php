<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MGS\Mpanel\Helper\Swatches;

use Magento\Catalog\Api\Data\ProductInterface as Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product as ModelProduct;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollectionFactory;
use Magento\Swatches\Model\Swatch;
use Magento\Swatches\Model\SwatchAttributesProvider;


/**
 * Contact base helper
 */
class Data extends \Magento\Swatches\Helper\Data
{
	/**
     * When we init media gallery empty image types contain this value.
     */
    const EMPTY_IMAGE_VALUE = 'no_selection';

    /**
     * Default store ID
     */
    const DEFAULT_STORE_ID = 0;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SwatchCollectionFactory
     */
    protected $swatchCollectionFactory;

    /**
     * Catalog Image Helper
     *
     * @var Image
     */
    protected $imageHelper;

    /**
     * Product metadata pool
     *
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var SwatchAttributesProvider
     */
    private $swatchAttributesProvider;

    /**
     * Data key which should populated to Attribute entity from "additional_data" field
     *
     * @var array
     */
    protected $eavAttributeAdditionalDataKeys = [
        Swatch::SWATCH_INPUT_TYPE_KEY,
        'update_product_preview_image',
        'use_product_image_for_swatch'
    ];

    /**
     * Serializer to/from JSON.
     *
     * @var Json
     */
    private $serializer;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param SwatchCollectionFactory $swatchCollectionFactory
     * @param Image $imageHelper
     * @param Json|null $serializer
     * @param SwatchAttributesProvider $swatchAttributesProvider
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        SwatchCollectionFactory $swatchCollectionFactory,
        Image $imageHelper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Json $serializer = null,
        SwatchAttributesProvider $swatchAttributesProvider = null
    ) {
        $this->productCollectionFactory   = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
		$this->_scopeConfig = $scopeConfig;
        $this->swatchCollectionFactory = $swatchCollectionFactory;
        $this->imageHelper = $imageHelper;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->create(Json::class);
        $this->swatchAttributesProvider = $swatchAttributesProvider
            ?: ObjectManager::getInstance()->get(SwatchAttributesProvider::class);
    }
	
	
	/**
     * @param ModelProduct $product
     * @param string $imageFile
     * @return array
     */
    private function getAllSizeImages(ModelProduct $product, $imageFile)
    {
		$largeSize = $this->getImageSizeForDetails();
		$mediumSize = $this->getImageSize();
		$minSize = $this->getImageMinSize();
		
        return [
            'large' => $this->imageHelper->init($product, 'product_page_image_large')
				->resize($largeSize['width'],$largeSize['height'])
                ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(true)
                ->setImageFile($imageFile)
                ->getUrl(),
            'medium' => $this->imageHelper->init($product, 'product_page_image_medium')
				->resize($mediumSize['width'],$mediumSize['height'])
                ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(true)
                ->setImageFile($imageFile)
                ->getUrl(),
            'small' => $this->imageHelper->init($product, 'product_page_image_small')
				->resize($minSize['width'],$minSize['height'])
				->constrainOnly(true)->keepAspectRatio(true)->keepFrame(true)
                ->setImageFile($imageFile)
                ->getUrl(),
        ];
    }
	
	/**
     * @param Attribute $attribute
     * @return $this
     */
    public function assembleAdditionalDataEavAttribute(Attribute $attribute)
    {
        $initialAdditionalData = [];
        $additionalData = (string) $attribute->getData('additional_data');
        if (!empty($additionalData)) {
            $additionalData = $this->serializer->unserialize($additionalData);
            if (is_array($additionalData)) {
                $initialAdditionalData = $additionalData;
            }
        }

        $dataToAdd = [];
        foreach ($this->eavAttributeAdditionalDataKeys as $key) {
            $dataValue = $attribute->getData($key);
            if (null !== $dataValue) {
                $dataToAdd[$key] = $dataValue;
            }
        }
        $additionalData = array_merge($initialAdditionalData, $dataToAdd);
        $attribute->setData('additional_data', $this->serializer->serialize($additionalData));
        return $this;
    }

    /**
     * @param Attribute $attribute
     * @return $this
     */
    private function populateAdditionalDataEavAttribute(Attribute $attribute)
    {
        $serializedAdditionalData = $attribute->getData('additional_data');
        if ($serializedAdditionalData) {
            $additionalData = $this->serializer->unserialize($serializedAdditionalData);
            if (isset($additionalData) && is_array($additionalData)) {
                foreach ($this->eavAttributeAdditionalDataKeys as $key) {
                    if (isset($additionalData[$key])) {
                        $attribute->setData($key, $additionalData[$key]);
                    }
                }
            }
        }
        return $this;
    }
	
	/**
     * @param string $attributeCode swatch_image|image
     * @param ModelProduct $configurableProduct
     * @param array $requiredAttributes
     * @return bool|Product
     */
    private function loadFirstVariation($attributeCode, ModelProduct $configurableProduct, array $requiredAttributes)
    {
        if ($this->isProductHasSwatch($configurableProduct)) {
            $usedProducts = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);

            foreach ($usedProducts as $simpleProduct) {
                if (!in_array($simpleProduct->getData($attributeCode), [null, self::EMPTY_IMAGE_VALUE], true)
                    && !array_diff_assoc($requiredAttributes, $simpleProduct->getData())
                ) {
                    return $simpleProduct;
                }
            }
        }

        return false;
    }
	
	/**
     * @param Product $configurableProduct
     * @param array $requiredAttributes
     * @return bool|Product
     */
    public function loadFirstVariationWithSwatchImage(Product $configurableProduct, array $requiredAttributes)
    {
        return $this->loadFirstVariation('swatch_image', $configurableProduct, $requiredAttributes);
    }

    /**
     * @param Product $configurableProduct
     * @param array $requiredAttributes
     * @return bool|Product
     */
    public function loadFirstVariationWithImage(Product $configurableProduct, array $requiredAttributes)
    {
        return $this->loadFirstVariation('image', $configurableProduct, $requiredAttributes);
    }
	
	/**
     * Load Variation Product using fallback
     *
     * @param Product $parentProduct
     * @param array $attributes
     * @return bool|Product
     */
    public function loadVariationByFallback(Product $parentProduct, array $attributes)
    {
        if (! $this->isProductHasSwatch($parentProduct)) {
            return false;
        }

        $productCollection = $this->productCollectionFactory->create();

        $productLinkedFiled = $this->getMetadataPool()
            ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();
        $parentId = $parentProduct->getData($productLinkedFiled);

        $this->addFilterByParent($productCollection, $parentId);

        $configurableAttributes = $this->getAttributesFromConfigurable($parentProduct);
        $allAttributesArray = [];
        foreach ($configurableAttributes as $attribute) {
            if (!empty($attribute['default_value'])) {
                $allAttributesArray[$attribute['attribute_code']] = $attribute['default_value'];
            }
        }

        $resultAttributesToFilter = array_merge(
            $attributes,
            array_diff_key($allAttributesArray, $attributes)
        );

        $this->addFilterByAttributes($productCollection, $resultAttributesToFilter);

        $variationProduct = $productCollection->getFirstItem();
        if ($variationProduct && $variationProduct->getId()) {
            return $this->productRepository->getById($variationProduct->getId());
        }

        return false;
    }
	
	/**
     * @param ProductCollection $productCollection
     * @param array $attributes
     * @return void
     */
    private function addFilterByAttributes(ProductCollection $productCollection, array $attributes)
    {
        foreach ($attributes as $code => $option) {
            $productCollection->addAttributeToFilter($code, ['eq' => $option]);
        }
    }

    /**
     * @param ProductCollection $productCollection
     * @param integer $parentId
     * @return void
     */
    private function addFilterByParent(ProductCollection $productCollection, $parentId)
    {
        $tableProductRelation = $productCollection->getTable('catalog_product_relation');
        $productCollection
            ->getSelect()
            ->join(
                ['pr' => $tableProductRelation],
                'e.entity_id = pr.child_id'
            )
            ->where('pr.parent_id = ?', $parentId);
    }
	
	/**
     * Method getting full media gallery for current Product
     * Array structure: [
     *  ['image'] => 'http://url/pub/media/catalog/product/2/0/blabla.jpg',
     *  ['mediaGallery'] => [
     *      galleryImageId1 => simpleProductImage1.jpg,
     *      galleryImageId2 => simpleProductImage2.jpg,
     *      ...,
     *      ]
     * ]
     * @param ModelProduct $product
     * @return array
     */
	 
	 /*
		Note fix error : Insert : array same magento 2.3.1
	 */
    public function getProductMediaGallery(ModelProduct $product): array
    {
        if (!in_array($product->getData('image'), [null, self::EMPTY_IMAGE_VALUE], true)) {
            $baseImage = $product->getData('image');
        } else {
            $productMediaAttributes = array_filter($product->getMediaAttributeValues(), function ($value) {
                return $value !== self::EMPTY_IMAGE_VALUE && $value !== null;
            });
            foreach ($productMediaAttributes as $attributeCode => $value) {
                if ($attributeCode !== 'swatch_image') {
                    $baseImage = (string)$value;
                    break;
                }
            }
        }

        if (empty($baseImage)) {
            return [];
        }

        $resultGallery = $this->getAllSizeImages($product, $baseImage);
        $resultGallery['gallery'] = $this->getGalleryImages($product);

        return $resultGallery;
    }
	
	/**
     * @param ModelProduct $product
     * @return array
     */
    private function getGalleryImages(ModelProduct $product)
    {
        //TODO: remove after fix MAGETWO-48040
        $product = $this->productRepository->getById($product->getId());

        $result = [];
        $mediaGallery = $product->getMediaGalleryImages();
        foreach ($mediaGallery as $media) {
            $result[$media->getData('value_id')] = $this->getAllSizeImages(
                $product,
                $media->getData('file')
            );
        }
        return $result;
    }
	
	public function getStoreConfig($node){
		return $this->_scopeConfig->getValue($node, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	
	/* Get product image size */
	public function getImageSize(){
		$ratio = $this->getStoreConfig('mpanel/catalog/picture_ratio');
		$maxWidth = $this->getStoreConfig('mpanel/catalog/max_width_image_detail');
		$result = [];
        switch ($ratio) {
            // 1/1 Square
            case 1:
                $result = array('width' => round($maxWidth), 'height' => round($maxWidth));
                break;
            // 1/2 Portrait
            case 2:
                $result = array('width' => round($maxWidth), 'height' => round($maxWidth*2));
                break;
            // 2/3 Portrait
            case 3:
                $result = array('width' => round($maxWidth), 'height' => round(($maxWidth * 1.5)));
                break;
            // 3/4 Portrait
            case 4:
                $result = array('width' => round($maxWidth), 'height' => round(($maxWidth * 4) / 3));
                break;
            // 2/1 Landscape
            case 5:
                $result = array('width' => round($maxWidth), 'height' => round($maxWidth/2));
                break;
            // 3/2 Landscape
            case 6:
                $result = array('width' => round($maxWidth), 'height' => round(($maxWidth*2) / 3));
                break;
            // 4/3 Landscape
            case 7:
                $result = array('width' => round($maxWidth), 'height' => round(($maxWidth*3) / 4));
                break;
        }

        return $result;
	}
	
	public function getImageSizeForDetails() {
		$ratio = $this->getStoreConfig('mpanel/catalog/picture_ratio');
		$maxWidth = $this->getStoreConfig('mpanel/catalog/max_width_image_detail');
        $result = [];
        switch ($ratio) {
            // 1/1 Square
            case 1:
                $result = array('width' => round($maxWidth), 'height' => round($maxWidth));
                break;
            // 1/2 Portrait
            case 2:
                $result = array('width' => round($maxWidth), 'height' => round($maxWidth*2));
                break;
            // 2/3 Portrait
            case 3:
                $result = array('width' => round($maxWidth), 'height' => round(($maxWidth * 1.5)));
                break;
            // 3/4 Portrait
            case 4:
                $result = array('width' => round($maxWidth), 'height' => round(($maxWidth * 4) / 3));
                break;
            // 2/1 Landscape
            case 5:
                $result = array('width' => round($maxWidth), 'height' => round($maxWidth/2));
                break;
            // 3/2 Landscape
            case 6:
                $result = array('width' => round($maxWidth), 'height' => round(($maxWidth*2) / 3));
                break;
            // 4/3 Landscape
            case 7:
                $result = array('width' => round($maxWidth), 'height' => round(($maxWidth*3) / 4));
                break;
        }

        return $result;
    }
	
	public function getImageMinSize() {
        $ratio = $this->getStoreConfig('mpanel/catalog/picture_ratio');
        $result = [];
        switch ($ratio) {
            // 1/1 Square
            case 1:
                $result = array('width' => 120, 'height' => 120);
                break;
            // 1/2 Portrait
            case 2:
                $result = array('width' => 120, 'height' => 240);
                break;
            // 2/3 Portrait
            case 3:
                $result = array('width' => 120, 'height' => 180);
                break;
            // 3/4 Portrait
            case 4:
                $result = array('width' => 120, 'height' => 160);
                break;
            // 2/1 Landscape
            case 5:
                $result = array('width' => 120, 'height' => 60);
                break;
            // 3/2 Landscape
            case 6:
                $result = array('width' => 120, 'height' => 80);
                break;
            // 4/3 Landscape
            case 7:
                $result = array('width' => 120, 'height' => 90);
                break;
        }

        return $result;
    }
	
	/**
     * Retrieve collection of Swatch attributes
     *
     * @param Product $product
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     */
    private function getSwatchAttributes(Product $product)
    {
        $swatchAttributes = $this->swatchAttributesProvider->provide($product);
        return $swatchAttributes;
    }
	
	/**
     * Get swatch options by option id's according to fallback logic
     *
     * @param array $optionIds
     * @return array
     */
    public function getSwatchesByOptionsId(array $optionIds)
    {
        /** @var \Magento\Swatches\Model\ResourceModel\Swatch\Collection $swatchCollection */
        $swatchCollection = $this->swatchCollectionFactory->create();
        $swatchCollection->addFilterByOptionsIds($optionIds);

        $swatches = [];
        $currentStoreId = $this->storeManager->getStore()->getId();
        foreach ($swatchCollection as $item) {
            if ($item['type'] != Swatch::SWATCH_TYPE_TEXTUAL) {
                $swatches[$item['option_id']] = $item->getData();
            } elseif ($item['store_id'] == $currentStoreId && $item['value']) {
                $fallbackValues[$item['option_id']][$currentStoreId] = $item->getData();
            } elseif ($item['store_id'] == self::DEFAULT_STORE_ID) {
                $fallbackValues[$item['option_id']][self::DEFAULT_STORE_ID] = $item->getData();
            }
        }

        if (!empty($fallbackValues)) {
            $swatches = $this->addFallbackOptions($fallbackValues, $swatches);
        }

        return $swatches;
    }
	
	/**
     * @param array $fallbackValues
     * @param array $swatches
     * @return array
     */
    private function addFallbackOptions(array $fallbackValues, array $swatches)
    {
        $currentStoreId = $this->storeManager->getStore()->getId();
        foreach ($fallbackValues as $optionId => $optionsArray) {
            if (isset($optionsArray[$currentStoreId])) {
                $swatches[$optionId] = $optionsArray[$currentStoreId];
            } else {
                $swatches[$optionId] = $optionsArray[self::DEFAULT_STORE_ID];
            }
        }

        return $swatches;
    }
	
	/**
     * Check if the Product has Swatch attributes
     *
     * @param Product $product
     * @return bool
     */
    public function isProductHasSwatch(Product $product)
    {
        $swatchAttributes = $this->getSwatchAttributes($product);
        return count($swatchAttributes) > 0;
    }
	
	/**
     * Retrieve collection of Eav Attributes from Configurable product
     *
     * @param Product $product
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     */
    public function getAttributesFromConfigurable(Product $product)
    {
        $result = [];
        $typeInstance = $product->getTypeInstance();
        if ($typeInstance instanceof Configurable) {
            $configurableAttributes = $typeInstance->getConfigurableAttributes($product);
            /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute $configurableAttribute */
            foreach ($configurableAttributes as $configurableAttribute) {
                /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
                $attribute = $configurableAttribute->getProductAttribute();
                $result[] = $attribute;
            }
        }
        return $result;
    }
	
	/**
     * Retrieve all visible Swatch attributes for current product.
     *
     * @param Product $product
     * @return array
     */
    public function getSwatchAttributesAsArray(Product $product)
    {
        $result = [];
        $swatchAttributes = $this->getSwatchAttributes($product);
        foreach ($swatchAttributes as $swatchAttribute) {
            $swatchAttribute->setStoreId($this->storeManager->getStore()->getId());
            $attributeData = $swatchAttribute->getData();
            foreach ($swatchAttribute->getSource()->getAllOptions(false) as $option) {
                $attributeData['options'][$option['value']] = $option['label'];
            }
            $result[$attributeData['attribute_id']] = $attributeData;
        }

        return $result;
    }
	
	/**
     * Check if an attribute is Swatch
     *
     * @param Attribute $attribute
     * @return bool
     */
    public function isSwatchAttribute(Attribute $attribute)
    {
        $result = $this->isVisualSwatch($attribute) || $this->isTextSwatch($attribute);
        return $result;
    }
	
	/**
     * Is attribute Visual Swatch
     *
     * @param Attribute $attribute
     * @return bool
     */
    public function isVisualSwatch(Attribute $attribute)
    {
        if (!$attribute->hasData(Swatch::SWATCH_INPUT_TYPE_KEY)) {
            $this->populateAdditionalDataEavAttribute($attribute);
        }
        return $attribute->getData(Swatch::SWATCH_INPUT_TYPE_KEY) == Swatch::SWATCH_INPUT_TYPE_VISUAL;
    }
	
	/**
     * Is attribute Textual Swatch
     *
     * @param Attribute $attribute
     * @return bool
     */
    public function isTextSwatch(Attribute $attribute)
    {
        if (!$attribute->hasData(Swatch::SWATCH_INPUT_TYPE_KEY)) {
            $this->populateAdditionalDataEavAttribute($attribute);
        }
        return $attribute->getData(Swatch::SWATCH_INPUT_TYPE_KEY) == Swatch::SWATCH_INPUT_TYPE_TEXT;
    }
}