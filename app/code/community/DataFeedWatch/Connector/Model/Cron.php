<?php

class DataFeedWatch_Connector_Model_Cron
    extends Mage_Catalog_Model_Resource_Product_Collection
{
    const CATALOGRULE_DATE_TABLE_ALIAS = 'catalogrule_product_price_date';

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function reindex()
    {
        $date               = date('Y-m-d H:i:s');
        $lastPriceId        = $this->helper()->getLastCatalogRulePriceId();
        $resource           = Mage::getSingleton('core/resource');
        $writeConnection    = $resource->getConnection('core_write');
        $select             = new Zend_Db_Select($this->getEntity()->getReadConnection());
        $select->from(
            array(
                self::CATALOGRULE_DATE_TABLE_ALIAS => $this->getTable('catalogrule/rule_product_price'),
            )
        );

        if (!empty($lastPriceId)) {
            $select->where('rule_product_price_id > ?', $lastPriceId);
        }
        $select->where('customer_group_id = ?', Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        $select->where('rule_date <= ?', $date);

        $priceData = $select->query()->fetchAll();
        if (count($priceData) < 1) {

            return $this;
        }

        $this->helper()->cronLog($select->__toString());
        $this->helper()->cronLog(count($priceData));

        $updatedDataTable = $this->getTable('datafeedwatch_connector/updated_products');
        foreach ($priceData as $data) {
            $insertedData = array(
                'dfw_prod_id'   => $data['product_id'],
                'updated_at'    => $date,
            );
            $writeConnection->insertOnDuplicate($updatedDataTable, $insertedData, array('updated_at'));
        }

        if (!empty($priceData)) {
            $data = end($priceData);
            $this->helper()->setLastCatalogRulePriceId($data['rule_product_price_id']);
        }

        return $this;
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('datafeedwatch_connector');
    }
}