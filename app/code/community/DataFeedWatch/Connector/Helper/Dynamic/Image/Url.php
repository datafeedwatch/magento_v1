<?php
class DataFeedWatch_Connector_Helper_Dynamic_Image_Url
    extends DataFeedWatch_Connector_Helper_Attribute
    implements DataFeedWatch_Connector_Helper_Attribute_Interface
{
    public function getAttributeByLogic($attributeCode,$logic)
    {
        $result = Mage::registry('datafeedwatch_connector_result');

        $product = $result->getProduct();
        $parentProduct = $result->getParentProduct();

        if($parentProduct) {
            if ($logic == 'parent') {
                $imageUrl = $this->getImageUrlFromProduct($parentProduct);
            } else if ($logic == 'child') {
                $imageUrl = $this->getImageUrlFromProduct($product);
            } else if ($logic == 'child_then_parent') {
                $imageUrl = $this->getImageUrlFromProduct($product);
                if (!$imageUrl) {
                    $imageUrl = $this->getImageUrlFromProduct($parentProduct);
                }
            }
        } else {
            $imageUrl = $this->getImageUrlFromProduct($product);
        }
        return $imageUrl;
    }

    public function getImageUrlFromProduct($product){
        $imageUrl = (string)$product->getMediaConfig()->getMediaUrl($product->getData('image'));
        $imageTmpArr = explode('.', $imageUrl);
        //$countImgArr = count($imageTmpArr);

        if(substr($imageUrl, -12)=='no_selection'){
            $imageUrl=null;
        }

        // Return placeholder image
        //if (empty($imageUrl) || $imageUrl == '' || !isset($imageUrl) || $countImgArr < 2) {
//            $imageUrl = (string)Mage::helper('catalog/image')->init($product, 'image');
//        }

        return $imageUrl;
    }
}