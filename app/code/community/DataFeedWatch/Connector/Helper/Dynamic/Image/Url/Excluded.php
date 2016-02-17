<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 24.09.15
 * Time: 22:13
 */

class DataFeedWatch_Connector_Helper_Dynamic_Image_Url_Excluded extends DataFeedWatch_Connector_Helper_Attribute {

    public function addExcludedImagesToResult()
    {
        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $allImages = $product->getMediaGallery('images');
        $i = 1;
        foreach ($allImages as $image) {
            if ($image['disabled']) {
                $excludedUrl = (string)$product->getMediaConfig()->getMediaUrl($image['file']);
                $result->setValueOf('image_url_excluded'.$i++,$excludedUrl);
            }
        }

        return $result;
    }

}