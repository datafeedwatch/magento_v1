<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Form_Button_Add
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     * @throws Exception
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $url        = Mage::helper('adminhtml')->getUrl('adminhtml/datafeedwatch/addStore');
        $onclick    = sprintf("window.open('%s', '_blank')", $url);
        $html       = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel($this->__('Add Store'))
            ->setOnClick($onclick)
            ->toHtml();

        return $html;
    }
}