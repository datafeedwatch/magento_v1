<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Form_Grid_Inheritance
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     * @throws Exception
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->getLayout()->getBlock('datafeedwatch_connector_inheritance_grid')->toHtml();
    }
}