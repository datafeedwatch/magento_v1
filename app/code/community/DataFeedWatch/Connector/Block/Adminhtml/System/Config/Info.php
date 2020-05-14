<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Info
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     * @throws Exception
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->getLayout()
                    ->getBlock('datafeedwatch_connector_info')
                    ->setId($element->getData('html_id'))
                    ->toHtml();
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        if (Mage::helper('datafeedwatch_connector')->getInstallationComplete()) {
            return '';
        }

        return parent::render($element);
    }

    /**
     * @return Mage_Cron_Model_Resource_Schedule_Collection
     */
    public function getScheduledTasks()
    {
        /** @var Mage_Cron_Model_Resource_Schedule_Collection $collection */
        $collection = Mage::getResourceModel('cron/schedule_collection');
        $collection->addFieldToFilter('job_code', 'datafeedwatch_connector_installer');

        return $collection;
    }
}