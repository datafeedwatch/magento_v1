<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Pager
    extends Mage_Adminhtml_Block_Abstract
{
    public $page    = 1;
    public $limit   = 10;
    /**
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getCollection()
    {
        $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->setPageSize($this->limit)
            ->setCurPage($this->page);
        $attributeCollection->getSelect()->joinLeft(
            array('cai' => Mage::getModel('core/resource')
                                                  ->getTableName('datafeedwatch_connector/catalog_attribute_info')),
            'cai.catalog_attribute_id = main_table.attribute_id'
        )
        ->where('cai.can_configure_inheritance != 0 and cai.import_to_dfw != 0 or cai.can_configure_inheritance = 1');
        $attributeCollection->setOrder('frontend_label', 'asc');
        
        return $attributeCollection;
    }

    /**
     * @param int $page
     * @return DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Pager $this
     */
    public function setPage($page)
    {
        if (!empty($page) && is_numeric($page)) {
            $this->page = $page;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return (int)$this->page;
    }

    /**
     * @param int $limit
     * @return DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Pager $this
     */
    public function setLimit($limit)
    {
        if (!empty($limit) && is_numeric($limit)) {
            $this->limit = $limit;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return (int)$this->limit;
    }
}