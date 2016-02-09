<?php

class DataFeedWatch_Connector_Model_Resource_Salesrule_Info extends Mage_Core_Model_Resource_Db_Abstract {

    public function _construct(){
        $this->_init('connector/salesrule_info','salesruleinfo_id');
    }
}