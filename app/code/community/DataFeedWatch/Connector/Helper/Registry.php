<?php

class DataFeedWatch_Connector_Helper_Registry
    extends Mage_Core_Helper_Abstract
{
    const ALL_CATEGORIES_ARRAY_KEY      = 'all_categories_array';
    const ALL_SUPER_ATTRIBUTES_KEY      = 'all_super_attributes_array';
    const ALL_IMPORTABLE_ATTRIBUTES_KEY = 'all_importable_attributes';
    const ALL_ATTRIBUTE_COLLECTION_KEY  = 'all_attribute_collection';
    const DFW_STATUS_ATTRIBUTE_KEY      = 'dfw_status_attribute';
    const DFW_UPDATED_AT_ATTRIBUTE_KEY  = 'dfw_updated_at_attribute';
    const DFW_VISIBILITY_ATTRIBUTE_KEY  = 'dfw_visibility_at_attribute';

    /**
     * @param string $storeId
     */
    public function initImportRegistry($storeId)
    {
        $this->registerCategories($storeId);
        $this->registerStatusAttribute();
        $this->registerUpdatedAtAttribute();
        $this->registerVisibilityAttribute();
        $this->registerSuperAttributes();
        $this->registerInheritableAttributes();
        $this->registerAttributeCollection();
    }
    /**
     * @param string $storeId
     */
    protected function registerCategories($storeId)
    {
        $registry = Mage::registry(self::ALL_CATEGORIES_ARRAY_KEY);
        if (empty($registry)) {
            $categories = Mage::getResourceModel('catalog/category_collection')
                ->addNameToResult()
                ->setStoreId($storeId)
                ->addFieldToFilter('level', array('gt' => 0))
                ->getItems();

            Mage::register(self::ALL_CATEGORIES_ARRAY_KEY, $categories);
        }
    }

    protected function registerSuperAttributes()
    {
        $registry = Mage::registry(self::ALL_SUPER_ATTRIBUTES_KEY);
        if (empty($registry)) {
            $superAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->getItems();
            Mage::register(self::ALL_SUPER_ATTRIBUTES_KEY, $superAttributes);
        }
    }

    protected function registerInheritableAttributes()
    {
        $registry = Mage::registry(self::ALL_IMPORTABLE_ATTRIBUTES_KEY);
        if (empty($registry)) {
            $importableAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->addFieldToFilter('import_to_dfw', 1);
            Mage::register(self::ALL_IMPORTABLE_ATTRIBUTES_KEY, $importableAttributes);
        }
    }

    protected function registerAttributeCollection()
    {
        $registry = Mage::registry(self::ALL_ATTRIBUTE_COLLECTION_KEY);
        if (empty($registry)) {
            $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')->addVisibleFilter();
            foreach ($attributeCollection as $key => $attribute) {
                if (!$this->isAttributeInheritable($attribute) || !$this->isAttributeImportable($attribute)) {
                    $attributeCollection->removeItemByKey($key);
                }
            }
            Mage::register(self::ALL_ATTRIBUTE_COLLECTION_KEY, $attributeCollection);
        }
    }

    protected function registerStatusAttribute()
    {
        $registry = Mage::registry(self::DFW_STATUS_ATTRIBUTE_KEY);
        if (empty($registry)) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $statusAttribute */
            $statusAttribute = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->addFieldToFilter('attribute_code', 'status')->getFirstItem();
            Mage::register(self::DFW_STATUS_ATTRIBUTE_KEY, $statusAttribute);
        }
    }

    /**
     * @return bool
     */
    public function isStatusAttributeInheritable()
    {
        return $this->isAttributeInheritable(Mage::registry(self::DFW_STATUS_ATTRIBUTE_KEY));
    }

    protected function registerUpdatedAtAttribute()
    {
        $registry = Mage::registry(self::DFW_UPDATED_AT_ATTRIBUTE_KEY);
        if (empty($registry)) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $statusAttribute */
            $updatedAtAttribute = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->addFieldToFilter('attribute_code', 'updated_at')->getFirstItem();
            Mage::register(self::DFW_UPDATED_AT_ATTRIBUTE_KEY, $updatedAtAttribute);
        }
    }

    protected function registerVisibilityAttribute()
    {
        $registry = Mage::registry(self::DFW_VISIBILITY_ATTRIBUTE_KEY);
        if (empty($registry)) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $statusAttribute */
            $visibilityAttribute = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->addFieldToFilter('attribute_code', 'visibility')->getFirstItem();
            Mage::register(self::DFW_VISIBILITY_ATTRIBUTE_KEY, $visibilityAttribute);
        }
    }

    /**
     * @return bool
     */
    public function isUpdatedAtAttributeInheritable()
    {
        return $this->isAttributeInheritable(Mage::registry(self::DFW_UPDATED_AT_ATTRIBUTE_KEY));
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    public function isAttributeInheritable($attribute)
    {
        return in_array($attribute->getInheritance(),
            array(
                (string) DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::PARENT_OPTION_ID,
                (string) DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_THEN_PARENT_OPTION_ID,
            )
        );
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    public function isAttributeImportable($attribute)
    {
        return (int)$attribute->getImportToDfw() === 1;
    }
}