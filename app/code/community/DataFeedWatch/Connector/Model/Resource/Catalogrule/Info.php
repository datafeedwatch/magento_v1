<?php

class DataFeedWatch_Connector_Model_Resource_Catalogrule_Info extends Mage_Core_Model_Resource_Db_Abstract {

    public function _construct(){
        $this->_init('connector/catalogrule_info','catalogruleinfo_id');
    }
}