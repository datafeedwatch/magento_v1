<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Form_Button_Refresh
    extends DataFeedWatch_Connector_Block_Adminhtml_System_Config_Form_Button_BaseButton
{
    protected function _construct()
    {
        parent::_construct();

        $url            = Mage::helper('adminhtml')->getUrl('adminhtml/datafeedwatch/refresh');
        $this->onClick  = sprintf("setLocation('%s')", $url);
        $this->label    = 'Refresh';
    }
}
