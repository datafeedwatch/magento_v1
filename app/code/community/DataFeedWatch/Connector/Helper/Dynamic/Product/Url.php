<?php
class DataFeedWatch_Connector_Helper_Dynamic_Product_Url extends DataFeedWatch_Connector_Helper_Attribute_Url {
    public function getAttributeByLogic($attributeCode,$logic='child_then_parent'){

        $result = Mage::registry('datafeedwatch_connector_result');

        if(Mage::getStoreConfig('datafeedwatch/settings/url_type')){
            /* 2 stands for Full URL */
            if(Mage::getStoreConfig('datafeedwatch/settings/url_type') == 2){
                return $this->getFullUrl($result->product);
            } else {
                if (Mage::helper('connector')->isSupportedEnterprise()) {
                    return $result->product->getProductUrl();
                } else {
                    $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
                    return $baseUrl . $result->product->getUrlPath();
                }
            }
        }


    }
}