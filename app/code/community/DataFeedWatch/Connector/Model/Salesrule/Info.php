<?php

class DataFeedWatch_Connector_Model_Salesrule_Info extends Mage_Core_Model_Abstract {

    public function _construct(){
        $this->_init('connector/salesrule_info','salesruleinfo_id');
    }
}