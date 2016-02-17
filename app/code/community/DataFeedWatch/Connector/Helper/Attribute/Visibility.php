<?php
class DataFeedWatch_Connector_Helper_Attribute_Visibility
    extends DataFeedWatch_Connector_Helper_Attribute
    implements DataFeedWatch_Connector_Helper_Attribute_Interface
{
    /**
     * Unlike many other classes, this will not have child_then_parent logic available
     * visibility is required attribute, so there will be no null or empty values
     */
    public function getAttributeByLogic($attributeCode,$logic='child')
    {
        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $parentProduct = $result->getParentProduct();

        if ($parentProduct && $logic =='parent') {
            return $parentProduct->getVisibility();
        } else if ($product && $logic =='child') {
            /** @TODO: what was that code for ? */
            //if ($parentProduct && $parentProduct->getTypeId()=='configurable' && $product->getVisibility()) {
                return $product->getVisibility();
            //}
            //return null;
        }
        return null;
    }
}