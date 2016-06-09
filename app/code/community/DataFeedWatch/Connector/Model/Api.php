<?php

class DataFeedWatch_Connector_Model_Api
    extends Mage_Catalog_Model_Product_Api
{
    /**
     * @return DataFeedWatch_Connector_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('datafeedwatch_connector');
    }

    /**
     * @return string
     */
    public function version()
    {
        $this->helper()->log('datafeedwatch.version');
        $version = Mage::getConfig()->getNode()->modules->DataFeedWatch_Connector->version->__toString();
        $this->helper()->log($version);

        return $version;
    }

    /**
     * @return int
     */
    public function gmt_offset()
    {
        $this->helper()->log('datafeedwatch.gmt_offset');
        $timeZone = Mage::getStoreConfig('general/locale/timezone');
        $timeZone = new DateTimeZone($timeZone);
        $time     = new DateTime('now', $timeZone);
        $offset   = (int)($timeZone->getOffset($time) / 3600);
        $this->helper()->log($offset);

        return $offset;
    }

    /**
     * @return array
     */
    public function stores()
    {
        $this->helper()->log('datafeedwatch.stores');
        $storeViews = $this->getStoresArray();
        $this->helper()->log($storeViews);

        return $storeViews;
    }

    /**
     * @param array $options
     * @return array
     */
    public function products($options = array())
    {
        $this->helper()->log('datafeedwatch.products');
        $this->unsetUpdatedOptions($options);
        $this->filterOptions($options);
        $collection = $this->getProductCollection($options);
        $collection->applyInheritanceLogic();

        return $this->processProducts($collection);
    }

    /**
     * @param array $options
     * @return int
     */
    public function product_count($options = array())
    {
        $this->helper()->log('datafeedwatch.product_count');
        $this->unsetUpdatedOptions($options);
        $this->filterOptions($options);
        $collection = $this->getProductCollection($options);
        $amount     = (int) $collection->getSize();
        $this->helper()->log(sprintf('datafeedwatch.product_count %d', $amount));

        return $amount;
    }

    /**
     * @param array $options
     * @return array
     */
    public function updated_products($options = array())
    {
        $this->helper()->log('datafeedwatch.updated_products');
        $this->filterOptions($options);
        if (!$this->isFromDateEarlierThanConfigDate($options)) {
            $collection = $this->getProductCollection($options);
            $collection->applyInheritanceLogic();

            return $this->processProducts($collection);
        } else {
            $this->helper()->log('datafeedwatch.updated_products -> datafeedwatch.products');

            return $this->products($options);
        }
    }

    /**
     * @param array $options
     * @return int
     */
    public function updated_product_count($options = array())
    {
        $this->helper()->log('datafeedwatch.updated_product_count');
        $this->filterOptions($options);
        if (!$this->isFromDateEarlierThanConfigDate($options)) {
            $collection = $this->getProductCollection($options);
            $amount     = (int) $collection->getSize();
            $this->helper()->log(sprintf('datafeedwatch.updated_product_count %d', $amount));
        } else {
            $this->helper()->log('datafeedwatch.updated_product_count -> datafeedwatch.product_count');
            $amount = $this->product_count($options);
        }

        return $amount;
    }

    /**
     * @param array $options
     * @return array
     */
    public function product_ids($options = array())
    {
        $this->helper()->log('datafeedwatch.product_ids');
        $this->filterOptions($options);
        $collection = $this->getProductCollection($options);

        return $collection->getColumnValues('entity_id');
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function isFromDateEarlierThanConfigDate($options)
    {
        $this->helper()->log('START: Model/Api.php->isFromDateEarlierThanConfigDate()');
        if (!isset($options['from_date'])) {
            $this->helper()->log('$options[\'from_date\'] is not set');
            $this->helper()->log('END: Model/Api.php->isFromDateEarlierThanConfigDate()');

            return false;
        }
        $this->helper()->log('$options[\'from_date\']');
        $this->helper()->log($options['from_date']);
        $this->helper()->log('$this->helper()->getLastInheritanceUpdateDate()');
        $this->helper()->log($this->helper()->getLastInheritanceUpdateDate());
        $this->helper()->log('result');
        if ($options['from_date'] < $this->helper()->getLastInheritanceUpdateDate()) {
            $this->helper()->log('$options[\'from_date\'] < $this->helper()->getLastInheritanceUpdateDate()');
        } else {
            $this->helper()->log('From date is equal or greater');
        }
        $this->helper()->log('END: Model/Api.php->isFromDateEarlierThanConfigDate()');

        return $options['from_date'] < $this->helper()->getLastInheritanceUpdateDate();
    }

    /**
     * @return array
     */
    protected function getStoresArray()
    {
        $storeViews = array();
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {
                    $storeViews[$store->getCode()] = array(
                        'Website'       => $website->getName(),
                        'Store'         => $group->getName(),
                        'Store View'    => $store->getName(),
                    );
                }
            }
        }

        return $storeViews;
    }

    /**
     * @param array $options
     * @return DataFeedWatch_Connector_Model_Resource_Product_Collection
     */
    public function getProductCollection($options)
    {
        /** @var DataFeedWatch_Connector_Model_Resource_Product_Collection $collection */
        $collection = Mage::getResourceModel('datafeedwatch_connector/product_collection')->addAttributeToSelect('*');
        $collection->applyFiltersOnCollection($options);

        return $collection;
    }

    /**
     * @param array $options
     */
    public function filterOptions(&$options)
    {
        $this->helper()->log($options);

        if (isset($options['store'])) {
            $this->filterStoreOption($options);
        }

        if (isset($options['type'])) {
            $this->filterTypeOption($options);
        }

        if (isset($options['status'])) {
            $this->filterStatusOption($options);
        }

        if (isset($options['timezone'])) {
            $this->filterTimeZoneOption($options);
        }

        if (isset($options['updated_at'])) {
            $options['from_date'] = $options['updated_at'];
            unset($options['updated_at']);
        }

        if (isset($options['from_date'])) {
            $this->filterFromDateOption($options);
        }

        if (!isset($options['page'])) {
            $options['page'] = 1;
        }

        if (!isset($options['per_page'])) {
            $options['per_page'] = 100;
        }
    }

    /**
     * @param array $options
     */
    public function filterStoreOption(&$options)
    {
        $existingStoreViews = array_keys($this->getStoresArray());
        if (!in_array($options['store'], $existingStoreViews)) {
            $message = 'The store view %s does not exist. Default store will be applied';
            $this->helper()->log(sprintf($message, $options['store']));
            $options['store'] = Mage::app()->getDefaultStoreView()->getCode();
        }
        Mage::app()->setCurrentStore($options['store']);
    }

    /**
     * @param array $options
     */
    public function filterTypeOption(&$options)
    {
        $types          = $options['type'];
        $magentoTypes   = array(
            Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
            Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
            Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
            Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
            Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        );
        if (!is_array($types)) {
            $types = array($types);
        }
        $types = array_map('strtolower', $types);
        $types = array_intersect($types, $magentoTypes);
        if (!empty($types)) {
            $options['type'] = $types;
        } else {
            $this->helper()->log('The type below does not exist');
            $this->helper()->log($options['type']);
            unset($options['type']);
        }
    }

    /**
     * @param array $options
     */
    public function filterStatusOption(&$options)
    {
        $status = (string) $options['status'];
        if ($status === '0') {
            $options['status'] = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
        } else if ($status === '1') {
            $options['status'] = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        } else {
            $message = 'The status %s does not exist';
            $this->helper()->log(sprintf($message, $options['status']));
            unset($options['status']);
        }
    }

    /**
     * @param array $options
     */
    public function unsetUpdatedOptions(&$options)
    {
        unset($options['from_date']);
        unset($options['updated_at']);
        unset($options['timezone']);
    }

    /**
     * @param array $options
     */
    public function filterTimeZoneOption(&$options)
    {
        try {
            $options['timezone'] = new DateTimeZone($options['timezone']);
        } catch (Exception $e) {
            $this->helper()->log(sprintf('%s timezone is wrong', $options['timezone']));
            $options['timezone'] = null;
        }
    }

    /**
     * @param array $options
     */
    public function filterFromDateOption(&$options)
    {
        if (!isset($options['timezone'])) {
            $options['timezone'] = null;
        }
        try {
            $options['from_date'] = new DateTime($options['from_date'], $options['timezone']);
        } catch (Exception $e) {
            $this->helper()->log(sprintf('%s from_date is wrong', $options['from_date']));
            $options['from_date'] = new DateTime();
        }
        $options['from_date'] = $options['from_date']->format('Y-m-d H:i:s');
    }

    /**
     * @param DataFeedWatch_Connector_Model_Resource_Product_Collection $collection
     * @return array
     */
    protected function processProducts($collection)
    {
        $products = array();
        foreach ($collection as $product) {
            $products[] = $product->getDataToImport();
        }

        return $products;
    }
}