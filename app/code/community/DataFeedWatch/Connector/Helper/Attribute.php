<?php
class DataFeedWatch_Connector_Helper_Attribute extends DataFeedWatch_Connector_Model_Product_Result {

    /**
     * Attributes that should be NEVER returned
     * @var array $_excluded_attributes
     * @TODO: get rid of the "=> 0" */
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
        'entity_id' => 0,
        'ignore_datafeedwatch'=>0, /*this will always return 0 anyway, others are ignored*/
    );

    /**
     * Attributes that should be ALWAYS returned
     *  @var array $_required_attributes */
    private $_required_attributes = array(
        /* always available in both */
        "name",
        "description",
        "short_description",
        "tax_class_id",
        "visibility",
        "is_in_stock",
        "status",

        "country_of_manufacture",
        "meta_title",
        "meta_keyword",
        "meta_description",
        /* not sure */
        "gift_wrapping_available",
        "gift_wrapping_price",
        "color",
        "occasion",
        "apparel_type",
        "sleeve_length",
        "fit",

        "gender",
        "image_url",
        'thumbnail',
        'msrp_enabled',
        'minimal_price',
        'msrp_display_actual_price_type',
        'msrp',
        /* always forced child */
        "product_id",
        "sku",
        "product_url",
        "product_url_rewritten",
        "price",
        "price_with_tax",
        "special_price",
        "special_price_with_tax",
        "special_from_date",
        "special_to_date",
        "product_type",
        "variant_spac_price",
        "variant_spac_price_with_tax",
        /* forced child - can't let people pick */
        "weight", /* parent never has it */
        "size",
        "length",
        'updated_at', /* updating parent doesn't update child, so take from product in question */
        "quantity", /* because parent not having it at all */
        "dfw_default_variant",
        /* always forced parent */
        "parent_id",
        "parent_sku",
        "parent_url",
        "parent_price",
        "parent_price_with_tax",

        /* doesn't come from product, but returned */
        //"currency_code",

    );

    public function addStockInfoToResult(){

        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();

        $inventoryStatus = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        if (!empty($inventoryStatus)) {
            $result->setValueOf('quantity', (int)$inventoryStatus->getQty());
        }

        return $result;
    }



    /**
     * @TODO: write phpdoc
     */
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

    public function getRequiredAttributes(){
        return $this->_required_attributes;
    }

    public function getExcludedAttributes(){
        return $this->_excluded_attributes;
    }

    public function getProductAttributes(Mage_Catalog_Model_Product $product)
    {
        // Getting Additional information
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {

            //only fetch if this is not excluded field
            if (!array_key_exists($attribute->getAttributeCode(), $this->getExcludedAttributes())) {
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
     * this is fallback method - for default logic handling if there is none
     * */
    public function getAttributeByLogic($attributeCode, $logic){

        $result = Mage::registry('datafeedwatch_connector_result');

        // Child regardless the value
        if($result->parentProduct != null) {
            if ($logic == 'child') {
                return $result->product->getData($attributeCode);
            }

            // Parent regardless the value
            if ($logic == 'parent') {
                return $result->parentProduct->getData($attributeCode);
            }

            // Child then parent logic
            if ($logic == 'child_then_parent') {
                if ($result->product->getData($attributeCode) == ''
                    || $result->product->getData($attributeCode) == null
                ) {
                    return $result->parentProduct->getData($attributeCode);
                }
            }

            // If no logic has been selected, fallback to child value
            return $result->product->getData($attributeCode);
        }else{
            // If no parent exist, get child value
            return $result->product->getData($attributeCode);
        }
    }
}