<?php
class DataFeedWatch_Connector_Helper_Data extends Mage_Core_Helper_Abstract
{

    private $_categories;
    private $_storeCategories;

    private $_required_attributes = array(
        "product_id",
        "sku",
        "product_type",
        "parent_id",
        "parent_sku",
        "parent_url",
        "name",
        "description",
        "short_description",
        "weight",
        "status",
        "visibility",
        "country_of_manufacture",
        "price",
        "special_price",
        "special_from_date",
        "special_to_date",
        "tax_class_id",
        "meta_title",
        "meta_keyword",
        "meta_description",
        "gift_wrapping_available",
        "gift_wrapping_price",
        "color",
        "occasion",
        "apparel_type",
        "sleeve_length",
        "fit",
        "size",
        "length",
        "gender",
        "product_url",
        "image_url",
        "price_with_tax",
        "special_price_with_tax",
        "additional_image_url1",
        "additional_image_url2",
        "quantity",
        "is_in_stock",
        'msrp_enabled',
        'minimal_price',
        'msrp_display_actual_price_type',
        'msrp',
        'thumbnail',
        'updated_at' /* aded to required so we can test updated_products */
    );

    private $_excluded_attributes = array(
        'type' => 0,
        'type_id' => 0,
        'set' => 0,
        'categories' => 0,
        'websites' => 0,
        'old_id' => 0,
        'news_from_date' => 0,
        'news_to_date' => 0,
        'category_ids' => 0,
        'required_options' => 0,
        'has_options' => 0,
        'image_label' => 0,
        'small_image_label' => 0,
        'thumbnail_label' => 0,
        'created_at' => 0,
        /*'updated_at' => 0,*/
        'group_price' => 0,
        'tier_price' => 0,
        'enable_googlecheckout' => 0,
        'is_recurring' => 0,
        'recurring_profile' => 0,
        'custom_design' => 0,
        'custom_design_from' => 0,
        'custom_design_to' => 0,
        'custom_layout_update' => 0,
        'page_layout' => 0,
        'options_container' => 0,
        'gift_message_available' => 0,
        'url_key' => 0,
        'url_path' => 0,
        'image' => 0,
        'small_image' => 0,
        'media_gallery' => 0,
        'gallery' => 0,
        'entity_type_id' => 0,
        'attribute_set_id' => 0,
        'entity_id' => 0
    );


    /* currency fields - to prevent multiple calls */
    private $_bas_curncy_code = null;
    private $_cur_curncy_code = null;
    private $_allowedCurrencies = null;
    private $_currencyRates = null;

    private $_filtersMap = array();

    public $storeId;

    private $_attributeDefaultValues = array();

    /*@TODO: rewrite this so this is not in both places, here and api class */
    public $isSupportedEnterprise = false;

    public function getRequiredAttributes(){
        return $this->_required_attributes;
    }

    public function getAttributesList(){
        $attributesList = array();
        $entityType = Mage::getModel('eav/config')->getEntityType(Mage_Catalog_Model_Product::ENTITY);
        $attributesCollection = Mage::getModel('eav/entity_attribute')->getCollection()
            ->addFieldToFilter('entity_type_id', array('eq' => $entityType->getEntityTypeId()));
        foreach($attributesCollection as $attribute){
            if(!in_array($attribute->getAttributeCode(),$this->_required_attributes)){
                $attributesList[$attribute->getAttributeCode()] = $attribute->getAttributeCode();
            }
        }
        return $attributesList;
    }

    /**
     * Parse filters and format them to be applicable for collection filtration
     *
     * @param null|object|array $filters
     * @param array $fieldsMap Map of field names in format: array('field_name_in_filter' => 'field_name_in_db')
     * @return array
     */
    public function parseFiltersReplacement($filters, $fieldsMap = null)
    {
        // if filters are used in SOAP they must be represented in array format to be used for collection filtration
        if (is_object($filters)) {
            $parsedFilters = array();
            // parse simple filter
            if (isset($filters->filter) && is_array($filters->filter)) {
                foreach ($filters->filter as $field => $value) {
                    if (is_object($value) && isset($value->key) && isset($value->value)) {
                        $parsedFilters[$value->key] = $value->value;
                    } else {
                        $parsedFilters[$field] = $value;
                    }
                }
            }
            // parse complex filter
            if (isset($filters->complex_filter) && is_array($filters->complex_filter)) {
                $parsedFilters += $this->_parseComplexFilterReplacement($filters->complex_filter);
            }

            $filters = $parsedFilters;
        }
        // make sure that method result is always array
        if (!is_array($filters)) {
            $filters = array();
        }
        // apply fields mapping
        if (isset($fieldsMap) && is_array($fieldsMap)) {
            foreach ($filters as $field => $value) {
                if (isset($fieldsMap[$field])) {
                    unset($filters[$field]);
                    $field = $fieldsMap[$field];
                    $filters[$field] = $value;
                }
            }
        }
        return $filters;
    }

