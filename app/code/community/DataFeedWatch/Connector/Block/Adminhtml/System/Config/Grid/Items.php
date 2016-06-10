<?php

class DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Items
    extends Mage_Adminhtml_Block_Abstract
{
    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return string
     */
    public function getItemRow($attribute)
    {
        return $this->getChild('datafeedwatch_connector_inheritance_grid_items_row')->setAttributeItem($attribute)->toHtml();
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getCollection()
    {
        return $this->getPager()->getCollection();
    }

    /**
     * @param int $page
     * @return DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Items $this
     */
    public function setPage($page)
    {
        if (!empty($page)) {
            $this->getPager()->setPage($page);
        }

        return $this;
    }

    /**
     * @param int $limit
     * @return DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Items $this
     */
    public function setLimit($limit)
    {
        if (!empty($limit)) {
            $this->getPager()->setLimit($limit);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getPager()->toHtml();
    }

    /**
     * @return DataFeedWatch_Connector_Block_Adminhtml_System_Config_Grid_Pager
     */
    public function getPager()
    {
        return $this->getChild('datafeedwatch_connector_inheritance_grid_pager');
    }
}