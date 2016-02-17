<?php
class DataFeedWatch_Connector_Helper_Attribute_Status
    extends DataFeedWatch_Connector_Helper_Attribute
    implements DataFeedWatch_Connector_Helper_Attribute_Interface
{
    public function getAttributeByLogic($attributeCode,$logic='child_then_parent')
    {
        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $parentProduct = $result->getParentProduct();

        if($parentProduct){
            if ($logic =='parent') {
                //Mage::log('using parent logic - '.$parentProduct->getStatus());
                return $parentProduct->getStatus();
            } else if ($logic =='child') {
                //Mage::log('using child logic');
                return $product->getStatus();
            } else if ($logic =='child_then_parent') {

                //@TODO: implement ignoring this status when called from updated products
                // but before re-implementing it, check if and why we need it

                // Change child product status to disabled if using updated_products and parent status is Disabled
                if(
                    //$fetchingUpdatedProducts &&
                    $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
                    && $parentProduct->getStatus()==Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                ){
                    //Mage::log('using child_then_parent logic - p - '.$product->getVisibility().'/'.$parentProduct->getStatus().' - '.$parentProduct->getStatus());
                    return $parentProduct->getStatus();
                }


                //Mage::log('using child_then_parent logic  - c - '.$product->getVisibility().'/'.$parentProduct->getStatus().' - '.$parentProduct->getStatus());
                return $product->getStatus();
            }
        } else {
            return $product->getStatus();
        }
    }
}