    /**
     * Parses complex filter, which may contain several nodes, e.g. when user want to fetch orders which were updated
     * between two dates.
     *
     * @param array $complexFilter
     * @return array
     */
    protected function _parseComplexFilterReplacement($complexFilter)
    {
        $parsedFilters = array();

        foreach ($complexFilter as $filter) {
            if (!isset($filter->key) || !isset($filter->value)) {
                continue;
            }

            list($fieldName, $condition) = array($filter->key, $filter->value);
            $conditionName = $condition->key;
            $conditionValue = $condition->value;
            $this->formatFilterConditionValueReplacement($conditionName, $conditionValue);

            if (array_key_exists($fieldName, $parsedFilters)) {
                $parsedFilters[$fieldName] += array($conditionName => $conditionValue);
            } else {
                $parsedFilters[$fieldName] = array($conditionName => $conditionValue);
            }
        }

        return $parsedFilters;
    }

    /**
     * Convert condition value from the string into the array
     * for the condition operators that require value to be an array.
     * Condition value is changed by reference
     *
     * @param string $conditionOperator
     * @param string $conditionValue
     */
    public function formatFilterConditionValueReplacement($conditionOperator, &$conditionValue)
    {
        if (is_string($conditionOperator) && in_array($conditionOperator, array('in', 'nin', 'finset'))
            && is_string($conditionValue)
        ) {
            $delimiter = ',';
            $conditionValue = explode($delimiter, $conditionValue);
        }
    }

    public function buildCategoryPath($category_id, &$path = array())
    {
        if(!$category_id){
            return $path;
        }
        if(array_key_exists($category_id,$this->_categories)){
            $category = $this->_categories[$category_id];
            if ($category['parent_id'] != '0') {
                $this->buildCategoryPath($category['parent_id'], $path);
            }

            if ($category['is_active'] == '1') {
                $path[] = $category['name'];
            }
        }

        return $path;
    }

    /**
     * prepare _storeCategories and _categories
     * @return array
     * @throws Mage_Core_Exception
     */
    public function loadCategories($storeRootCategoryId)
    {
        /* prepare _storeCategories */
        $storeCategoriesCollection = Mage::getResourceModel('catalog/category_collection');
        $storeCategoriesCollection->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active')
            ->addPathsFilter('%/' . $storeRootCategoryId);

        foreach ($storeCategoriesCollection as $storeCategory) {
            /* @var $storeCategory Mage_Catalog_Model_Category */
            $this->_storeCategories[] = $storeCategory->getId();
        }

        /* prepare _categories */
        $parentId = 1;

        /* @var $tree Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree */
        $tree = Mage::getResourceSingleton('catalog/category_tree')->load();
        $root = $tree->getNodeById($parentId);

        if ($root && $root->getId() == 1) {
            $root->setName(Mage::helper('catalog')->__('Root'));
        }

        $collection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active');

        $tree->addCollectionData($collection, true);

        return $this->_nodeToArray($root);
    }

    /**
     * Convert node to array
     *
     * @param Varien_Data_Tree_Node $node
     * @return array
     */
    private function _nodeToArray(Varien_Data_Tree_Node $node)
    {
        /* add this node to $this->_categories */
        $this->_categories[$node->getId()] = array(
            'category_id' => $node->getId(),
            'parent_id' => $node->getParentId(),
            'name' => $node->getName(),
            'is_active' => $node->getIsActive()
        );

        /* parse children nodes */
        $children = $node->getChildren();
        /* @var $children  Varien_Data_Tree_Node_Collection */
        if (!empty($children)) {
            foreach ($children->getNodes() as $child) {
                $this->_nodeToArray($child);
            }
        }
    }

