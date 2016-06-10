<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid
    extends Mage_Adminhtml_Block_Abstract
{
    /**
     * @return string
     */
    public function getActionUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/datafeedwatch/renderInheritanceGrid');
    }

    /**
     * @return string
     */
    public function getSaveInheritanceActionUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/datafeedwatch/saveAttributeInheritance');
    }

    /**
     * @return string
     */
    public function getSaveImportActionUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/datafeedwatch/saveAttributeImport');
    }
}