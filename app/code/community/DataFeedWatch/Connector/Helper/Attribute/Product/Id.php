<?php
class DataFeedWatch_Connector_Helper_Attribute_Product_Id
    extends DataFeedWatch_Connector_Helper_Attribute
    implements DataFeedWatch_Connector_Helper_Attribute_Interface
{
    public function getAttributeByLogic($attributeCode,$logic='child_then_parent')
    {
        $result = Mage::registry('datafeedwatch_connector_result');
        return $result->getProduct()->getId();
    }
}