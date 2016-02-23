<?php
class DataFeedWatch_Connector_Helper_Data extends Mage_Core_Helper_Abstract
{

    public $storeId;

    private $_collection;
    private $_requestOptions;

    public function prepareCollection($options,$applyFilters = true){

        //make sure status is int filter, not string
        if(array_key_exists('status',$options)){
            $options['status'] = (int) $options['status'];
        }

        $this->_requestOptions = $options;

        /**
         * Since this method is also called for product_count API method,
         * do not limit records within prepareCollection
         */
        unset($this->_requestOptions['page']);
        unset($this->_requestOptions['per_page']);

        $this->_collection = Mage::getModel('catalog/product')->getCollection();
        /**
         * selectCollectionStore is still required
         * so we filter by storeview value
         */
        $this->selectCollectionStore();

        $this->handleMagentoFilters();
        $this->handleIgnoreDataFeedWatch();

        return $this;
    }

    public function getCollection(){
        if($this->_collection != null) {
            return $this->_collection;
        }
        return null;
    }

    public function handleMagentoFilters(){

        $coreApiHelper = Mage::helper('api');
        $dataFeedWatchFilterHelper = Mage::helper('connector/filter');

        /***
         * @TODO: make sure type filter this never reaches this place, is should only work just before returning items
         */
        unset($this->_requestOptions['type']);

        if (method_exists($coreApiHelper, 'parseFilters')) {
            /* Use core methods if available - Magento 1.5.2.0 or newer */
            $filters = $coreApiHelper->parseFilters($this->_requestOptions, $dataFeedWatchFilterHelper->filtersMap);
        } else {
            /* Use methods from DFW if core methods not available.
            added to support older releases (version < 1.5.2.0) without parseFilters method */
            $filters = $dataFeedWatchFilterHelper->parseFiltersReplacement($this->_requestOptions, $dataFeedWatchFilterHelper->filtersMap);
        }

        try {
            /* Ignore status and store when flat catalog is enabled.
               These fields are not available in flat table so calling them will throw an Exception */
            if (Mage::helper('catalog/product_flat')->isEnabled()){
                $fieldsToIgnore = array('store','status');
                foreach($fieldsToIgnore as $field){
                    unset($filters[$field]);
                }
            }

            foreach ($filters as $field => $value) {
                if($field =='store') {
                    $this->_collection->setStoreId($this->storeId);
                } else {
                    if($field=='status'){
                        $this->_collection->addAttributeToFilter($field, $value);
                    } else {
                        $this->_collection->addFieldToFilter($field, $value);
                    }
                }
            }
        } catch (Mage_Core_Exception $e) {
            throw new Exception('filters_invalid', $e->getCode(), $e);
        }
        return $this;
    }

