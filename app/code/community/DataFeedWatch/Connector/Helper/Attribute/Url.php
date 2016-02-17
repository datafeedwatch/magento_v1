<?php
class DataFeedWatch_Connector_Helper_Attribute_Url
    extends DataFeedWatch_Connector_Helper_Attribute
    implements DataFeedWatch_Connector_Helper_Attribute_Interface
{
    /** @TODO: move to correct classnames - Product_Url & Dynamic_Product_Url_Rewritten  */
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

    public function getFullUrl (Mage_Catalog_Model_Product $product ,
                                Mage_Catalog_Model_Category $category = null ,
                                $mustBeIncludedInNavigation = true ){

        $storeId = Mage::getModel('core/store')->load('en', 'code')->getId(); //the store you need the base url:
        $homeUrl = Mage::getUrl('', array('_store' => $storeId));

        // Try to find url matching provided category
        if( $category != null){
            // Category is no match then we'll try to find some other category later
            if( !in_array($product->getId() , $category->getProductCollection()->getAllIds() )
                ||  !$this->isCategoryAcceptable($category , $mustBeIncludedInNavigation )){
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
                if ( !$this->isCategoryAcceptable($tmpCategory , $mustBeIncludedInNavigation ) ) {
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
    public function isCategoryAcceptable(Mage_Catalog_Model_Category $category = null, $mustBeIncludedInNavigation = true){
        if( !$category->getIsActive() || is_null( $category->getUrlKey() )
            || ( $mustBeIncludedInNavigation && !$category->getIncludeInMenu()) ){
            return false;
        }
        return true;
    }
}