    public function getCategories(){
        return $this->_categories;
    }

    public function getStoreCategories(){
        return $this->_storeCategories;
    }

    public function getExcludedAttributes(){
        return $this->_excluded_attributes;
    }

    public function getProductAttributes(Mage_Catalog_Model_Product $product)
    {
        $mageObject = new Mage;

        $prices['description'] = $product->getDescription();
        $prices['short_description'] = $product->getShortDescription();

        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        //$this->isSupportedEnterprise is set within api products call
        if (method_exists($mageObject, 'getEdition') && Mage::getEdition() == Mage::EDITION_ENTERPRISE && $this->isSupportedEnterprise) {
            $prices['product_url'] = $product->getProductUrl();
        } else {
            $prices['product_url_rewritten'] = $baseUrl . $this->getRewrittenProductUrl($product,null,$this->storeId);
            $prices['product_url'] = $baseUrl . $product->getUrlPath();
        }



        // Getting Additional information
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {

            //only fetch if this is not excluded field
            if (!array_key_exists($attribute->getAttributeCode(), Mage::helper('connector')->getExcludedAttributes())) {
                $value = $product->getData($attribute->getAttributeCode());

                //only fetch if value is not emtpy
                if (!empty($value)) {
                    $value = $attribute->getFrontend()->getValue($product);
                    if(is_string($value)) {
                        $value = trim($value);
                    }
                }
                $prices[$attribute->getAttributeCode()] = $value;
            }
        }

        return $prices;
    }

    /**
     * @param $product Mage_Catalog_Model_Product
     * @param $product_result array
     * @param $parent_product Mage_Catalog_Model_Product
     * @return array
     */
    public function addProductDynamicAttributesToResult($product, $product_result, $parent_product = null){
        //categories

        if ($parent_product != null) {
            // inherit categories from parent
            $product_result = $this->addProductCategoriesToResult($parent_product,$product_result);
        } else {
            $product_result = $this->addProductCategoriesToResult($product,$product_result);
        }

        //excluded images
        $product_result = $this->addExcludedImagesToResult($product,$product_result);

        //additional images
        $product_result = $this->addAdditionalImagesToResult($product,$product_result, $parent_product);

        return $product_result;
    }

    public function addProductCategoriesToResult($product,$product_result){

        $categoryCollection = $product->getCategoryCollection();
        foreach($categoryCollection as $category){
            $category_id[] = $category->getId();
        }

        if (empty($category_id)) {
            $product_result['category_name'] = '';
            $product_result['category_parent_name'] = '';
            $product_result['category_path'] = '';
        } else {
            rsort($category_id);
            $index = '';
            foreach ($category_id as $key => $cate) {

                /* $this $this->_storeCategories is created in loadCategories call on beginning of products() */
                if (!in_array($cate, $this->_storeCategories)) {
                    continue;
                }

                /* $this $this->_storeCategories is created in loadCategories call on beginning of products(),
                specifically nodeToArray function */
                if (!array_key_exists($cate, $this->_categories)) {
                    continue;
                }

                $category = $this->_categories[$cate];

                $product_result['category_name' . $index] = $category['name'];

                /* @TODO: move these two to to property, doesn't make sense to read them everytime */
                $storeRoot = Mage::app()->getStore($this->storeId)->getRootCategoryId();
                $magentoRootCat = $this->_categories[$storeRoot]['parent_id'];

                if(array_key_exists($category['parent_id'],$this->_categories)
                    && $category['parent_id']!= $magentoRootCat
                ) {
                    $product_result['category_parent_name' . $index] = $this->_categories[$category['parent_id']]['name'];
                }

                $categoryPath = $this->buildCategoryPath($category['category_id']);
                $product_result['category_path' . $index] = implode(' > ', $categoryPath);

                if ($index == '') {
                    $index = 1;
                } else {
                    $index++;
                }
            }
        }

        return $product_result;

    }

    public function addAdditionalImagesToResult($product, $product_result, $parent_product = null){
        $additional_images = $product->getMediaGalleryImages();
        if ($parent_product) {
            $parent_additional_images = $parent_product->getMediaGalleryImages();
            if (count($parent_additional_images) > 0) {
                $i = 1;
                foreach ($parent_additional_images as $images) {
                    if ($images->getUrl() != $product_result['image_url']) {
                        $product_result['additional_image_url' . $i++] = $images->getUrl();
                    }
                }
            }
        }

        if (count($additional_images) > 0) {
            $i = 1;
            foreach ($additional_images as $images) {
                if ($images->getUrl() != $product_result['image_url']) {
                    $product_result['additional_image_url' . $i++] = $images->getUrl();
                }
            }
        }
        return $product_result;
    }

