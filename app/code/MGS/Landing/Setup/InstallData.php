<?php

namespace MGS\Landing\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

class InstallData implements InstallDataInterface
{
    
    /**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * Init
     *
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(CategorySetupFactory $categorySetupFactory)
    {
        $this->categorySetupFactory = $categorySetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        // set new resource model paths
        /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */
        
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);
        
        $menu_attributes = [
            'is_landing' => [
                'type' => 'int',
                'label' => 'Is Landing Page',
                'input' => 'select',
                'required' => false,
                'sort_order' => 10,
                'default' => '0',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'group' => 'Is Landing Page'
            ],
            'cate_landing_img' => [
                'type' => 'varchar',
                'label' => 'Thumbnail Image',
                'input' => 'image',
                'backend' => 'Magento\Catalog\Model\Category\Attribute\Backend\Image',
                'required' => false,
                'sort_order' => 20,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Landing Image',
            ],
            'cate_landing_type' => [
				'type' => 'varchar',
				'label' => 'Landing Template',
				'input' => 'select',
				'source' => 'MGS\Landing\Model\Category\Attribute\Source\Template',
				'required' => false,
				'sort_order' => 30,
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'group' => 'Landing Template',
			]
        ];
        
        foreach($menu_attributes as $item => $data) {
            $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $item, $data);
        }
        
        $idg =  $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'MGS Megamenu');
        
        foreach($menu_attributes as $item => $data) {
            $categorySetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $idg,
                $item,
                $data['sort_order']
            );
        }
        
        $setup->endSetup();
    }
}