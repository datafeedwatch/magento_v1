<?php
class DataFeedWatch_Connector_Helper_Dynamic_Dfw_Default_Variant extends DataFeedWatch_Connector_Helper_Attribute{

    private $_attributeDefaultValues = array();

    public function addDefaultVariantFlag(){
        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $parent_product = $result->getParentProduct();

        $attributes = $parent_product->getTypeInstance(true)->getConfigurableAttributesAsArray($parent_product);

        $result->setValueOf('dfw_default_variant',1);

        foreach ($attributes as $productAttribute) {

            if(!array_key_exists($productAttribute['attribute_id'],$this->_attributeDefaultValues)) {
                $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($productAttribute['attribute_id']);
                $this->_attributeDefaultValues[$productAttribute['attribute_id']] = $attribute->getData('default_value');
            }

            //reset to 0 if any attribute doesn't have default value
            $currentValue = $product->getData($productAttribute['attribute_code']);
            if($currentValue != $this->_attributeDefaultValues[$productAttribute['attribute_id']]){
                $result->setValueOf('dfw_default_variant',0);

                //return. we already have our result, we don't have to iterate over others
                return $result;
            }
        }

        //none "not default" found and value still == 1
        return $result;
    }

}