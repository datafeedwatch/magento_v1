<?php

class DataFeedWatch_Connector_Model_Resource_Product_Collection
    extends DataFeedWatch_Connector_Model_Resource_Product_Collection_Db
{
    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }

    protected function _construct()
    {
        $this->_init('datafeedwatch_connector/product');
        $this->_initTables();
    }

    /**
     * Overwrite to set default $joinLeft value to true.
     *
     * @param bool $joinLeft
     * @return DataFeedWatch_Connector_Model_Resource_Product_Collection $this
     */
    protected function _productLimitationPrice($joinLeft = true)
    {
        parent::_productLimitationPrice($joinLeft);

        return $this;
    }

    /**
     * @param array $options
     * @return DataFeedWatch_Connector_Model_Resource_Product_Collection $this
     */
    public function applyFiltersOnCollection($options)
    {
        $this->helper()->log($options);
        $this->optionsFilters = $options;
        $this->applyStoreFilter();
        $this->registryHelper()->initImportRegistry($this->getStoreId());
        $this->addRuleDate();
        $this->joinRelationsTable();
        $this->joinVisibilityTable(DataFeedWatch_Connector_Model_Resource_Product_Collection_Db::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE, '0');
        $this->joinVisibilityTable(DataFeedWatch_Connector_Model_Resource_Product_Collection_Db::ORIGINAL_VISIBILITY_TABLE_ALIAS, $this->getStoreId());
        $this->applyTypeFilter();
        $this->joinQty();
        $this->addFinalPrice();
        $this->addUrlRewrite();
        $this->applyStatusFilter();
        $this->applyUpdatedAtFilter();
        $this->addAttributeToSelect('ignore_datafeedwatch');
        $this->addAttributeToFilter('ignore_datafeedwatch', array(array('null' => true),array('neq' => 1)), 'left');


        $this->setPage($this->optionsFilters['page'], $this->optionsFilters['per_page']);
        $this->helper()->sqlLog($this->getSelect()->__toString());

        return $this;
    }

    /**
     * @param $store
     * @return DataFeedWatch_Connector_Model_Resource_Product_Collection $this
     */
    public function applySpecificStore($store)
    {
        $store                          = Mage::app()->getStore($store);
        $this->optionsFilters['store']  = $store->getId();

        return $this->applyStoreFilter();
    }

    /**
     * @return $this
     */
    protected function applyStoreFilter()
    {
        if (isset($this->optionsFilters['store'])) {
            $store          = Mage::getModel('core/store')->load($this->optionsFilters['store']);
            $StoreColumn    = sprintf('IFNULL(null, %s) as store_id', $store->getId());
            $this->setStoreId($store->getId());
            $this->addStoreFilter($store);
            $this->getSelect()->columns($StoreColumn);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function applyTypeFilter()
    {
        if (isset($this->optionsFilters['type'])) {
            $this->addAttributeToFilter('type_id', array('in' => $this->optionsFilters['type']));
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function applyUpdatedAtFilter()
    {
        if (!isset($this->optionsFilters['from_date'])) {

            return $this;
        }

        $this->getSelect()->where($this->ruleDateSelect . ' >= ?', $this->optionsFilters['from_date']);

        return $this;
    }

    /**
     * @return $this
     */
    protected function applyStatusFilter()
    {
        if (!isset($this->optionsFilters['status'])) {

            return $this;
        }

        if ($this->registryHelper()->isStatusAttributeInheritable()) {
            $this->buildFilterStatusCondition();
            $this->joinInheritedStatusTable(self::INHERITED_STATUS_TABLE_ALIAS, $this->getStoreId())
            ->joinInheritedStatusTable(self::INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE, '0')
            ->joinOriginalStatusTable(self::ORIGINAL_STATUS_TABLE_ALIAS, $this->getStoreId())
            ->joinOriginalStatusTable(self::ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE, '0');
            $this->getSelect()->where($this->filterStatusCondition . ' = ?', $this->optionsFilters['status']);
        } else {
            $this->addAttributeToFilter('status', $this->optionsFilters['status']);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function addParentData()
    {
        $this->joinRelationsTable();
        $parentCollection = $this->getParentProductsCollection();
        $parentCollection = $parentCollection->getItems();
        foreach ($this->getItems() as $product) {
            $parentId = $product->getParentId();
            if (empty($parentId) || !isset($parentCollection[$parentId])) {
                continue;
            }
            $product->setParent($parentCollection[$parentId]);
        }

        return $this;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function getParentProductsCollection()
    {
        $parentCollection = Mage::getResourceModel('datafeedwatch_connector/product_collection')
            ->addAttributeToSelect('*')
            ->addUrlRewrite()
            ->addFinalPrice()
            ->applySpecificStore($this->optionsFilters['store']);

        $parentCollection->getSelect()->joinLeft(
            array(self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS =>
                    $this->getTable('catalog/product_super_attribute'),
            ),
            sprintf('%s.product_id = e.entity_id', self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS),
            array('super_attribute_ids' =>
                sprintf('GROUP_CONCAT(DISTINCT %s.attribute_id)', self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS),
            )
        );

        $parentCollection->getSelect()->joinRight(
            array(self::PARENT_RELATIONS_TABLE_ALIAS => $this->getTable('catalog/product_relation')),
            sprintf('%s.parent_id = e.entity_id', self::PARENT_RELATIONS_TABLE_ALIAS),
            array('parent_id' => sprintf('%s.parent_id', self::PARENT_RELATIONS_TABLE_ALIAS))
        )->group('e.entity_id');

        return $parentCollection;
    }

    /**
     * @return $this
     */
    public function applyInheritanceLogic()
    {
        $this->addParentData();
        foreach ($this->getItems() as $product) {
            $parent = $product->getParent();
            if (!empty($parent)) {
                $product->getParentAttributes();
            }
        }

        return $this;
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Registry
     */
    public function registryHelper()
    {
        return Mage::helper('datafeedwatch_connector/registry');
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('datafeedwatch_connector');
    }
}