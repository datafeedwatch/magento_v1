<?php
class DataFeedWatch_Connector_Helper_Attribute_Price
    extends DataFeedWatch_Connector_Helper_Attribute
    implements DataFeedWatch_Connector_Helper_Attribute_Interface
{

    /**
     * @TODO: write phpdoc for these variables
     */
    /* currency fields - to prevent multiple calls */
    private $_bas_curncy_code = null;
    private $_cur_curncy_code = null;
    private $_allowedCurrencies = null;
    private $_currencyRates = null;

    public function getAttributeByLogic($attributeCode,$logic)
    {
        $result = Mage::registry('datafeedwatch_connector_result');
        $parentProduct = $result->getParentProduct();
        $product = $result->getProduct();

        if($parentProduct) {
            if ($logic == 'parent') {
                $basePrice = $this->getPriceIncludingRules($parentProduct);
                return $basePrice;
            } else if ($logic == 'child') {
                $basePrice = $this->getPriceIncludingRules($product);
                return $basePrice;
            } else if ($logic == 'child_then_parent') {
                $basePrice = $this->getPriceIncludingRules($product);
                if (!$basePrice) {
                    $basePrice = $this->getPriceIncludingRules($parentProduct);
                }
                return $basePrice;
            }
        } else {
            $basePrice = $this->getPriceIncludingRules($product);
            return $basePrice;
        }
        return null;
    }


    public function getPriceIncludingRules($product){
        $finalPrice = Mage::getModel('catalogrule/rule')->calcProductPriceRule($product,$product->getPrice());
        if($finalPrice){
            return $finalPrice;
        }
        return $product->getPrice();
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

    public function addPricesToResult(){

        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $parent_product = $result->getParentProduct();

        $_taxHelper = Mage::helper('tax');
        $this->prepareCurrencyRates();

        //price - forced child value
        //price_with_tax - forced child value
        $priceWithRules = $this->setResult($result)->getAttributeByLogic('price','child');
        $result->setValueOf('price',$_taxHelper->getPrice($product, $priceWithRules, NULL));
        $result->setValueOf('price_with_tax',$_taxHelper->getPrice($product, $priceWithRules, true));

        //special_price - forced child value
        //special_price_with_tax - forced child value
        //special_from_date - forced child value
        //special_to_date - forced child value
        $result->setValueOf('special_price',null);
        $result->setValueOf('special_price_with_tax',null);
        $specialTmpPrice = $product->getSpecialPrice();

        if ($specialTmpPrice
            /* @note: the special price range SHOULD NOT be checked when fetching special price */
            /*&& (time() <= strtotime($product['special_to_date']) || empty($product['special_to_date']))
            && (time() >= strtotime($product['special_from_date']) || empty($product['special_from_date']))*/
        ) {
            $result->setValueOf('special_price',$_taxHelper->getPrice($product, $specialTmpPrice, NULL));
            $result->setValueOf('special_price_with_tax',$_taxHelper->getPrice($product, $result->getValueOf('special_price'), true));
            $result->setValueOf('special_from_date',$product->getData('special_from_date'));
            $result->setValueOf('special_to_date',$product->getData('special_to_date'));
        }

        /*Considering a product is simple, also fetch following values */
        //parent_price - forced parent value
        //parent_price_with_tax - forced parent value
        //parent_special_price
        //parent_special_price_with_tax
        //parent_special_from_date
        //parent_special_to_date
        //variant_spac_price
        //variant_spac_price_with_tax
        if ($product->getTypeId() == "simple") {
            // which is child of some parent product
            if (gettype($parent_product) == 'object' && $parent_product->getId()) {
                if ($parent_product->getTypeInstance(true) instanceof Mage_Catalog_Model_Product_Type_Configurable) {

                    $parentPrice = $this->getPriceIncludingRules($parent_product);
                    $result->setValueOf('parent_price',$_taxHelper->getPrice($parent_product,$parentPrice,null));
                    $result->setValueOf('parent_price_with_tax',$_taxHelper->getPrice($parent_product, $parentPrice, true));

                    $result->setValueOf('parent_special_price',$_taxHelper->getPrice($parent_product, $parent_product->getSpecialPrice(), null));
                    $result->setValueOf('parent_special_price_with_tax',$_taxHelper->getPrice($parent_product, $parent_product->getSpecialPrice(), true));

                    $result->setValueOf('parent_special_from_date',$parent_product->getData('special_from_date'));
                    $result->setValueOf('parent_special_to_date',$parent_product->getData('special_to_date'));

                    $variantSpacPriceHandler = Mage::helper('connector/attribute_variant_spac_price');
                    $variantSpacPrice = $variantSpacPriceHandler->getVariantSpacPrice($product, $parent_product);
                    $result->setValueOf('variant_spac_price',$_taxHelper->getPrice($parent_product, $variantSpacPrice, null));
                    $result->setValueOf('variant_spac_price_with_tax',$_taxHelper->getPrice($parent_product, $variantSpacPrice, true));

                } else {
                    // item has a parent because it extends Mage_Catalog_Model_Product_Type_Grouped
                    // it has no effect on price modifiers, however, so we ignore it
                }
            }
        }

        $this->convertCurrencyInResult();
        $this->formatResultPrices();

        //return updated result object for the sake of intuiveness
        return Mage::registry('datafeedwatch_connector_result');
    }

    public function convertCurrencyInResult(){
        $result = Mage::registry('datafeedwatch_connector_result');

        if ($this->_bas_curncy_code != $this->_cur_curncy_code
            && array_key_exists($this->_bas_curncy_code, $this->_currencyRates)
            && array_key_exists($this->_cur_curncy_code, $this->_currencyRates)
        ) {
            if ($result->getValueOf('special_price')
                /* @note: the special price range SHOULD NOT be checked when fetching special price */
                /*&& (time() <= strtotime($product['special_to_date']) || empty($product['special_to_date']))
                && (time() >= strtotime($product['special_from_date']) || empty($product['special_from_date']))*/
            ) {
                $result->setValueOf('special_price_with_tax',Mage::helper('directory')->currencyConvert($result->getValueOf('special_price_with_tax'), $this->_bas_curncy_code, $this->_cur_curncy_code));
                $result->setValueOf('special_price',Mage::helper('directory')->currencyConvert($result->getValueOf('special_price'), $this->_bas_curncy_code, $this->_cur_curncy_code));
            }

            $result->setValueOf('price_with_tax',Mage::helper('directory')->currencyConvert($result->getValueOf('price_with_tax'), $this->_bas_curncy_code, $this->_cur_curncy_code));
            $result->setValueOf('price',Mage::helper('directory')->currencyConvert($result->getValueOf('price'), $this->_bas_curncy_code, $this->_cur_curncy_code));
        }

        return $result;
    }


    public function formatResultPrices(){

        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();

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
            if(array_key_exists($key,$result->get())) {
                $value = $result->getValueOf($key);
                if(is_string($value)){
                    $value = trim($value);
                }
                $result->setValueOf($key,sprintf("%.2f", round($value, 2)));
            }
        }

        //nullify special prices if price == 0
        if($result->getValueOf('special_price') <= 0) {
            $result->setValueOf('special_price',null);
            $result->setValueOf('special_price_with_tax',null);
        }

        if(array_key_exists('parent_special_price',$result->get()) && $result->getValueOf('parent_special_price') <= 0) {
            $result->setValueOf('parent_special_price',null);
            $result->setValueOf('parent_special_price_with_tax',null);
        }

        //nullify tax prices if tax class is "None" for product,
        //but do not touch parent price fields!
        if($product->getTaxClassId()==0){
            foreach($priceKeys as $key) {
                if (!stristr($key,'variant_') && !stristr($key,'parent_') && array_key_exists($key, $result->get()) && stristr($key, '_with_tax')) {
                    $result->setValueOf($key, $result->getValueOf(str_replace('_with_tax','',$key)));
                }
            }
        }
        return $result;
    }
}