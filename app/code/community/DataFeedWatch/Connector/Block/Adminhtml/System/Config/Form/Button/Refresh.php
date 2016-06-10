<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Form_Button_Refresh
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     * @throws Exception
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $url        = Mage::helper('adminhtml')->getUrl('adminhtml/datafeedwatch/refresh');
        $onclick    = sprintf("setLocation('%s')", $url);
        $html       = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel($this->__('Refresh'))
            ->setOnClick($onclick)
            ->toHtml();

        return $html;
    }
}