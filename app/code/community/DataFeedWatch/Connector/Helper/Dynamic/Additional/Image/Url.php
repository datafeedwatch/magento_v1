<?php
class DataFeedWatch_Connector_Helper_Dynamic_Additional_Image_Url extends DataFeedWatch_Connector_Helper_Attribute{

    /** @TODO: do not return 'no_selection' */

    public function addAdditionalImagesToResult(){

        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $parent_product = $result->getParentProduct();


        if ($parent_product) {
            $parent_additional_images = $parent_product->getMediaGalleryImages();
            if (count($parent_additional_images) > 0) {
                $i = 1;
                foreach ($parent_additional_images as $image) {
                    if ($image->getUrl() != $this->getValueOf('image_url') && $image->getUrl()!='no_selection') {
                        if($image->getUrl())
                        $result->setValueOf('parent_additional_image_url' . $i++,$image->getUrl());
                    }
                }
            }
        }

        $additional_images = $product->getMediaGalleryImages();
        if (count($additional_images) > 0) {
            $i = 1;
            foreach ($additional_images as $image) {
                if ($image->getUrl() != $this->getValueOf('image_url') && $image->getUrl()!='no_selection' ){
                    $result->setValueOf('product_additional_image_url' . $i++,$image->getUrl());
                }
            }
        }
        return $result;
    }
}