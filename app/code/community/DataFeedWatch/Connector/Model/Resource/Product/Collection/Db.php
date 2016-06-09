<?php

class DataFeedWatch_Connector_Model_Resource_Product_Collection_Db
    extends Mage_Catalog_Model_Resource_Product_Collection
{
    const INHERITED_STATUS_TABLE_ALIAS                  = 'inherited_status';
    const INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE    = 'inherited_status_default_store';
    const ORIGINAL_STATUS_TABLE_ALIAS                   = 'original_status';
    const ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE     = 'status_default_store';
    const ORIGINAL_VISIBILITY_TABLE_ALIAS               = 'original_visibility';
    const VISIBILITY_TABLE_ALIAS_DEFAULT_STORE          = 'visibility_default_store';
    const PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE          = 'dfw_parent_ids_default_store';
    const ORIGINAL_PARENT_IDS_TABLE_ALIAS               = 'original_dfw_parent_ids';
    const MIXED_STATUS_COLUMN_ALIAS                     = 'filter_status';
    const PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS    = 'parent_configurable_attributes';
    const PARENT_RELATIONS_TABLE_ALIAS                  = 'parent_relation';
    const UPDATED_AT_TABLE_ALIAS                        = 'custom_updated_at';
    const CATALOGRULE_DATE_COLUMN_ALIAS                 = 'rule_date';

    /** @var string $filterStatusCondition */
    protected $filterStatusCondition;
    /** @var array $optionsFilters */
    protected $optionsFilters;
    /** @var  string $ruleDateSelect */
    protected $ruleDateSelect;

    /**
     * @param string $tableAlias
     * @return bool
     * @throws Zend_Db_Select_Exception
     */
    protected function isTableAliasAdded($tableAlias)
    {
        $tables         = $this->getSelect()->getPart(Zend_Db_Select::FROM);
        $currentAliases = array_keys($tables);

        return in_array($tableAlias, $currentAliases);

    }

    /**
     * @return $this
     */
    protected function addRuleDate()
    {
        /** @var DataFeedWatch_Connector_Model_Cron $cron */
        $cron = Mage::getModel('datafeedwatch_connector/cron');
        $cron->reindex();

        $condition  = $this->getUpdatedAtCondition();
        $select     = new Zend_Db_Select($this->getEntity()->getReadConnection());
        $select->from(
            array(
                self::UPDATED_AT_TABLE_ALIAS => $this->getTable('datafeedwatch_connector/updated_products'),
            ),
            array(
                sprintf('COALESCE(%1$s.updated_at, 0)', self::UPDATED_AT_TABLE_ALIAS),
            )
        );
        $select->where($condition);
        $select->limit(1);

        $this->ruleDateSelect = sprintf('GREATEST(IFNULL((%s), 0), COALESCE(%2$s.updated_at, 0))',
            $select->__toString(), self::MAIN_TABLE_ALIAS);
        $this->getSelect()->columns(array(self::CATALOGRULE_DATE_COLUMN_ALIAS => new Zend_Db_Expr($this->ruleDateSelect)));
        
        return $this;
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function getUpdatedAtCondition()
    {
        $condition = '(IFNULL(%3$s.value, %4$s.value) IS NOT NULL AND %1$s.dfw_prod_id IN (IFNULL(%3$s.value, %4$s.value)) OR %1$s.dfw_prod_id = %2$s.entity_id)';
        $condition = sprintf($condition,
            self::UPDATED_AT_TABLE_ALIAS, self::MAIN_TABLE_ALIAS, self::ORIGINAL_PARENT_IDS_TABLE_ALIAS, self::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE);

        return $condition;
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    protected function joinQty()
    {
        $this->joinField('qty',
            'cataloginventory/stock_item',
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left');

        return $this;
    }
    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function getStatusTable()
    {
        return Mage::registry(DataFeedWatch_Connector_Helper_Registry::DFW_STATUS_ATTRIBUTE_KEY)
                   ->getBackend()->getTable();
    }
    
    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function getVisibilityTable()
    {
        return Mage::registry(DataFeedWatch_Connector_Helper_Registry::DFW_STATUS_ATTRIBUTE_KEY)
                   ->getBackend()->getTable();
    }
    
    /**
     * @return string|int
     * @throws Mage_Core_Exception
     */
    protected function getVisibilityAttributeId()
    {
        return Mage::registry(DataFeedWatch_Connector_Helper_Registry::DFW_STATUS_ATTRIBUTE_KEY)->getAttributeId();
    }

    protected function buildFilterStatusCondition()
    {
        $childString        = 'IFNULL(%1$s.value, %3$s.value)';
        $parentString       = 'IFNULL(%2$s.value, %4$s.value)';
        $enable             = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        $statusAttribute    = Mage::registry(DataFeedWatch_Connector_Helper_Registry::DFW_STATUS_ATTRIBUTE_KEY);
        switch($statusAttribute->getInheritance()) {
            case (string) DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_THEN_PARENT_OPTION_ID:
                $inheritString = "IFNULL({$childString}, {$parentString})";
//                $inheritWithStatusString = 'IFNULL(
//                                IF(' . $childString . ' <> ' . $enable . ', ' . $parentString . ', ' . $childString . '),
//                                ' . $childString
//                    . ')';
                $notVisibleIndividually = "IF({$childString} <> {$enable}, {$childString}, {$parentString})";
                $string = 'IF(IFNULL(%5$s.value, %6$s.value) = '. Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
                          . ', ' . $notVisibleIndividually .', '. $inheritString.')';
                break;
            case (string) DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::PARENT_OPTION_ID:
                $string = 'IFNULL(' . $parentString . ', ' . $childString . ')';
                break;
            default :
                $string = $childString;
        }
        $this->filterStatusCondition = sprintf($string,
            self::ORIGINAL_STATUS_TABLE_ALIAS, self::INHERITED_STATUS_TABLE_ALIAS,
            self::ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE, self::INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE,
            self::ORIGINAL_VISIBILITY_TABLE_ALIAS, self::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     */
    protected function joinInheritedStatusTable($tableAlias = self::INHERITED_STATUS_TABLE_ALIAS, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {

            return $this;
        }

        $this->getSelect()->joinLeft(
            array($tableAlias => $this->getStatusTable()),
            $this->getJoinInheritedStatusTableStatement($tableAlias, $storeId),
            array(self::MIXED_STATUS_COLUMN_ALIAS => $this->filterStatusCondition));

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinInheritedStatusTableStatement($tableAlias, $storeId)
    {
        return sprintf('%1$s.entity_id IN (IFNULL(%2$s.value, %3$s.value)) and %4$s',
            $tableAlias, self::ORIGINAL_PARENT_IDS_TABLE_ALIAS, self::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE,
            $this->getJoinStatusAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     */
    protected function joinOriginalStatusTable($tableAlias = self::ORIGINAL_STATUS_TABLE_ALIAS, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {

            return $this;
        }

        $this->getSelect()->joinLeft(
            array($tableAlias => $this->getStatusTable()),
            $this->getJoinOriginalStatusTableStatement($tableAlias, $storeId),
            array(self::MIXED_STATUS_COLUMN_ALIAS => $this->filterStatusCondition));

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     */
    protected function joinVisibilityTable($tableAlias = self::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {

            return $this;
        }

        $this->getSelect()->joinLeft(
            array($tableAlias => $this->getVisibilityTable()),
            $this->getJoinVisibilityTableStatement($tableAlias, $storeId),
            array('value'));

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinVisibilityTableStatement($tableAlias, $storeId)
    {
        return sprintf('%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias, $this->getJoinVisibilityAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinVisibilityAttributeStatement($tableAlias, $storeId = '0')
    {
        $visibilityAttribute = Mage::registry(DataFeedWatch_Connector_Helper_Registry::DFW_VISIBILITY_ATTRIBUTE_KEY);
        return sprintf('%1$s.attribute_id = %2$s and %1$s.store_id = %3$s',
            $tableAlias, $visibilityAttribute->getId(), $storeId);
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinOriginalStatusTableStatement($tableAlias, $storeId)
    {
        return sprintf('%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias, $this->getJoinStatusAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinStatusAttributeStatement($tableAlias, $storeId = '0')
    {
        $statusAttribute = Mage::registry(DataFeedWatch_Connector_Helper_Registry::DFW_STATUS_ATTRIBUTE_KEY);
        return sprintf('%1$s.attribute_id = %2$s and %1$s.store_id = %3$s',
            $tableAlias, $statusAttribute->getId(), $storeId);
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     */
    protected function joinParentIdsTable($tableAlias = self::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {

            return $this;
        }

        $this->getSelect()->joinLeft(
            array($tableAlias => $this->getParentIdsTable()),
            $this->getJoinParentIdsTableStatement($tableAlias, $storeId),
            array('value'));
        $this->getSelect()->columns(sprintf('IFNULL(%1$s.value, %2$s.value) as parent_id', self::ORIGINAL_PARENT_IDS_TABLE_ALIAS, self::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE));

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinParentIdsTableStatement($tableAlias, $storeId)
    {
        return sprintf('%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias, $this->getJoinParentIdsAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinParentIdsAttributeStatement($tableAlias, $storeId = '0')
    {
        $attribute = Mage::registry(DataFeedWatch_Connector_Helper_Registry::DFW_PARENT_ID_ATTRIBUTE_KEY);
        return sprintf('%1$s.attribute_id = %2$s and %1$s.store_id = %3$s',
            $tableAlias, $attribute->getId(), $storeId);
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function getParentIdsTable()
    {
        return Mage::registry(DataFeedWatch_Connector_Helper_Registry::DFW_PARENT_ID_ATTRIBUTE_KEY)
                   ->getBackend()->getTable();
    }
}