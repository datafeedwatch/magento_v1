<?php
$attribute = Mage::getResourceModel('catalog/product_attribute_collection')
                 ->addVisibleFilter()
                 ->addFieldToFilter('attribute_code', 'dfw_parent_ids')->getFirstItem();

$attribute->setImportToDfw(0)
          ->setCanConfigureImport(0)
          ->setCanConfigureInheritance(0)
          ->setInheritance(DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_OPTION_ID)
          ->save();

$currentStoreId = Mage::app()->getStore()->getId();

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$collection = Mage::getResourceModel('catalog/product_collection');
foreach($collection as $product) {
    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
    if (!empty($parentIds)) {
//        $product->setDfwParentIds(implode(',', $parentIds));
        $product->setDfwParentIds(current($parentIds));
        $product->getResource()->saveAttribute($product, 'dfw_parent_ids');
    }
}

Mage::app()->setCurrentStore($currentStoreId);