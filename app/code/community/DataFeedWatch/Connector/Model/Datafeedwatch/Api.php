<?php

class DataFeedWatch_Connector_Model_Datafeedwatch_Api extends Mage_Catalog_Model_Product_Api
{
    // category
    const CATEGORY_NAME_FIELD = 'name';
    const CATEGORY_SEPARATOR = ' > ';

    public $storeRootCategoryId = 2;

    public $categories = array();
    public $storeCategories = array();

    private $_versionInfo;

    /* has been tested with this EE version and works completely */
    private $_supportedEnterprise = array(
        'major' => '1',
        'minor' => '13',
        'revision' => '0',
        'patch' => '2',
        'stability' => '',
        'number' => '',
    );
    private $_isSupportedEnterprise = false;


    public function __construct()
    {
        ini_set('memory_limit', '1024M');
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
        $collection = $dataFeedWatchHelper->prepareCollection($options)->setPage($options['page'],$options['per_page']);

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
        $products = array();
        $dataFeedWatchHelper = Mage::helper('connector');
        /* var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */
        $collection = $dataFeedWatchHelper->prepareCollection($options);

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

        $numberOfProducts = count($products);

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
        $mageObject = new Mage;
        $dataFeedWatchHelper = Mage::helper('connector');
        /* @var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */

        $this->_versionInfo = Mage::getVersionInfo();

        /* If we have Enterprise Edition, make sure our current Enterprise is supported */
        if (method_exists($mageObject, 'getEdition')
            && Mage::getEdition() == Mage::EDITION_ENTERPRISE
            && version_compare(implode('.',$this->_versionInfo),implode('.',$this->_supportedEnterprise),'>=')) {
            $this->_isSupportedEnterprise = true;
            $dataFeedWatchHelper->isSupportedEnterprise = true;
        }

        /* Use default page if not set */
        if (!array_key_exists('page', $options)) {
            $options['page'] = 0;
        }

        /* Use default limit if not set */
        if (!array_key_exists('per_page', $options)) {
            $options['per_page'] = 100;
        }

        /* Get Product Collection */
        $collection = $dataFeedWatchHelper->prepareCollection($options);

        /* Set current store using storeId got in prepareCollection */
        $store = Mage::app()->getStore($dataFeedWatchHelper->storeId);

        /* Clear options that are not product filters and were meant only for prepareCollection */
        /* page and per_page already removed in preparecollection */
        unset($options['store']);

        /* @TODO: check if this shouldn't be prepareCollection part */
        $collection->addAttributeToSelect('*')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $dataFeedWatchHelper->storeId)
            ->setPage($options['page'], $options['per_page']);

        /* set current store manually so we get specific store url returned in getBaseUrl */
        $this->storeRootCategoryId = Mage::app()->getStore($dataFeedWatchHelper->storeId)->getRootCategoryId();

        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        /* prepare categories and storeCategories */
        $dataFeedWatchHelper->loadCategories($this->storeRootCategoryId);

        $result = array();

