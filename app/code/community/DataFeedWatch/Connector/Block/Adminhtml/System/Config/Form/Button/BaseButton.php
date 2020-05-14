<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Form_Button_BaseButton
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public $onClick = '#';
    public $label   = 'Button';

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     * @throws Exception
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel($this->__($this->label))
            ->setOnClick($this->onClick)
            ->setId($element->getData('html_id'))
            ->toHtml();
    }
}
