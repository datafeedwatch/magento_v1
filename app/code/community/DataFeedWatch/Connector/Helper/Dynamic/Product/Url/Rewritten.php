<?php
class DataFeedWatch_Connector_Helper_Dynamic_Product_Url_Rewritten extends DataFeedWatch_Connector_Helper_Attribute_Url {
    public function getAttributeByLogic($attributeCode,$logic='child_then_parent')
    {
        $result = Mage::registry('datafeedwatch_connector_result');

        if (Mage::helper('connector')->isSupportedEnterprise()) {
            //return null later
        } else {
            $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            return $baseUrl . $this->getRewrittenProductUrl($result->product,null,$this->storeId);
        }
        return null;
    }
}