        foreach ($collection as $product) {

            //re-setters
            $parent_id = null;
            $parent_sku = null;
            $parent_url = null;
            $isConfigurable = false;

            //reload product to get all attributes for particular store
            if ($dataFeedWatchHelper->storeId) {
                $product = Mage::getModel('catalog/product')->setStoreId($dataFeedWatchHelper->storeId)->load($product->getId());
            } else {
                $product = Mage::getModel('catalog/product')->load($product->getId());
            }
            /* @var $product Mage_Catalog_Model_Product */

            $product_result = array(
                // Basic product data
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'product_type' => $product->getTypeId()
            );

            /* Get attribute settings */
            /* user attributes selected in Admin -> Catalog -> Datafeedwatch -> Settings */
            $selected_attributes = $this->synced_fields();

            /* hardcoded attributes list to fetch */
            $requiredAttributes = $dataFeedWatchHelper->getRequiredAttributes();

            /* join two of the above into one */
            $allowedAttributes = array_merge($selected_attributes, $requiredAttributes);

            /* attributes that should never be returned */
            $excludedAttributes = $dataFeedWatchHelper->getExcludedAttributes();

            foreach ($product->getAttributes() as $attribute) {

                /* ignore excluded attributes */
                if (array_key_exists($attribute->getAttributeCode(), $excludedAttributes)) {
                    continue;
                }

                /* only use user-selected fields from DataFeedWatch -> Settings + required attributes */
                if (in_array($attribute->getAttributeCode(), $allowedAttributes)) {
                    $value = $product->getData($attribute->getAttributeCode());
                    if (!empty($value)) {
                        $value = $attribute->getFrontend()->getValue($product);
                        if(is_string($value)) {
                            $value = trim($value);
                        }
                    }
                    $product_result[$attribute->getAttributeCode()] = $value;
                } else {
                    //if you ever decide to log this:
                    //Mage::log('attr_code: '.$attribute->getAttributeCode().' was not synced',null,'datafeedwatch_connector.log');
                }
            }

            /* get product Url */
            if ($this->_isSupportedEnterprise) {
                $product_result['product_url'] = $product->getProductUrl();
            } else {
                $product_result['product_url_rewritten'] = $baseUrl . $dataFeedWatchHelper->getRewrittenProductUrl($product, null, $dataFeedWatchHelper->storeId);
                $product_result['product_url'] = $baseUrl . $product->getUrlPath();
            }


            $parent_product = $dataFeedWatchHelper->getParentProductFromChild($product);
            /* @var $parent_product Mage_Catalog_Model_Product */

            if($parent_product) {
                $parent_id = $parent_product->getId();

                $product_result['parent_id'] = $parent_id;
                $product_result['parent_sku'] = $parent_sku =$parent_product->getSku();
                $product_result['parent_url'] = $parent_url;

                $configurableParentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                if(in_array($parent_product->getId(),$configurableParentIds)){
                    $isConfigurable = true;
                }
            }


            /* Do not return the product if we should skip it */
            if (
                !$fetchingUpdatedProducts
                && $dataFeedWatchHelper->shouldSkipProduct($product, $parent_product)
            ) {
                continue;
            }

            /* Change child product status to disabled if using updated_products and parent status is Disabled */
            if(
                $fetchingUpdatedProducts
                && $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
                && is_object($parent_product)
                && $parent_product->getStatus()==Mage_Catalog_Model_Product_Status::STATUS_DISABLED
            ){
                $product_result['status'] = Mage::helper('catalog')->__('Disabled');
            }

            //parent_url
            if($parent_product) {
                if ($this->_isSupportedEnterprise) {
                    $parent_url = $parent_product->getProductUrl();
                } else {
                    $parent_url = $baseUrl . $parent_product->getUrlPath();
                }
            }

            /* if child is NVI, use parent attributes */
            if ($parent_id && $isConfigurable && $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
                /* rewrite to prepare array of fields to overwrite with parent values */
                $productAttributes = $dataFeedWatchHelper->getProductAttributes($parent_product);

                // get child product visibility
                $visibilityStatuses = Mage_Catalog_Model_Product_Visibility::getOptionArray();
                if (isset($visibilityStatuses[$product->getVisibility()])) {
                    $productAttributes['visibility'] = $visibilityStatuses[$product->getVisibility()];
                } else {
                    $productAttributes['visibility'] = null;
                }
            } else {
                $productAttributes = $dataFeedWatchHelper->getProductAttributes($product);
            }



            //add product main image
            /* @TODO: move to helper */
            $imageUrl = (string)$product->getMediaConfig()->getMediaUrl($product->getData('image'));
            $imageTmpArr = explode('.', $imageUrl);
            $countImgArr = count($imageTmpArr);
            if (empty($imageUrl) || $imageUrl == '' || !isset($imageUrl) || $countImgArr < 2) {
                $imageUrl = (string)Mage::helper('catalog/image')->init($product, 'image');
            }
            $product_result['image_url'] = $imageUrl;



            //always use parent values for description, short_description
            if($parent_id){
                $product_results['short_description'] = $parent_product->getShortDescription();
                $product_results['description'] = $parent_product->getDescription();


                //use parent image_url if(only if) it's empty in child
                if(!array_key_exists('image_url',$product_result) || $product_result['image_url']==''){
                    $product_result['image_url'] = $parent_product->getImageUrl();
                }

                //use parent attribute value if child attribute value empty or doesn't exist in child
                foreach ($productAttributes as $key => $value) {

                    /*skip attributes we overwritten above */
                    if(in_array($key,array('description','short_description','image_url'))){
                        continue;
                    }

                    if (!array_key_exists($key, $product_result)
                        || (array_key_exists($key, $product_result) && !$product_result[$key])
                    ){
                        $product_result[$key] = $value;
                    }
                }

            }

            // add some parent attributes
            if ($parent_id && $isConfigurable && ($product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)) {
                $product_result = $dataFeedWatchHelper->addProductDynamicAttributesToResult($product, $product_result, $parent_product);
            } else {
                $product_result = $dataFeedWatchHelper->addProductDynamicAttributesToResult($product, $product_result, null);
            }

            // get variant name and default flag
            if ($product->getTypeId() == "simple") {
                // which is child of some parent product
                if (!empty($parent_id) && gettype($parent_product) == 'object') {
                    if ($parent_product->getTypeInstance(true) instanceof Mage_Catalog_Model_Product_Type_Configurable) {

                        $product_result['variant_name'] = $product->getName();
                        $product_result = $dataFeedWatchHelper->addDefaultVariantFlag($product, $parent_product, $product_result);

                    } else {
                        // item has a parent because it extends Mage_Catalog_Model_Product_Type_Grouped
                        // it has no effect on price modifiers, however, so we ignore it
                    }
                }
            }

            //add multiple product images to result
            $product_result = $dataFeedWatchHelper->addImageToResult($product,$product_result);
            //add prices and custom price fields


            $product_result = $dataFeedWatchHelper->addPricesToResult($product,$product_result,$parent_product);
            //format prices and get rid of empty price fields (nullify them)
            $product_result = $dataFeedWatchHelper->formatPrices($product, $product_result);
            //add in stock and qty information
            $product_result = $dataFeedWatchHelper->addStockInfoToResult($product, $product_result,$parent_product);

            // adding currency code ex. 'USD'
            $product_result['currency_code'] = $store->getCurrentCurrencyCode();



            /*override product url*/
            if(Mage::getStoreConfig('datafeedwatch/settings/url_type')){
                /* 2 stands for Full URL */
                if(Mage::getStoreConfig('datafeedwatch/settings/url_type') == 2){
                    $product_result['product_url'] = Mage::helper('connector')->getFullUrl($product);
                    if($parent_product) {
                        $product_result['parent_url'] = Mage::helper('connector')->getFullUrl($parent_product);
                    }
                }
            }



            $result[] = $product_result;

        }
        return $result;
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

        $dataFeedWatchHelper = Mage::helper('connector');
        /* @var $dataFeedWatchHelper DataFeedWatch_Connector_Helper_Data */

        $fetchList = $dataFeedWatchHelper->getUpdatedProductList($options);

        if(!empty($fetchList)) {
            $options['entity_id'] = $fetchList;

            /*remove updated at filter, we do not want to use it on normal call*/
            unset($options['updated_at']);
            unset($options['status']);

            $fetchingUpdatedProducts = true;
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