<?php
/**
* Copyright © 2016 SW-THEMES. All rights reserved.
*/

namespace MGS\ClaueTheme\Setup;

use Magento\Framework\Module\Setup\Migration;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    
	/**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;
	
	/**
	 * Eav setup factory
	 * @var EavSetupFactory
	 */
	private $eavSetupFactory;
 
    /**
     * Init
     *
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(CategorySetupFactory $categorySetupFactory, \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory)
    {
        $this->categorySetupFactory = $categorySetupFactory;
		$this->eavSetupFactory = $eavSetupFactory;
    }
    
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
		
        if (version_compare($context->getVersion(), '1.0.1', '<=')) {
            // set new resource model paths
            /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */
            
            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);
            
            $categories_attributes = [
                'category_full_width' => [
                    'type' => 'int',
                    'label' => 'Full Width Layout',
                    'input' => 'select',
                    'required' => false,
                    'sort_order' => 87,
					'default' => '0',
					'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'group' => 'Display Settings'
                ]
            ];
            
            foreach($categories_attributes as $item => $data) {
                $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $item, $data);
            }
            
            $idg =  $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'Display Settings');
            
            foreach($categories_attributes as $item => $data) {
                $categorySetup->addAttributeToGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $idg,
                    $item,
                    $data['sort_order']
                );
            }
        }
		
		if (version_compare($context->getVersion(), '1.0.2', '<=')) {
			$eavSetup = $this->eavSetupFactory->create();

			$eavSetup->addAttribute(
				\Magento\Catalog\Model\Product::ENTITY,
				'thumb_degree_image',
				[
					'type' => 'varchar',
					'label' => '360 Degree Thumbnail',
					'input' => 'media_image',
					'required' => false,
					'sort_order' => 1000,
					'frontend' => \Magento\Catalog\Model\Product\Attribute\Frontend\Image::class,
					'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
					'used_in_product_listing' => true,
					'user_defined' => true,
					'visible' => true,
					'visible_on_front' => true
				]
			);
			
			$eavSetup->addAttribute(
				\Magento\Catalog\Model\Product::ENTITY,
				'thumb_ar_image',
				[
					'type' => 'varchar',
					'label' => '3D Thumbnail',
					'input' => 'media_image',
					'required' => false,
					'sort_order' => 1100,
					'frontend' => \Magento\Catalog\Model\Product\Attribute\Frontend\Image::class,
					'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
					'used_in_product_listing' => true,
					'user_defined' => true,
					'visible' => true,
					'visible_on_front' => true
				]
			);
			
			$degreeId = $eavSetup->getAttributeId(
				\Magento\Catalog\Model\Product::ENTITY,
				'thumb_degree_image'
			);
			
			$arId = $eavSetup->getAttributeId(
				\Magento\Catalog\Model\Product::ENTITY,
				'thumb_ar_image'
			);

			$attributeSetId = $eavSetup->getDefaultAttributeSetId(\Magento\Catalog\Model\Product::ENTITY);
			$eavSetup->addAttributeToGroup(\Magento\Catalog\Model\Product::ENTITY, $attributeSetId, 'image-management', $degreeId, 1000);
			$eavSetup->addAttributeToGroup(\Magento\Catalog\Model\Product::ENTITY, $attributeSetId, 'image-management', $arId, 1100);
        }
		
		$setup->endSetup();
    }
}
