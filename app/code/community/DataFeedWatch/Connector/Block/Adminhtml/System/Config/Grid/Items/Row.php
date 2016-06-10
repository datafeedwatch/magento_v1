<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Items_Row
    extends Mage_Adminhtml_Block_Abstract
{
    /** @var  Mage_Catalog_Model_Resource_Eav_Attribute $attributeItem */
    public $attributeItem;

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Items_Row $this
     */
    public function setAttributeItem($attribute)
    {
        $this->attributeItem = $attribute;

        return $this;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    public function getAttributeItem()
    {
        return $this->attributeItem;
    }

    /**
     * @return string
     */
    public function getAttributeLabel()
    {
        $attribute  = $this->getAttributeItem();
        $label      = $attribute->getFrontendLabel();

        if (empty($label)) {
            $label = $attribute->getAttributeCode();
        }

        return $label;
    }

    /**
     * @return mixed
     */
    public function getAttributeLink()
    {
        $attribute = $this->getAttributeItem();

        return Mage::helper('adminhtml')->getUrl('adminhtml/catalog_product_attribute/edit', array(
            'attribute_id' => $attribute->getId(),
        ));
    }
}