    public function addExcludedImagesToResult($product,$product_result)
    {
        $allImages = $product->getMediaGallery('images');

        $i = 1;
        foreach ($allImages as $image) {
            if ($image['disabled']) {
                $excludedUrl = (string)$product->getMediaConfig()->getMediaUrl($image['file']);
                $product_result['image_url_excluded'.$i++] = $excludedUrl;
            }
        }

        return $product_result;
    }

    private function prepareCurrencyRates(){
        if($this->_currencyRates===null) {
            $store_code = Mage::app()->getStore()->getCode();
            // Get Currency Code
            $this->_bas_curncy_code = Mage::app()->getStore()->getBaseCurrencyCode();
            $this->_cur_curncy_code = Mage::app()->getStore($store_code)->getCurrentCurrencyCode();

            $this->_allowedCurrencies = Mage::getModel('directory/currency')
                ->getConfigAllowCurrencies();
            $this->_currencyRates = Mage::getModel('directory/currency')
                ->getCurrencyRates($this->_bas_curncy_code, array_values($this->_allowedCurrencies));
        }
    }

    public function addPricesToResult($product,$prices,$parent_product){

        $_taxHelper = Mage::helper('tax');

        $this->prepareCurrencyRates();

        $priceWithRules = $this->getPriceIncludingRules($product);

        $prices['price'] = $_taxHelper->getPrice($product, $priceWithRules, NULL);
        $prices['price_with_tax'] = $_finalPriceInclTax = $_taxHelper->getPrice($product, $priceWithRules, true);

        $prices['special_price'] = null;
        $prices['special_price_with_tax'] = null;
        $specialTmpPrice = $product->getSpecialPrice();

        if ($specialTmpPrice
            /* @note: the special price range SHOULD NOT be checked when fetching special price */
            /*&& (time() <= strtotime($product['special_to_date']) || empty($product['special_to_date']))
            && (time() >= strtotime($product['special_from_date']) || empty($product['special_from_date']))*/
        ) {
            $prices['special_price'] = $_taxHelper->getPrice($product, $specialTmpPrice, NULL);
            $prices['special_price_with_tax'] = $_taxHelper->getPrice($product, $prices['special_price'], true);
            $prices['special_from_date'] = $product['special_from_date'];
            $prices['special_to_date'] = $product['special_to_date'];
        }

        if ($this->_bas_curncy_code != $this->_cur_curncy_code
            && array_key_exists($this->_bas_curncy_code, $this->_currencyRates)
            && array_key_exists($this->_cur_curncy_code, $this->_currencyRates)
        ) {
            if ($prices['special_price']
                /* @note: the special price range SHOULD NOT be checked when fetching special price */
                /*&& (time() <= strtotime($product['special_to_date']) || empty($product['special_to_date']))
                && (time() >= strtotime($product['special_from_date']) || empty($product['special_from_date']))*/
            ) {
                $prices['special_price_with_tax'] = Mage::helper('directory')->currencyConvert($prices['special_price_with_tax'], $this->_bas_curncy_code, $this->_cur_curncy_code);
                $prices['special_price'] = Mage::helper('directory')->currencyConvert($prices['special_price'], $this->_bas_curncy_code, $this->_cur_curncy_code);
            }

            $prices['price_with_tax'] = Mage::helper('directory')->currencyConvert($_finalPriceInclTax, $this->_bas_curncy_code, $this->_cur_curncy_code);
            $prices['price'] = Mage::helper('directory')->currencyConvert($prices['price'], $this->_bas_curncy_code, $this->_cur_curncy_code);
        }

        // get simple product price with Super Attributes Prices Values
        if ($product->getTypeId() == "simple") {
            // which is child of some parent product
            if (gettype($parent_product) == 'object' && $parent_product->getId()) {
                if ($parent_product->getTypeInstance(true) instanceof Mage_Catalog_Model_Product_Type_Configurable) {

                    $parentPrice = $this->getPriceIncludingRules($parent_product);
                    $prices['parent_price'] = $_taxHelper->getPrice($parent_product,$parentPrice,null);
                    $prices['parent_price_with_tax'] = $_taxHelper->getPrice($parent_product, $parentPrice, true);

                    $prices['parent_special_price'] = $_taxHelper->getPrice($parent_product, $parent_product->getSpecialPrice(), null);
                    $prices['parent_special_price_with_tax'] = $_taxHelper->getPrice($parent_product, $parent_product->getSpecialPrice(), true);

                    $variantSpacPrice = $this->getVariantSpacPrice($product, $parent_product);
                    $prices['variant_spac_price'] = $_taxHelper->getPrice($parent_product, $variantSpacPrice, null);
                    $prices['variant_spac_price_with_tax'] = $_taxHelper->getPrice($parent_product, $variantSpacPrice, true);

                } else {
                    // item has a parent because it extends Mage_Catalog_Model_Product_Type_Grouped
                    // it has no effect on price modifiers, however, so we ignore it
                }
            }
        }

        return $prices;
    }

