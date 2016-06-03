<?php

class DataFeedWatch_Connector_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function addInheritanceFieldToAttributeForm(Varien_Event_Observer $observer)
    {
        $form       = $observer->getForm();
        $fieldset   = $form->getElement('base_fieldset');
        $attribute  = $observer->getAttribute();
        $fieldset->addField('import_to_dfw', 'select', array(
            'name'      => 'import_to_dfw',
            'label'     => Mage::helper('datafeedwatch_connector')->__('Import To DataFeedWatch'),
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'disabled'  => $attribute->hasCanConfigureImport() && !$attribute->getCanConfigureImport(),
        ), 'is_configurable');
        $fieldset->addField('inheritance', 'select', array(
            'name'      => 'inheritance',
            'label'     => Mage::helper('datafeedwatch_connector')->__('DataFeedWatch Inheritance'),
            'values'    => Mage::getModel('datafeedwatch_connector/system_config_source_inheritance')->toOptionArray(),
            'disabled'  => $attribute->hasCanConfigureImport() && !$attribute->getCanConfigureInheritance(),
        ), 'import_to_dfw');

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function updateLastInheritanceUpdateDateOnCategoryChangeName(Varien_Event_Observer $observer)
    {
        $category = $observer->getCategory();
        if($category instanceof Varien_Object && $category->dataHasChangedFor('name')) {
            $this->helper()->updateLastInheritanceUpdateDate();
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function updateLastInheritanceUpdateDate(Varien_Event_Observer $observer)
    {
        $this->helper()->updateLastInheritanceUpdateDate();

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function removeProductFromUpdatedTable(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product        = $observer->getProduct();
        $resource       = Mage::getSingleton('core/resource');
        $connection     = $resource->getConnection('core_write');
        $connection->delete(Mage::getModel('core/resource')->getTableName('datafeedwatch_connector/updated_products'),
            sprintf('product_id = %s', $product->getId()));

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function updateInheritanceUpdateDate(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Model_Config_Data $configModel */
        $configModel = $observer->getObject();
        if ($configModel->getSection() !== 'datafeedwatch_connector') {

            return $this;
        }

        $productUrlXpath    = DataFeedWatch_Connector_Helper_Data::PRODUCT_URL_CUSTOM_INHERITANCE_XPATH;
        $imageUrlXpath      = DataFeedWatch_Connector_Helper_Data::IMAGE_URL_CUSTOM_INHERITANCE_XPATH;

        if ($this->hasConfigDataChanged($configModel, $productUrlXpath)
            || $this->hasConfigDataChanged($configModel, $imageUrlXpath)) {
            $this->helper()->updateLastInheritanceUpdateDate();
        }

       return $this;
    }

    /**
     * @param Mage_Adminhtml_Model_Config_Data $configModel
     * @param string $xpath
     * @return null
     */
    protected function getConfigDataFromXpath($configModel, $xpath)
    {
        $xpath = explode('/', $xpath);
        if (!is_array($xpath)) {
            return null;
        }

        if (count($xpath) === 3) {
            unset($xpath[0]);
        }

        try {
            return $configModel->getGroups()[reset($xpath)]['fields'][end($xpath)]['value'];
        } catch (Exception $e) {
            $this->helper()->log($e->getMessage());

            return null;
        }
    }

    /**
     * @param Mage_Adminhtml_Model_Config_Data $configModel
     * @param string $xpath
     * @return bool
     */
    protected function hasConfigDataChanged($configModel, $xpath)
    {
        return $configModel->getConfigDataValue($xpath) !== $this->getConfigDataFromXpath($configModel, $xpath);
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    protected function isProductEntityType($attribute)
    {
        $productEntityType = Mage::getResourceModel('catalog/product')->getEntityType()->getEntityTypeId();

        return $productEntityType === $attribute->getEntityTypeId();
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('datafeedwatch_connector');
    }
}