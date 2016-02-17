<?php
class DataFeedWatch_Connector_Model_Product_Result extends Mage_Core_Model_Abstract {

    public $product = null;
    public $parentProduct = null;
    public $result = array();

    public function getValueOf($key){
        if(array_key_exists($key,$this->result)) {
            return $this->result[$key];
        }
        return false;
    }

    public function setValueOf($key,$value){
        Mage::unregister('datafeedwatch_connector_result');
        $this->result[$key] = $value;
        Mage::register('datafeedwatch_connector_result', $this);
        return $this;
    }

    public function getResult(){
        return $this->result;
    }

    public function getProduct(){
        return $this->product;
    }

    public function setProduct(Mage_Catalog_Model_Product $product){
        Mage::unregister('datafeedwatch_connector_result');
        $this->product = $product;
        Mage::register('datafeedwatch_connector_result', $this);
        return $this;
    }

    public function getParentProduct(){
        return $this->parentProduct;
    }

    public function setParentProduct(Mage_Catalog_Model_Product $product){
        Mage::unregister('datafeedwatch_connector_result');
        $this->parentProduct = $product;
        Mage::register('datafeedwatch_connector_result', $this);
        return $this;
    }

    public function getAttributeByLogic($attributeCode,$logic){

        /**
         * NOTE: there's no other way in Magento to do that I could come across
         */
        $helperClass = Mage::getConfig()->getHelperClassName('connector/attribute_' . $attributeCode);
        $classPath = MAGENTO_ROOT.'/app/code/community/'.str_replace('_','/',$helperClass).'.php';

        if(!file_exists($classPath)){
            $attributeHandler = Mage::helper('connector/attribute');
        } else {
            $attributeHandler = Mage::helper('connector/attribute_' . $attributeCode);
        }

        $attributeValue = $attributeHandler->getAttributeByLogic($attributeCode,$logic);

        return $attributeValue;
    }

    public function getAttributeProductByLogic($attributeCode,$logic='child_then_parent')
    {
            $result = Mage::registry('datafeedwatch_connector_result');

            if ($result->parentProduct != null) {
                if ($logic == 'child') {
                    return $result->product;
                }

                // Parent regardless the value
                if ($logic == 'parent') {
                    return $result->parentProduct;
                }

                // Child then parent logic
                if ($logic == 'child_then_parent') {
                    if ($result->product->getData($attributeCode) == '' || $result->product->getData($attributeCode) == null) {
                        return $result->parentProduct;
                    }
                }

                // If no logic has been selected, fallback to child
                return $result->product;
            } else {
                // If no parent exist, get child value
                return $result->product;
            }
    }

}