    public function selectCollectionStore(){
        /* determine store for collection */
        if (array_key_exists('store', $this->_requestOptions)) {
            //convert store code to store id
            if (!is_numeric($this->_requestOptions['store'])) {
                $this->_requestOptions['store'] = Mage::app()->getStore($this->_requestOptions['store'])->getId();
            }

            if ($this->_requestOptions['store']) {
                $this->storeId = $this->_requestOptions['store'];
                $this->setStoreId($this->storeId);
                Mage::app()->setCurrentStore($this->storeId);
            }
        }
        $this->_collection->addStoreFilter($this->storeId);

        /* Get visilibity for collect storeView */
        $this->_collection->addAttributeToSelect('*')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $this->storeId)
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $this->storeId);
    }

    public function handleIgnoreDataFeedWatch(){
        /* Check if the store has ignore_datafeedwatch_attribute and collection should apply it */
        $attributeModel = Mage::getModel('eav/entity_attribute');
        $attributeId = $attributeModel->getIdByCode('catalog_product', 'ignore_datafeedwatch');

        if ($attributeId > 0) {
            $this->_collection->addAttributeToFilter(
                array(
                    array('attribute'=>'ignore_datafeedwatch', 'neq'=> 1),
                    array('attribute'=>'ignore_datafeedwatch', 'null'=> true),
                ),
                '',
                'left'
            );
        }
    }

    public function shouldSkipProduct($product,$parent_product){
        if(is_object($parent_product) && $parent_product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED
            && $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE){

            if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
                Mage::log('' . $product->getSku() . ' - parent (sku: ' . $parent_product->getSku() . ') is disabled, and product is NVI', null, 'dfw_skipped_skus.log');
            }

            return true;
        }
        return false;
    }

    public function getParentProductFromChild($product){

        $dataFeedWatchHelper = Mage::helper('connector');

        if ($product->getTypeId() == "simple") {

            /* check if the product is grouped */
            $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());

            /* if not grouped, check if configurable */
            if (!$parentIds) {
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            }

            /* if at least one parent has been found in child details */
            if (isset($parentIds[0])) {

                $parent_product = Mage::getModel('catalog/product')->setStoreId($dataFeedWatchHelper->storeId)->load($parentIds[0]);
                /* @var $parent_product Mage_Catalog_Model_Product_Type_Configurable */

                while (!$parent_product->getId()) {
                    if (count($parentIds) > 1) {
                        //parent not found, remove and retry with next one
                        array_shift($parentIds);
                        $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);
                    } else {
                        break;
                    }
                }
                return $parent_product;
            }
        }

        return null;
    }

    public function getUpdatedProductList($options){
        $dataFeedWatchHelper = $this;
        /* @var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */

        $fetchList = array();

        if (!isset($options['updated_at'])){
            return 'No updated_at option given! Please provide datetime in following format: 2015-03-25 23:34:59';
        }

        $updatedAt = $options['updated_at'];
        $updatedAt = date("Y-m-d H:i:s",strtotime($updatedAt));

        /*remove updated at filter, we do not want to use it on normal call*/
        unset($options['updated_at']);

        /* Use default page if not set */
        if (!array_key_exists('page', $options)) {
            $options['page'] = 0;
        }

        /* Use default limit if not set */
        if (!array_key_exists('per_page', $options)) {
            $options['per_page'] = 100;
        }

        /* Get Product Collection by updated_at field */
        $collection = $dataFeedWatchHelper->prepareCollection($options)->getCollection();
        $collection->addFieldToFilter('updated_at',array('gteq' => $updatedAt));

        foreach($collection as $product){
            $fetchList[]=$product->getId();


            /* add configurable children to fetchlist */
            if ($product->getTypeId() == "configurable") {
                $product = Mage::getModel('catalog/product')->load($product->getId());
                $childProducts = Mage::getModel('catalog/product_type_configurable')
                    ->getUsedProducts(null, $product);
                if ($childProducts) {
                    foreach ($childProducts as $child) {
                        $fetchList[] = $child->getId();
                    }
                }
            }
        }

        /* Catalog price rules */
        /* @TODO: move to our catalogrule model, should return $promotionAffectedProducts array */
        $promotionAffectedProducts = array();
        $catalogRuleDates = Mage::getModel('connector/catalogrule_info')->getCollection();
        $catalogRuleDates->addFieldToFilter('updated_at',array('gteq' => $updatedAt));
        if($catalogRuleDates) {
            foreach ($catalogRuleDates as $info) {
                $rule = Mage::getModel('catalogrule/rule')->load($info->getCatalogruleId());
                $productIds = $rule->getMatchingProductIds();
                foreach ($productIds as $productId => $ifApplicable) {
                    $uniqueValues = array_values(array_unique($ifApplicable));
                    if (count($uniqueValues) == 1 && $uniqueValues[0] == 1) {
                        $promotionAffectedProducts[] = (int)$productId;
                    }
                }
            }
        }

        /* Checkout price rules */
        /* @TODO: move to our salesrule model, should return $promotionAffectedProducts array */
        $saleRuleDates = Mage::getModel('connector/salesrule_info')->getCollection();
        $saleRuleDates->addFieldToFilter('updated_at',array('gteq' => $updatedAt));

        if($saleRuleDates) {
            foreach ($saleRuleDates as $info) {
                $rule = Mage::getModel('catalogrule/rule')->load($info->getSalesruleId());
                $productIds = $rule->getMatchingProductIds();
                foreach ($productIds as $productId => $ifApplicable) {
                    $uniqueValues = array_values(array_unique($ifApplicable));
                    if (count($uniqueValues) == 1 && $uniqueValues[0] == 1) {
                        $promotionAffectedProducts[] = (int)$productId;
                    }
                }
            }
        }

        if(count($promotionAffectedProducts)>0) {
            $promotionAffectedProducts = array_values(array_unique($promotionAffectedProducts));
            foreach ($promotionAffectedProducts as $productId) {
                /* Add to fetchlist */
                $fetchList[] = (int)$productId;

                /* Foreach promoted products, make sure that also children are fetched. */
                $product = Mage::getModel('catalog/product')->load($productId);
                if ($product->getTypeId() == "configurable") {
                    $childProducts = Mage::getModel('catalog/product_type_configurable')
                        ->getUsedProducts(null, $product);
                    if ($childProducts) {
                        foreach ($childProducts as $child) {
                            $fetchList[] = $child->getId();
                        }
                    }
                }
            }
        }

        return $fetchList;
    }

    public function setStoreId($storeId){
        $this->storeId = $storeId;
    }

    public function isSupportedEnterprise(){
        /* has been tested with this EE version and works completely */
        $supportedEnterprise = array(
            'major' => '1',
            'minor' => '13',
            'revision' => '0',
            'patch' => '2',
            'stability' => '',
            'number' => '',
        );

        $mageObject = new Mage;
        /* @var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */
        $versionInfo = Mage::getVersionInfo();
        /* If we have Enterprise Edition, make sure our current Enterprise is supported */
        if (method_exists($mageObject, 'getEdition')
            && Mage::getEdition() == Mage::EDITION_ENTERPRISE
            && version_compare(implode('.',$versionInfo),implode('.',$supportedEnterprise),'>=')
        ) {
            return true;
        }
        return false;
    }
}

