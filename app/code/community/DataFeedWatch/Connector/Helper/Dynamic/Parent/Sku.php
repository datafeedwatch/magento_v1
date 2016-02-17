<?php
class DataFeedWatch_Connector_Helper_Dynamic_Parent_Sku extends DataFeedWatch_Connector_Helper_Attribute {
    public function getAttributeByLogic($attributeCode,$logic='child_then_parent')
    {
        $result = Mage::registry('datafeedwatch_connector_result');
        if ($result->parentProduct) {
            return $result->parentProduct->getSku();
        }
        return null;
    }
}