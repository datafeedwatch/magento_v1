<?php

class DataFeedWatch_Connector_Model_Catalog_Attribute_Info
    extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init('datafeedwatch_connector/catalog_attribute_info');
    }

    /**
     * @param string|int $attributeId
     * @return DataFeedWatch_Connector_Model_Catalog_Attribute_Info
     */
    public function loadByAttributeId($attributeId)
    {
        return $this->load($attributeId, 'catalog_attribute_id');
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        $this->avoidDuplication();
        $this->fillDate();
        $this->avoidHackImportToDfwField();
        $this->avoidHackInheritanceField();
        $this->updateInheritanceConfigDate();
        if (!$this->hasInheritance()) {
            $this->setInheritance(DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_OPTION_ID);
        }

        return parent::_beforeSave();
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeDelete()
    {
        if ($this->canSaveUpdateDate()) {
            $this->helper()->updateLastInheritanceUpdateDate();
        }

        return parent::_beforeDelete();
    }

    protected function avoidDuplication()
    {
        $info       = clone $this;
        $collection = $info->getCollection()
            ->addFieldToFilter('catalog_attribute_id', $this->getCatalogAttributeId());
        if($collection->count() > 0) {
            $item = $collection->getFirstItem();
            $this->setId($item->getId());
            $this->fillAdditionalData($item);
        }
    }

    protected function fillDate()
    {
        $date = date('Y-m-d H:i:s', Mage::getModel('core/date')->timestamp(time()));
        $this->setUpdatedAt($date);
    }

    protected function avoidHackImportToDfwField()
    {
        if (!$this->getCanConfigureImport() && !$this->isObjectNew()) {
            $this->setImportToDfw($this->getOrigData('import_to_dfw'));
        }
    }
    protected function avoidHackInheritanceField()
    {
        if ($this->hasCanConfigureInheritance() && !$this->getCanConfigureInheritance() && !$this->isObjectNew()) {
            $this->setInheritance($this->getOrigData('inheritance'));
        }
    }

    /**
     * @param DataFeedWatch_Connector_Model_Catalog_Attribute_Info $item
     */
    protected function fillAdditionalData($item)
    {
        $this->setOrigData('import_to_dfw', $item->getImportToDfw());
        $this->setOrigData('inheritance', $item->getInheritance());
        $this->setCanConfigureImport($item->getCanConfigureImport());
        $this->setCanConfigureInheritance($item->getCanConfigureInheritance());
    }

    /**
     * @return $this
     */
    protected function updateInheritanceConfigDate()
    {
        if ($this->canSaveUpdateDate()) {
            $this->helper()->updateLastInheritanceUpdateDate();
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function canSaveUpdateDate()
    {
        return ($this->dataHasChangedFor('inheritance') && (int)$this->getOrigData('import_to_dfw') === 1)
                || $this->dataHasChangedFor('import_to_dfw')
                || (int)$this->getData('import_to_dfw') === 1
                || $this->isObjectNew();
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('datafeedwatch_connector');
    }
}