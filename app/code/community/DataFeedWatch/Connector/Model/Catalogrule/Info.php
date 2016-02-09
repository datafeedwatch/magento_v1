<?php

class DataFeedWatch_Connector_Model_Catalogrule_Info extends Mage_Core_Model_Abstract {

    public function _construct(){
        $this->_init('connector/catalogrule_info','catalogruleinfo_id');
    }
}