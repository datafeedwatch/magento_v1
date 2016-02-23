<?php

/**
 * @TODO: add $this->_fault calls for better exception handling and error reporting!
 * Class DataFeedWatch_Connector_Model_Datafeedwatch_Api
 */
class DataFeedWatch_Connector_Model_Datafeedwatch_Api extends Mage_Catalog_Model_Product_Api
{

    private $_logFileName = 'dfw_skipped_skus.log';

    public $storeRootCategoryId = 2;

    public $categories = array();
    public $storeCategories = array();

    public function __construct()
    {
        /* @TODO: add check for the setings so we don't override it with smaller value! */
        //ini_set('memory_limit', '4096M');
    }

    /**
     * @return array
     */
    public function stores()
    {
        $returned = array();
        foreach (Mage::app()->getWebsites() as $website) {
            /* @var $website Mage_Core_Model_Website */
            foreach ($website->getGroups() as $group) {
                /* @var $group Mage_Core_Model_Store_Group */
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    /* @var $store Mage_Core_Model_Store */
                    $returned[$store->getCode()] = array(
                        'Website' => $website->getName(),
                        'Store' => $group->getName(),
                        'Store View' => $store->getName(),
                    );
                }
            }
        }
        return $returned;
    }

    /**
     * @param array $options
     * @return mixed
     * @throws Mage_Api_Exception
     */
    public function product_ids($options = array())
    {
        $dataFeedWatchHelper = Mage::helper('connector');
        /* @var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */

        if (!array_key_exists('page', $options)) {
            $options['page'] = 0;
        }

        if (!array_key_exists('per_page', $options)) {
            $options['per_page'] = 100;
        }
        $collection = $dataFeedWatchHelper->prepareCollection($options)
            ->getCollection()
            ->setPage($options['page'],$options['per_page']);

        if(count($collection)>0){
            foreach($collection as $product){

                if ($product->getTypeId() == "simple") {
                    $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
                    if (!$parentIds) {
                        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                        if (isset($parentIds[0])) {
                            $isConfigurable = true;
                        }
                    }

                    if (isset($parentIds[0])) {
                        $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);
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

                        //do not include variant products that will not be fetched by products method
                        if ($dataFeedWatchHelper->shouldSkipProduct($product,$parent_product)) {
                            continue;
                        }
                    }
                    $products[] = $product->getId();
                }else {
                    $products[] = $product->getId();
                }
            }
            return array_values($products);
        }

        return array();
    }

    /**
     * @return string
     */
    public function version()
    {
        return (string)Mage::getConfig()->getNode('modules/DataFeedWatch_Connector')->version;
    }

    /**
     * @param array $options
     * @return int
     * @throws Mage_Api_Exception
     */
    public function product_count($options = array())
    {
        $finalTypeFilter = null;
        if(isset($options['status'])) {
            $finalTypeFilter = $options['type'];
            unset($options['type']);
        }

        $finalStatusFilter = null;
        if(isset($options['status'])) {
            $finalStatusFilter = $options['status'];
            unset($options['status']);
        }

        $products = array();

        $attributeLogicList = unserialize(Mage::getStoreConfig('datafeedwatch/settings/attribute_logic'));

        $dataFeedWatchHelper = Mage::helper('connector');

        if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
            Mage::log(__METHOD__, null, $this->_logFileName);
            Mage::log($options, null, $this->_logFileName);
        }

        /* var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */
        $collection = $dataFeedWatchHelper->prepareCollection($options)
            ->getCollection();

        if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
            Mage::log('initial collection to parse has ' . $collection->count() . ' products', null, $this->_logFileName);
        }

        foreach($collection as $product){
            $result = Mage::getModel('connector/product_result');
            //re-setters
            $isConfigurable = false;

            //reload product to get all attributes for particular store
            if ($dataFeedWatchHelper->storeId) {
                $product = Mage::getModel('catalog/product')->setStoreId($dataFeedWatchHelper->storeId)->load($product->getId());
            } else {
                $product = Mage::getModel('catalog/product')->load($product->getId());
            }

            /* @var $product Mage_Catalog_Model_Product */
            $result->setProduct($product);

            $result
                ->setValueOf('product_id',$product->getId())
                ->setValueOf('sku',$product->getSku())
                ->setValueOf('product_type',$product->getTypeId());

            $parent_product = $dataFeedWatchHelper->getParentProductFromChild($product);
            /* @var $parent_product Mage_Catalog_Model_Product */

            if($parent_product) {
                $result->setParentProduct($parent_product);
                $configurableParentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                if(in_array($parent_product->getId(),$configurableParentIds)){
                    $isConfigurable = true;
                }
            }

            /* Do not return the product if we should skip it */
            /* Do that only for parent & child_then_parent logic */
            $fetchingUpdatedProducts = false; //always false in this method
            $shouldSkipProduct = $dataFeedWatchHelper->shouldSkipProduct($product, $parent_product);
            if (
                $attributeLogicList['status'] != 'child'
                && !$fetchingUpdatedProducts
                && $shouldSkipProduct
            ) {
                if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
                    Mage::log('' . $product->getSku() . ' - shouldSkipProduct = ' . var_export($shouldSkipProduct, 1) . ' and status is NOT from child, but ' . $attributeLogicList['status'], null, $this->_logFileName);
                }
                continue;
            }


            //get inherited status and visibility -start
            /* @TODO: This code is partially used in products method. Refactor this for re-usable method */
            foreach(array('status','visibility') as $attributeCode) {

                $attributeLogic = $attributeLogicList[$attributeCode];
                if(!$attributeLogic){
                    $attributeLogic=null;
                }

                $value = $result->getAttributeByLogic($attributeCode, $attributeLogic);
                $targetProduct = $result->getAttributeProductByLogic($attributeCode, $attributeLogic);

                /* Check if there's no mapped value for that value */
                /* AND it is number */
                /* BUT not a price or is_in_stock or product_id or tax_class_id */
                if (is_numeric($value)
                    && !stristr($attributeCode,'price')
                    && !in_array($attributeCode,array('is_in_stock','product_id','tax_class_id'))
                ) {
                    //Mage::log('foreacing - '.$attributeCode);
                    $value = $targetProduct->getResource()->getAttribute($attributeCode)->getFrontend()->getValue($targetProduct);

                    /* Trim for better display */
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                }
                $result->setValueOf($attributeCode, $value);
            }
            //get inherited status and visibility -stop

            if ($product->getTypeId() == "simple") {
                $shouldSkipProduct = $dataFeedWatchHelper->shouldSkipProduct($product, $parent_product);
                //do not include variant products that will not be fetched by products method
                if (
                    $attributeLogicList['status'] != 'child'
                    && $shouldSkipProduct
                ) {
                    if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
                        Mage::log('skipping ' . $product->getSku() . '- shouldSkipProduct2 = ' . var_export($shouldSkipProduct, 1) . '', null, $this->_logFileName);
                    }
                    continue;
                }
            }

            /* if product matches finaltype filter */
            if(
                is_array($finalTypeFilter) && in_array($result->getValueOf('product_type'),$finalTypeFilter)
                || !is_array($finalTypeFilter)
            ){

                //AND if product matches final status filter
                if($finalStatusFilter === 0 || $finalStatusFilter === 1
                    || $finalStatusFilter ==='0' || $finalStatusFilter === '1' ) {
                    $statusLabels =  Mage_Catalog_Model_Product_Status::getOptionArray();
                    $statusLabels[0] =$statusLabels[2];
                    $statusLabels[1] =$statusLabels[1];
                    $statusFilterLabel = $statusLabels[$finalStatusFilter];
                    //Mage::log('id:'.$result->getValueOf('product_id').'  label:'.$statusFilterLabel.' status:'.$result->getValueOf('status').' ');
                    if($result->getValueOf('status')==$statusFilterLabel) {
                        $products[] = $result->getResult();
                    } else {
                        if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
                            Mage::log('' . $product->getSku() . ' status ' . $result->getValueOf('status') . ' does not match expected ' . $statusFilterLabel . ', skipping', null, $this->_logFileName);
                        }
                    }
                } else {
                    //if no status filter given
                    $products[] = $result->getResult();
                }
            } else {
                if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
                    Mage::log('product ' . $product->getSku() . ' skipped, not in product_type filter', null, $this->_logFileName);
                }
            }

            /* Get rid of our global product object from registry */
            Mage::unregister('datafeedwatch_connector_result');

        }
        $numberOfProducts = count($products);
        if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
            Mage::log('initial collection to parse has ' . $collection->count() . ' products', null, $this->_logFileName);
        }
        /* @deprecated since this doesn't apply filters based on status
        $numberOfProducts = 0;
        if (!empty($collection)) {
        $numberOfProducts = $collection->getSize();
        }
         */

        return $numberOfProducts;
    }

    /**
     * @param array $options
     * @return array
     * @throws Mage_Api_Exception
     */
    public function products($options = array(), $fetchingUpdatedProducts = false)
    {
        $attributeLogicList = unserialize(Mage::getStoreConfig('datafeedwatch/settings/attribute_logic'));

        /* Do not process type and status for magento collection, filter them at the end */

        $finalTypeFilter = null;
        if(isset($options['status'])) {
            $finalTypeFilter = $options['type'];
            unset($options['type']);
        }

        $finalStatusFilter = null;
        if(isset($options['status'])) {
            $finalStatusFilter = $options['status'];
            unset($options['status']);
        }

        $dataFeedWatchHelper = Mage::helper('connector');
        /* @var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */

        /* Use default page if not set */
        if (!array_key_exists('page', $options)) {
            $options['page'] = 0;
        }

        /* Use default limit if not set */
        if (!array_key_exists('per_page', $options)) {
            $options['per_page'] = 99999;
        }

        if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
            Mage::log(__METHOD__, null, $this->_logFileName);
            Mage::log($options, null, $this->_logFileName);
        }

        /* Get Product Collection */
        $collection = $dataFeedWatchHelper
            ->prepareCollection($options)
            ->getCollection()
            ->setPage($options['page'], $options['per_page']);

        /* Set current store using storeId got in prepareCollection */
        $store = Mage::app()->getStore($dataFeedWatchHelper->storeId);

        /* Clear options that are not product filters and were meant only for prepareCollection */
        /* page and per_page already removed in preparecollection */
        unset($options['store']);

        /* set current store manually so we get specific store url returned in getBaseUrl */
        $this->storeRootCategoryId = Mage::app()->getStore($dataFeedWatchHelper->storeId)->getRootCategoryId();

        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        /* prepare categories and storeCategories */
        $categoryHandler = Mage::helper('connector/dynamic_category');
        $categoryHandler->loadCategories($this->storeRootCategoryId);

        $products = array();
        if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
            Mage::log('initial collection to parse has ' . $collection->count() . ' products', null, $this->_logFileName);
        }

        foreach ($collection as $product) {
            $result = Mage::getModel('connector/product_result');

            //re-setters
            $isConfigurable = false;
            //reload product to get all attributes for particular store
            if ($dataFeedWatchHelper->storeId) {
                $product = Mage::getModel('catalog/product')->setStoreId($dataFeedWatchHelper->storeId)->load($product->getId());
            } else {
                $product = Mage::getModel('catalog/product')->load($product->getId());
            }
            /* @var $product Mage_Catalog_Model_Product */
            $result->setProduct($product);

            $result
                ->setValueOf('product_id',$product->getId())
                ->setValueOf('sku',$product->getSku())
                ->setValueOf('product_type',$product->getTypeId());


            $parent_product = $dataFeedWatchHelper->getParentProductFromChild($product);
            /* @var $parent_product Mage_Catalog_Model_Product */

            if($parent_product) {
                $result->setParentProduct($parent_product);
                $configurableParentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                if(in_array($parent_product->getId(),$configurableParentIds)){
                    $isConfigurable = true;
                }
            }

            /* Do not return the product if we should skip it */
            /* Do that only for parent & child_then_parent logic */
            $shouldSkipProduct = $dataFeedWatchHelper->shouldSkipProduct($product, $parent_product);
            if (
                $attributeLogicList['status'] != 'child'
                && !$fetchingUpdatedProducts
                && $shouldSkipProduct
            ) {
                if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
                    Mage::log('' . $product->getSku() . '- shouldSkipProduct = ' . var_export($shouldSkipProduct, 1) . ' and status is NOT from child, but ' . $attributeLogicList['status'], null, $this->_logFileName);
                }
                continue;
            }

            /* Get attribute settings */
            /* user attributes selected in Admin -> Catalog -> Datafeedwatch -> Settings */
            $selected_attributes = $this->synced_fields();

            /* hardcoded attributes list to fetch */
            $attributeHelper = Mage::helper('connector/attribute')->set($result);
            $requiredAttributes = $attributeHelper->getRequiredAttributes();

            /* join two of the above into one */
            /**
             * @TODO: move getAllowedAttributes to separate helper method
             */
            $allowedAttributes = array_merge($selected_attributes, $requiredAttributes);
            $unprocessedAttributes = array_values($allowedAttributes);

            /* attributes that should never be returned */
            $excludedAttributes = $attributeHelper->getExcludedAttributes();


            foreach ($allowedAttributes as $attributeCode) {

                /* Ignore excluded attributes */
                if (array_key_exists($attributeCode, $excludedAttributes)) {
                    unset($unprocessedAttributes[$attributeCode]);
                    continue;
                }

                /* Only use user-selected fields from DataFeedWatch -> Settings + required attributes */
                if (in_array($attributeCode, $allowedAttributes)){

                    $attributeLogic = $attributeLogicList[$attributeCode];
                    if(!$attributeLogic){
                        $attributeLogic=null;
                    }

                    $value = $result->getAttributeByLogic($attributeCode,$attributeLogic);
                    $targetProduct = $result->getAttributeProductByLogic($attributeCode,$attributeLogic);

                    /* Check if there's no mapped value for that value */
                    /* AND it is number */
                    /* BUT not a price or is_in_stock or product_id */
                    if (is_numeric($value)
                        && !stristr($attributeCode,'price')
                        && !in_array($attributeCode,array('is_in_stock','product_id','tax_class_id'))
                    ) {
                        //Mage::log('foreacing - '.$attributeCode);
                        $value = $targetProduct->getResource()->getAttribute($attributeCode)->getFrontend()->getValue($targetProduct);

                        /* Trim for better display */
                        if(is_string($value)) {
                            $value = trim($value);
                        }
                    }
                    $result->setValueOf($attributeCode,$value);
                } else {
                    //if you ever decide to log this:
                    //Mage::log('attr_code: '.$attributeCode.' was not synced - not allowed',null,'datafeedwatch_connector.log');
                }
                unset($unprocessedAttributes[$attributeCode]);
            }

            // add some parent attributes
            $dynamicHandler = Mage::helper('connector/dynamic_handler');
            if ($result->getParentProduct() && $isConfigurable && ($product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)) {
                $useParent = 1;
                $dynamicHandler->addDynamicAttributesToResult($result,$useParent);
            } else {
                $useParent = 0;
                $dynamicHandler->addDynamicAttributesToResult($result,$useParent);
            }

            // get variant name and default flag
            if ($product->getTypeId() == "simple") {
                // which is child of some parent product
                if ($result->getParentProduct()) {
                    if ($parent_product->getTypeInstance(true) instanceof Mage_Catalog_Model_Product_Type_Configurable) {

                        $result->setValueOf('variant_name',$product->getName());
                        $defaultVariantHandler = Mage::helper('connector/dynamic_dfw_default_variant');
                        $defaultVariantHandler->addDefaultVariantFlag($result);

                    } else {
                        // item has a parent because it extends Mage_Catalog_Model_Product_Type_Grouped
                        // it has no effect on price modifiers, however, so we ignore it
                    }
                }
            }

            //add prices and custom price fields
            $priceHandler = Mage::helper('connector/attribute_price');
            $priceHandler->addPricesToResult($result);

            //add in stock and qty information
            /**
             * @TODO: rewrite
             */
            $attributeHelper->addStockInfoToResult($result);

            /** Handle dynamic attributes
             * @TODO: put into separate method
             */

            // adding currency code ex. 'USD', this is NOT product attribute, but store setting
            $result->setValueOf('currency_code',$store->getCurrentCurrencyCode());

            /* parent_url */
            $parentProductUrlHandler = Mage::helper('connector/dynamic_parent_url');
            $parentProductUrl = $parentProductUrlHandler->getAttributeByLogic('parent_url','parent');
            $result->setValueOf('parent_url',$parentProductUrl);

            /* parent_id */
            $parentProductIdHandler = Mage::helper('connector/dynamic_parent_id');
            $parentProductId = $parentProductIdHandler->getAttributeByLogic('parent_id','parent');
            $result->setValueOf('parent_id',$parentProductId);

            /* parent_sku */
            $parentProductSkuHandler = Mage::helper('connector/dynamic_parent_sku');
            $parentProductSku = $parentProductSkuHandler->getAttributeByLogic('parent_sku','parent');
            $result->setValueOf('parent_sku',$parentProductSku);

            /* product_url */
            $productUrlHandler = Mage::helper('connector/dynamic_product_url');
            $productUrl = $productUrlHandler->setResult($result)->getAttributeByLogic('product_url','child');
            $result->setValueOf('product_url',$productUrl);

            /* product_url_rewritten */
            $productUrlRewrittenHandler = Mage::helper('connector/dynamic_product_url_rewritten');
            $productUrlRewritten = $productUrlRewrittenHandler->getAttributeByLogic('product_url_rewritten','child');
            $result->setValueOf('product_url_rewritten',$productUrlRewritten);

            /* HANDLE FINAL FILTERS to decide if return product or not */
            /* @TODO:same code needs to be added to other methods so they behave the same way */

            /* if product matches finaltype filter */
            if(
                is_array($finalTypeFilter) && in_array($result->getValueOf('product_type'),$finalTypeFilter)
                || !is_array($finalTypeFilter)
            ){

                //AND if product matches final status filter
                if($finalStatusFilter === 0 || $finalStatusFilter === 1
                || $finalStatusFilter ==='0' || $finalStatusFilter === '1' ) {
                    $statusLabels =  Mage_Catalog_Model_Product_Status::getOptionArray();
                    $statusLabels[0] =$statusLabels[2];
                    $statusLabels[1] =$statusLabels[1];
                    $statusFilterLabel = $statusLabels[$finalStatusFilter];
                    //Mage::log('id:'.$result->getValueOf('product_id').'  label:'.$statusFilterLabel.' status:'.$result->getValueOf('status').' ');
                    if($result->getValueOf('status')==$statusFilterLabel) {
                        $products[] = $result->getResult();
                    } else {
                        if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
                            Mage::log('' . $product->getSku() . ' status ' . $result->getValueOf('status') . ' does not match expected ' . $statusFilterLabel . '', null, $this->_logFileName);
                        }
                    }
                } else {
                    //if no status filter given
                    $products[] = $result->getResult();
                }
            } else {
                if(Mage::getStoreConfig('datafeedwatch/settings/debug')) {
                    Mage::log('product ' . $product->getSku() . ' not in product_type filter', null, $this->_logFileName);
                }
            }

            /* Get rid of our global product object from registry */
            Mage::unregister('datafeedwatch_connector_result');
        }

        return $products;
    }

    /**
     * @return int
     */
    public function gmt_offset(){
        // get timezone offset in GMT
        $timeZone = new DateTimeZone(Mage::getStoreConfig('general/locale/timezone'));
        $time     = new DateTime('now', $timeZone);
        $offset   = (int)($timeZone->getOffset($time) / 3600);

        return $offset;
    }

    /**
     * @return array|mixed
     */
    public function synced_fields(){
        $additional = array();
        if(Mage::getStoreConfig('datafeedwatch/settings/attributes')){
            $additional = unserialize(Mage::getStoreConfig('datafeedwatch/settings/attributes'));
        }

        return $additional;
    }

    /**
     * @param $options
     * @return array
     */
    public function updated_products($options){

        /* Temporary get the type filter out of options so magento collection doesn't filter it yet */
        $finalTypeFilter = $options['type'];
        $finalStatusFilter =$options['status'];
        unset($options['type']);
        unset($options['status']);

        $dataFeedWatchHelper = Mage::helper('connector');
        /* @var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */

        $fetchList = $dataFeedWatchHelper->getUpdatedProductList($options);

        if(!empty($fetchList)) {
            $options['entity_id'] = $fetchList;

            /*remove updated at filter, we do not want to use it on normal call*/
            unset($options['updated_at']);


            $fetchingUpdatedProducts = true;
            /* Reintroduce type filter to options */
            $options['type'] = $finalTypeFilter;
            $options['status'] = $finalStatusFilter;
            return $this->products($options,$fetchingUpdatedProducts);
        } else {
            return array();
        }
    }

    /**
     * @param $options
     * @return int
     */
    public function updated_product_count($options){

        $dataFeedWatchHelper = Mage::helper('connector');
        /* @var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */

        $fetchList = $dataFeedWatchHelper->getUpdatedProductList($options);
        return count($fetchList);
    }


}