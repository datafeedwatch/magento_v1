<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Form_Button_Add
    extends DataFeedWatch_Connector_Block_Adminhtml_System_Config_Form_Button_BaseButton
{
    protected function _construct()
    {
        parent::_construct();

        $url            = Mage::helper('adminhtml')->getUrl('adminhtml/datafeedwatch/addStore');
        $this->onClick  = sprintf("window.open('%s', '_blank')", $url);
        $this->label    = 'Add Store';
    }
}