    public function addImageToResult($product,$result){
        $imageUrl = (string)$product->getMediaConfig()->getMediaUrl($product->getData('image'));
        $imageTmpArr = explode('.', $imageUrl);
        $countImgArr = count($imageTmpArr);
        if (empty($imageUrl) || $imageUrl == '' || !isset($imageUrl) || $countImgArr < 2) {
            $imageUrl = (string)Mage::helper('catalog/image')->init($product, 'image');
        }
        $result['image_url'] = $imageUrl;

        return $result;
    }

    public function addStockInfoToResult($product,$product_result,$parent_product=null){

        $parentInStock = null;

        if($parent_product){
            $parentInventoryStatus = Mage::getModel('cataloginventory/stock_item')->loadByProduct($parent_product);
            $parentInStock = ($parentInventoryStatus->getIsInStock() == '1') ? 1 : 0;
        }

        $inventoryStatus = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        if (!empty($inventoryStatus)) {
            $product_result['quantity'] = (int)$inventoryStatus->getQty();
            $product_result['is_in_stock'] = $inventoryStatus->getIsInStock() == '1' ? 1 : 0;
        }

        /*NVI variants should inherit is_in_stock ==0 from parent only when it is 0*/
        if($parentInStock == 0 && $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
            $product_result['is_in_stock'] = 0;
        }

        return $product_result;
    }

    public function getRewrittenProductUrl($productObject, $categoryId, $storeId)
    {
        $productId = $productObject->getId();
        $rewrite = Mage::getSingleton('core/url_rewrite');
        $idPath = sprintf('product/%d', $productId);
        if ($categoryId) {
            $idPath = sprintf('%s/%d', $idPath, $categoryId);
        }
        $rewrite->loadByIdPath($idPath);
        return $rewrite->getRequestPath();
    }

    public function setStoreId($storeId){
        $this->storeId = $storeId;
    }

    public function prepareCollection($options,$applyFilters = true){

        unset($options['page']);
        unset($options['per_page']);

        /* determine store for collection */
        if (array_key_exists('store', $options)) {
            //convert store code to store id
            if (!is_numeric($options['store'])) {
                $options['store'] = Mage::app()->getStore($options['store'])->getId();
            }

            if ($options['store']) {
                $this->storeId = $options['store'];
                $this->setStoreId($this->storeId);
                Mage::app()->setCurrentStore($this->storeId);
            }
        }

        /* Check if the store has ignore_datafeedwatch_attribute and collection should apply it */
        $attributeModel = Mage::getModel('eav/entity_attribute');
        $attributeId = $attributeModel->getIdByCode('catalog_product', 'ignore_datafeedwatch');

        if ($attributeId > 0) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToFilter(array(
                    array('attribute'=>'ignore_datafeedwatch', 'neq'=> 1),
                    array('attribute'=>'ignore_datafeedwatch', 'null'=> true),
                ),
                    '',
                    'left'
                )
            ;
        } else {
            $collection = Mage::getModel('catalog/product')->getCollection();
        }

        $collection->addStoreFilter($this->storeId);
        if(isset($options['type']) && $options['type']) {
            $collection->addAttributeToFilter('type_id', array('in' => array($options['type'])));
            unset($options['type']);
        }

        $apiHelper = Mage::helper('api');

