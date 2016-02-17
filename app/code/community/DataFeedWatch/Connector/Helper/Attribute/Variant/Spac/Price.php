<?php
class DataFeedWatch_Connector_Helper_Attribute_Variant_Spac_Price extends DataFeedWatch_Connector_Helper_Attribute{
    /**
     * @param $product
     * @param $parent_product
     * @param $attributes
     * @return mixed
     */
    public function getVariantSpacPrice(){

        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $parent_product = $result->getParentProduct();
        // get all configurable attributes
        if ($parent_product) {
            $attributes = $parent_product->getTypeInstance(true)->getConfigurableAttributes($parent_product);
        }

        // array to keep the price differences for each attribute value
        $pricesByAttributeValues = array();
        // base price of the configurable product
        $basePrice = $parent_product->getFinalPrice();
        // loop through the attributes and get the price adjustments specified in the configurable product admin page
        foreach ($attributes as $attribute) {
            $prices = $attribute->getPrices();
            foreach ($prices as $price) {
                if ($price['is_percent']) {
                    $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'] * $basePrice / 100;
                } else {
                    $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'];
                }
            }
        }

        $totalPrice = $basePrice;
        // loop through the configurable attributes
        foreach ($attributes as $attribute) {
            // get the value for a specific attribute for a simple product
            $value = $product->getData($attribute->getProductAttribute()->getAttributeCode());
            // add the price adjustment to the total price of the simple product
            if (isset($pricesByAttributeValues[$value])) {
                $totalPrice += $pricesByAttributeValues[$value];
            }
        }

        return $totalPrice;
    }
}