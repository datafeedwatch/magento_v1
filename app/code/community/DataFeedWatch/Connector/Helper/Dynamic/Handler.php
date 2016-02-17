<?php
class DataFeedWatch_Connector_Helper_Dynamic_Handler extends DataFeedWatch_Connector_Model_Product_Result{
    /**
     * @param $product Mage_Catalog_Model_Product
     * @param $product_result array
     * @param $parent_product Mage_Catalog_Model_Product
     * @return array
     */
    public function addDynamicAttributesToResult($usingParent){

        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $parent_product = $result->getParentProduct();


        $attributeLogicList = unserialize(Mage::getStoreConfig('datafeedwatch/settings/attribute_logic'));

        //categories
        $dynamicCategory = Mage::helper('connector/dynamic_category');


        if ($usingParent && $parent_product != null) {
            // inherit categories from parent
            $result = $dynamicCategory->addProductCategoriesToResult(1);
        } else {
            //uses children
            $result = $dynamicCategory->addProductCategoriesToResult(0);
        }

        //excluded images - was using child vales before
        $excludedImagesHandler = Mage::helper('connector/dynamic_image_url_excluded');
        $excludedImagesHandler->addExcludedImagesToResult();

        //additional images - was using child+parent values before

        $additionalImagesHandler = Mage::helper('connector/dynamic_additional_image_url');
        $result = $additionalImagesHandler->addAdditionalImagesToResult();

        //image_url
        $imageUrlHandler = Mage::helper('connector/dynamic_image_url')->setResult($result);
        $imageUrlValue = $imageUrlHandler->getAttributeByLogic('image_url',$attributeLogicList['image_url']);
        $result->setValueOf('image_url',$imageUrlValue);

        return $result;
    }
}