        if (method_exists($apiHelper, 'parseFilters')) {
            $filters = $apiHelper->parseFilters($options, $this->_filtersMap);
        } else {
            /* added to support older releases without parseFilters */
            $filters = $this->parseFiltersReplacement($options, $this->_filtersMap);
        }

        try {
            //ignore status and store when flat catalog is enabled
            if (Mage::helper('catalog/product_flat')->isEnabled()){
                $fieldsToIgnore = array('store','status');
                foreach($fieldsToIgnore as $field){
                    unset($filters[$field]);
                }
            }

            foreach ($filters as $field => $value) {
                if($field =='store') {
                    $collection->setStoreId($this->storeId);
                } else {
                    $collection->addFieldToFilter($field, $value);
                }
            }
        } catch (Mage_Core_Exception $e) {
            throw new Exception('filters_invalid', $e->getCode(), $e);
        }

        return $collection;
    }

    /**
     * Returns price key for given product
     * @param $product Mage_Catalog_Model_Product
     * @return float|null
     */
    public function getPriceIncludingRules($product){
        $finalPrice = Mage::getModel('catalogrule/rule')->calcProductPriceRule($product,$product->getPrice());
        if($finalPrice){
            return $finalPrice;
        }
        return $product->getPrice();
    }

    public function formatPrices($product,$product_result){
        if ( $product->getTypeId() == "simple" ) {
            $priceKeys = array(
                'price',
                'price_with_tax',
                'special_price',
                'special_price_with_tax',
                'parent_price',
                'parent_price_with_tax',
                'parent_special_price',
                'parent_special_price_with_tax',
                'variant_spac_price',
                'variant_spac_price_with_tax'
            );

        } else {
            $priceKeys = array(
                'price',
                'price_with_tax',
                'special_price',
                'special_price_with_tax',
            );
        }

        //format each price
        foreach($priceKeys as $key){
            if(array_key_exists($key,$product_result)) {
                $value = $product_result[$key];
                if(is_string($value)){
                    $value = trim($product_result[$key]);
                }
                $product_result[$key] = sprintf("%.2f", round($value, 2));
            }
        }

        //nullify special prices if price == 0
        if($product_result['special_price'] <= 0) {
            $product_result['special_price'] = null;
            $product_result['special_price_with_tax'] = null;
        }

        if(array_key_exists('parent_special_price',$product_result) && $product_result['parent_special_price'] <= 0) {
            $product_result['parent_special_price'] = null;
            $product_result['parent_special_price_with_tax'] = null;
        }

        //nullify tax prices if tax class is "None" for product,
        //but do not touch parent price fields!
        if($product->getTaxClassId()==0){
            foreach($priceKeys as $key) {
                if (!stristr($key,'variant_') && !stristr($key,'parent_') && array_key_exists($key, $product_result) && stristr($key, '_with_tax')) {
                    $product_result[$key] = $product_result[str_replace('_with_tax','',$key)];
                }
            }
        }
        return $product_result;
    }

    /**
     * @param $product
     * @param $parent_product
     * @param $attributes
     * @return mixed
     */
    public function getVariantSpacPrice($product,$parent_product){

        // get all configurable attributes
        if ($parent_product) {
            $attributes = $parent_product->getTypeInstance(true)->getConfigurableAttributes($parent_product);
        }

        // array to keep the price differences for each attribute value
        $pricesByAttributeValues = array();
        // base price of the configurable product
        $basePrice = $parent_product->getFinalPrice();
        // loop through the attributes and get the price adjustments specified in the configurable product admin page
        foreach ($attributes as $attribute) {
            $prices = $attribute->getPrices();
            foreach ($prices as $price) {
                if ($price['is_percent']) {
                    $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'] * $basePrice / 100;
                } else {
                    $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'];
                }
            }
        }

        $totalPrice = $basePrice;
        // loop through the configurable attributes
        foreach ($attributes as $attribute) {
            // get the value for a specific attribute for a simple product
            $value = $product->getData($attribute->getProductAttribute()->getAttributeCode());
            // add the price adjustment to the total price of the simple product
            if (isset($pricesByAttributeValues[$value])) {
                $totalPrice += $pricesByAttributeValues[$value];
            }
        }

        return $totalPrice;
    }

    public function shouldSkipProduct($product,$parent_product){
        if(is_object($parent_product) && $parent_product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED
            && $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE){
            return true;
        }
        return false;
    }

    public function addDefaultVariantFlag($product,$parent_product, $product_result){

        $attributes = $parent_product->getTypeInstance(true)->getConfigurableAttributesAsArray($parent_product);

        $product_result['dfw_default_variant'] = 1;

        foreach ($attributes as $productAttribute) {

            if(!array_key_exists($productAttribute['attribute_id'],$this->_attributeDefaultValues)) {
                $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($productAttribute['attribute_id']);
                $this->_attributeDefaultValues[$productAttribute['attribute_id']] = $attribute->getData('default_value');
            }

            //reset to 0 if any attribute doesn't have default value
            $currentValue = $product->getData($productAttribute['attribute_code']);
            if($currentValue != $this->_attributeDefaultValues[$productAttribute['attribute_id']]){
                $product_result['dfw_default_variant'] = 0;

                //return. we already have our result, we don't have to iterate over others
                return $product_result;
            }
        }

        //none "not default" found and value still == 1
        return $product_result;
    }

    public function getParentProductFromChild($product){
        if ($product->getTypeId() == "simple") {

            /* check if the product is grouped */
            $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());

            /* if not grouped, check if configurable */
            if (!$parentIds) {
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            }

            /* if at least one parent has been found in child details */
            if (isset($parentIds[0])) {
                /* @var $parent_product Mage_Catalog_Model_Product_Type_Configurable */
                $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);
                $parentProductStores = $parent_product->getStoreIds();
                if (!in_array($this->storeId, $parentProductStores) || empty($parentProductStores)) {
                    $parent_product= Mage::getModel('catalog/product');
                }
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
            return null;
        } else {
            return null;
        }
    }

    public function getFullUrl (Mage_Catalog_Model_Product $product ,
                                Mage_Catalog_Model_Category $category = null ,
                                $mustBeIncludedInNavigation = true ){

        $storeId = Mage::getModel('core/store')->load('en', 'code')->getId(); //the store you need the base url:
        $homeUrl = Mage::getUrl('', array('_store' => $storeId));

        // Try to find url matching provided category
        if( $category != null){
            // Category is no match then we'll try to find some other category later
            if( !in_array($product->getId() , $category->getProductCollection()->getAllIds() )
                ||  !self::isCategoryAcceptable($category , $mustBeIncludedInNavigation )){
                $category = null;
            }
        }
        if ($category == null) {
            if( is_null($product->getCategoryIds() )){
                return $product->getProductUrl();
            }
            $catCount = 0;
            $productCategories = $product->getCategoryIds();
            // Go through all product's categories
            while( $catCount < count($productCategories) && $category == null ) {
                $tmpCategory = Mage::getModel('catalog/category')->load($productCategories[$catCount]);
                // See if category fits (active, url key, included in menu)
                if ( !self::isCategoryAcceptable($tmpCategory , $mustBeIncludedInNavigation ) ) {
                    $catCount++;
                }else{
                    $category = Mage::getModel('catalog/category')->load($productCategories[$catCount]);
                }
            }
        }
        if($category && !is_null( $product->getUrlPath($category))) {
            $url = $homeUrl . str_replace('.html', '/', $category->getUrlPath()) . $product->getData('url_key') . '.html';
        } else {
            $url = $product->getProductUrl();

        }
        //$url = (!is_null( $product->getUrlPath($category))) ?  $homeUrl . $product->getUrlPath($category) : $product->getProductUrl();

        return $url;
    }

    /**
     * Checks if a category matches criteria: active && url_key not null && included in menu if it has to
     */
    protected function isCategoryAcceptable(Mage_Catalog_Model_Category $category = null, $mustBeIncludedInNavigation = true){
        if( !$category->getIsActive() || is_null( $category->getUrlKey() )
            || ( $mustBeIncludedInNavigation && !$category->getIncludeInMenu()) ){
            return false;
        }
        return true;
    }

    public function getUpdatedProductList($options){
        $mageObject = new Mage;
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
            $options['page'] = 1;
        }

        /* Use default limit if not set */
        if (!array_key_exists('per_page', $options)) {
            $options['per_page'] = 100;
        }

        /* Get Product Collection by updated_at field */
        $collection = $dataFeedWatchHelper->prepareCollection($options);
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

}

