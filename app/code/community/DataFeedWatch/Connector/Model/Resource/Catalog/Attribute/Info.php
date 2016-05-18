<?php

class DataFeedWatch_Connector_Model_Resource_Catalog_Attribute_Info
    extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('datafeedwatch_connector/catalog_attribute_info', 'catalog_attribute_info_id');
    }
}