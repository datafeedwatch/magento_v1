<?php
class DataFeedWatch_Connector_Helper_Dynamic_Parent_Url extends DataFeedWatch_Connector_Helper_Attribute_Url {
    public function getAttributeByLogic($attributeCode,$logic='child_then_parent')
    {
        $result = Mage::registry('datafeedwatch_connector_result');
        if ($result->parentProduct) {
            if (Mage::getStoreConfig('datafeedwatch/settings/url_type')) {
                /* 2 stands for Full URL */
                if (Mage::getStoreConfig('datafeedwatch/settings/url_type') == 2) {
                    return $this->getFullUrl($result->parentProduct);
                } else {
                    /* Normal Url (1) */
                    return $this->getNormalUrl($result);
                }
            } else {
                if (Mage::helper('connector')->isSupportedEnterprise()) {
                    return $result->parentProduct->getProductUrl();
                } else {
                    return $this->getNormalUrl($result);
                }
            }
        }
        return null;
    }

    public function getNormalUrl($result){
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        return $baseUrl . $result->parentProduct->getUrlPath();
    }
}