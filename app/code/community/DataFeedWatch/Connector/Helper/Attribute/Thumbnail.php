<?php
class DataFeedWatch_Connector_Helper_Attribute_Thumbnail
    extends DataFeedWatch_Connector_Helper_Attribute
    implements DataFeedWatch_Connector_Helper_Attribute_Interface
{
    public function getAttributeByLogic($attributeCode,$logic)
    {
        $result = Mage::registry('datafeedwatch_connector_result');

        $product = $result->getProduct();
        $parentProduct = $result->getParentProduct();

        if ($parentProduct) {
            if ($logic == 'parent') {
                $thumbnail = $this->getThumbnailFromProduct($parentProduct);
            } else if ($logic == 'child') {
                $thumbnail = $this->getThumbnailFromProduct($product);
            } else if ($logic == 'child_then_parent') {
                $thumbnail = $this->getThumbnailFromProduct($product);
                if (!$thumbnail) {
                    $thumbnail = $this->getThumbnailFromProduct($parentProduct);
                }
            }
        } else {
            $thumbnail = $this->getThumbnailFromProduct($product);
        }
        return $thumbnail;
    }

    public function getThumbnailFromProduct($product){
        $thumbnail = $product->getThumbnail();
        if($thumbnail=='no_selection'){
            $thumbnail=null;
        } else {
            $thumbnail = Mage::getUrl().'media/catalog/product'.$thumbnail;
        }

        return $thumbnail;
    }
}